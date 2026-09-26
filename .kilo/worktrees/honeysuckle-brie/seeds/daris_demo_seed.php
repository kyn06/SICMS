<?php
// DARIS (SICMS) permanent demo dataset seed.
// Re-runnable (idempotent): skips accounts/cases that already exist.
//
// Creates:
//   - 27 sample accounts (1 SDRU Head, 2 Discipline Coordinators,
//     2 Reformation Coordinators, 2 SDRU Staff, 10 Complainant/Students,
//     10 Respondents)
//   - 18 realistic cases across the full Discipline + Reformation workflows
//   - Respondents/witnesses/evidence (with placeholder files), counter-statements,
//     complainant responses, hearings, case updates, case history/timeline,
//     messages, notifications, audit logs, reformation records/reports,
//     case approvals, and legacy cases with case_source='Legacy'.
//
// Common demo password for all 27 accounts: Darisdemo@2026

declare(strict_types=1);

require_once __DIR__ . '/../web/config/Database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    fwrite(STDERR, "Cannot connect to database.\n");
    exit(1);
}

$passwordHash = password_hash('Darisdemo@2026', PASSWORD_DEFAULT);
$demoTag      = 'Sample demo case';

/* ---------------------------------------------------------------- helpers */

function runDemoQuery(mysqli $db, string $sql, array $params = [], string $types = ''): bool {
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        fwrite(STDERR, 'PREPARE FAILED: ' . $sql . ' :: ' . $db->error . "\n");
        return false;
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        fwrite(STDERR, 'EXEC FAILED: ' . $stmt->error . "\n");
        return false;
    }
    return true;
}

function demoInsert(mysqli $db, string $table, array $data): int {
    $cols = array_keys($data);
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES ($placeholders)";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        fwrite(STDERR, 'PREPARE FAILED: ' . $sql . ' :: ' . $db->error . "\n");
        return 0;
    }
    $vals = array_values($data);
    $types = '';
    foreach ($vals as $v) {
        $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
    }
    $stmt->bind_param($types, ...$vals);
    if (!$stmt->execute()) {
        fwrite(STDERR, 'INSERT INTO ' . $table . ' FAILED: ' . $stmt->error . "\n");
        return 0;
    }
    return (int) $stmt->insert_id;
}

function demoFetchOne(mysqli $db, string $sql, array $params = [], string $types = ''): ?array {
    $stmt = $db->prepare($sql);
    if (!$stmt) return null;
    if ($types !== '') $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) return null;
    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
}

/* --------------------------------------------- accounts (upsert by email) */

function demoAccount(mysqli $db, array $a, string $passwordHash): int {
    $existing = demoFetchOne($db, "SELECT account_id FROM accounts WHERE email = ?", [$a['email']], 's');
    if ($existing) {
        return (int) $existing['account_id'];
    }
    $data = array_merge([
        'first_name' => '', 'last_name' => '', 'email' => '',
        'password_hash' => $passwordHash, 'auth_provider' => null,
        'role' => 'student', 'respondent_type' => null, 'status' => 'active',
        'college_id' => null, 'student_number' => null, 'college' => null,
        'course' => null, 'year_level' => null, 'section' => null,
        'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        'phone_number' => '', 'gender' => '', 'birthday' => null,
        'address' => '', 'affiliation' => null, 'organization' => null,
    ], $a);
    return demoInsert($db, 'accounts', $data);
}

// 1 SDRU Head
$head = demoAccount($db, [
    'first_name' => 'Rosario', 'last_name' => 'Evasco',
    'email' => 'rosario.evasco@darisdemo.local',
    'role' => 'head-of-sdru', 'status' => 'active',
    'phone_number' => '09171234501', 'gender' => 'Female',
    'birthday' => '1978-05-14', 'address' => 'Brgy. Central, City of Muñoz, Nueva Ecija',
    'created_at' => '2025-01-10 09:00:00', 'updated_at' => '2025-01-10 09:00:00',
], $passwordHash);

// 2 Discipline Coordinators
$discCoordA = demoAccount($db, [
    'first_name' => 'Carlito', 'last_name' => 'Villanueva',
    'email' => 'carlito.villanueva@darisdemo.local',
    'role' => 'coordinator', 'status' => 'active',
    'phone_number' => '09171234502', 'gender' => 'Male',
    'birthday' => '1982-09-02', 'address' => 'Brgy. Rizal, San Jose City, Nueva Ecija',
    'created_at' => '2025-01-10 09:05:00', 'updated_at' => '2025-01-10 09:05:00',
], $passwordHash);

$discCoordB = demoAccount($db, [
    'first_name' => 'Ivy', 'last_name' => 'Rosales',
    'email' => 'ivy.rosales@darisdemo.local',
    'role' => 'coordinator', 'status' => 'active',
    'phone_number' => '09171234503', 'gender' => 'Female',
    'birthday' => '1985-11-23', 'address' => 'Brgy. Sto. Domingo, Cabanatuan City, Nueva Ecija',
    'created_at' => '2025-01-10 09:10:00', 'updated_at' => '2025-01-10 09:10:00',
], $passwordHash);

// 2 Reformation Coordinators
$reformCoordA = demoAccount($db, [
    'first_name' => 'Nestor', 'last_name' => 'Palafox',
    'email' => 'nestor.palafox@darisdemo.local',
    'role' => 'reformation-coordinator', 'status' => 'active',
    'phone_number' => '09171234504', 'gender' => 'Male',
    'birthday' => '1980-03-30', 'address' => 'Brgy. San Isidro, Gapan City, Nueva Ecija',
    'created_at' => '2025-01-10 09:15:00', 'updated_at' => '2025-01-10 09:15:00',
], $passwordHash);

$reformCoordB = demoAccount($db, [
    'first_name' => 'Gina', 'last_name' => 'Sarmiento',
    'email' => 'gina.sarmiento@darisdemo.local',
    'role' => 'reformation-coordinator', 'status' => 'active',
    'phone_number' => '09171234505', 'gender' => 'Female',
    'birthday' => '1987-07-19', 'address' => 'Brgy. San Nicolas, Palayan City, Nueva Ecija',
    'created_at' => '2025-01-10 09:20:00', 'updated_at' => '2025-01-10 09:20:00',
], $passwordHash);

// 2 SDRU Staff
$staffA = demoAccount($db, [
    'first_name' => 'Donna', 'last_name' => 'Aquino',
    'email' => 'donna.aquino@darisdemo.local',
    'role' => 'sdru-staff', 'status' => 'active',
    'phone_number' => '09171234506', 'gender' => 'Female',
    'birthday' => '1992-02-11', 'address' => 'Brgy. Burgos, Talavera, Nueva Ecija',
    'created_at' => '2025-01-10 09:25:00', 'updated_at' => '2025-01-10 09:25:00',
], $passwordHash);

$staffB = demoAccount($db, [
    'first_name' => 'Felix', 'last_name' => 'Manansala',
    'email' => 'felix.manansala@darisdemo.local',
    'role' => 'sdru-staff', 'status' => 'active',
    'phone_number' => '09171234507', 'gender' => 'Male',
    'birthday' => '1990-08-25', 'address' => 'Brgy. General Luna, Guimba, Nueva Ecija',
    'created_at' => '2025-01-10 09:30:00', 'updated_at' => '2025-01-10 09:30:00',
], $passwordHash);

// 10 Complainant/Students
$students = [
    ['Michaela', 'Dizon',     'Female', '2004-03-12', '23-1001', 'College of Engineering',             'Bachelor of Science in Information Technology (BSIT)', 'Fourth Year', '4-2', '09171234511'],
    ['Rainier',    'Castro',     'Male',   '2005-07-08', '24-1002', 'College of Business and Accountancy', 'Bachelor of Science in Accountancy (BSAc)',          'Third Year',  '3-1', '09171234512'],
    ['Andrea',     'Mercado',    'Female', '2003-11-21', '22-1003', 'College of Science',                  'Bachelor of Science in Biology (BSBio)',             'Fourth Year', '4-1', '09171234513'],
    ['Kenneth',    'Lumibao',    'Male',   '2005-01-30', '24-1004', 'College of Arts and Social Sciences', 'Bachelor of Science in Psychology (BSPsych)',        'Third Year',  '3-2', '09171234514'],
    ['Janica',     'Del Rosario','Female', '2004-06-17', '23-1005', 'College of Education',                'Bachelor of Elementary Education (BEEd)',            'Fourth Year', '4-3', '09171234515'],
    ['Nathaniel',  'Buenavides', 'Male',   '2006-02-05', '25-1006', 'College of Agriculture',              'Bachelor of Science in Agriculture (BSA)',           'Second Year', '2-1', '09171234516'],
    ['Pearl',      'Santiago',   'Female', '2005-09-14', '24-1007', 'College of Hospitality Management',   'Bachelor of Science in Hospitality Management (BSHM)','Third Year','3-3', '09171234517'],
    ['Alvin',      'Ramos',      'Male',   '2004-04-28', '23-1008', 'College of Veterinary Science and Medicine', 'Doctor of Veterinary Medicine (DVM)',          'Fifth Year',  '5-1', '09171234518'],
    ['Shanaia',    'Pineda',     'Female', '2005-12-03', '24-1009', 'College of Fisheries',                'Bachelor of Science in Fisheries (BSF)',             'Third Year',  '3-1', '09171234519'],
    ['Dexter',     'Palencia',   'Male',   '2004-10-19', '23-1010', 'College of Home Science and Industry','Bachelor of Science in Textile and Fashion Technology (BSTFT)', 'Fourth Year', '4-1', '09171234520'],
];
$studentIds = [];
foreach ($students as $i => $s) {
    $sid = demoAccount($db, [
        'first_name' => $s[0], 'last_name' => $s[1],
        'email' => strtolower($s[0] . '.' . $s[1]) . '@darisdemo.edu.ph',
        'role' => 'student', 'status' => 'active',
        'student_number' => $s[4], 'college' => $s[5], 'course' => $s[6],
        'year_level' => $s[7], 'section' => $s[8],
        'phone_number' => $s[9], 'gender' => $s[2], 'birthday' => $s[3],
        'address' => 'Dormitory, ' . $s[5] . ' Campus, Nueva Ecija',
        'created_at' => '2025-01-15 10:00:00', 'updated_at' => '2025-01-15 10:00:00',
    ], $passwordHash);
    $studentIds[] = $sid;
}
list(
    $stu1, $stu2, $stu3, $stu4, $stu5,
    $stu6, $stu7, $stu8, $stu9, $stu10
) = $studentIds;

// 10 Respondents
$respondents = [
    ['Emerson',    'Gatdula',   'Male',   '2004-01-22', '23-2001', 'College of Engineering',             'Bachelor of Science in Civil Engineering (BSCE)',     'Fourth Year', '4-3', '09171234521', 'Student'],
    ['Charmaine',  'Ortiz',     'Female', '2005-08-09', '24-2002', 'College of Science',                  'Bachelor of Science in Chemistry (BSChem)',          'Third Year',  '3-2', '09171234522', 'Student'],
    ['Jomari',     'Tongol',    'Male',   '2004-05-16', '23-2003', 'College of Business and Accountancy', 'Bachelor of Science in Business Administration (BSBA)','Fourth Year','4-1','09171234523', 'Student'],
    ['Patricia',   'Navarro',   'Female', '2005-03-27', '24-2004', 'College of Arts and Social Sciences', 'Bachelor of Arts in Social Sciences (BASS)',         'Third Year',  '3-3', '09171234524', 'Student'],
    ['Renan',      'Corpuz',    'Male',   '2006-06-04', '25-2005', 'College of Education',                'Bachelor of Physical Education (BPEd)',              'Second Year', '2-1', '09171234525', 'Student'],
    ['Angelica',   'Domingo',   'Female', '2005-10-11', '24-2006', 'College of Fisheries',                'Bachelor of Science in Fisheries (BSF)',             'Third Year',  '3-1', '09171234526', 'Student'],
    ['Luis',       'Hermoso',   'Male',   '2004-07-20', '23-2007', 'College of Engineering',              'Bachelor of Science in Agricultural and Biosystems Engineering (BSABE)', 'Fourth Year', '4-2', '09171234527', 'Student'],
    ['Kathrina',   'Suarez',    'Female', '2004-12-13', '23-2008', 'College of Science',                  'Bachelor of Science in Environmental Science (BSES)', 'Fourth Year','4-1','09171234528', 'Student'],
    ['Marco',      'Ilagan',    'Male',   '2005-04-02', '24-2009', 'College of Business and Accountancy', 'Bachelor of Science in Entrepreneurship (BSEntrep)',  'Third Year',  '3-2', '09171234529', 'Student'],
    ['Jennica',    'Ocampo',    'Female', '2005-09-28', '24-2010', 'College of Veterinary Science and Medicine', 'Doctor of Veterinary Medicine (DVM)',             'Third Year',  '3-1', '09171234530', 'Student'],
];
$respondentIds = [];
foreach ($respondents as $r) {
    $rid = demoAccount($db, [
        'first_name' => $r[0], 'last_name' => $r[1],
        'email' => strtolower($r[0] . '.' . $r[1]) . '@darisdemo.edu.ph',
        'role' => 'respondent', 'respondent_type' => $r[10], 'status' => 'active',
        'student_number' => $r[4], 'college' => $r[5], 'course' => $r[6],
        'year_level' => $r[7], 'section' => $r[8],
        'phone_number' => $r[9], 'gender' => $r[2], 'birthday' => $r[3],
        'address' => 'Residence Hall ' . $r[5] . ' Campus, Nueva Ecija',
        'created_at' => '2025-01-15 10:30:00', 'updated_at' => '2025-01-15 10:30:00',
    ], $passwordHash);
    $respondentIds[] = $rid;
}
list(
    $resp1, $resp2, $resp3, $resp4, $resp5,
    $resp6, $resp7, $resp8, $resp9, $resp10
) = $respondentIds;

echo "Accounts seeded (Head, 2 DC, 2 RC, 2 Staff, 10 students, 10 respondents).\n";

/* ------------------------------------------------------------- cases */

/**
 * Insert a complaint row. Returns complaint_id or 0 if case number exists.
 */
function demoCase(mysqli $db, array $c, string $demoTag): int {
    $existing = demoFetchOne($db, "SELECT complaint_id FROM complaints WHERE case_number = ?", [$c['case_number']], 's');
    if ($existing) {
        return (int) $existing['complaint_id'];
    }
    $defaults = [
        'complaint_id' => null, 'case_number' => '', 'complaint_title' => '',
        'submitted_by_account_id' => 0, 'complainant_name' => '',
        'complainant_gender' => null, 'complainant_age' => null,
        'complainant_type' => 'Student', 'complainant_relationship' => null,
        'complainant_address' => null, 'complainant_employee_no' => null,
        'complainant_department' => null, 'complainant_position' => null,
        'complainant_affiliation' => null, 'complainant_purpose' => null,
        'complainant_student_no' => null, 'complainant_email' => null,
        'complainant_contact' => null, 'complainant_college' => null,
        'complainant_course' => null, 'complainant_year_level' => null,
        'complainant_section' => null, 'complainant_course_year' => null,
        'case_classification' => 'Unclassified', 'incident_datetime' => null,
        'incident_location' => null, 'complaint_details' => null,
        'status' => 'Under Investigation', 'respondent_released_at' => null,
        'respondent_released_by_account_id' => null, 'respondent_visibility' => null,
        'assigned_coordinator_account_id' => null,
        'assigned_reformation_coordinator_account_id' => null,
        'reformation_completed_at' => null, 'submitted_at' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        'case_source' => 'Online Submission', 'original_case_date' => null,
        'legacy_outcome' => null, 'action_taken' => null, 'resolution_date' => null,
        'remarks_notes' => $demoTag, 'outcome' => null, 'legacy_entry_source' => null,
    ];
    $data = array_merge($defaults, $c);
    $id = demoInsert($db, 'complaints', $data);
    return $id;
}

function demoRespondent(mysqli $db, int $complaintId, array $r, string $demoTag = 'Sample demo case'): int {
    $defaults = [
        'complaint_id' => $complaintId, 'account_id' => null,
        'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => '', 'gender' => null, 'age' => null, 'birthday' => null,
        'student_no' => null, 'college' => null, 'course_year' => null,
        'contact_info' => null, 'details' => $demoTag, 'invited_at' => null,
        'invitation_token' => null, 'email' => null, 'employee_no' => null,
        'office_department' => null, 'position' => null, 'affiliation' => null,
        'address' => null, 'year_level' => null, 'created_at' => date('Y-m-d H:i:s'),
    ];
    return demoInsert($db, 'complaint_respondents', array_merge($defaults, $r));
}

function demoWitness(mysqli $db, int $complaintId, array $w): int {
    $defaults = [
        'complaint_id' => $complaintId, 'person_type' => 'Student',
        'full_name' => '', 'gender' => null, 'age' => null, 'birthday' => null,
        'student_no' => null, 'contact_info' => null, 'statement' => null,
        'email' => null, 'employee_no' => null, 'college' => null,
        'office_department' => null, 'position' => null, 'affiliation' => null,
        'address' => null, 'year_level' => null, 'course_year' => null,
        'created_at' => date('Y-m-d H:i:s'),
    ];
    return demoInsert($db, 'complaint_witnesses', array_merge($defaults, $w));
}

/**
 * Ensure a placeholder evidence file exists under storage/evidence.
 * Returns a relative path usable for file_path fields.
 */
function demoEvidenceFile(string $relativeName, string $kind = 'jpg'): string {
    $root = __DIR__ . '/../storage/evidence/';
    if (!is_dir($root)) {
        mkdir($root, 0777, true);
    }
    $path = $root . $relativeName;
    if (!file_exists($path)) {
        $bin = "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 32); // JPEG-ish placeholder
        if ($kind === 'pdf') {
            $bin = "%PDF-1.4\n% sample demo attachment\n%%EOF";
        }
        if ($kind === 'png') {
            $bin = "\x89PNG\r\n\x1a\n" . str_repeat("\x00", 32);
        }
        file_put_contents($path, $bin);
    }
    return 'storage/evidence/' . $relativeName;
}

function demoEvidence(mysqli $db, int $complaintId, array $e): int {
    $defaults = [
        'complaint_id' => $complaintId, 'update_id' => null,
        'reformation_record_id' => null, 'counter_statement_id' => null,
        'original_filename' => '', 'stored_filename' => '',
        'file_path' => '', 'mime_type' => 'application/octet-stream',
        'file_size' => 0, 'uploaded_at' => date('Y-m-d H:i:s'), 'doc_type' => null,
    ];
    return demoInsert($db, 'complaint_evidence', array_merge($defaults, $e));
}

function demoHistory(mysqli $db, int $complaintId, array $h): int {
    $defaults = [
        'complaint_id' => $complaintId, 'action' => null, 'previous_status' => null,
        'new_status' => null, 'remarks' => null, 'revision_fields' => null,
        'assigned_coordinator_account_id' => null,
        'reformation_coordinator_account_id' => null,
        'created_by_account_id' => null, 'created_at' => date('Y-m-d H:i:s'),
    ];
    return demoInsert($db, 'case_history', array_merge($defaults, $h));
}

function demoUpdate(mysqli $db, int $complaintId, array $u): int {
    $defaults = [
        'complaint_id' => $complaintId, 'author_account_id' => 0,
        'update_type' => 'administrative', 'details' => null,
        'case_status_snapshot' => 'Under Investigation', 'created_at' => date('Y-m-d H:i:s'),
    ];
    return demoInsert($db, 'case_updates', array_merge($defaults, $u));
}

function demoCounterStatement(mysqli $db, int $complaintId, int $respondentId, int $accountId, array $cs): int {
    $existing = demoFetchOne($db, 'SELECT counter_statement_id FROM counter_statements WHERE complaint_id = ? AND respondent_id = ? LIMIT 1', [$complaintId, $respondentId], 'ii');
    if ($existing) {
        return (int) $existing['counter_statement_id'];
    }
    $defaults = [
        'complaint_id' => $complaintId, 'respondent_id' => $respondentId,
        'respondent_account_id' => $accountId, 'content' => '',
        'status' => 'Draft', 'submitted_at' => null, 'coordinator_action' => null,
        'coordinator_action_by_account_id' => null, 'coordinator_action_at' => null,
        'forwarded_at' => null, 'complaint_response_content' => null,
        'complaint_response_updated_at' => null, 'complaint_response_submitted_at' => null,
        'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
    ];
    return demoInsert($db, 'counter_statements', array_merge($defaults, $cs));
}

function demoHearing(mysqli $db, int $complaintId, array $h): int {
    $defaults = [
        'complaint_id' => $complaintId, 'scheduled_by_account_id' => null,
        'hearing_datetime' => null, 'venue' => null, 'google_meet_link' => null,
        'google_event_id' => null, 'hearing_type' => 'Face-to-face',
        'remarks' => null, 'instructions' => null, 'status' => 'Scheduled',
        'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
    ];
    return demoInsert($db, 'hearings', array_merge($defaults, $h));
}

function demoNotification(mysqli $db, int $accountId, array $n): int {
    $defaults = [
        'account_id' => $accountId, 'type' => null, 'title' => null,
        'message' => null, 'link' => null, 'is_read' => 0, 'read_at' => null,
        'created_at' => date('Y-m-d H:i:s'),
    ];
    return demoInsert($db, 'notifications', array_merge($defaults, $n));
}

function demoAudit(mysqli $db, array $a): int {
    $defaults = [
        'account_id' => null, 'user_name' => null, 'user_role' => null,
        'action' => null, 'description' => null, 'ip_address' => '127.0.0.1',
        'user_agent' => 'DARIS Demo Dataset', 'created_at' => date('Y-m-d H:i:s'),
    ];
    return demoInsert($db, 'audit_logs', array_merge($defaults, $a));
}

function demoMessage(mysqli $db, int $complaintId, int $senderId, int $receiverId, string $message, string $createdAt, int $isRead = 0): int {
    $existing = demoFetchOne($db, 'SELECT message_id FROM case_messages
        WHERE complaint_id = ? AND sender_account_id = ? AND receiver_account_id = ?
          AND message = ? AND created_at = ? LIMIT 1',
        [$complaintId, $senderId, $receiverId, $message, $createdAt], 'iiiss');
    if ($existing) {
        return (int) $existing['message_id'];
    }
    return demoInsert($db, 'case_messages', [
        'complaint_id' => $complaintId,
        'sender_account_id' => $senderId,
        'receiver_account_id' => $receiverId,
        'message' => $message,
        'attachment' => null,
        'is_read' => $isRead,
        'read_at' => $isRead ? $createdAt : null,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

function demoDemoComplaintIds(mysqli $db): array {
    $ids = [];
    $stmt = $db->prepare("SELECT complaint_id FROM complaints WHERE remarks_notes = ?");
    if (!$stmt) {
        fwrite(STDERR, 'PREPARE FAILED (demoDemoComplaintIds): ' . $db->error . "\n");
        return $ids;
    }
    $tag = 'Sample demo case';
    $stmt->bind_param('s', $tag);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ids[] = (int) $row['complaint_id'];
    }
    $stmt->close();
    return $ids;
}

function demoClearDemoThreads(mysqli $db): void {
    $demoIds = demoDemoComplaintIds($db);
    if (!$demoIds) { return; }
    $ids = implode(',', $demoIds);
    $db->query("DELETE FROM conversation_participants WHERE complaint_id IN ($ids)");
    $db->query("DELETE FROM case_messages WHERE complaint_id IN ($ids)");
}

function demoParticipant(mysqli $db, int $complaintId, int $accountId, int $counterpartId): int {
    $existing = demoFetchOne($db,
        'SELECT participant_id FROM conversation_participants
         WHERE complaint_id = ? AND account_id = ? AND counterpart_account_id = ? LIMIT 1',
        [$complaintId, $accountId, $counterpartId], 'iii');
    if ($existing) {
        return (int) $existing['participant_id'];
    }
    return demoInsert($db, 'conversation_participants', [
        'complaint_id' => $complaintId,
        'account_id' => $accountId,
        'counterpart_account_id' => $counterpartId,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

$casesCreated = 0;

/* ============ CASE 1 — Resolved: Cyberbullying in group chat (full flow) ============ */
$c1 = demoCase($db, [
    'case_number' => 'SDRU-20250721-0101',
    'complaint_title' => 'Cyberbullying in Freshmen Group Chat',
    'submitted_by_account_id' => $stu1,
    'complainant_name' => 'Michaela Dizon',
    'complainant_gender' => 'Female', 'complainant_age' => 21,
    'complainant_type' => 'Student',
    'complainant_address' => 'Balibago, San Jose City, Nueva Ecija',
    'complainant_student_no' => '23-1001', 'complainant_email' => 'michaela.dizon@darisdemo.edu.ph',
    'complainant_contact' => '09171234511', 'complainant_college' => 'College of Engineering',
    'complainant_course' => 'Bachelor of Science in Information Technology (BSIT)',
    'complainant_year_level' => 'Fourth Year', 'complainant_section' => '4-2',
    'complainant_course_year' => 'Bachelor of Science in Information Technology (BSIT) | 4-2',
    'case_classification' => 'Cyberbullying',
    'incident_datetime' => '2025-07-10 21:15:00',
    'incident_location' => 'Online — Freshmen class group chat',
    'complaint_details' => 'Complainant reported receiving repeated derogatory and harassing messages in the official class group chat, including private message follow-ups containing threats on social media. Screenshots were attached at submission.',
    'status' => 'Resolved', 'submitted_at' => '2025-07-21 09:30:00',
    'created_at' => '2025-07-21 09:30:00', 'updated_at' => '2025-12-18 11:20:00',
    'assigned_coordinator_account_id' => $discCoordA,
    'resolution_date' => '2025-12-18',
    'action_taken' => 'Respondent issued formal written reprimand and required to attend school-wide cyber-safety seminar.',
    'outcome' => 'Resolved through mediation; respondent complied with sanction.',
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c1) {
    $casesCreated++;
    $r1 = demoRespondent($db, $c1, [
        'account_id' => $resp1, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Emerson Gatdula', 'gender' => 'Male', 'age' => 21, 'birthday' => '2004-01-22',
        'student_no' => '23-2001', 'college' => 'College of Engineering',
        'course_year' => 'Bachelor of Science in Civil Engineering (BSCE) | 4-3',
        'contact_info' => '09171234521', 'invited_at' => '2025-08-01 10:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'emerson.gatdula@darisdemo.edu.ph',
        'address' => 'Residence Hall, Engineering Campus', 'year_level' => 'Fourth Year',
        'created_at' => '2025-07-21 09:35:00',
    ]);
    // released full visibility
    runDemoQuery($db, "UPDATE complaints SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ? WHERE complaint_id = ?",
        ['2025-08-01 10:05:00', $discCoordA, '{"complaint_details":true,"incident":true,"hearings":true,"final_information":true}', $c1], 'sisi');
    demoWitness($db, $c1, [
        'person_type' => 'Student', 'full_name' => 'Kristel Abad', 'gender' => 'Female', 'age' => 20,
        'statement' => 'I saw the messages posted in the group chat. They were directed at the complainant.',
        'college' => 'College of Engineering', 'course_year' => 'BSIT | 4-2',
        'student_no' => '23-1006', 'year_level' => 'Fourth Year',
    ]);
    demoEvidence($db, $c1, [
        'original_filename' => 'group_chat_screenshots.jpg', 'stored_filename' => '20250721_0101_screenshots.jpg',
        'file_path' => demoEvidenceFile('20250721_0101_screenshots.jpg', 'jpg'),
        'mime_type' => 'image/jpeg', 'file_size' => 64, 'uploaded_at' => '2025-07-21 09:40:00',
        'doc_type' => 'Screenshot',
    ]);
    demoHistory($db, $c1, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu1, 'created_at' => '2025-07-21 09:30:00']);
    demoHistory($db, $c1, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffA, 'created_at' => '2025-07-25 14:00:00']);
    demoHistory($db, $c1, ['action' => 'Case Assignment', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $head, 'created_at' => '2025-07-28 09:00:00']);
    demoHistory($db, $c1, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $head, 'created_at' => '2025-07-28 09:00:00']);
    demoHistory($db, $c1, ['action' => 'Case Classification', 'new_status' => 'Under Investigation', 'remarks' => 'Classified as Cyberbullying.', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $discCoordA, 'created_at' => '2025-08-01 10:00:00']);
    demoHistory($db, $c1, ['action' => 'Forwarded to Respondent', 'new_status' => 'Under Investigation', 'remarks' => 'Case details (complaint, incident, hearings, final info) released to the respondent.', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $discCoordA, 'created_at' => '2025-08-01 10:05:00']);
    demoHistory($db, $c1, ['action' => 'Counter-Statement Requested', 'created_by_account_id' => $discCoordA, 'created_at' => '2025-08-01 10:10:00']);
    $cs1 = demoCounterStatement($db, $c1, $r1, $resp1, [
        'content' => 'I acknowledge posting comments but they were not meant as harassment. I offered to remove the posts and apologize. I dispute the claim of threats.',
        'status' => 'Submitted', 'submitted_at' => '2025-08-15 16:20:00',
        'coordinator_action' => 'forwarded_to_complainant', 'coordinator_action_by_account_id' => $discCoordA,
        'coordinator_action_at' => '2025-08-20 09:00:00', 'forwarded_at' => '2025-08-20 09:00:00',
        'complaint_response_content' => 'I accept the apology. I request that the school keep the sanction on record so it does not happen again.',
        'complaint_response_updated_at' => '2025-09-01 13:00:00', 'complaint_response_submitted_at' => '2025-09-01 13:05:00',
        'created_at' => '2025-08-10 18:00:00', 'updated_at' => '2025-09-01 13:05:00',
    ]);
    demoEvidence($db, $c1, [
        'counter_statement_id' => $cs1, 'original_filename' => 'counter_statement_affidavit.pdf',
        'stored_filename' => '20250815_0101_affidavit.pdf',
        'file_path' => demoEvidenceFile('20250815_0101_affidavit.pdf', 'pdf'),
        'mime_type' => 'application/pdf', 'file_size' => 48, 'uploaded_at' => '2025-08-15 16:25:00',
        'doc_type' => 'Affidavit',
    ]);
    demoHistory($db, $c1, ['action' => 'Counter-Statement Submitted', 'remarks' => 'Counter-statement submitted by respondent and forwarded to the complainant for response.', 'created_by_account_id' => $resp1, 'created_at' => '2025-08-15 16:25:00']);
    demoHearing($db, $c1, [
        'scheduled_by_account_id' => $discCoordA, 'hearing_datetime' => '2025-09-15 10:00:00',
        'venue' => 'SDRU Conference Room 301', 'hearing_type' => 'Face-to-face',
        'remarks' => 'Initial hearing; both parties present.', 'status' => 'Completed',
        'created_at' => '2025-09-05 09:00:00', 'updated_at' => '2025-09-15 12:00:00',
    ]);
    demoUpdate($db, $c1, [
        'author_account_id' => $discCoordA, 'update_type' => 'investigation_update',
        'details' => 'Both parties attended the initial hearing. Mediation ongoing; respondent agreed to delete offending posts.',
        'case_status_snapshot' => 'Under Investigation', 'created_at' => '2025-09-15 12:10:00',
    ]);
    demoHistory($db, $c1, ['action' => 'Case Resolution', 'previous_status' => 'Under Investigation', 'new_status' => 'Resolved', 'remarks' => 'Outcome: mediation successful.', 'created_by_account_id' => $head, 'created_at' => '2025-12-18 11:20:00']);
    demoNotification($db, $stu1, ['type' => 'case_status_updated', 'title' => 'Case Resolved', 'message' => 'Your case SDRU-20250721-0101 has been resolved.', 'link' => 'web/views/cases/show.php?id=' . $c1, 'created_at' => '2025-12-18 11:20:00']);
    demoNotification($db, $resp1, ['type' => 'case_status_updated', 'title' => 'Case Resolved', 'message' => 'Your case SDRU-20250721-0101 has been resolved.', 'link' => 'web/views/cases/show.php?id=' . $c1, 'created_at' => '2025-12-18 11:20:00']);
    demoAudit($db, ['account_id' => $discCoordA, 'user_name' => 'Carlito Villanueva', 'user_role' => 'coordinator', 'action' => 'Case Resolution', 'description' => 'Marked case SDRU-20250721-0101 as resolved.', 'created_at' => '2025-12-18 11:20:00']);
}

/* ============ CASE 2 — Resolved: Physical altercation (2 respondents) ============ */
$c2 = demoCase($db, [
    'case_number' => 'SDRU-20250815-0102',
    'complaint_title' => 'Physical Altercation at Activity Center',
    'submitted_by_account_id' => $stu2,
    'complainant_name' => 'Rainier Castro',
    'complainant_gender' => 'Male', 'complainant_age' => 20,
    'complainant_student_no' => '24-1002', 'complainant_email' => 'rainier.castro@darisdemo.edu.ph',
    'complainant_contact' => '09171234512', 'complainant_college' => 'College of Business and Accountancy',
    'complainant_course' => 'Bachelor of Science in Accountancy (BSAc)',
    'complainant_year_level' => 'Third Year', 'complainant_section' => '3-1',
    'complainant_course_year' => 'Bachelor of Science in Accountancy (BSAc) | 3-1',
    'case_classification' => 'Physical Assault',
    'incident_datetime' => '2025-08-10 17:45:00',
    'incident_location' => 'Student Activity Center, second floor lobby',
    'complaint_details' => 'Altercation broke out between complainant and two respondents during an organization event after a heated argument. Complainant sustained minor injuries.',
    'status' => 'Resolved', 'submitted_at' => '2025-08-15 10:00:00',
    'created_at' => '2025-08-15 10:00:00', 'updated_at' => '2026-01-22 15:40:00',
    'assigned_coordinator_account_id' => $discCoordB,
    'resolution_date' => '2026-01-22',
    'action_taken' => 'Both respondents sanctioned with community service and conflict-resolution seminar.',
    'outcome' => 'Resolved; parties signed agreement of no further incident.',
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c2) {
    $casesCreated++;
    $r2a = demoRespondent($db, $c2, [
        'account_id' => $resp2, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Charmaine Ortiz', 'gender' => 'Female', 'age' => 20, 'birthday' => '2005-08-09',
        'student_no' => '24-2002', 'college' => 'College of Science',
        'course_year' => 'Bachelor of Science in Chemistry (BSChem) | 3-2',
        'contact_info' => '09171234522', 'invited_at' => '2025-08-25 09:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'charmaine.ortiz@darisdemo.edu.ph',
        'year_level' => 'Third Year',
    ]);
    $r2b = demoRespondent($db, $c2, [
        'account_id' => $resp3, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Jomari Tongol', 'gender' => 'Male', 'age' => 21, 'birthday' => '2004-05-16',
        'student_no' => '23-2003', 'college' => 'College of Business and Accountancy',
        'course_year' => 'Bachelor of Science in Business Administration (BSBA) | 4-1',
        'contact_info' => '09171234523', 'invited_at' => '2025-08-25 09:05:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'jomari.tongol@darisdemo.edu.ph',
        'year_level' => 'Fourth Year',
    ]);
    runDemoQuery($db, "UPDATE complaints SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ? WHERE complaint_id = ?",
        ['2025-08-25 09:10:00', $discCoordB, '{"complaint_details":true,"incident":true,"hearings":false,"final_information":false}', $c2], 'sisi');
    demoHistory($db, $c2, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu2, 'created_at' => '2025-08-15 10:00:00']);
    demoHistory($db, $c2, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffB, 'created_at' => '2025-08-20 13:00:00']);
    demoHistory($db, $c2, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordB, 'created_by_account_id' => $head, 'created_at' => '2025-08-22 09:00:00']);
    demoHistory($db, $c2, ['action' => 'Forwarded to Respondent', 'new_status' => 'Under Investigation', 'remarks' => 'Complaint details and incident released only.', 'assigned_coordinator_account_id' => $discCoordB, 'created_by_account_id' => $discCoordB, 'created_at' => '2025-08-25 09:10:00']);
    demoCounterStatement($db, $c2, $r2a, $resp2, [
        'content' => 'I was defending myself. The other respondent escalated the argument.', 'status' => 'Submitted',
        'submitted_at' => '2025-09-05 11:00:00', 'coordinator_action' => 'proceed_to_investigation',
        'coordinator_action_by_account_id' => $discCoordB, 'coordinator_action_at' => '2025-09-10 09:00:00',
        'created_at' => '2025-09-03 20:00:00', 'updated_at' => '2025-09-10 09:00:00',
    ]);
    demoCounterStatement($db, $c2, $r2b, $resp3, [
        'content' => 'We were merely caught in the middle of the argument. Willing to settle through mediation.',
        'status' => 'Submitted', 'submitted_at' => '2025-09-06 15:00:00',
        'coordinator_action' => 'proceed_to_investigation', 'coordinator_action_by_account_id' => $discCoordB,
        'coordinator_action_at' => '2025-09-10 09:00:00',
        'created_at' => '2025-09-04 19:00:00', 'updated_at' => '2025-09-10 09:00:00',
    ]);
    demoHearing($db, $c2, [
        'scheduled_by_account_id' => $discCoordB, 'hearing_datetime' => '2025-10-06 10:30:00',
        'venue' => 'SDRU Mediation Room', 'hearing_type' => 'Face-to-face',
        'remarks' => 'Both respondents attended.', 'status' => 'Completed',
        'created_at' => '2025-09-28 09:30:00', 'updated_at' => '2025-10-06 13:00:00',
    ]);
    demoHistory($db, $c2, ['action' => 'Case Resolution', 'previous_status' => 'Under Investigation', 'new_status' => 'Resolved', 'created_by_account_id' => $head, 'created_at' => '2026-01-22 15:40:00']);
    demoUpdate($db, $c2, [
        'author_account_id' => $discCoordB, 'update_type' => 'investigation_update',
        'details' => 'Hearing completed; mediation agreement signed by all parties.',
        'case_status_snapshot' => 'Under Investigation', 'created_at' => '2025-10-06 13:10:00',
    ]);
}

/* ============ CASE 3 — Under Investigation: unauthorized access (CS draft) ============ */
$c3 = demoCase($db, [
    'case_number' => 'SDRU-20250903-0103',
    'complaint_title' => 'Unauthorized Access to Student Portal',
    'submitted_by_account_id' => $stu3,
    'complainant_name' => 'Andrea Mercado',
    'complainant_gender' => 'Female', 'complainant_age' => 22,
    'complainant_student_no' => '22-1003', 'complainant_email' => 'andrea.mercado@darisdemo.edu.ph',
    'complainant_contact' => '09171234513', 'complainant_college' => 'College of Science',
    'complainant_course' => 'Bachelor of Science in Biology (BSBio)',
    'complainant_year_level' => 'Fourth Year', 'complainant_section' => '4-1',
    'complainant_course_year' => 'Bachelor of Science in Biology (BSBio) | 4-1',
    'case_classification' => 'Forging, falsifying public documents, and misinterpretation of fact',
    'incident_datetime' => '2025-09-01 22:05:00',
    'incident_location' => 'University student portal (online) and Library Terminal 4',
    'complaint_details' => 'Complainant reported that someone accessed her portal account and altered her class schedule for the second semester.',
    'status' => 'Under Investigation', 'submitted_at' => '2025-09-03 09:45:00',
    'created_at' => '2025-09-03 09:45:00', 'updated_at' => '2026-01-08 10:00:00',
    'assigned_coordinator_account_id' => $discCoordA,
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c3) {
    $casesCreated++;
    $r3 = demoRespondent($db, $c3, [
        'account_id' => $resp4, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Patricia Navarro', 'gender' => 'Female', 'age' => 20, 'birthday' => '2005-03-27',
        'student_no' => '24-2004', 'college' => 'College of Arts and Social Sciences',
        'course_year' => 'Bachelor of Arts in Social Sciences (BASS) | 3-3',
        'contact_info' => '09171234524', 'invited_at' => '2025-09-10 10:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'patricia.navarro@darisdemo.edu.ph',
        'year_level' => 'Third Year',
    ]);
    runDemoQuery($db, "UPDATE complaints SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ? WHERE complaint_id = ?",
        ['2025-09-10 10:05:00', $discCoordA, '{"complaint_details":true,"incident":true,"hearings":true,"final_information":true}', $c3], 'sisi');
    demoHistory($db, $c3, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu3, 'created_at' => '2025-09-03 09:45:00']);
    demoHistory($db, $c3, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffA, 'created_at' => '2025-09-06 10:00:00']);
    demoHistory($db, $c3, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $head, 'created_at' => '2025-09-08 09:00:00']);
    demoHistory($db, $c3, ['action' => 'Forwarded to Respondent', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $discCoordA, 'created_at' => '2025-09-10 10:05:00']);
    demoCounterStatement($db, $c3, $r3, $resp4, [
        'content' => 'I borrowed the laptop but I did not alter anything. I only checked my own schedule.',
        'status' => 'Draft', 'created_at' => '2026-01-06 20:00:00', 'updated_at' => '2026-01-08 10:00:00',
    ]);
    demoHearing($db, $c3, [
        'scheduled_by_account_id' => $discCoordA, 'hearing_datetime' => '2026-02-12 09:30:00',
        'venue' => 'SDRU Conference Room 301', 'hearing_type' => 'Face-to-face',
        'remarks' => 'Pending scheduling confirmation.', 'status' => 'Scheduled',
        'created_at' => '2026-01-30 09:00:00', 'updated_at' => '2026-01-30 09:00:00',
    ]);
    demoUpdate($db, $c3, [
        'author_account_id' => $discCoordA, 'update_type' => 'administrative',
        'details' => 'Library has confirmed terminal logs for the reported date. Awaiting respondent counter-statement.',
        'case_status_snapshot' => 'Under Investigation', 'created_at' => '2025-11-20 14:00:00',
    ]);
}

/* ============ CASE 4 — Under Investigation: plagiarism (linked, NOT forwarded) ============ */
$c4 = demoCase($db, [
    'case_number' => 'SDRU-20251010-0104',
    'complaint_title' => 'Plagiarism in Research Proposal Draft',
    'submitted_by_account_id' => $stu4,
    'complainant_name' => 'Kenneth Lumibao',
    'complainant_gender' => 'Male', 'complainant_age' => 20,
    'complainant_student_no' => '24-1004', 'complainant_email' => 'kenneth.lumibao@darisdemo.edu.ph',
    'complainant_contact' => '09171234514', 'complainant_college' => 'College of Arts and Social Sciences',
    'complainant_course' => 'Bachelor of Science in Psychology (BSPsych)',
    'complainant_year_level' => 'Third Year', 'complainant_section' => '3-2',
    'complainant_course_year' => 'Bachelor of Science in Psychology (BSPsych) | 3-2',
    'case_classification' => 'Plagiarism',
    'incident_datetime' => '2025-10-06 15:30:00',
    'incident_location' => 'College of Arts and Social Sciences, Room 204',
    'complaint_details' => 'The respondent allegedly copied a significant portion of the complainant\'s research proposal draft and submitted it under their own name to a shared journal group.',
    'status' => 'Under Investigation', 'submitted_at' => '2025-10-10 09:00:00',
    'created_at' => '2025-10-10 09:00:00', 'updated_at' => '2026-02-03 13:30:00',
    'assigned_coordinator_account_id' => $discCoordB,
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c4) {
    $casesCreated++;
    $r4 = demoRespondent($db, $c4, [
        'account_id' => $resp5, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Renan Corpuz', 'gender' => 'Male', 'age' => 19, 'birthday' => '2006-06-04',
        'student_no' => '25-2005', 'college' => 'College of Education',
        'course_year' => 'Bachelor of Physical Education (BPEd) | 2-1',
        'contact_info' => '09171234525', 'invited_at' => '2025-10-18 09:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'renan.corpuz@darisdemo.edu.ph',
        'year_level' => 'Second Year',
    ]);
    // Linked but NOT yet released/forwarded -> forward panel available
    demoHistory($db, $c4, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu4, 'created_at' => '2025-10-10 09:00:00']);
    demoHistory($db, $c4, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffB, 'created_at' => '2025-10-14 11:00:00']);
    demoHistory($db, $c4, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordB, 'created_by_account_id' => $head, 'created_at' => '2025-10-16 09:00:00']);
    demoHistory($db, $c4, ['action' => 'Case Classification', 'new_status' => 'Under Investigation', 'remarks' => 'Classified as Plagiarism.', 'created_by_account_id' => $discCoordB, 'created_at' => '2025-10-18 09:00:00']);
    demoUpdate($db, $c4, [
        'author_account_id' => $discCoordB, 'update_type' => 'additional_evidence',
        'details' => 'Plagiarism report from the department journal editor attached.',
        'case_status_snapshot' => 'Under Investigation', 'created_at' => '2025-11-05 10:00:00',
    ]);
    demoEvidence($db, $c4, [
        'original_filename' => 'similarity_report.pdf', 'stored_filename' => '20251105_0104_similarity.pdf',
        'file_path' => demoEvidenceFile('20251105_0104_similarity.pdf', 'pdf'),
        'mime_type' => 'application/pdf', 'file_size' => 52, 'uploaded_at' => '2025-11-05 10:05:00',
        'doc_type' => 'Report',
    ]);
    demoNotification($db, $resp5, ['type' => 'respondent_case_released', 'title' => 'Case Forwarded to You', 'message' => 'A case has been forwarded to you. Please prepare your counter-statement.', 'link' => 'web/views/respondent/case_show.php?id=' . $c4, 'created_at' => '2025-10-18 09:05:00']);
}

/* ============ CASE 5 — Returned for Revision ============ */
$c5 = demoCase($db, [
    'case_number' => 'SDRU-20251117-0105',
    'complaint_title' => 'Harassment by Groupmate on Research',
    'submitted_by_account_id' => $stu5,
    'complainant_name' => 'Janica Del Rosario',
    'complainant_gender' => 'Female', 'complainant_age' => 20,
    'complainant_student_no' => '23-1005', 'complainant_email' => 'janica.delrosario@darisdemo.edu.ph',
    'complainant_contact' => '09171234515', 'complainant_college' => 'College of Education',
    'complainant_course' => 'Bachelor of Elementary Education (BEEd)',
    'complainant_year_level' => 'Fourth Year', 'complainant_section' => '4-3',
    'complainant_course_year' => 'Bachelor of Elementary Education (BEEd) | 4-3',
    'case_classification' => 'Intimidation, Threat and Harassment',
    'incident_datetime' => '2025-11-12 18:45:00',
    'incident_location' => 'College of Education, peer tutoring room',
    'complaint_details' => 'Complainant alleges repeated verbal harassment from a groupmate during group work sessions.',
    'status' => 'Returned for Revision', 'submitted_at' => '2025-11-17 14:00:00',
    'created_at' => '2025-11-17 14:00:00', 'updated_at' => '2025-12-05 10:00:00',
    'assigned_coordinator_account_id' => $discCoordA,
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c5) {
    $casesCreated++;
    demoRespondent($db, $c5, [
        'account_id' => $resp6, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Angelica Domingo', 'gender' => 'Female', 'age' => 20, 'birthday' => '2005-10-11',
        'student_no' => '24-2006', 'college' => 'College of Fisheries',
        'course_year' => 'Bachelor of Science in Fisheries (BSF) | 3-1',
        'contact_info' => '09171234526', 'email' => 'angelica.domingo@darisdemo.edu.ph',
        'year_level' => 'Third Year',
    ]);
    demoWitness($db, $c5, [
        'person_type' => 'Student', 'full_name' => 'Carlos Bumatay', 'gender' => 'Male', 'age' => 20,
        'statement' => 'I was present during the group session. The respondent raised her voice at the complainant.',
        'college' => 'College of Education', 'course_year' => 'BEEd | 4-3', 'year_level' => 'Fourth Year',
    ]);
    demoHistory($db, $c5, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu5, 'created_at' => '2025-11-17 14:00:00']);
    demoHistory($db, $c5, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffA, 'created_at' => '2025-11-20 09:00:00']);
    demoHistory($db, $c5, ['action' => 'Returned for Revision', 'previous_status' => 'Verified', 'new_status' => 'Returned for Revision', 'remarks' => 'Incident date is unclear. Please clarify the timeline and add full details.', 'revision_fields' => json_encode(['incident_date', 'complaint_details']), 'created_by_account_id' => $discCoordA, 'created_at' => '2025-12-05 10:00:00']);
    demoNotification($db, $stu5, ['type' => 'complaint_revision', 'title' => 'Complaint Returned for Revision', 'message' => 'Please revise your complaint for case SDRU-20251117-0105.', 'link' => 'web/views/complaints/create.php', 'created_at' => '2025-12-05 10:00:00']);
}

/* ============ CASE 6 — Rejected ============ */
$c6 = demoCase($db, [
    'case_number' => 'SDRU-20251205-0106',
    'complaint_title' => 'Social Media Intimidation Complaint',
    'submitted_by_account_id' => $stu6,
    'complainant_name' => 'Nathaniel Buenavides',
    'complainant_gender' => 'Male', 'complainant_age' => 19,
    'complainant_student_no' => '25-1006', 'complainant_email' => 'nathaniel.buenavides@darisdemo.edu.ph',
    'complainant_contact' => '09171234516', 'complainant_college' => 'College of Agriculture',
    'complainant_course' => 'Bachelor of Science in Agriculture (BSA)',
    'complainant_year_level' => 'Second Year', 'complainant_section' => '2-1',
    'complainant_course_year' => 'Bachelor of Science in Agriculture (BSA) | 2-1',
    'case_classification' => 'Intimidation, Threat and Harassment',
    'incident_datetime' => '2025-12-01 20:10:00',
    'incident_location' => 'Online — direct messages',
    'complaint_details' => 'Complainant submitted screenshots of alleged threats. After review, no credible threat was established; messages were deemed a misunderstanding.',
    'status' => 'Rejected', 'submitted_at' => '2025-12-05 08:30:00',
    'created_at' => '2025-12-05 08:30:00', 'updated_at' => '2026-01-15 09:00:00',
    'assigned_coordinator_account_id' => $discCoordA,
    'remarks_notes' => $demoTag . ' — dismissed after initial review.',
], $demoTag);
if ($c6) {
    $casesCreated++;
    demoRespondent($db, $c6, [
        'account_id' => $resp7, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Luis Hermoso', 'gender' => 'Male', 'age' => 21, 'birthday' => '2004-07-20',
        'student_no' => '23-2007', 'college' => 'College of Engineering',
        'course_year' => 'Bachelor of Science in Agricultural and Biosystems Engineering (BSABE) | 4-2',
        'contact_info' => '09171234527', 'email' => 'luis.hermoso@darisdemo.edu.ph',
        'year_level' => 'Fourth Year',
    ]);
    demoHistory($db, $c6, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu6, 'created_at' => '2025-12-05 08:30:00']);
    demoHistory($db, $c6, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffB, 'created_at' => '2025-12-10 09:00:00']);
    demoHistory($db, $c6, ['action' => 'Case Classification', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $head, 'created_at' => '2025-12-12 09:00:00']);
    demoHistory($db, $c6, ['action' => 'Case Resolution', 'previous_status' => 'Verified', 'new_status' => 'Rejected', 'remarks' => 'Complaint dismissed for lack of substantial evidence.', 'created_by_account_id' => $head, 'created_at' => '2026-01-15 09:00:00']);
    demoNotification($db, $stu6, ['type' => 'case_status_updated', 'title' => 'Case Rejected', 'message' => 'Your complaint SDRU-20251205-0106 was not able to proceed.', 'link' => 'web/views/cases/show.php?id=' . $c6, 'created_at' => '2026-01-15 09:00:00']);
}

/* ============ CASE 7 — Resolved: intoxicating beverages ============ */
$c7 = demoCase($db, [
    'case_number' => 'SDRU-20260308-0107',
    'complaint_title' => 'Bringing Intoxicating Beverages Within University Premises',
    'submitted_by_account_id' => $stu7,
    'complainant_name' => 'Pearl Santiago',
    'complainant_gender' => 'Female', 'complainant_age' => 20,
    'complainant_student_no' => '24-1007', 'complainant_email' => 'pearl.santiago@darisdemo.edu.ph',
    'complainant_contact' => '09171234517', 'complainant_college' => 'College of Hospitality Management',
    'complainant_course' => 'Bachelor of Science in Hospitality Management (BSHM)',
    'complainant_year_level' => 'Third Year', 'complainant_section' => '3-3',
    'complainant_course_year' => 'Bachelor of Science in Hospitality Management (BSHM) | 3-3',
    'case_classification' => 'Bringing Intoxicating Beverages/Drinks within the University Premises',
    'incident_datetime' => '2026-03-06 23:20:00',
    'incident_location' => 'Male Dormitory, ground floor common area',
    'complaint_details' => 'Respondent was reported for bringing and consuming alcoholic beverages inside the dormitory common area, in violation of the university code.',
    'status' => 'Resolved', 'submitted_at' => '2026-03-08 09:15:00',
    'created_at' => '2026-03-08 09:15:00', 'updated_at' => '2026-05-20 15:00:00',
    'assigned_coordinator_account_id' => $discCoordA,
    'resolution_date' => '2026-05-20',
    'action_taken' => 'Formal warning issued; respondent required to attend alcohol-awareness seminar.',
    'outcome' => 'Resolved after admission and commitment not to repeat.',
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c7) {
    $casesCreated++;
    $r7 = demoRespondent($db, $c7, [
        'account_id' => $resp8, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Kathrina Suarez', 'gender' => 'Female', 'age' => 21, 'birthday' => '2004-12-13',
        'student_no' => '23-2008', 'college' => 'College of Science',
        'course_year' => 'Bachelor of Science in Environmental Science (BSES) | 4-1',
        'contact_info' => '09171234528', 'invited_at' => '2026-03-16 09:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'kathrina.suarez@darisdemo.edu.ph',
        'year_level' => 'Fourth Year',
    ]);
    runDemoQuery($db, "UPDATE complaints SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ? WHERE complaint_id = ?",
        ['2026-03-16 09:05:00', $discCoordA, '{"complaint_details":true,"incident":true,"hearings":false,"final_information":false}', $c7], 'sisi');
    demoHistory($db, $c7, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu7, 'created_at' => '2026-03-08 09:15:00']);
    demoHistory($db, $c7, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffA, 'created_at' => '2026-03-12 10:00:00']);
    demoHistory($db, $c7, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $head, 'created_at' => '2026-03-14 09:00:00']);
    demoHistory($db, $c7, ['action' => 'Forwarded to Respondent', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $discCoordA, 'created_at' => '2026-03-16 09:05:00']);
    demoCounterStatement($db, $c7, $r7, $resp8, [
        'content' => 'I admit to bringing the beverages. It will not happen again.',
        'status' => 'Submitted', 'submitted_at' => '2026-03-20 18:00:00',
        'coordinator_action' => 'proceed_to_investigation', 'coordinator_action_by_account_id' => $discCoordA,
        'coordinator_action_at' => '2026-03-25 09:00:00',
        'created_at' => '2026-03-18 20:00:00', 'updated_at' => '2026-03-25 09:00:00',
    ]);
    demoHistory($db, $c7, ['action' => 'Counter-Statement Submitted', 'created_by_account_id' => $resp8, 'created_at' => '2026-03-20 18:00:00']);
    demoHearing($db, $c7, [
        'scheduled_by_account_id' => $discCoordA, 'hearing_datetime' => '2026-04-08 09:00:00',
        'venue' => 'SDRU Conference Room 301', 'status' => 'Completed',
        'created_at' => '2026-04-01 09:00:00', 'updated_at' => '2026-04-08 11:30:00',
    ]);
    demoHistory($db, $c7, ['action' => 'Case Resolution', 'previous_status' => 'Under Investigation', 'new_status' => 'Resolved', 'created_by_account_id' => $head, 'created_at' => '2026-05-20 15:00:00']);
}

/* ============ CASE 8 — Reformation in Progress ============ */
$c8 = demoCase($db, [
    'case_number' => 'SDRU-20260112-0108',
    'complaint_title' => 'Physical Assault During Intramurals',
    'submitted_by_account_id' => $stu8,
    'complainant_name' => 'Alvin Ramos',
    'complainant_gender' => 'Male', 'complainant_age' => 21,
    'complainant_student_no' => '23-1008', 'complainant_email' => 'alvin.ramos@darisdemo.edu.ph',
    'complainant_contact' => '09171234518', 'complainant_college' => 'College of Veterinary Science and Medicine',
    'complainant_course' => 'Doctor of Veterinary Medicine (DVM)',
    'complainant_year_level' => 'Fifth Year', 'complainant_section' => '5-1',
    'complainant_course_year' => 'Doctor of Veterinary Medicine (DVM) | 5-1',
    'case_classification' => 'Physical Assault',
    'incident_datetime' => '2026-01-10 16:00:00',
    'incident_location' => 'University Gymnasium during baseline basketball game',
    'complaint_details' => 'Altercation during intramurals basketball. Complainant reported physical contact after the game.',
    'status' => 'Reformation in Progress', 'submitted_at' => '2026-01-12 09:00:00',
    'created_at' => '2026-01-12 09:00:00', 'updated_at' => '2026-09-01 10:00:00',
    'assigned_coordinator_account_id' => $discCoordB,
    'assigned_reformation_coordinator_account_id' => $reformCoordA,
    'resolution_date' => '2026-06-12',
    'action_taken' => 'Respondent sanctioned to complete 40 hours of community service and undergo conflict-resolution training.',
    'outcome' => 'Case resolved in discipline; reformation program still in progress.',
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c8) {
    $casesCreated++;
    $r8 = demoRespondent($db, $c8, [
        'account_id' => $resp9, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Marco Ilagan', 'gender' => 'Male', 'age' => 20, 'birthday' => '2005-04-02',
        'student_no' => '24-2009', 'college' => 'College of Business and Accountancy',
        'course_year' => 'Bachelor of Science in Entrepreneurship (BSEntrep) | 3-2',
        'contact_info' => '09171234529', 'invited_at' => '2026-01-20 09:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'marco.ilagan@darisdemo.edu.ph',
        'year_level' => 'Third Year',
    ]);
    runDemoQuery($db, "UPDATE complaints SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ? WHERE complaint_id = ?",
        ['2026-01-20 09:05:00', $discCoordB, '{"complaint_details":true,"incident":true,"hearings":true,"final_information":false}', $c8], 'sisi');
    demoHistory($db, $c8, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu8, 'created_at' => '2026-01-12 09:00:00']);
    demoHistory($db, $c8, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffB, 'created_at' => '2026-01-15 10:00:00']);
    demoHistory($db, $c8, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordB, 'created_by_account_id' => $head, 'created_at' => '2026-01-17 09:00:00']);
    demoHistory($db, $c8, ['action' => 'Forwarded to Respondent', 'assigned_coordinator_account_id' => $discCoordB, 'created_by_account_id' => $discCoordB, 'created_at' => '2026-01-20 09:05:00']);
    demoCounterStatement($db, $c8, $r8, $resp9, [
        'content' => 'It was an accident during the game. I apologize for what happened.',
        'status' => 'Submitted', 'submitted_at' => '2026-02-01 12:00:00',
        'coordinator_action' => 'proceed_to_investigation', 'coordinator_action_by_account_id' => $discCoordB,
        'coordinator_action_at' => '2026-02-05 09:00:00',
        'created_at' => '2026-01-28 19:00:00', 'updated_at' => '2026-02-05 09:00:00',
    ]);
    demoHearing($db, $c8, [
        'scheduled_by_account_id' => $discCoordB, 'hearing_datetime' => '2026-03-03 10:00:00',
        'venue' => 'SDRU Mediation Room', 'status' => 'Completed',
        'created_at' => '2026-02-25 09:00:00', 'updated_at' => '2026-03-03 12:30:00',
    ]);
    demoHistory($db, $c8, ['action' => 'Case Resolution', 'previous_status' => 'Under Investigation', 'new_status' => 'Resolved', 'created_by_account_id' => $head, 'created_at' => '2026-06-12 15:00:00']);
    demoHistory($db, $c8, ['action' => 'Reformation Assignment', 'previous_status' => 'Resolved', 'new_status' => 'Reformation in Progress', 'remarks' => 'Assigned to reformation coordinator for behavioral reformation program.', 'reformation_coordinator_account_id' => $reformCoordA, 'created_by_account_id' => $head, 'created_at' => '2026-06-20 09:00:00']);
    $rec1 = demoInsert($db, 'reformation_records', [
        'complaint_id' => $c8, 'coordinator_account_id' => $reformCoordA,
        'activity' => 'Community service — 20/40 hours completed at university library.',
        'progress_date' => '2026-07-15', 'progress_status' => 'Ongoing',
        'remarks' => 'Respondent consistent attendance for two weeks.',
        'created_at' => '2026-07-15 10:00:00',
    ]);
    demoInsert($db, 'reformation_records', [
        'complaint_id' => $c8, 'coordinator_account_id' => $reformCoordA,
        'activity' => 'Conflict-resolution training session 2 of 3.',
        'progress_date' => '2026-08-05', 'progress_status' => 'For Follow-up',
        'remarks' => 'Respondent to complete final session.',
        'created_at' => '2026-08-05 10:00:00',
    ]);
    demoEvidence($db, $c8, [
        'reformation_record_id' => $rec1, 'original_filename' => 'community_service_log.jpg',
        'stored_filename' => '20260715_0108_service_log.jpg',
        'file_path' => demoEvidenceFile('20260715_0108_service_log.jpg', 'jpg'),
        'mime_type' => 'image/jpeg', 'file_size' => 66, 'uploaded_at' => '2026-07-15 10:05:00',
        'doc_type' => 'Progress Evidence',
    ]);
    demoNotification($db, $resp9, ['type' => 'case_status_updated', 'title' => 'Reformation Program Started', 'message' => 'A reformation program has been assigned to your case.', 'link' => 'web/views/cases/show.php?id=' . $c8, 'created_at' => '2026-06-20 09:00:00']);
    demoAudit($db, ['account_id' => $head, 'user_name' => 'Rosario Evasco', 'user_role' => 'head-of-sdru', 'action' => 'Reformation Assignment', 'description' => 'Assigned reformation coordinator for SDRU-20260112-0108.', 'created_at' => '2026-06-20 09:00:00']);
}

/* ============ CASE 9 — Reformation Completed ============ */
$c9 = demoCase($db, [
    'case_number' => 'SDRU-20260202-0109',
    'complaint_title' => 'Sexual Harassment at Off-Campus Event',
    'submitted_by_account_id' => $stu9,
    'complainant_name' => 'Shanaia Pineda',
    'complainant_gender' => 'Female', 'complainant_age' => 20,
    'complainant_student_no' => '24-1009', 'complainant_email' => 'shanaia.pineda@darisdemo.edu.ph',
    'complainant_contact' => '09171234519', 'complainant_college' => 'College of Fisheries',
    'complainant_course' => 'Bachelor of Science in Fisheries (BSF)',
    'complainant_year_level' => 'Third Year', 'complainant_section' => '3-1',
    'complainant_course_year' => 'Bachelor of Science in Fisheries (BSF) | 3-1',
    'case_classification' => 'Sexual Harassment',
    'incident_datetime' => '2026-01-25 22:00:00',
    'incident_location' => 'Off-campus study tour, lodging area',
    'complaint_details' => 'Complainant reported inappropriate advances during a department study tour.',
    'status' => 'Reformation Completed', 'submitted_at' => '2026-02-02 10:00:00',
    'created_at' => '2026-02-02 10:00:00', 'updated_at' => '2026-09-10 15:00:00',
    'assigned_coordinator_account_id' => $discCoordA,
    'assigned_reformation_coordinator_account_id' => $reformCoordB,
    'reformation_completed_at' => '2026-09-10 15:00:00',
    'resolution_date' => '2026-05-30',
    'action_taken' => 'Respondent required to undergo gender-sensitivity and reformation program; completed all sessions and community service.',
    'outcome' => 'Reformation program completed successfully; case closed.',
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c9) {
    $casesCreated++;
    $r9 = demoRespondent($db, $c9, [
        'account_id' => $resp10, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Jennica Ocampo', 'gender' => 'Female', 'age' => 20, 'birthday' => '2005-09-28',
        'student_no' => '24-2010', 'college' => 'College of Veterinary Science and Medicine',
        'course_year' => 'Doctor of Veterinary Medicine (DVM) | 3-1',
        'contact_info' => '09171234530', 'invited_at' => '2026-02-12 09:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'jennica.ocampo@darisdemo.edu.ph',
        'year_level' => 'Third Year',
    ]);
    runDemoQuery($db, "UPDATE complaints SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ? WHERE complaint_id = ?",
        ['2026-02-12 09:05:00', $discCoordA, '{"complaint_details":true,"incident":true,"hearings":true,"final_information":true}', $c9], 'sisi');
    demoHistory($db, $c9, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu9, 'created_at' => '2026-02-02 10:00:00']);
    demoHistory($db, $c9, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffA, 'created_at' => '2026-02-06 09:00:00']);
    demoHistory($db, $c9, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $head, 'created_at' => '2026-02-09 09:00:00']);
    demoHistory($db, $c9, ['action' => 'Forwarded to Respondent', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $discCoordA, 'created_at' => '2026-02-12 09:05:00']);
    demoCounterStatement($db, $c9, $r9, $resp10, [
        'content' => 'I deny the allegations. The conversation was consensual and taken out of context.',
        'status' => 'Submitted', 'submitted_at' => '2026-02-25 17:00:00',
        'coordinator_action' => 'forwarded_to_complainant', 'coordinator_action_by_account_id' => $discCoordA,
        'coordinator_action_at' => '2026-03-02 09:00:00', 'forwarded_at' => '2026-03-02 09:00:00',
        'complaint_response_content' => 'I maintain my complaint. I lost access to the group during the tour due to the incident.',
        'complaint_response_updated_at' => '2026-03-10 12:00:00', 'complaint_response_submitted_at' => '2026-03-10 12:05:00',
        'created_at' => '2026-02-20 19:00:00', 'updated_at' => '2026-03-10 12:05:00',
    ]);
    demoHearing($db, $c9, [
        'scheduled_by_account_id' => $discCoordA, 'hearing_datetime' => '2026-04-20 09:30:00',
        'venue' => 'SDRU Conference Room 301', 'status' => 'Completed',
        'created_at' => '2026-04-13 09:00:00', 'updated_at' => '2026-04-20 12:00:00',
    ]);
    demoHearing($db, $c9, [
        'scheduled_by_account_id' => $discCoordA, 'hearing_datetime' => '2026-05-05 09:30:00',
        'venue' => 'SDRU Mediation Room', 'status' => 'Completed',
        'created_at' => '2026-04-27 09:00:00', 'updated_at' => '2026-05-05 12:00:00',
    ]);
    demoHistory($db, $c9, ['action' => 'Case Resolution', 'previous_status' => 'Under Investigation', 'new_status' => 'Resolved', 'created_by_account_id' => $head, 'created_at' => '2026-05-30 15:00:00']);
    demoHistory($db, $c9, ['action' => 'Reformation Assignment', 'previous_status' => 'Resolved', 'new_status' => 'Reformation in Progress', 'reformation_coordinator_account_id' => $reformCoordB, 'created_by_account_id' => $head, 'created_at' => '2026-06-05 09:00:00']);
    demoInsert($db, 'reformation_records', [
        'complaint_id' => $c9, 'coordinator_account_id' => $reformCoordB,
        'activity' => 'Gender sensitivity training completed.',
        'progress_date' => '2026-07-01', 'progress_status' => 'Completed',
        'remarks' => 'Attended all four sessions.',
        'created_at' => '2026-07-01 10:00:00',
    ]);
    demoInsert($db, 'reformation_records', [
        'complaint_id' => $c9, 'coordinator_account_id' => $reformCoordB,
        'activity' => 'Community service completed at university main library.',
        'progress_date' => '2026-08-20', 'progress_status' => 'Completed',
        'remarks' => 'Completed required 30 hours.',
        'created_at' => '2026-08-20 10:00:00',
    ]);
    demoHistory($db, $c9, ['action' => 'Reformation Completed', 'previous_status' => 'Reformation in Progress', 'new_status' => 'Reformation Completed', 'remarks' => 'All reformation requirements fulfilled.', 'reformation_coordinator_account_id' => $reformCoordB, 'created_by_account_id' => $reformCoordB, 'created_at' => '2026-09-10 15:00:00']);
    demoInsert($db, 'reformation_reports', [
        'complaint_id' => $c9, 'coordinator_account_id' => $reformCoordB,
        'report_title' => 'Reformation Completion Report',
        'original_filename' => 'reformation_completion_report.pdf', 'stored_filename' => '20260910_0109_completion.pdf',
        'file_path' => demoEvidenceFile('20260910_0109_completion.pdf', 'pdf'),
        'mime_type' => 'application/pdf', 'file_size' => 72, 'created_at' => '2026-09-10 15:05:00',
    ]);
    demoNotification($db, $stu9, ['type' => 'case_status_updated', 'title' => 'Reformation Completed', 'message' => 'The reformation program for your case has been completed.', 'link' => 'web/views/cases/show.php?id=' . $c9, 'created_at' => '2026-09-10 15:00:00']);
}

/* ============ CASE 10 — Escalated ============ */
$c10 = demoCase($db, [
    'case_number' => 'SDRU-20260219-0110',
    'complaint_title' => 'Forging Academic Documents',
    'submitted_by_account_id' => $stu10,
    'complainant_name' => 'Dexter Palencia',
    'complainant_gender' => 'Male', 'complainant_age' => 21,
    'complainant_student_no' => '23-1010', 'complainant_email' => 'dexter.palencia@darisdemo.edu.ph',
    'complainant_contact' => '09171234520', 'complainant_college' => 'College of Home Science and Industry',
    'complainant_course' => 'Bachelor of Science in Textile and Fashion Technology (BSTFT)',
    'complainant_year_level' => 'Fourth Year', 'complainant_section' => '4-1',
    'complainant_course_year' => 'Bachelor of Science in Textile and Fashion Technology (BSTFT) | 4-1',
    'case_classification' => 'Forging, falsifying public documents, and misinterpretation of fact',
    'incident_datetime' => '2026-02-17 14:00:00',
    'incident_location' => 'Registrar\'s Office, Annex Building',
    'complaint_details' => 'Respondent allegedly presented an altered certificate of enrollment to claim benefits. Matter escalated to the SDRU head for review.',
    'status' => 'Escalated', 'submitted_at' => '2026-02-19 09:30:00',
    'created_at' => '2026-02-19 09:30:00', 'updated_at' => '2026-09-15 11:00:00',
    'assigned_coordinator_account_id' => $discCoordB,
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c10) {
    $casesCreated++;
    demoRespondent($db, $c10, [
        'account_id' => $resp1, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Emerson Gatdula', 'gender' => 'Male', 'age' => 21, 'birthday' => '2004-01-22',
        'student_no' => '23-2001', 'college' => 'College of Engineering',
        'course_year' => 'Bachelor of Science in Civil Engineering (BSCE) | 4-3',
        'contact_info' => '09171234521', 'invited_at' => '2026-02-25 09:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'emerson.gatdula@darisdemo.edu.ph',
        'year_level' => 'Fourth Year',
    ]);
    runDemoQuery($db, "UPDATE complaints SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ? WHERE complaint_id = ?",
        ['2026-02-25 09:05:00', $discCoordB, '{"complaint_details":true,"incident":true,"hearings":false,"final_information":false}', $c10], 'sisi');
    demoHistory($db, $c10, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu10, 'created_at' => '2026-02-19 09:30:00']);
    demoHistory($db, $c10, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffB, 'created_at' => '2026-02-23 09:00:00']);
    demoHistory($db, $c10, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordB, 'created_by_account_id' => $head, 'created_at' => '2026-02-24 09:00:00']);
    demoHistory($db, $c10, ['action' => 'Forwarded to Respondent', 'assigned_coordinator_account_id' => $discCoordB, 'created_by_account_id' => $discCoordB, 'created_at' => '2026-02-25 09:05:00']);
    demoCounterStatement($db, $c10, demoFetchOne($db, 'SELECT respondent_id FROM complaint_respondents WHERE complaint_id = ? LIMIT 1', [$c10], 'i')['respondent_id'], $resp1, [
        'content' => 'I presented the document in good faith. The issue with my certificate was a registrar\'s clerical error.',
        'status' => 'Submitted', 'submitted_at' => '2026-03-10 16:00:00',
        'coordinator_action' => 'proceed_to_investigation', 'coordinator_action_by_account_id' => $discCoordB,
        'coordinator_action_at' => '2026-03-18 09:00:00',
        'created_at' => '2026-03-05 20:00:00', 'updated_at' => '2026-03-18 09:00:00',
    ]);
    demoHistory($db, $c10, ['action' => 'Case Escalation', 'previous_status' => 'Under Investigation', 'new_status' => 'Escalated', 'remarks' => 'Escalated to the SDRU head for further review due to document authenticity concerns.', 'created_by_account_id' => $discCoordB, 'created_at' => '2026-09-15 11:00:00']);
    demoUpdate($db, $c10, [
        'author_account_id' => $discCoordB, 'update_type' => 'clarification',
        'details' => 'Received the respondent\'s copy of the certificate. Cross-check with Registrar pending.',
        'case_status_snapshot' => 'Escalated', 'created_at' => '2026-09-15 11:10:00',
    ]);
    demoNotification($db, $head, ['type' => 'case_action_approval_needed', 'title' => 'Case escalated for review', 'message' => 'Coordinator escalated SDRU-20260219-0110 for your review.', 'link' => 'web/views/cases/show.php?id=' . $c10, 'created_at' => '2026-09-15 11:00:00']);
    demoAudit($db, ['account_id' => $discCoordB, 'user_name' => 'Ivy Rosales', 'user_role' => 'coordinator', 'action' => 'Case Escalation', 'description' => 'Escalated case SDRU-20260219-0110.', 'created_at' => '2026-09-15 11:00:00']);
}

/* ============ CASE 11 — Under Investigation, partial visibility (final_information only) ============ */
$c11 = demoCase($db, [
    'case_number' => 'SDRU-20260327-0111',
    'complaint_title' => 'Public Disturbance in Dormitory',
    'submitted_by_account_id' => $stu1,
    'complainant_name' => 'Michaela Dizon',
    'complainant_gender' => 'Female', 'complainant_age' => 21,
    'complainant_student_no' => '23-1001', 'complainant_email' => 'michaela.dizon@darisdemo.edu.ph',
    'complainant_contact' => '09171234511', 'complainant_college' => 'College of Engineering',
    'complainant_course' => 'Bachelor of Science in Information Technology (BSIT)',
    'complainant_year_level' => 'Fourth Year', 'complainant_section' => '4-2',
    'complainant_course_year' => 'Bachelor of Science in Information Technology (BSIT) | 4-2',
    'case_classification' => 'Public Disturbance',
    'incident_datetime' => '2026-03-25 23:45:00',
    'incident_location' => 'Women\'s Dormitory, second floor corridor',
    'complaint_details' => 'Loud music and shouting at midnight disrupted dormitory residents.',
    'status' => 'Under Investigation', 'submitted_at' => '2026-03-27 09:20:00',
    'created_at' => '2026-03-27 09:20:00', 'updated_at' => '2026-08-05 10:00:00',
    'assigned_coordinator_account_id' => $discCoordA,
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c11) {
    $casesCreated++;
    $r11 = demoRespondent($db, $c11, [
        'account_id' => $resp2, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Charmaine Ortiz', 'gender' => 'Female', 'age' => 20, 'birthday' => '2005-08-09',
        'student_no' => '24-2002', 'college' => 'College of Science',
        'course_year' => 'Bachelor of Science in Chemistry (BSChem) | 3-2',
        'contact_info' => '09171234522', 'invited_at' => '2026-04-02 09:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'charmaine.ortiz@darisdemo.edu.ph',
        'year_level' => 'Third Year',
    ]);
    runDemoQuery($db, "UPDATE complaints SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ? WHERE complaint_id = ?",
        ['2026-04-02 09:05:00', $discCoordA, '{"complaint_details":true,"incident":false,"hearings":false,"final_information":true}', $c11], 'sisi');
    demoHistory($db, $c11, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu1, 'created_at' => '2026-03-27 09:20:00']);
    demoHistory($db, $c11, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffA, 'created_at' => '2026-03-30 09:00:00']);
    demoHistory($db, $c11, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $head, 'created_at' => '2026-04-01 09:00:00']);
    demoHistory($db, $c11, ['action' => 'Forwarded to Respondent', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $discCoordA, 'created_at' => '2026-04-02 09:05:00']);
    demoCounterStatement($db, $c11, $r11, $resp2, [
        'content' => 'It was a birthday celebration. Other residents had previously played loud music too.',
        'status' => 'Submitted', 'submitted_at' => '2026-04-15 18:30:00',
        'coordinator_action' => 'proceed_to_investigation', 'coordinator_action_by_account_id' => $discCoordA,
        'coordinator_action_at' => '2026-04-22 09:00:00',
        'created_at' => '2026-04-12 20:00:00', 'updated_at' => '2026-04-22 09:00:00',
    ]);
    demoHearing($db, $c11, [
        'scheduled_by_account_id' => $discCoordA, 'hearing_datetime' => '2026-09-30 14:00:00',
        'venue' => 'SDRU Conference Room 301', 'hearing_type' => 'Google Meet',
        'google_meet_link' => 'https://meet.google.com/daris-demo-0111',
        'remarks' => 'Upcoming hearing.', 'status' => 'Scheduled',
        'created_at' => '2026-08-05 10:00:00', 'updated_at' => '2026-08-05 10:00:00',
    ]);
    demoUpdate($db, $c11, [
        'author_account_id' => $discCoordA, 'update_type' => 'investigation_update',
        'details' => 'Dormitory head interviewed; neighbors corroborated the noise complaint.',
        'case_status_snapshot' => 'Under Investigation', 'created_at' => '2026-05-20 10:00:00',
    ]);
}

/* ============ CASE 12 — Verified, linked respondents NOT forwarded ============ */
$c12 = demoCase($db, [
    'case_number' => 'SDRU-20260414-0112',
    'complaint_title' => 'Cyberbullying via Private Messages',
    'submitted_by_account_id' => $stu2,
    'complainant_name' => 'Rainier Castro',
    'complainant_gender' => 'Male', 'complainant_age' => 20,
    'complainant_student_no' => '24-1002', 'complainant_email' => 'rainier.castro@darisdemo.edu.ph',
    'complainant_contact' => '09171234512', 'complainant_college' => 'College of Business and Accountancy',
    'complainant_course' => 'Bachelor of Science in Accountancy (BSAc)',
    'complainant_year_level' => 'Third Year', 'complainant_section' => '3-1',
    'complainant_course_year' => 'Bachelor of Science in Accountancy (BSAc) | 3-1',
    'case_classification' => 'Cyberbullying',
    'incident_datetime' => '2026-04-10 21:00:00',
    'incident_location' => 'Online — private messaging app',
    'complaint_details' => 'Repeatedly received hurtful messages from two classmates after a disagreement in class.',
    'status' => 'Verified', 'submitted_at' => '2026-04-14 09:00:00',
    'created_at' => '2026-04-14 09:00:00', 'updated_at' => '2026-04-20 10:00:00',
    'assigned_coordinator_account_id' => $discCoordB,
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c12) {
    $casesCreated++;
    demoRespondent($db, $c12, [
        'account_id' => $resp3, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Jomari Tongol', 'gender' => 'Male', 'age' => 21, 'birthday' => '2004-05-16',
        'student_no' => '23-2003', 'college' => 'College of Business and Accountancy',
        'course_year' => 'Bachelor of Science in Business Administration (BSBA) | 4-1',
        'contact_info' => '09171234523', 'invited_at' => '2026-04-18 09:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'jomari.tongol@darisdemo.edu.ph',
        'year_level' => 'Fourth Year',
    ]);
    demoRespondent($db, $c12, [
        'account_id' => $resp6, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Angelica Domingo', 'gender' => 'Female', 'age' => 20, 'birthday' => '2005-10-11',
        'student_no' => '24-2006', 'college' => 'College of Fisheries',
        'course_year' => 'Bachelor of Science in Fisheries (BSF) | 3-1',
        'contact_info' => '09171234526', 'invited_at' => '2026-04-18 09:05:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'angelica.domingo@darisdemo.edu.ph',
        'year_level' => 'Third Year',
    ]);
    demoHistory($db, $c12, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu2, 'created_at' => '2026-04-14 09:00:00']);
    demoHistory($db, $c12, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffB, 'created_at' => '2026-04-20 10:00:00']);
    // No forward yet -> management can still forward.
}

/* ============ CASE 13 — Submitted (fresh, awaiting verification) ============ */
$c13 = demoCase($db, [
    'case_number' => 'SDRU-20260429-0113',
    'complaint_title' => 'Plagiarism in Laboratory Report',
    'submitted_by_account_id' => $stu3,
    'complainant_name' => 'Andrea Mercado',
    'complainant_gender' => 'Female', 'complainant_age' => 22,
    'complainant_student_no' => '22-1003', 'complainant_email' => 'andrea.mercado@darisdemo.edu.ph',
    'complainant_contact' => '09171234513', 'complainant_college' => 'College of Science',
    'complainant_course' => 'Bachelor of Science in Biology (BSBio)',
    'complainant_year_level' => 'Fourth Year', 'complainant_section' => '4-1',
    'complainant_course_year' => 'Bachelor of Science in Biology (BSBio) | 4-1',
    'case_classification' => 'Plagiarism',
    'incident_datetime' => '2026-04-27 13:00:00',
    'incident_location' => 'BIOSCI 220 laboratory',
    'complaint_details' => 'Laboratory report sections appear copied from previous semester compilations.',
    'status' => 'Submitted', 'submitted_at' => '2026-04-29 10:00:00',
    'created_at' => '2026-04-29 10:00:00', 'updated_at' => '2026-04-29 10:00:00',
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c13) {
    $casesCreated++;
    demoHistory($db, $c13, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu3, 'created_at' => '2026-04-29 10:00:00']);
    demoNotification($db, $staffA, ['type' => 'complaint_submitted', 'title' => 'New Complaint Submitted', 'message' => 'SDRU-20260429-0113 was submitted by Andrea Mercado.', 'link' => 'web/views/cases/show.php?id=' . $c13, 'created_at' => '2026-04-29 10:00:00']);
}

/* ============ CASE 14 — Archived (post-resolution archival) ============ */
$c14 = demoCase($db, [
    'case_number' => 'SDRU-20260518-0114',
    'complaint_title' => 'Hazing Initiation Rites',
    'submitted_by_account_id' => $stu4,
    'complainant_name' => 'Kenneth Lumibao',
    'complainant_gender' => 'Male', 'complainant_age' => 20,
    'complainant_student_no' => '24-1004', 'complainant_email' => 'kenneth.lumibao@darisdemo.edu.ph',
    'complainant_contact' => '09171234514', 'complainant_college' => 'College of Arts and Social Sciences',
    'complainant_course' => 'Bachelor of Science in Psychology (BSPsych)',
    'complainant_year_level' => 'Third Year', 'complainant_section' => '3-2',
    'complainant_course_year' => 'Bachelor of Science in Psychology (BSPsych) | 3-2',
    'case_classification' => 'Hazing',
    'incident_datetime' => '2026-05-15 20:00:00',
    'incident_location' => 'Off-campus fraternity initiation activity',
    'complaint_details' => 'Complainant reported being subjected to humiliating initiation activities. Organization activities suspended during investigation.',
    'status' => 'Archived', 'submitted_at' => '2026-05-18 09:00:00',
    'created_at' => '2026-05-18 09:00:00', 'updated_at' => '2026-09-18 16:00:00',
    'assigned_coordinator_account_id' => $discCoordB,
    'resolution_date' => '2026-08-30',
    'action_taken' => 'Organization placed under disciplinary review; respondent issued stern warning.',
    'outcome' => 'Resolved; case archived after appeal period.',
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c14) {
    $casesCreated++;
    $r14 = demoRespondent($db, $c14, [
        'account_id' => $resp4, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Patricia Navarro', 'gender' => 'Female', 'age' => 20, 'birthday' => '2005-03-27',
        'student_no' => '24-2004', 'college' => 'College of Arts and Social Sciences',
        'course_year' => 'Bachelor of Arts in Social Sciences (BASS) | 3-3',
        'contact_info' => '09171234524', 'invited_at' => '2026-05-28 09:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'patricia.navarro@darisdemo.edu.ph',
        'year_level' => 'Third Year',
    ]);
    runDemoQuery($db, "UPDATE complaints SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ? WHERE complaint_id = ?",
        ['2026-05-28 09:05:00', $discCoordB, '{"complaint_details":true,"incident":true,"hearings":true,"final_information":true}', $c14], 'sisi');
    demoHistory($db, $c14, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu4, 'created_at' => '2026-05-18 09:00:00']);
    demoHistory($db, $c14, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffA, 'created_at' => '2026-05-22 09:00:00']);
    demoHistory($db, $c14, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordB, 'created_by_account_id' => $head, 'created_at' => '2026-05-25 09:00:00']);
    demoHistory($db, $c14, ['action' => 'Forwarded to Respondent', 'assigned_coordinator_account_id' => $discCoordB, 'created_by_account_id' => $discCoordB, 'created_at' => '2026-05-28 09:05:00']);
    demoCounterStatement($db, $c14, $r14, $resp4, [
        'content' => 'I participated in a group orientation but did not join any prohibited activity.',
        'status' => 'Submitted', 'submitted_at' => '2026-06-10 17:00:00',
        'coordinator_action' => 'proceed_to_investigation', 'coordinator_action_by_account_id' => $discCoordB,
        'coordinator_action_at' => '2026-06-18 09:00:00',
        'created_at' => '2026-06-05 19:00:00', 'updated_at' => '2026-06-18 09:00:00',
    ]);
    demoHearing($db, $c14, [
        'scheduled_by_account_id' => $discCoordB, 'hearing_datetime' => '2026-07-15 10:00:00',
        'venue' => 'SDRU Conference Room 301', 'status' => 'Completed',
        'created_at' => '2026-07-06 09:00:00', 'updated_at' => '2026-07-15 12:30:00',
    ]);
    demoHistory($db, $c14, ['action' => 'Case Resolution', 'previous_status' => 'Under Investigation', 'new_status' => 'Resolved', 'created_by_account_id' => $head, 'created_at' => '2026-08-30 15:00:00']);
    demoHistory($db, $c14, ['action' => 'Case Archival', 'previous_status' => 'Resolved', 'new_status' => 'Archived', 'created_by_account_id' => $head, 'created_at' => '2026-09-18 16:00:00']);
    demoUpdate($db, $c14, [
        'author_account_id' => $head, 'update_type' => 'administrative',
        'details' => 'Case archived after the lapse of the appeal period.',
        'case_status_snapshot' => 'Archived', 'created_at' => '2026-09-18 16:00:00',
    ]);
}

/* ============ CASE 15 — Under Investigation: 2 respondents, 1 CS submitted / 1 Draft ============ */
$c15 = demoCase($db, [
    'case_number' => 'SDRU-20260609-0115',
    'complaint_title' => 'Theft of Personal Property',
    'submitted_by_account_id' => $stu5,
    'complainant_name' => 'Janica Del Rosario',
    'complainant_gender' => 'Female', 'complainant_age' => 20,
    'complainant_student_no' => '23-1005', 'complainant_email' => 'janica.delrosario@darisdemo.edu.ph',
    'complainant_contact' => '09171234515', 'complainant_college' => 'College of Education',
    'complainant_course' => 'Bachelor of Elementary Education (BEEd)',
    'complainant_year_level' => 'Fourth Year', 'complainant_section' => '4-3',
    'complainant_course_year' => 'Bachelor of Elementary Education (BEEd) | 4-3',
    'case_classification' => 'Article 295, Section 2B.1 - Slight physical injuries and maltreatment',
    'incident_datetime' => '2026-06-07 12:30:00',
    'incident_location' => 'College of Education, locker area',
    'complaint_details' => 'Laptop charger and notebook missing from the complainant\'s open locker during lunch break.',
    'status' => 'Under Investigation', 'submitted_at' => '2026-06-09 09:00:00',
    'created_at' => '2026-06-09 09:00:00', 'updated_at' => '2026-09-20 09:00:00',
    'assigned_coordinator_account_id' => $discCoordA,
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c15) {
    $casesCreated++;
    $r15a = demoRespondent($db, $c15, [
        'account_id' => $resp5, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Renan Corpuz', 'gender' => 'Male', 'age' => 19, 'birthday' => '2006-06-04',
        'student_no' => '25-2005', 'college' => 'College of Education',
        'course_year' => 'Bachelor of Physical Education (BPEd) | 2-1',
        'contact_info' => '09171234525', 'invited_at' => '2026-06-15 09:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'renan.corpuz@darisdemo.edu.ph',
        'year_level' => 'Second Year',
    ]);
    $r15b = demoRespondent($db, $c15, [
        'account_id' => $resp7, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Luis Hermoso', 'gender' => 'Male', 'age' => 21, 'birthday' => '2004-07-20',
        'student_no' => '23-2007', 'college' => 'College of Engineering',
        'course_year' => 'Bachelor of Science in Agricultural and Biosystems Engineering (BSABE) | 4-2',
        'contact_info' => '09171234527', 'invited_at' => '2026-06-15 09:05:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'luis.hermoso@darisdemo.edu.ph',
        'year_level' => 'Fourth Year',
    ]);
    runDemoQuery($db, "UPDATE complaints SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ? WHERE complaint_id = ?",
        ['2026-06-15 09:10:00', $discCoordA, '{"complaint_details":true,"incident":true,"hearings":true,"final_information":true}', $c15], 'sisi');
    demoHistory($db, $c15, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu5, 'created_at' => '2026-06-09 09:00:00']);
    demoHistory($db, $c15, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffB, 'created_at' => '2026-06-12 09:00:00']);
    demoHistory($db, $c15, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $head, 'created_at' => '2026-06-13 09:00:00']);
    demoHistory($db, $c15, ['action' => 'Forwarded to Respondent', 'assigned_coordinator_account_id' => $discCoordA, 'created_by_account_id' => $discCoordA, 'created_at' => '2026-06-15 09:10:00']);
    demoCounterStatement($db, $c15, $r15a, $resp5, [
        'content' => 'I was in the library the entire lunch break and have witnesses to that.',
        'status' => 'Submitted', 'submitted_at' => '2026-06-28 16:00:00',
        'coordinator_action' => 'proceed_to_investigation', 'coordinator_action_by_account_id' => $discCoordA,
        'coordinator_action_at' => '2026-07-02 09:00:00',
        'created_at' => '2026-06-25 20:00:00', 'updated_at' => '2026-07-02 09:00:00',
    ]);
    demoCounterStatement($db, $c15, $r15b, $resp7, [
        'content' => 'Preparing my statement.', 'status' => 'Draft',
        'created_at' => '2026-09-18 20:00:00', 'updated_at' => '2026-09-20 09:00:00',
    ]);
    demoHearing($db, $c15, [
        'scheduled_by_account_id' => $discCoordA, 'hearing_datetime' => '2026-10-14 10:00:00',
        'venue' => 'SDRU Mediation Room', 'status' => 'Scheduled',
        'created_at' => '2026-09-20 09:00:00', 'updated_at' => '2026-09-20 09:00:00',
    ]);
    demoUpdate($db, $c15, [
        'author_account_id' => $discCoordA, 'update_type' => 'investigation_update',
        'details' => 'CCTV footage requested from security office; one respondent submitted a counter-statement.',
        'case_status_snapshot' => 'Under Investigation', 'created_at' => '2026-07-02 10:00:00',
    ]);
}

/* ============ CASE 16 — Discipline Resolved (intimidation) ============ */
$c16 = demoCase($db, [
    'case_number' => 'SDRU-20260812-0116',
    'complaint_title' => 'Intimidation Through Group Chat',
    'submitted_by_account_id' => $stu6,
    'complainant_name' => 'Nathaniel Buenavides',
    'complainant_gender' => 'Male', 'complainant_age' => 19,
    'complainant_student_no' => '25-1006', 'complainant_email' => 'nathaniel.buenavides@darisdemo.edu.ph',
    'complainant_contact' => '09171234516', 'complainant_college' => 'College of Agriculture',
    'complainant_course' => 'Bachelor of Science in Agriculture (BSA)',
    'complainant_year_level' => 'Second Year', 'complainant_section' => '2-1',
    'complainant_course_year' => 'Bachelor of Science in Agriculture (BSA) | 2-1',
    'case_classification' => 'Intimidation, Threat and Harassment',
    'incident_datetime' => '2026-08-08 19:40:00',
    'incident_location' => 'Online — organization group chat',
    'complaint_details' => 'Respondent sent intimidating messages toward the complainant in an org group chat after a disagreement.',
    'status' => 'Resolved', 'submitted_at' => '2026-08-12 09:00:00',
    'created_at' => '2026-08-12 09:00:00', 'updated_at' => '2026-09-12 10:00:00',
    'assigned_coordinator_account_id' => $discCoordB,
    'resolution_date' => '2026-09-12',
    'action_taken' => 'Mediation held; both parties reconciled; respondent apologized in the group chat.',
    'outcome' => 'Resolved by mutual agreement.',
    'remarks_notes' => $demoTag,
], $demoTag);
if ($c16) {
    $casesCreated++;
    $r16 = demoRespondent($db, $c16, [
        'account_id' => $resp8, 'respondent_type' => 'Student', 'person_type' => 'Student',
        'full_name' => 'Kathrina Suarez', 'gender' => 'Female', 'age' => 21, 'birthday' => '2004-12-13',
        'student_no' => '23-2008', 'college' => 'College of Science',
        'course_year' => 'Bachelor of Science in Environmental Science (BSES) | 4-1',
        'contact_info' => '09171234528', 'invited_at' => '2026-08-20 09:00:00',
        'invitation_token' => bin2hex(random_bytes(32)), 'email' => 'kathrina.suarez@darisdemo.edu.ph',
        'year_level' => 'Fourth Year',
    ]);
    runDemoQuery($db, "UPDATE complaints SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ? WHERE complaint_id = ?",
        ['2026-08-20 09:05:00', $discCoordB, '{"complaint_details":true,"incident":true,"hearings":true,"final_information":true}', $c16], 'sisi');
    demoHistory($db, $c16, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $stu6, 'created_at' => '2026-08-12 09:00:00']);
    demoHistory($db, $c16, ['action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified', 'created_by_account_id' => $staffA, 'created_at' => '2026-08-17 09:00:00']);
    demoHistory($db, $c16, ['action' => 'Assigned Coordinator', 'assigned_coordinator_account_id' => $discCoordB, 'created_by_account_id' => $head, 'created_at' => '2026-08-18 09:00:00']);
    demoHistory($db, $c16, ['action' => 'Forwarded to Respondent', 'assigned_coordinator_account_id' => $discCoordB, 'created_by_account_id' => $discCoordB, 'created_at' => '2026-08-20 09:05:00']);
    demoCounterStatement($db, $c16, $r16, $resp8, [
        'content' => 'I apologize for the misunderstanding. They were not threats.',
        'status' => 'Submitted', 'submitted_at' => '2026-08-28 18:00:00',
        'coordinator_action' => 'proceed_to_investigation', 'coordinator_action_by_account_id' => $discCoordB,
        'coordinator_action_at' => '2026-09-02 09:00:00',
        'created_at' => '2026-08-25 20:00:00', 'updated_at' => '2026-09-02 09:00:00',
    ]);
    demoHearing($db, $c16, [
        'scheduled_by_account_id' => $discCoordB, 'hearing_datetime' => '2026-09-08 10:30:00',
        'venue' => 'SDRU Mediation Room', 'status' => 'Completed',
        'created_at' => '2026-09-01 09:00:00', 'updated_at' => '2026-09-08 12:00:00',
    ]);
    demoHistory($db, $c16, ['action' => 'Case Resolution', 'previous_status' => 'Under Investigation', 'new_status' => 'Resolved', 'created_by_account_id' => $head, 'created_at' => '2026-09-12 10:00:00']);
    demoAudit($db, ['account_id' => $head, 'user_name' => 'Rosario Evasco', 'user_role' => 'head-of-sdru', 'action' => 'Case Resolution', 'description' => 'Marked case SDRU-20260812-0116 as resolved.', 'created_at' => '2026-09-12 10:00:00']);
}

/* ============ CASE 17 — Legacy case (2019) ============ */
$c17 = demoCase($db, [
    'case_number' => 'SDRU-20190924-0201',
    'complaint_title' => 'Legacy: Non-payment of Cafeteria Dues',
    'submitted_by_account_id' => $head,
    'complainant_name' => 'Gloria Manaloto',
    'complainant_gender' => 'Female',
    'complainant_type' => 'Staff',
    'complainant_department' => 'University Cafeteria',
    'complainant_position' => 'Cashier',
    'complainant_college' => 'College of Home Science and Industry',
    'case_classification' => 'Unclassified',
    'incident_datetime' => '2019-09-15 12:00:00',
    'incident_location' => 'University Cafeteria, Main Building',
    'complaint_details' => 'Legacy record: A student repeatedly failed to settle cafeteria meal dues for the second semester of SY 2018-2019.',
    'status' => 'Resolved', 'submitted_at' => '2019-09-24 09:00:00',
    'created_at' => '2019-09-24 09:00:00', 'updated_at' => '2019-11-15 15:00:00',
    'case_source' => 'Legacy', 'original_case_date' => '2019-09-24',
    'legacy_outcome' => 'Restitution arranged through parents; case closed.',
    'legacy_entry_source' => 'SDRU Registry Ledger 2019',
    'resolution_date' => '2019-11-15',
    'action_taken' => 'Payment schedule arranged with the student\'s guardian.',
    'outcome' => 'Restitution completed.',
    'remarks_notes' => $demoTag . ' — migrated legacy entry.',
], $demoTag);
if ($c17) {
    $casesCreated++;
    demoHistory($db, $c17, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $head, 'created_at' => '2019-09-24 09:00:00']);
    demoHistory($db, $c17, ['action' => 'Case Resolution', 'previous_status' => 'Under Investigation', 'new_status' => 'Resolved', 'created_by_account_id' => $head, 'created_at' => '2019-11-15 15:00:00']);
    demoAudit($db, ['account_id' => $staffB, 'user_name' => 'Felix Manansala', 'user_role' => 'sdru-staff', 'action' => 'Legacy Entry Created', 'description' => 'Imported legacy case SDRU-20190924-0201 from the 2019 registry ledger.', 'created_at' => '2026-09-20 09:00:00']);
}

/* ============ CASE 18 — Legacy case (2021) ============ */
$c18 = demoCase($db, [
    'case_number' => 'SDRU-20210912-0202',
    'complaint_title' => 'Legacy: Dress Code Violation During Events',
    'submitted_by_account_id' => $head,
    'complainant_name' => 'Benigno Ocampo',
    'complainant_gender' => 'Male',
    'complainant_type' => 'Staff',
    'complainant_department' => 'Office of Student Affairs',
    'complainant_position' => 'Event Coordinator',
    'complainant_college' => 'College of Arts and Social Sciences',
    'case_classification' => 'Unclassified',
    'incident_datetime' => '2021-09-10 14:00:00',
    'incident_location' => 'University Grandstand during foundation day',
    'complaint_details' => 'Legacy record: Respondents violated the prescribed dress code during the university foundation day parade.',
    'status' => 'Archived', 'submitted_at' => '2021-09-12 09:00:00',
    'created_at' => '2021-09-12 09:00:00', 'updated_at' => '2021-12-10 15:00:00',
    'case_source' => 'Legacy', 'original_case_date' => '2021-09-12',
    'legacy_outcome' => 'Verbal warning issued; case archived upon school year closing.',
    'legacy_entry_source' => 'SDRU Registry Ledger 2021',
    'resolution_date' => '2021-12-10',
    'action_taken' => 'Verbal warning and guidance counseling for the students involved.',
    'outcome' => 'Case archived after resolution.',
    'remarks_notes' => $demoTag . ' — migrated legacy entry.',
], $demoTag);
if ($c18) {
    $casesCreated++;
    demoHistory($db, $c18, ['action' => 'Complaint Submission', 'new_status' => 'Submitted', 'created_by_account_id' => $head, 'created_at' => '2021-09-12 09:00:00']);
    demoHistory($db, $c18, ['action' => 'Case Resolution', 'previous_status' => 'Under Investigation', 'new_status' => 'Resolved', 'created_by_account_id' => $head, 'created_at' => '2021-12-10 15:00:00']);
    demoHistory($db, $c18, ['action' => 'Case Archival', 'previous_status' => 'Resolved', 'new_status' => 'Archived', 'created_by_account_id' => $head, 'created_at' => '2021-12-10 15:05:00']);
    demoAudit($db, ['account_id' => $staffA, 'user_name' => 'Donna Aquino', 'user_role' => 'sdru-staff', 'action' => 'Legacy Entry Created', 'description' => 'Imported legacy case SDRU-20210912-0202 from the 2021 registry ledger.', 'created_at' => '2026-09-20 09:30:00']);
}

/* ---------------------------------------- messages & participants */
/* Idempotency: clear any prior demo thread rows (demo complaints only) so reruns
   do not collide on the unique participant-pair key or duplicate messages. */
demoClearDemoThreads($db);

demoParticipant($db, $c1, $head, $discCoordA);
demoParticipant($db, $c1, $discCoordA, $head);
demoMessage($db, $c1, $head, $discCoordA, 'Please update me on SDRU-20250721-0101 after the mediation.', '2025-09-05 09:00:00', 1);
demoMessage($db, $c1, $discCoordA, $head, 'Mediation was successful. I will submit the resolution notes.', '2025-09-05 11:30:00', 1);

demoParticipant($db, $c2, $discCoordB, $stu2);
demoParticipant($db, $c2, $stu2, $discCoordB);
demoMessage($db, $c2, $discCoordB, $stu2, 'Kindly confirm your availability for the hearing on October 6.', '2025-09-28 09:30:00', 1);
demoMessage($db, $c2, $stu2, $discCoordB, 'Yes, I am available. Thank you.', '2025-09-28 12:00:00', 1);

demoParticipant($db, $c8, $reformCoordA, $resp9);
demoParticipant($db, $c8, $resp9, $reformCoordA);
demoMessage($db, $c8, $reformCoordA, $resp9, 'Your community service schedule is set. Bring your logbook on Monday.', '2026-07-10 09:30:00', 0);

demoParticipant($db, $c9, $reformCoordB, $resp10);
demoParticipant($db, $c9, $resp10, $reformCoordB);
demoMessage($db, $c9, $reformCoordB, $resp10, 'Congratulations on completing all reformation sessions.', '2026-09-10 15:10:00', 0);

/* ---------------------------------------- case approval (pending) */
demoInsert($db, 'case_approvals', [
    'complaint_id' => $c12,
    'requested_by_account_id' => $discCoordB,
    'action_type' => 'resolve',
    'action_label' => 'Resolve Case: Cyberbullying via Private Messages',
    'payload' => json_encode(['case_action' => 'resolve', 'remarks' => 'Both respondents admitted; recommend mediation resolution.']),
    'status' => 'Pending',
    'created_at' => '2026-09-18 14:00:00',
]);
demoNotification($db, $head, ['type' => 'case_action_approval_needed', 'title' => 'Case Action Approval Needed', 'message' => 'A coordinator submitted "Resolve Case: Cyberbullying via Private Messages" and is waiting for your review.', 'link' => 'web/views/cases/show.php?id=' . $c12, 'created_at' => '2026-09-18 14:00:00']);
demoAudit($db, ['account_id' => $discCoordB, 'user_name' => 'Ivy Rosales', 'user_role' => 'coordinator', 'action' => 'Case Resolution', 'description' => 'Requested approval to resolve SDRU-20260414-0112.', 'created_at' => '2026-09-18 14:00:00']);

/* ---------------------------------------- done */
echo "Cases seeded: {$casesCreated} new (18 total demo targets).\n";

$connection = $db;
$database = null;
echo "DARIS demo dataset seed completed.\n";