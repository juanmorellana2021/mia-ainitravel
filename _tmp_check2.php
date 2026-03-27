<?php
$pdo = new PDO('mysql:host=localhost;dbname=mia_db;charset=utf8mb4','miauser','MiaPass2026!');
$rows = $pdo->query("SELECT id, phone, contact_name FROM mia_client_leads WHERE client_id=1 ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo "=== Recent leads (client 1) ===\n";
foreach ($rows as $r) {
    $isLid = preg_match('/^[0-9]{14,16}$/', $r['phone']) ? 'LID' : 'PHONE';
    echo "#{$r['id']} | {$isLid} | {$r['phone']} | {$r['contact_name']}\n";
}
