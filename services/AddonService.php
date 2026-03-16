<?php
/**
 * mia/services/AddonService.php
 *
 * Manages one-time monthly capacity add-ons:
 *   - extra_500:       +500 conversations for the current month (stackable)
 *   - unlimited_month: removes monthly cap entirely for the current month
 *   - notice:          internal flag so we only email the owner once per month
 *
 * MP Preference external_reference format:  addon-{clientId}-{typeCode}-{YYYYMM}
 *   typeCode: e500 = extra_500, ulm = unlimited_month
 *
 * This service owns all DB reads/writes for the mia_client_addons table.
 * BillingController calls createCheckout() then routes the return via addonReturn().
 * The BillingService webhook calls activateByExternalRef() for server-push confirmation.
 */

declare(strict_types=1);

class AddonService
{
    private PDO $db;

    private const MP_API = 'https://api.mercadopago.com/';

    public function __construct()
    {
        $this->db = Database::get();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS mia_client_addons (
                id               INT          AUTO_INCREMENT PRIMARY KEY,
                client_id        INT          NOT NULL,
                type             ENUM('extra_500','unlimited_month','notice') NOT NULL,
                month_year       VARCHAR(7)   NOT NULL COMMENT 'YYYY-MM',
                status           ENUM('pending','active') DEFAULT 'pending',
                amount_paid      DECIMAL(10,2) DEFAULT 0.00,
                mp_preference_id VARCHAR(120) NULL,
                mp_payment_id    VARCHAR(120) NULL,
                created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_client      (client_id),
                INDEX idx_client_month(client_id, month_year, type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // ── Plan cap read helpers (called by ClientBotService) ────────────────────

    /**
     * Returns the total number of extra conversations purchased (active) this month.
     * Stackable: two extra_500 addons = 1000 extra slots.
     */
    public function getExtraConvosThisMonth(int $clientId): int
    {
        $month = date('Y-m');
        $stmt  = $this->db->prepare(
            "SELECT COUNT(*) FROM mia_client_addons
             WHERE client_id = ? AND type = 'extra_500' AND month_year = ? AND status = 'active'"
        );
        $stmt->execute([$clientId, $month]);
        return (int)$stmt->fetchColumn() * 500;
    }

    /**
     * Returns true if the client purchased an unlimited-month add-on that is active.
     */
    public function hasUnlimitedThisMonth(int $clientId): bool
    {
        $month = date('Y-m');
        $stmt  = $this->db->prepare(
            "SELECT COUNT(*) FROM mia_client_addons
             WHERE client_id = ? AND type = 'unlimited_month' AND month_year = ? AND status = 'active'"
        );
        $stmt->execute([$clientId, $month]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Returns true if we have already sent a limit-hit notice this month,
     * so we don't spam the owner every single blocked message.
     */
    public function limitNoticeAlreadySentThisMonth(int $clientId): bool
    {
        $month = date('Y-m');
        $stmt  = $this->db->prepare(
            "SELECT COUNT(*) FROM mia_client_addons
             WHERE client_id = ? AND type = 'notice' AND month_year = ?"
        );
        $stmt->execute([$clientId, $month]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Record that we sent a limit-hit notice this month (idempotent).
     */
    public function markLimitNoticeSent(int $clientId): void
    {
        if ($this->limitNoticeAlreadySentThisMonth($clientId)) {
            return;
        }
        $stmt = $this->db->prepare(
            "INSERT INTO mia_client_addons (client_id, type, month_year, status, amount_paid)
             VALUES (?, 'notice', ?, 'active', 0)"
        );
        $stmt->execute([$clientId, date('Y-m')]);
    }

    // ── Checkout (creates Mercado Pago Preference) ────────────────────────────

    /**
     * Create a Mercado Pago checkout preference for an add-on purchase.
     * Returns ['url' => '...'] on success, ['error' => '...'] on failure.
     */
    public function createCheckout(Client $client, string $type): array
    {
        if (!in_array($type, ['extra_500', 'unlimited_month'], true)) {
            return ['error' => 'Tipo de complemento desconocido.'];
        }

        $month    = date('Y-m');
        $monthFmt = date('Ym');    // YYYYMM for external_reference
        $typeCode = $type === 'extra_500' ? 'e500' : 'ulm';
        $extRef   = "addon-{$client->id}-{$typeCode}-{$monthFmt}";

        $price = $type === 'extra_500'
            ? (float)App::ADDON_EXTRA_500_PRICE
            : (float)App::ADDON_UNLIMITED_PRICE;

        $label = $type === 'extra_500'
            ? '+500 conversaciones — ' . date('F Y')
            : 'Ilimitado este mes — ' . date('F Y');

        $base = App::URL;
        $body = [
            'items' => [[
                'title'      => 'Mia — ' . $label,
                'unit_price' => $price,
                'quantity'   => 1,
                'currency_id'=> 'PEN',
            ]],
            'external_reference' => $extRef,
            'back_urls' => [
                'success' => $base . '/dashboard/billing/addon-return?status=success&ref=' . urlencode($extRef),
                'failure' => $base . '/dashboard/billing/addon-return?status=failure',
                'pending' => $base . '/dashboard/billing/addon-return?status=pending&ref=' . urlencode($extRef),
            ],
            'auto_return' => 'approved',
            'payer'       => ['email' => $client->email],
        ];

        $response = $this->mpPost('checkout/preferences', $body);

        if (!empty($response['error']) || empty($response['id'])) {
            $msg = $response['message'] ?? $response['error'] ?? 'Error al conectar con Mercado Pago.';
            error_log('[AddonService] MP error: ' . json_encode($response));
            return ['error' => is_string($msg) ? $msg : 'Error al crear el pago.'];
        }

        // Store pending record
        $stmt = $this->db->prepare(
            "INSERT INTO mia_client_addons
                (client_id, type, month_year, status, amount_paid, mp_preference_id)
             VALUES (?, ?, ?, 'pending', ?, ?)"
        );
        $stmt->execute([$client->id, $type, $month, $price, $response['id']]);

        $url = $response['init_point'] ?? $response['sandbox_init_point'] ?? '';
        return ['url' => $url];
    }

    // ── Activation ────────────────────────────────────────────────────────────

    /**
     * Activate an addon given the external_reference and the MP payment_id.
     * Called from: BillingController::addonReturn() (synchronous) AND
     *              BillingService::handleWebhookEvent() (async webhook).
     *
     * Returns true if successfully activated, false if not found / already active.
     */
    public function activateByExternalRef(string $extRef, string $mpPaymentId): bool
    {
        // Parse: addon-{clientId}-{typeCode}-{YYYYMM}
        if (!preg_match('/^addon-(\d+)-(e500|ulm)-(\d{6})$/', $extRef, $m)) {
            error_log('[AddonService] Invalid extRef: ' . $extRef);
            return false;
        }

        $clientId = (int)$m[1];
        $type     = $m[2] === 'e500' ? 'extra_500' : 'unlimited_month';
        $monthRaw = $m[3]; // YYYYMM
        $month    = substr($monthRaw, 0, 4) . '-' . substr($monthRaw, 4, 2);

        // Idempotency: if this exact payment was already activated, skip.
        // Check by mp_payment_id so stacked extra_500 purchases are NOT blocked.
        $stmt = $this->db->prepare(
            "SELECT id FROM mia_client_addons WHERE mp_payment_id = ? AND status = 'active' LIMIT 1"
        );
        $stmt->execute([$mpPaymentId]);
        if ($stmt->fetchColumn()) {
            return true; // Already activated (webhook + return race-condition on same payment)
        }

        // Activate the most recent pending record
        $stmt = $this->db->prepare(
            "UPDATE mia_client_addons
             SET status = 'active', mp_payment_id = ?
             WHERE client_id = ? AND type = ? AND month_year = ? AND status = 'pending'
             ORDER BY created_at DESC
             LIMIT 1"
        );
        $stmt->execute([$mpPaymentId, $clientId, $type, $month]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Returns all active (non-notice) addons for the current month for display in billing.
     */
    public function activeThisMonth(int $clientId): array
    {
        $month = date('Y-m');
        $stmt  = $this->db->prepare(
            "SELECT * FROM mia_client_addons
             WHERE client_id = ? AND month_year = ? AND status = 'active' AND type != 'notice'
             ORDER BY created_at ASC"
        );
        $stmt->execute([$clientId, $month]);
        return array_map([ClientAddon::class, 'fromRow'], $stmt->fetchAll());
    }

    // ── MP REST helper (mirrors BillingService pattern) ───────────────────────

    private function mpPost(string $endpoint, array $body): array
    {
        $token = App::MP_ACCESS_TOKEN;
        if (!$token || str_starts_with($token, 'PLACEHOLDER')) {
            return ['error' => 'Mercado Pago no configurado.'];
        }
        $url = self::MP_API . ltrim($endpoint, '/');
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err) return ['error' => $err];
        return json_decode($resp ?: '{}', true) ?: [];
    }

    public function mpGetPayment(string $paymentId): array
    {
        $token = App::MP_ACCESS_TOKEN;
        if (!$token || str_starts_with($token, 'PLACEHOLDER')) {
            return ['error' => 'Mercado Pago no configurado.'];
        }
        $url = self::MP_API . 'v1/payments/' . urlencode($paymentId);
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp ?: '{}', true) ?: [];
    }
}
