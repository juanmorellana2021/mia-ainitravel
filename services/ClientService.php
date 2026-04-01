<?php
/**
 * mia/services/ClientService.php
 *
 * Registration, authentication, and CRUD for Mia business clients.
 */

declare(strict_types=1);

class ClientService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get();
        $this->ensureOnboardingColumn();
    }

    private function ensureOnboardingColumn(): void
    {
        try {
            $this->db->exec(
                'ALTER TABLE mia_clients ADD COLUMN onboarding_done TINYINT(1) NOT NULL DEFAULT 0'
            );
        } catch (\Throwable $e) {
            // Column already exists — ignore
        }
    }

    public function markOnboardingDone(int $clientId): void
    {
        $this->db->prepare(
            'UPDATE mia_clients SET onboarding_done = 1, updated_at = NOW() WHERE id = ? LIMIT 1'
        )->execute([$clientId]);
    }

    // ── Registration ──────────────────────────────────────────────────────────

    /**
     * Register a new client. Returns the new Client or throws on duplicate email.
     */
    public function register(array $data): Client
    {
        // Check email uniqueness
        $stmt = $this->db->prepare('SELECT id FROM mia_clients WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($data['email']))]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Este email ya está registrado.');
        }

        $hash       = password_hash($data['password'], PASSWORD_DEFAULT);
        $trialEnd   = date('Y-m-d H:i:s', strtotime('+' . App::FREE_TRIAL_DAYS . ' days'));

        $stmt = $this->db->prepare(
            'INSERT INTO mia_clients
                (business_name, contact_name, email, password_hash, phone, business_type, trial_ends_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            trim($data['business_name']),
            trim($data['contact_name']),
            strtolower(trim($data['email'])),
            $hash,
            trim($data['phone'] ?? ''),
            $data['business_type'] ?? 'other',
            $trialEnd,
        ]);

        return $this->findById((int)$this->db->lastInsertId());
    }

    // ── Authentication ────────────────────────────────────────────────────────

    /**
     * Verify email + password. Returns Client on success, null on failure.
     */
    public function authenticate(string $email, string $password): ?Client
    {
        $stmt = $this->db->prepare('SELECT * FROM mia_clients WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        $row = $stmt->fetch();

        if (!$row) return null;
        if (!password_verify($password, $row['password_hash'])) return null;

        return Client::fromRow($row);
    }

    // ── Lookups ───────────────────────────────────────────────────────────────

    public function findById(int $id): ?Client
    {
        $stmt = $this->db->prepare('SELECT * FROM mia_clients WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Client::fromRow($row) : null;
    }

    public function findByEmail(string $email): ?Client
    {
        $stmt = $this->db->prepare('SELECT * FROM mia_clients WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ? Client::fromRow($row) : null;
    }

    // ── Updates ───────────────────────────────────────────────────────────────

    public function updatePlan(int $clientId, string $plan, string $status): void
    {
        $stmt = $this->db->prepare(
            'UPDATE mia_clients SET plan = ?, plan_status = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$plan, $status, $clientId]);
    }

    public function updateSettings(int $clientId, array $data): void
    {
        // Build bot_config JSON from structured fields
        $botConfig = json_encode([
            'business_type'  => trim($data['business_type']   ?? 'other'),
            'custom_type'    => trim($data['bot_custom_type'] ?? ''),
            'description'    => trim($data['bot_description'] ?? ''),
            'services'       => trim($data['bot_services']    ?? ''),
            'pricing'        => trim($data['bot_pricing']     ?? ''),
            'hours'          => trim($data['bot_hours']       ?? ''),
            'faqs'           => trim($data['bot_faqs']        ?? ''),
            'website'        => trim($data['bot_website']     ?? ''),
            'location'       => trim($data['bot_location']    ?? ''),
            'google_maps'    => trim($data['bot_google_maps'] ?? ''),
            'language'       => trim($data['bot_language']    ?? 'es'),
            'tone'           => trim($data['bot_tone']        ?? 'friendly'),
            'char_skills'    => (array)($data['char_skills']  ?? []),
            // Business hours
            'hours_enabled'  => !empty($data['hours_enabled']),
            'hours_config'   => $data['hours_config'] ?? [],
            // Owner personal phone for owner-mode bypass
            'owner_phone'    => preg_replace('/[^0-9]/', '', $data['owner_phone'] ?? ''),
        ], JSON_UNESCAPED_UNICODE);

        $stmt = $this->db->prepare(
            'UPDATE mia_clients
             SET contact_name = ?, phone = ?, business_type = ?,
                 bot_config = ?,
                 notify_email = ?, notify_on_capture = ?, notify_daily_summary = ?,
                 updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            trim($data['contact_name']         ?? ''),
            trim($data['phone']                ?? ''),
            trim($data['business_type']        ?? 'other'),
            $botConfig,
            trim($data['notify_email']         ?? '') ?: null,
            (int)(bool)($data['notify_on_capture']    ?? 0),
            (int)(bool)($data['notify_daily_summary'] ?? 0),
            $clientId,
        ]);
    }

    public function updateWhatsApp(int $clientId, string $whatsappNumber): void
    {
        $stmt = $this->db->prepare(
            'UPDATE mia_clients SET whatsapp_number = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$whatsappNumber, $clientId]);
    }

    public function updateWaStatus(int $clientId, string $status, ?string $phone = null): void
    {
        if ($phone !== null) {
            $stmt = $this->db->prepare(
                'UPDATE mia_clients SET bot_wa_status = ?, whatsapp_number = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$status, $phone, $clientId]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE mia_clients SET bot_wa_status = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$status, $clientId]);
        }
    }

    /**
     * Merge sales-config fields into bot_config['sales'] without overwriting other fields.
     */
    public function updateSalesConfig(int $clientId, array $data): void
    {
        $stmt = $this->db->prepare('SELECT bot_config FROM mia_clients WHERE id = ? LIMIT 1');
        $stmt->execute([$clientId]);
        $row = $stmt->fetch();
        $cfg = json_decode($row['bot_config'] ?? '{}', true) ?: [];

        $cfg['sales'] = [
            'approach'            => $data['sales_approach']        ?? 'friendly',
            'cta_text'            => substr(trim($data['cta_text']            ?? ''), 0, 200),
            'cta_link'            => substr(trim($data['cta_link']            ?? ''), 0, 500),
            'deposit_text'        => substr(trim($data['deposit_text']        ?? ''), 0, 200),
            'qualifier_questions' => substr(trim($data['qualifier_questions'] ?? ''), 0, 1000),
            'qualifier_info'      => substr(trim($data['qualifier_info']      ?? ''), 0, 300),
            'handoff_triggers'    => substr(trim($data['handoff_triggers']    ?? ''), 0, 300),
            'handoff_message'     => substr(trim($data['handoff_message']     ?? ''), 0, 400),
            'handoff_phone'       => substr(trim($data['handoff_phone']       ?? ''), 0, 30),
            'followup_template'   => substr(trim($data['followup_template']   ?? ''), 0, 500),
            'special_offer'       => substr(trim($data['special_offer']       ?? ''), 0, 300),
            'yape_phone'          => substr(preg_replace('/[^0-9+ ]/', '', $data['yape_phone'] ?? ''), 0, 30),
            'plin_phone'          => substr(preg_replace('/[^0-9+ ]/', '', $data['plin_phone'] ?? ''), 0, 30),
            'bank_info'           => substr(trim($data['bank_info']           ?? ''), 0, 500),
            'payment_qr'          => substr(trim($data['payment_qr']          ?? ''), 0, 500),
        ];

        $this->db->prepare(
            'UPDATE mia_clients SET bot_config = ?, updated_at = NOW() WHERE id = ?'
        )->execute([json_encode($cfg, JSON_UNESCAPED_UNICODE), $clientId]);
    }
}
