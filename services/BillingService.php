<?php
/**
 * mia/services/BillingService.php
 *
 * Handles subscription management via Stripe Checkout.
 *
 * Flow:
 *   1. createCheckoutSession()  → returns a Stripe-hosted checkout URL
 *   2. User pays on Stripe
 *   3. Stripe redirects back → confirmFromSession() activates the subscription
 *   4. (Optional) Stripe webhook → handleWebhookEvent() for server-side confirmation
 *
 * Setup:
 *   - Set STRIPE_SECRET in App.php (sk_test_... or sk_live_...)
 *   - Create Products + Prices in your Stripe Dashboard, paste price IDs in App.php
 */

declare(strict_types=1);

class BillingService
{
    private PDO $db;

    private const STRIPE_API = 'https://api.stripe.com/v1/';

    public function __construct()
    {
        $this->db = Database::get();
    }

    // ── Stripe Checkout ───────────────────────────────────────────────────────

    /**
     * Create a Stripe Checkout Session and return the hosted URL.
     * Returns ['url' => '...'] on success, ['error' => '...'] on failure.
     */
    public function createCheckoutSession(Client $client, string $plan): array
    {
        $priceId = $this->priceIdForPlan($plan);
        if (!$priceId) {
            return ['error' => 'Plan desconocido o sin configurar.'];
        }

        $base = App::URL;

        $params = [
            'mode'                           => 'subscription',
            'customer_email'                 => $client->email,
            'line_items[0][price]'           => $priceId,
            'line_items[0][quantity]'        => '1',
            'success_url'                    => $base . '/dashboard/billing?payment=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'                     => $base . '/dashboard/billing?payment=cancelled',
            'metadata[client_id]'            => (string)$client->id,
            'metadata[plan]'                 => $plan,
            'subscription_data[metadata][client_id]' => (string)$client->id,
        ];

        if ($client->stripe_customer_id) {
            unset($params['customer_email']);
            $params['customer'] = $client->stripe_customer_id;
        }

        $response = $this->stripePost('checkout/sessions', $params);

        if (isset($response['error'])) {
            error_log('[BillingService] Stripe error: ' . json_encode($response['error']));
            return ['error' => $response['error']['message'] ?? 'Error al conectar con Stripe.'];
        }

        // Store pending subscription record
        $this->createPendingRecord($client->id, $plan, $response['id'] ?? '');

        return ['url' => $response['url'] ?? ''];
    }

    /**
     * After Stripe redirects back with ?session_id=..., confirm it and activate.
     */
    public function confirmFromSession(int $clientId, string $sessionId): bool
    {
        $session = $this->stripeGet('checkout/sessions/' . urlencode($sessionId));

        if (empty($session['payment_status']) || $session['payment_status'] !== 'paid') {
            return false;
        }

        $plan = $session['metadata']['plan'] ?? 'basic';
        $stripeCustomer = $session['customer'] ?? null;
        $stripeSub = $session['subscription'] ?? null;

        // Update subscription record
        $stmt = $this->db->prepare(
            'UPDATE mia_subscriptions
             SET status = "active", stripe_customer_id = ?, stripe_subscription_id = ?,
                 paid_at = NOW(), billing_period_start = NOW(),
                 billing_period_end = DATE_ADD(NOW(), INTERVAL 1 MONTH)
             WHERE client_id = ? AND stripe_session_id = ?'
        );
        $stmt->execute([$stripeCustomer, $stripeSub, $clientId, $sessionId]);

        // Activate client plan
        $clientService = new ClientService();
        $clientService->updatePlan($clientId, $plan, 'active');
        if ($stripeCustomer) {
            $clientService->updateStripeCustomer($clientId, $stripeCustomer);
        }

        // Refresh session
        $client = $clientService->findById($clientId);
        if ($client) {
            $_SESSION['mia_client'] = $this->clientToSession($client);
        }

        return true;
    }

    /**
     * Handle Stripe webhook events (checkout.session.completed, etc.)
     */
    public function handleWebhookEvent(string $payload, string $sigHeader): void
    {
        $secret = App::STRIPE_WEBHOOK;
        if (!$secret || $secret === 'whsec_placeholder') return;

        // Verify webhook signature
        $computedSig = $this->computeWebhookSignature($payload, $sigHeader, $secret);
        if (!$computedSig) return;

        $event = json_decode($payload, true);
        if (!$event) return;

        switch ($event['type'] ?? '') {
            case 'checkout.session.completed':
                $session = $event['data']['object'] ?? [];
                $clientId = (int)($session['metadata']['client_id'] ?? 0);
                $sessionId = $session['id'] ?? '';
                if ($clientId && $sessionId) {
                    $this->confirmFromSession($clientId, $sessionId);
                }
                break;

            case 'customer.subscription.deleted':
                $sub = $event['data']['object'] ?? [];
                $this->cancelByStripeSubId($sub['id'] ?? '');
                break;
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

        // Cancel on Stripe if we have the subscription ID
        if ($sub->stripe_subscription_id) {
            $this->stripeDelete('subscriptions/' . urlencode($sub->stripe_subscription_id));
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
            'basic'      => ['label' => 'Básico',      'price' => App::PLAN_BASIC,      'price_id' => App::STRIPE_PRICE_BASIC],
            'pro'        => ['label' => 'Pro',         'price' => App::PLAN_PRO,        'price_id' => App::STRIPE_PRICE_PRO],
            'enterprise' => ['label' => 'Enterprise',  'price' => App::PLAN_ENTERPRISE, 'price_id' => App::STRIPE_PRICE_ENTERPRISE],
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function priceIdForPlan(string $plan): ?string
    {
        $options = self::planOptions();
        $priceId = $options[$plan]['price_id'] ?? '';
        return ($priceId && $priceId !== 'price_placeholder') ? $priceId : null;
    }

    private function createPendingRecord(int $clientId, string $plan, string $sessionId): void
    {
        $plans = self::planOptions();
        $amountCents = ($plans[$plan]['price'] ?? 0) * 100;

        $stmt = $this->db->prepare(
            'INSERT INTO mia_subscriptions (client_id, plan, amount_cents, currency, status, stripe_session_id)
             VALUES (?, ?, ?, ?, "pending", ?)'
        );
        $stmt->execute([$clientId, $plan, $amountCents, App::CURRENCY === 'S/' ? 'PEN' : 'USD', $sessionId]);
    }

    private function cancelByStripeSubId(string $stripeSubId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE mia_subscriptions SET status = 'cancelled' WHERE stripe_subscription_id = ?"
        );
        $stmt->execute([$stripeSubId]);
    }

    // ── Stripe REST calls (no library needed) ─────────────────────────────────

    private function stripePost(string $endpoint, array $params): array
    {
        return $this->stripeRequest('POST', $endpoint, $params);
    }

    private function stripeGet(string $endpoint): array
    {
        return $this->stripeRequest('GET', $endpoint, []);
    }

    private function stripeDelete(string $endpoint): array
    {
        return $this->stripeRequest('DELETE', $endpoint, []);
    }

    private function stripeRequest(string $method, string $endpoint, array $params): array
    {
        $secret = App::STRIPE_SECRET;
        if (!$secret || str_starts_with($secret, 'sk_placeholder')) {
            return ['error' => ['message' => 'Stripe no configurado. Agrega tu STRIPE_SECRET en App.php.']];
        }

        $ch = curl_init(self::STRIPE_API . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $secret . ':',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['error' => ['message' => 'cURL error: ' . $err]];
        }

        return json_decode($body ?: '{}', true) ?: [];
    }

    private function computeWebhookSignature(string $payload, string $sigHeader, string $secret): bool
    {
        // Parse Stripe-Signature header: t=...,v1=...
        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts['v1'] ?? '';

        if (!$timestamp || !$signature) return false;

        // Tolerance: 5 minutes
        if (abs(time() - (int)$timestamp) > 300) return false;

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        return hash_equals($expected, $signature);
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
