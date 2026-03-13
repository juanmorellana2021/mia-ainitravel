<?php
/**
 * mia/services/BillingService.php
 *
 * Handles subscription management via Mercado Pago Preapproval API.
 *
 * Flow:
 *   1. createSubscription()  → creates MP preapproval, returns redirect URL
 *   2. User authorizes on Mercado Pago
 *   3. MP redirects back → confirmSubscription() activates in our DB
 *   4. Webhook → handleWebhookEvent() for server-side payment confirmation
 *
 * Setup:
 *   - Set MP_ACCESS_TOKEN in App.php (TEST-... for sandbox, APP_USR-... for prod)
 */

declare(strict_types=1);

class BillingService
{
    private PDO $db;

    private const MP_API = 'https://api.mercadopago.com/';

    public function __construct()
    {
        $this->db = Database::get();
    }

    // ── Mercado Pago Subscriptions (Preapproval) ──────────────────────────────

    /**
     * Create a Mercado Pago preapproval (recurring subscription).
     * Returns ['url' => '...'] on success, ['error' => '...'] on failure.
     */
    public function createSubscription(Client $client, string $plan): array
    {
        $planInfo = self::planOptions()[$plan] ?? null;
        if (!$planInfo) {
            return ['error' => 'Plan desconocido.'];
        }

        // Cancel any existing active subscription first (upgrade/downgrade case).
        // This cancels the old MP preapproval so the client isn't double-charged.
        $existingSub = $this->activeSubscription($client->id);
        if ($existingSub) {
            if ($existingSub->mp_preapproval_id) {
                $this->mpPut('preapproval/' . urlencode($existingSub->mp_preapproval_id), [
                    'status' => 'cancelled',
                ]);
            }
            $this->db->prepare(
                "UPDATE mia_subscriptions SET status = 'cancelled' WHERE id = ?"
            )->execute([$existingSub->id]);
        }

        $base = App::URL;

        $body = [
            'reason'         => 'Mia ' . $planInfo['label'] . ' — ' . htmlspecialchars($client->business_name),
            'external_reference' => 'mia_client_' . $client->id . '_' . $plan,
            'payer_email'    => $client->email,
            'auto_recurring' => [
                'frequency'          => 1,
                'frequency_type'     => 'months',
                'transaction_amount' => (float)$planInfo['price'],
                'currency_id'        => 'PEN',
            ],
            'back_url' => $base . '/dashboard/billing?payment=success',
            'status'   => 'pending',
        ];

        $response = $this->mpPost('preapproval', $body);

        if (!empty($response['error']) || empty($response['id'])) {
            $msg = $response['message'] ?? $response['error'] ?? 'Error al conectar con Mercado Pago.';
            error_log('[BillingService] MP error: ' . json_encode($response));
            return ['error' => is_string($msg) ? $msg : 'Error al crear suscripción.'];
        }

        // Store pending subscription record
        $this->createPendingRecord($client->id, $plan, $response['id'], $response['init_point'] ?? '');

        return ['url' => $response['init_point'] ?? $response['sandbox_init_point'] ?? ''];
    }

    /**
     * Check a preapproval status and activate if authorized.
     */
    public function confirmSubscription(int $clientId, string $mpPreapprovalId): bool
    {
        $preapproval = $this->mpGet('preapproval/' . urlencode($mpPreapprovalId));

        $status = $preapproval['status'] ?? '';
        if (!in_array($status, ['authorized', 'active'], true)) {
            return false;
        }

        // Find the pending subscription for this client
        $stmt = $this->db->prepare(
            "SELECT * FROM mia_subscriptions
             WHERE client_id = ? AND mp_preapproval_id = ? AND status = 'pending'
             LIMIT 1"
        );
        $stmt->execute([$clientId, $mpPreapprovalId]);
        $row = $stmt->fetch();
        if (!$row) return false;

        $plan = $row['plan'];
        $payerEmail = $preapproval['payer_email'] ?? '';

        // Activate subscription
        $stmt = $this->db->prepare(
            "UPDATE mia_subscriptions
             SET status = 'active', mp_payer_email = ?,
                 paid_at = NOW(), billing_period_start = NOW(),
                 billing_period_end = DATE_ADD(NOW(), INTERVAL 1 MONTH)
             WHERE id = ?"
        );
        $stmt->execute([$payerEmail, $row['id']]);

        // Activate client plan
        $clientService = new ClientService();
        $clientService->updatePlan($clientId, $plan, 'active');

        // Refresh session
        $client = $clientService->findById($clientId);
        if ($client) {
            $_SESSION['mia_client'] = $this->clientToSession($client);
        }

        return true;
    }

    /**
     * Handle Mercado Pago webhook notifications.
     * MP sends: { "type": "subscription_preapproval", "data": { "id": "..." } }
     */
    public function handleWebhookEvent(string $payload): void
    {
        $event = json_decode($payload, true);
        if (!$event) return;

        $type = $event['type'] ?? '';
        $dataId = $event['data']['id'] ?? '';

        if (!$dataId) return;

        if (in_array($type, ['subscription_preapproval', 'subscription_authorized_payment'], true)) {
            // Fetch the preapproval to get current status
            $preapproval = $this->mpGet('preapproval/' . urlencode($dataId));
            $status = $preapproval['status'] ?? '';
            $externalRef = $preapproval['external_reference'] ?? '';

            // Parse client_id from external_reference: mia_client_{id}_{plan}
            if (preg_match('/^mia_client_(\d+)_/', $externalRef, $m)) {
                $clientId = (int)$m[1];
            } else {
                return;
            }

            if (in_array($status, ['authorized', 'active'], true)) {
                $this->confirmSubscription($clientId, $dataId);
            } elseif (in_array($status, ['cancelled', 'paused'], true)) {
                $this->cancelByMpPreapprovalId($dataId);
            }
        }

        if ($type === 'payment') {
            // A recurring payment was made — check if it's linked to a preapproval
            $payment = $this->mpGet('v1/payments/' . urlencode($dataId));
            $preapprovalId = $payment['metadata']['preapproval_id'] ?? '';
            if ($preapprovalId) {
                // Update billing_period_end to extend by 1 month
                $stmt = $this->db->prepare(
                    "UPDATE mia_subscriptions
                     SET billing_period_end = DATE_ADD(NOW(), INTERVAL 1 MONTH),
                         paid_at = NOW()
                     WHERE mp_preapproval_id = ? AND status = 'active'"
                );
                $stmt->execute([$preapprovalId]);
            }
        }
    }

    // ── Subscription Records ──────────────────────────────────────────────────

    public function historyForClient(int $clientId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM mia_subscriptions WHERE client_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$clientId]);
        return array_map([Subscription::class, 'fromRow'], $stmt->fetchAll());
    }

    public function activeSubscription(int $clientId): ?Subscription
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM mia_subscriptions
             WHERE client_id = ? AND status = 'active'
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$clientId]);
        $row = $stmt->fetch();
        return $row ? Subscription::fromRow($row) : null;
    }

    public function cancelSubscription(int $clientId): bool
    {
        $sub = $this->activeSubscription($clientId);
        if (!$sub) return false;

        // Cancel on Mercado Pago
        if ($sub->mp_preapproval_id) {
            $this->mpPut('preapproval/' . urlencode($sub->mp_preapproval_id), [
                'status' => 'cancelled',
            ]);
        }

        $stmt = $this->db->prepare(
            "UPDATE mia_subscriptions SET status = 'cancelled' WHERE id = ?"
        );
        $stmt->execute([$sub->id]);

        (new ClientService())->updatePlan($clientId, 'trial', 'cancelled');
        return true;
    }

    // ── Plan helpers ──────────────────────────────────────────────────────────

    public static function planOptions(): array
    {
        return [
            'starter'    => ['label' => 'Starter',     'price' => App::PLAN_STARTER],
            'basic'      => ['label' => 'Básico',      'price' => App::PLAN_BASIC],
            'pro'        => ['label' => 'Pro',          'price' => App::PLAN_PRO],
            'enterprise' => ['label' => 'Enterprise',   'price' => App::PLAN_ENTERPRISE],
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createPendingRecord(int $clientId, string $plan, string $mpPreapprovalId, string $initPoint): void
    {
        $plans = self::planOptions();
        $amountCents = (int)(($plans[$plan]['price'] ?? 0) * 100);

        $stmt = $this->db->prepare(
            'INSERT INTO mia_subscriptions (client_id, plan, amount_cents, currency, status, mp_preapproval_id, mp_init_point)
             VALUES (?, ?, ?, ?, "pending", ?, ?)'
        );
        $stmt->execute([$clientId, $plan, $amountCents, 'PEN', $mpPreapprovalId, $initPoint]);
    }

    private function cancelByMpPreapprovalId(string $mpPreapprovalId): void
    {
        // Find client_id before updating
        $stmt = $this->db->prepare(
            "SELECT client_id FROM mia_subscriptions WHERE mp_preapproval_id = ? AND status = 'active' LIMIT 1"
        );
        $stmt->execute([$mpPreapprovalId]);
        $clientId = (int)($stmt->fetchColumn() ?: 0);

        $stmt = $this->db->prepare(
            "UPDATE mia_subscriptions SET status = 'cancelled' WHERE mp_preapproval_id = ?"
        );
        $stmt->execute([$mpPreapprovalId]);

        if ($clientId) {
            (new ClientService())->updatePlan($clientId, 'trial', 'cancelled');
        }
    }

    // ── Mercado Pago REST calls (no SDK needed) ───────────────────────────────

    private function mpPost(string $endpoint, array $body): array
    {
        return $this->mpRequest('POST', $endpoint, $body);
    }

    private function mpGet(string $endpoint): array
    {
        return $this->mpRequest('GET', $endpoint, []);
    }

    private function mpPut(string $endpoint, array $body): array
    {
        return $this->mpRequest('PUT', $endpoint, $body);
    }

    private function mpRequest(string $method, string $endpoint, array $body): array
    {
        $token = App::MP_ACCESS_TOKEN;
        if (!$token || str_starts_with($token, 'PLACEHOLDER')) {
            return ['error' => 'Mercado Pago no configurado. Agrega MP_ACCESS_TOKEN en App.php.'];
        }

        $url = self::MP_API . ltrim($endpoint, '/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log('[BillingService] cURL error: ' . $err);
            return ['error' => 'Error de conexión: ' . $err];
        }

        $data = json_decode($response ?: '{}', true) ?: [];

        if ($httpCode >= 400) {
            error_log('[BillingService] MP HTTP ' . $httpCode . ': ' . $response);
        }

        return $data;
    }

    public function clientToSession(Client $client): array
    {
        return [
            'id'            => $client->id,
            'business_name' => $client->business_name,
            'contact_name'  => $client->contact_name,
            'email'         => $client->email,
            'plan'          => $client->plan,
            'plan_status'   => $client->plan_status,
            'trial_ends_at' => $client->trial_ends_at,
        ];
    }
}
