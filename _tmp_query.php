<?php
$pdo = new PDO('mysql:host=localhost;dbname=mia_db;charset=utf8mb4','miauser','MiaPass2026!');
$rows = $pdo->query('SELECT id, business_name, plan, plan_status, trial_ends_at FROM mia_clients ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
echo "=== mia_clients ===\n";
foreach ($rows as $r) echo implode(' | ', $r) . "\n";
