<?php
$db = new mysqli('127.0.0.1', 'root', '1234', 'sicms');
if ($db->connect_errno) { echo "CONNECT ERR: {$db->connect_error}\n"; exit(1); }

$complaintId = 9;
$actorAccountId = 1;
$now = date('Y-m-d H:i:s');
$keys = array('complaint_details', 'incident', 'hearings', 'final_information');
$normalized = array();
foreach ($keys as $key) { $normalized[$key] = true; }
$json = json_encode($normalizedapse);
echo "JSON (" . strlen($json) . " chars): $json\n";

$col = $db->query("SHOW COLUMNS FROM complaints LIKE 'respondent_visibility'")->fetch_assoc();
echo "Live column: {$col['Type']}\n";

$db->begin_transaction();
$stmt = $db->prepare(
    "UPDATE complaints
     SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ?, updated_at = ?
     WHERE complaint_id = ?"
);
$stmt->bind_param('sissi', $now, $actorAccountId, $json, $now, $complaintId);
if ($stmt->execute()) { $db->commit(); echo "UPDATE OK (JSON now fits)\n"; }
else { $db->rollback(); echo "UPDATE FAIL: ({$stmt->errno}) {$stmt->error}\n"; }

$row = $db->query("SELECT respondent_visibility FROM complaints WHERE complaint_id = $complaintId")->fetch_assoc();
echo "Stored: " . json_encode($row['respondent_visibility']) . "\n";
$db->close();
