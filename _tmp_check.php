<?php
$pdo = new PDO('mysql:host=localhost;dbname=mia_db;charset=utf8mb4','miauser','MiaPass2026!');
$r = $pdo->query('SELECT id, business_name, plan, plan_status, updated_at FROM mia_clients WHERE id=1')->fetch(PDO::FETCH_ASSOC);
echo "Client: {$r['business_name']} | plan={$r['plan']} | status={$r['plan_status']} | updated={$r['updated_at']}\n";
