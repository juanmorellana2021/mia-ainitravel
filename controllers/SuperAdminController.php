<?php
/**
 * mia/controllers/SuperAdminController.php
 *
 * Superadmin panel — manage all clients, KPIs, billing.
 * Completely separate session from client auth (key: mia_superadmin).
 */

declare(strict_types=1);

class SuperAdminController
{
    // ── Auth guard ────────────────────────────────────────────────────────────

    private function requireSuperAdmin(): void
    {
        if (empty($_SESSION['mia_superadmin'])) {
            // No active session — try remember-me cookie before redirecting to login
            $cookieToken = $_COOKIE['mia_sa_remember'] ?? '';
            if ($cookieToken && $this->verifyRememberMeToken($cookieToken)) {
                session_regenerate_id(true);
                $_SESSION['mia_superadmin']               = App::SUPERADMIN_USER;
                $_SESSION['mia_superadmin_logged_at']     = time();
                $_SESSION['mia_superadmin_last_activity'] = time();
                $this->setRememberMeCookie(); // rotate token on every recovery
            } else {
                header('Location: ' . App::basePath() . '/superadmin/login');
                exit;
            }
        }

        $now      = time();
        $ttl      = App::SUPERADMIN_SESSION_TTL;
        $loggedAt = (int)($_SESSION['mia_superadmin_logged_at'] ?? 0);
        $lastSeen = (int)($_SESSION['mia_superadmin_last_activity'] ?? 0);

        $absoluteExpired = ($loggedAt > 0) && (($now - $loggedAt) > $ttl);
        $idleExpired     = ($lastSeen > 0) && (($now - $lastSeen) > $ttl);

        if ($absoluteExpired || $idleExpired) {
            $this->clearSuperAdminSession();
            header('Location: ' . App::basePath() . '/superadmin/login?expired=1');
            exit;
        }

        $_SESSION['mia_superadmin_last_activity'] = $now;
    }

    private function clearSuperAdminSession(): void
    {
        unset(
            $_SESSION['mia_superadmin'],
            $_SESSION['mia_superadmin_logged_at'],
            $_SESSION['mia_superadmin_last_activity']
        );
    }

    // ── Remember-me helpers ───────────────────────────────────────────────────

    private function rememberMeFile(): string
    {
        return __DIR__ . '/../tmp/sa_remember.json';
    }

    private function setRememberMeCookie(): void
    {
        $token  = bin2hex(random_bytes(32)); // 64-char hex, cryptographically random
        $expiry = time() + (30 * 24 * 3600); // 30 days
        $data   = ['hash' => hash('sha256', $token), 'expires' => $expiry];

        $dir = dirname($this->rememberMeFile());
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        file_put_contents($this->rememberMeFile(), json_encode($data), LOCK_EX);

        setcookie('mia_sa_remember', $token, [
            'expires'  => $expiry,
            'path'     => App::basePath() ?: '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    private function verifyRememberMeToken(string $token): bool
    {
        $file = $this->rememberMeFile();
        if (!file_exists($file)) {
            return false;
        }
        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data) || empty($data['hash']) || empty($data['expires'])) {
            return false;
        }
        if ($data['expires'] < time()) {
            @unlink($file);
            return false;
        }
        return hash_equals((string)$data['hash'], hash('sha256', $token));
    }

    private function clearRememberMeCookie(): void
    {
        $file = $this->rememberMeFile();
        if (file_exists($file)) {
            @unlink($file);
        }
        setcookie('mia_sa_remember', '', [
            'expires'  => time() - 3600,
            'path'     => App::basePath() ?: '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function loginForm(): void
    {
        if (!empty($_SESSION['mia_superadmin'])) {
            header('Location: ' . App::basePath() . '/superadmin/dashboard');
            exit;
        }
        $error   = $_GET['error']   ?? '';
        $expired = $_GET['expired'] ?? '';
        require __DIR__ . '/../views/superadmin/login.php';
    }

    public function loginSubmit(): void
    {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';

        if ($user === App::SUPERADMIN_USER &&
            password_verify($pass, App::SUPERADMIN_HASH)) {
            session_regenerate_id(true);
            $_SESSION['mia_superadmin']               = $user;
            $_SESSION['mia_superadmin_logged_at']     = time();
            $_SESSION['mia_superadmin_last_activity'] = time();

            if (!empty($_POST['remember_me'])) {
                $this->setRememberMeCookie();
            }

            header('Location: ' . App::basePath() . '/superadmin/dashboard');
            exit;
        }

        header('Location: ' . App::basePath() . '/superadmin/login?error=1');
        exit;
    }

    public function logout(): void
    {
        $this->clearSuperAdminSession();
        $this->clearRememberMeCookie();
        header('Location: ' . App::basePath() . '/superadmin/login');
        exit;
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $this->requireSuperAdmin();
        $svc            = new SuperAdminService();
        $stats          = $svc->stats();
        $recentSignups  = $svc->recentSignups(10);
        $recentProspects = $svc->recentProspects(15);
        require __DIR__ . '/../views/superadmin/dashboard.php';
    }

    // ── Clients list ──────────────────────────────────────────────────────────

    public function clients(): void
    {
        $this->requireSuperAdmin();
        $search       = trim($_GET['q']      ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $clients      = (new SuperAdminService())->allClients($search, $statusFilter);
        require __DIR__ . '/../views/superadmin/clients.php';
    }

    // ── Client detail / edit ──────────────────────────────────────────────────

    public function clientDetail(int $id): void
    {
        $this->requireSuperAdmin();
        $data = (new SuperAdminService())->clientFull($id);
        if (!$data) {
            http_response_code(404);
            echo '<h1>Client not found</h1>';
            return;
        }
        $saved = isset($_GET['saved']);
        require __DIR__ . '/../views/superadmin/client_detail.php';
    }

    public function clientSave(int $id): void
    {
        $this->requireSuperAdmin();
        App::csrfVerify();
        $svc = new SuperAdminService();

        // Check client exists
        $data = $svc->clientFull($id);
        if (!$data) {
            http_response_code(404);
            return;
        }

        $svc->updateClient($id, $_POST);

        // Optional password reset
        $newPass = trim($_POST['new_password'] ?? '');
        if ($newPass !== '') {
            if (strlen($newPass) < 8) {
                header('Location: ' . App::basePath() . '/superadmin/clients/' . $id . '?error=password_short');
                exit;
            }
            $svc->resetPassword($id, $newPass);
        }

        header('Location: ' . App::basePath() . '/superadmin/clients/' . $id . '?saved=1');
        exit;
    }

    public function clientDelete(int $id): void
    {
        $this->requireSuperAdmin();
        App::csrfVerify();
        (new SuperAdminService())->deleteClient($id);
        header('Location: ' . App::basePath() . '/superadmin/clients?deleted=1');
        exit;
    }

    // ── Mia bot connection (QR scan) ──────────────────────────────────────────

    public function miaBot(): void
    {
        $this->requireSuperAdmin();
        $pageTitle    = 'Conectar Bot Mia';
        $pageTopTitle = 'Bot Mia — Conexión WhatsApp';
        $activeNav    = 'mia_bot';
        require __DIR__ . '/../views/superadmin/mia_bot.php';
    }

    // ── Mia brain — behavior & configuration reference ────────────────────────

    public function miaBrain(): void
    {
        $this->requireSuperAdmin();
        $pageTitle    = 'Cerebro de Mia — Comportamientos y Reglas';
        $pageTopTitle = '🧠 Cerebro de Mia';
        $activeNav    = 'mia_brain';
        require __DIR__ . '/../views/superadmin/mia_brain.php';
    }

    /** JSON proxy — polls the bot server and returns status+QR to the browser. */
    public function miaBotStatus(): void
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json');

        $ctx = stream_context_create(['http' => ['timeout' => 4]]);
        $raw = @file_get_contents('http://127.0.0.1:3001/qr/mia', false, $ctx);

        if ($raw === false) {
            echo json_encode(['status' => 'disconnected', 'qr_image' => null, 'phone' => null]);
            return;
        }

        $data = json_decode($raw, true);
        echo json_encode($data ?: ['status' => 'disconnected', 'qr_image' => null, 'phone' => null]);
    }

    // ── Prospects list ────────────────────────────────────────────────────────

    public function prospects(): void
    {
        $this->requireSuperAdmin();
        $search      = trim($_GET['q']     ?? '');
        $stateFilter = trim($_GET['state'] ?? '');
        $prospects   = (new SuperAdminService())->allProspects($search, $stateFilter);
        require __DIR__ . '/../views/superadmin/prospects.php';
    }

    // ── Prospect detail ───────────────────────────────────────────────────────

    public function prospectDetail(int $id): void
    {
        $this->requireSuperAdmin();
        $data = (new SuperAdminService())->prospectFull($id);
        if (!$data) {
            http_response_code(404);
            echo '<h1>Prospecto no encontrado</h1>';
            return;
        }
        require __DIR__ . '/../views/superadmin/prospect_detail.php';
    }

    /** JSON — returns conversation history for the slide-in panel */
    public function prospectChat(int $id): void
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json');
        $data = (new SuperAdminService())->prospectFull($id);
        if (!$data) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            return;
        }
        echo json_encode([
            'session' => [
                'id'            => $data['session']['id'],
                'phone'         => $data['session']['phone'],
                'state'         => $data['session']['state'],
                'business_name' => $data['session']['business_name'],
                'contact_name'  => $data['session']['contact_name'],
                'business_type' => $data['session']['business_type'],
                'email'         => $data['session']['email'],
                'updated_at'    => $data['session']['updated_at'],
                'client_id'     => $data['session']['client_id'] ?? null,
            ],
            'history' => $data['history'],
        ], JSON_UNESCAPED_UNICODE);
    }

    // ── Analytics ─────────────────────────────────────────────────────────────

    public function analytics(): void
    {
        $this->requireSuperAdmin();
        $db   = Database::get();
        $days = max(1, min(90, (int)($_GET['days'] ?? 30)));

        // Totals over period
        $totals = $db->query("
            SELECT
                COUNT(DISTINCT CASE WHEN event='pageview' THEN session_id END) AS sessions,
                COUNT(DISTINCT ip_hash)                                         AS unique_visitors,
                COUNT(CASE WHEN event='pageview' THEN 1 END)                    AS pageviews,
                COUNT(CASE WHEN event='cta_click' THEN 1 END)                   AS cta_clicks,
                ROUND(AVG(CASE WHEN event='pageleave' AND duration_ms>0 THEN duration_ms END)/1000,1) AS avg_seconds,
                COUNT(CASE WHEN event='pageleave' AND duration_ms < 10000 THEN 1 END) AS bounces
            FROM mia_page_events
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
        ")->fetch(PDO::FETCH_ASSOC);

        // Daily pageviews AND cta_clicks for dual chart
        $daily = $db->query("
            SELECT
                DATE(created_at) AS day,
                COUNT(CASE WHEN event='pageview'  THEN 1 END) AS pvs,
                COUNT(CASE WHEN event='cta_click' THEN 1 END) AS ctas
            FROM mia_page_events
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Top pages
        $topPages = $db->query("
            SELECT page, COUNT(*) AS views
            FROM mia_page_events
            WHERE event='pageview' AND created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            GROUP BY page ORDER BY views DESC LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Referrers — normalize to domain only (strip query strings & fbclid)
        $topReferrers = $db->query("
            SELECT
                CASE
                    WHEN referrer = '' OR referrer IS NULL THEN '(directo)'
                    WHEN referrer LIKE '%facebook.com%' OR referrer LIKE '%fb.com%' THEN 'facebook.com'
                    WHEN referrer LIKE '%instagram.com%' THEN 'instagram.com'
                    WHEN referrer LIKE '%google.com%'    THEN 'google.com'
                    WHEN referrer LIKE '%mia.ainitravel.com%' THEN 'mia.ainitravel.com (interno)'
                    ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(referrer,'https://',''),'http://',''),'/',1),'?',1)
                END AS ref,
                COUNT(*) AS cnt
            FROM mia_page_events
            WHERE event='pageview' AND created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            GROUP BY ref ORDER BY cnt DESC LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // UTM sources — show readable names, include cta_clicks per campaign
        $topUtm = $db->query("
            SELECT
                utm_source   AS src,
                utm_medium   AS med,
                utm_campaign AS camp,
                COUNT(CASE WHEN event='pageview'  THEN 1 END) AS sessions,
                COUNT(CASE WHEN event='cta_click' THEN 1 END) AS cta_clicks
            FROM mia_page_events
            WHERE utm_source != '' AND utm_source IS NOT NULL
              AND created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            GROUP BY src, med, camp ORDER BY sessions DESC LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Device breakdown with click behavior
        $devices = $db->query("
            SELECT
                device,
                COUNT(CASE WHEN event='pageview'  THEN 1 END) AS pageviews,
                COUNT(CASE WHEN event='cta_click' THEN 1 END) AS cta_clicks
            FROM mia_page_events
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            GROUP BY device
        ")->fetchAll(PDO::FETCH_ASSOC);

        // WhatsApp bot funnel stats for the same period
        $waStats = $db->query("
            SELECT
                COUNT(*) AS total_conversations,
                COUNT(CASE WHEN state NOT IN ('new','collecting_contact_name','intro') THEN 1 END) AS engaged,
                COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END)                       AS leads_captured,
                COUNT(CASE WHEN state = 'captured' THEN 1 END)                                     AS fully_captured,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY) THEN 1 END)    AS new_in_period
            FROM mia_sales_sessions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
        ")->fetch(PDO::FETCH_ASSOC);

        // Bot conversation trend by day
        $waTrend = $db->query("
            SELECT DATE(created_at) AS day, COUNT(*) AS cnt
            FROM mia_sales_sessions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            GROUP BY DATE(created_at) ORDER BY day ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Bot pipeline — how many prospects at each stage
        $waPipeline = $db->query("
            SELECT state, COUNT(*) AS cnt
            FROM mia_sales_sessions
            WHERE updated_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            GROUP BY state ORDER BY cnt DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/superadmin/analytics.php';
    }

    // ── Reset prospect bot state ─────────────────────────────────────────────

    public function prospectResetState(int $id): void
    {
        $this->requireSuperAdmin();
        App::csrfVerify();

        $db   = Database::get();
        $stmt = $db->prepare(
            "UPDATE mia_sales_sessions
             SET state = 'new', updated_at = NOW()
             WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);

        header('Location: ' . App::basePath() . '/superadmin/prospects/' . $id . '?reset=1');
        exit;
    }

    // ── Prospect outbound message (human agent → WhatsApp) ───────────────────

    public function prospectSendMessage(int $id): void
    {
        header('Content-Type: application/json');
        $this->requireSuperAdmin();
        App::csrfVerify();

        $data = (new SuperAdminService())->prospectFull($id);
        if (!$data) {
            http_response_code(404);
            echo json_encode(['error' => 'Prospect not found']);
            return;
        }

        $text = trim($_POST['message'] ?? '');
        if ($text === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Empty message']);
            return;
        }

        $phone = $data['session']['phone'];

        // Append to conv_history as 'human_agent' so the AI sees it as context
        // (MiaSalesService::loadHistory maps human_agent → assistant before sending to Groq)
        $db   = Database::get();
        $stmt = $db->prepare('SELECT conv_history FROM mia_sales_sessions WHERE id = ?');
        $stmt->execute([$id]);
        $raw     = $stmt->fetchColumn();
        $history = $raw ? (json_decode($raw, true) ?? []) : [];
        $history[] = ['role' => 'human_agent', 'content' => $text];
        $history   = array_slice($history, -30);
        $db->prepare('UPDATE mia_sales_sessions SET conv_history = ?, updated_at = NOW() WHERE id = ?')
           ->execute([json_encode($history, JSON_UNESCAPED_UNICODE), $id]);

        // Send via Mia's WhatsApp bot (admin API on 127.0.0.1:3001)
        $delivered = false;
        $payload   = json_encode(['to' => $phone, 'message' => $text]);
        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
            'content'       => $payload,
            'timeout'       => 8,
            'ignore_errors' => true,
        ]]);
        $result = @file_get_contents('http://127.0.0.1:3001/send', false, $ctx);
        if ($result !== false) {
            $r = json_decode($result, true);
            $delivered = !empty($r['success']);
        }

        echo json_encode(['success' => true, 'delivered' => $delivered]);
    }

    /** JSON — returns messages array for the live-polled conversation thread */
    public function prospectGetMessages(int $id): void
    {
        header('Content-Type: application/json');
        $this->requireSuperAdmin();
        $data = (new SuperAdminService())->prospectFull($id);
        if (!$data) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            return;
        }
        echo json_encode($data['history'], JSON_UNESCAPED_UNICODE);
    }

    // ── Convert prospect → client ─────────────────────────────────────────────

    public function prospectConvert(int $id): void
    {
        $this->requireSuperAdmin();
        App::csrfVerify();

        $result = (new SuperAdminService())->convertToClient($id);

        if (isset($result['error'])) {
            header('Location: ' . App::basePath() . '/superadmin/prospects/' . $id . '?error=' . urlencode($result['error']));
            exit;
        }

        // Redirect to new client page with the temp password surfaced once
        header('Location: ' . App::basePath() . '/superadmin/clients/' . $result['client_id']
            . '?converted=1&tmp=' . urlencode($result['temp_password']));
        exit;
    }
}
