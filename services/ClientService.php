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

    public function updateStripeCustomer(int $clientId, string $stripeCustomerId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE mia_clients SET stripe_customer_id = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$stripeCustomerId, $clientId]);
    }

    public function updateWhatsApp(int $clientId, string $whatsappNumber): void
    {
        $stmt = $this->db->prepare(
            'UPDATE mia_clients SET whatsapp_number = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$whatsappNumber, $clientId]);
    }
}
