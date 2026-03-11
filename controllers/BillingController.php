<?php
/**
 * mia/controllers/BillingController.php
 *
 * Subscription management, Stripe Checkout, and webhook handler.
 */

declare(strict_types=1);

class BillingController
{
    private BillingService $billing;

    public function __construct()
    {
        $this->billing = new BillingService();
    }

    private function requireClient(): Client
    {
        if (empty($_SESSION['mia_client_id'])) {
            header('Location: ' . App::basePath() . '/login');
            exit;
        }
        $client = (new ClientService())->findById((int)$_SESSION['mia_client_id']);
        if (!$client) {
            session_destroy();
            header('Location: ' . App::basePath() . '/login');
            exit;
        }
        // Keep session in sync with DB on every request
        $_SESSION['mia_client'] = $this->billing->clientToSession($client);
        return $client;
    }

    // ── Billing overview ──────────────────────────────────────────────────────

    public function index(): void
    {
        $client        = $this->requireClient();
        $clientService = new ClientService();

        // Handle Stripe redirect back
        $paymentStatus = $_GET['payment'] ?? '';
        if ($paymentStatus === 'success' && !empty($_GET['session_id'])) {
            $this->billing->confirmFromSession($client->id, $_GET['session_id']);
            // Re-fetch client after activation and refresh session
            $client = $clientService->findById($client->id);
            $_SESSION['mia_client'] = $this->billing->clientToSession($client);
        }

        // Auto-expire trial when trial_ends_at has passed
        $trialTs = $client->trial_ends_at ? strtotime($client->trial_ends_at) : false;
        if ($client->plan_status === 'trial' && $trialTs !== false && $trialTs < time()) {
            $clientService->updatePlan($client->id, 'trial', 'expired');
            $client = $clientService->findById($client->id);
            $_SESSION['mia_client'] = $this->billing->clientToSession($client);
        }

        $activeSub = $this->billing->activeSubscription($client->id);
        $history   = $this->billing->historyForClient($client->id);
        $plans     = BillingService::planOptions();

        require __DIR__ . '/../views/client/billing.php';
    }

    // ── Initiate Stripe Checkout ──────────────────────────────────────────────

    public function subscribe(): void
    {
        App::csrfVerify();
        $client = $this->requireClient();

        $plan = $_POST['plan'] ?? '';
        if (!in_array($plan, ['basic', 'pro', 'enterprise'], true)) {
            header('Location: ' . App::basePath() . '/dashboard/billing?error=invalid_plan');
            exit;
        }

        $result = $this->billing->createCheckoutSession($client, $plan);

        if (!empty($result['error'])) {
            $msg = urlencode($result['error']);
            header('Location: ' . App::basePath() . '/dashboard/billing?error=' . $msg);
            exit;
        }

        header('Location: ' . $result['url']);
        exit;
    }

    // ── Cancel subscription ───────────────────────────────────────────────────

    public function cancel(): void
    {
        App::csrfVerify();
        $client = $this->requireClient();

        $this->billing->cancelSubscription($client->id);

        header('Location: ' . App::basePath() . '/dashboard/billing?cancelled=1');
        exit;
    }

    // ── Stripe webhook (no session, no CSRF — uses Stripe signature) ──────────

    public function webhook(): void
    {
        $payload   = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        if (!$payload || !$sigHeader) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad request']);
            return;
        }

        (new BillingService())->handleWebhookEvent($payload, $sigHeader);

        http_response_code(200);
        echo json_encode(['received' => true]);
    }
}
