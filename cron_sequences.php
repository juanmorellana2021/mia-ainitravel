<?php
/**
 * mia/cron_sequences.php
 *
 * Fires due follow-up sequence steps for all clients.
 * Run every hour via cron on the VPS:
 *
 *   0 * * * * php /var/www/html/mia-whatsapp.com/cron_sequences.php >> /var/log/mia_sequences.log 2>&1
 *
 * Can also be triggered manually:
 *   php /var/www/html/mia-whatsapp.com/cron_sequences.php
 */

declare(strict_types=1);

// Only allow CLI or localhost invocations
$isCli = (PHP_SAPI === 'cli');
$isLocal = isset($_SERVER['REMOTE_ADDR'])
    && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true);

if (!$isCli && !$isLocal) {
    http_response_code(403);
    exit('Forbidden');
}

// Bootstrap — minimal set (no session, no controllers)
require_once __DIR__ . '/config/App.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Sequence.php';
require_once __DIR__ . '/models/SequenceStep.php';
require_once __DIR__ . '/models/LeadSequence.php';
require_once __DIR__ . '/services/SequenceService.php';

$start   = microtime(true);
$service = new SequenceService();
$result  = $service->fireDueSteps();
$elapsed = round((microtime(true) - $start) * 1000);

$ts = date('Y-m-d H:i:s');
echo "[{$ts}] cron_sequences: due={$result['due']} fired={$result['fired']} errors=" . count($result['errors']) . " ({$elapsed}ms)\n";

if (!empty($result['errors'])) {
    foreach ($result['errors'] as $err) {
        echo "  ERROR: {$err}\n";
    }
}
