<?php
/**
 * mia/controllers/BillingController.php
 *
 * Subscription management via Mercado Pago Preapproval API.
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
        // Always refresh session from DB so trial_ends_at is current
        $_SESSION['mia_client'] = (new BillingService())->clientToSession($client);
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

        // Auto-expire trial when trial_ends_at has passed
        $clientService = new ClientService();
        $trialTs = $client->trial_ends_at ? strtotime($client->trial_ends_at) : false;
        if ($client->plan_status === 'trial' && $trialTs !== false && $trialTs < time()) {
            $clientService->updatePlan($client->id, 'trial', 'expired');
            $client = $clientService->findById($client->id);
            $_SESSION['mia_client'] = $billing->clientToSession($client);
        }

        // Handle Mercado Pago redirect back
        $paymentStatus = $_GET['payment'] ?? '';
        if ($paymentStatus === 'success') {
            // Preapproval was authorized — look for pending sub and try to confirm
            $pendingSubs = $billing->historyForClient($client->id);
            foreach ($pendingSubs as $sub) {
                if ($sub->status === 'pending' && $sub->mp_preapproval_id) {
                    $billing->confirmSubscription($client->id, $sub->mp_preapproval_id);
                }
            }
            $client    = (new ClientService())->findById($client->id);
            $activeSub = $billing->activeSubscription($client->id);
            $history   = $billing->historyForClient($client->id);
        }

        require __DIR__ . '/../views/client/billing.php';
    }

    // ── Initiate Mercado Pago subscription ─────────────────────────────────────

    public function subscribe(): void
    {
        App::csrfVerify();
        $client = $this->requireClient();

        $plan = $_POST['plan'] ?? '';
        $validPlans = array_keys(BillingService::planOptions());
        if (!in_array($plan, $validPlans, true)) {
            header('Location: ' . App::basePath() . '/dashboard/billing?error=Plan%20inv%C3%A1lido');
            exit;
        }

        $billing = new BillingService();
        $result  = $billing->createSubscription($client, $plan);

        if (!empty($result['error'])) {
            $msg = urlencode($result['error']);
            header('Location: ' . App::basePath() . '/dashboard/billing?error=' . $msg);
            exit;
        }

        if (empty($result['url'])) {
            header('Location: ' . App::basePath() . '/dashboard/billing?error=No%20se%20recibi%C3%B3%20URL%20de%20pago');
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

    // ── Mercado Pago webhook (no session, no CSRF) ────────────────────────────

    public function webhook(): void
    {
        $payload = file_get_contents('php://input');

        if (!$payload) {
            http_response_code(400);
            echo json_encode(['error' => 'Empty payload']);
            return;
        }

        (new BillingService())->handleWebhookEvent($payload);

        http_response_code(200);
        echo json_encode(['received' => true]);
    }

    // ── Add-on checkout ───────────────────────────────────────────────────────

    /**
     * POST /dashboard/billing/addon
     * Initiates a Mercado Pago single-payment Preference for an add-on.
     * $_POST['type']: 'extra_500' | 'unlimited_month'
     */
    public function addonCheckout(): void
    {
        App::csrfVerify();
        $client = $this->requireClient();

        $type = $_POST['type'] ?? '';
        if (!in_array($type, ['extra_500', 'unlimited_month'], true)) {
            header('Location: ' . App::basePath() . '/dashboard/billing?error=Complemento+inv%C3%A1lido');
            exit;
        }

        // Only plans with a conv limit can purchase add-ons
        $limit = ClientBotService::CONV_LIMITS[$client->plan] ?? 0;
        if ($limit === 0) {
            header('Location: ' . App::basePath() . '/dashboard/billing?error=Tu+plan+ya+es+ilimitado');
            exit;
        }

        $result = (new AddonService())->createCheckout($client, $type);

        if (!empty($result['error'])) {
            $msg = urlencode($result['error']);
            header('Location: ' . App::basePath() . '/dashboard/billing?error=' . $msg);
            exit;
        }

        header('Location: ' . $result['url']);
        exit;
    }

    /**
     * GET /dashboard/billing/addon-return
     * MP redirects here after the add-on payment is approved/failed.
     * Query params: status, collection_id (payment_id), ref (external_reference)
     */
    public function addonReturn(): void
    {
        $client = $this->requireClient();

        $status    = $_GET['status']        ?? '';
        $paymentId = $_GET['collection_id'] ?? '';
        $extRef    = $_GET['ref']           ?? '';

        if ($status === 'approved' && $paymentId && $extRef) {
            // Verify with MP and activate
            $addonSvc = new AddonService();
            $payment  = $addonSvc->mpGetPayment($paymentId);

            if (($payment['status'] ?? '') === 'approved'
                && ($payment['external_reference'] ?? '') === $extRef) {
                $addonSvc->activateByExternalRef($extRef, $paymentId);
                header('Location: ' . App::basePath() . '/dashboard/billing?addon=success');
            } else {
                header('Location: ' . App::basePath() . '/dashboard/billing?addon=pending');
            }
        } elseif ($status === 'pending') {
            header('Location: ' . App::basePath() . '/dashboard/billing?addon=pending');
        } else {
            header('Location: ' . App::basePath() . '/dashboard/billing?addon=failed');
        }
        exit;
    }
}
