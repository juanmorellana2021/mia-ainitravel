<?php
/**
 * mia/controllers/BillingController.php
 *
 * Subscription management, Stripe Checkout, and webhook handler.
 */

declare(strict_types=1);

class BillingController
{
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
        return $client;
    }

    // ── Billing overview ──────────────────────────────────────────────────────

    public function index(): void
    {
        $client  = $this->requireClient();
        $billing = new BillingService();

        $activeSub    = $billing->activeSubscription($client->id);
        $history      = $billing->historyForClient($client->id);
        $plans        = BillingService::planOptions();

        // Handle Stripe redirect back
        $paymentStatus = $_GET['payment'] ?? '';
        if ($paymentStatus === 'success' && !empty($_GET['session_id'])) {
            $billing->confirmFromSession($client->id, $_GET['session_id']);
            // Re-fetch client after activation
            $client = (new ClientService())->findById($client->id);
            $activeSub = $billing->activeSubscription($client->id);
        }

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

        $billing = new BillingService();
        $result  = $billing->createCheckoutSession($client, $plan);

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

        (new BillingService())->cancelSubscription($client->id);

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
