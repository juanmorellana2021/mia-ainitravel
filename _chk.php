<?php
$db = new PDO('mysql:host=localhost;dbname=mia_db;charset=utf8mb4','miauser','MiaPass2026!');

$total = $db->query("SELECT COUNT(*) FROM mia_client_leads WHERE client_id=1")->fetchColumn();
echo "Total leads: $total\n\n";

// All duplicate names
$dupes = $db->query("
    SELECT contact_name, GROUP_CONCAT(id ORDER BY id) as ids, GROUP_CONCAT(phone ORDER BY id) as phones, COUNT(*) as cnt
    FROM mia_client_leads WHERE client_id=1 AND contact_name != '' AND contact_name != '.'
    GROUP BY contact_name HAVING cnt > 1
")->fetchAll(PDO::FETCH_ASSOC);
echo "Duplicate names:\n";
foreach ($dupes as $r) echo "  \"{$r['contact_name']}\" ids=[{$r['ids']}] phones=[{$r['phones']}]\n";

echo "\n--- Full list (first 30) ---\n";
$all = $db->query("
    SELECT l.id, l.contact_name, l.phone, LENGTH(l.phone) as plen,
    (SELECT MAX(m.created_at) FROM mia_client_messages m WHERE m.lead_id=l.id) as last_msg
    FROM mia_client_leads l WHERE l.client_id=1
    ORDER BY last_msg DESC LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $r) {
    $lid = ($r['plen'] >= 14) ? ' [LID]' : '';
    echo "ID={$r['id']} phone={$r['phone']}{$lid} name=\"{$r['contact_name']}\" last={$r['last_msg']}\n";
}
