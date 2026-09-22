<?php
// SICMS demo dataset seed: 10 online cases (Under Investigation) +
// 10 migrated legacy cases (Resolved).
//
// Data only, no accounts: everything is linked to the existing
// head-of-sdru account (Ricardo Santos, account_id 2) for FK compliance,
// while complainants/respondents carry their full information inline.
//
// Re-runnable (idempotent): skips a case_number that already exists.

declare(strict_types=1);

require_once __DIR__ . '/../web/config/Database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    fwrite(STDERR, "Cannot connect to database.\n");
    exit(1);
}

function seedQuery(mysqli $db, string $sql, array $params = [], string $types = ''): bool {
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

function seedInsert(mysqli $db, string $table, array $data): int {
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
        fwrite(STDERR, 'INSERT INTO ' . $table . ' FAILED: ' . $stmt->error . " (case might exist)\n");
        return 0;
    }
    return (int) $stmt->insert_id;
}

function seedFetchOne(mysqli $db, string $sql, array $params = [], string $types = ''): ?array {
    $stmt = $db->prepare($sql);
    if (!$stmt) return null;
    if ($types !== '') $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) return null;
    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
}

$HEAD_ID = 2;
define('SEED_TAG', 'Seed dataset 2026-09-23');

/**
 * Insert a complaint row. Returns complaint_id or 0 if case_number exists.
 */
function seedCase(mysqli $db, array $c, int $headId): int {
    $tag = SEED_TAG;
    $existing = seedFetchOne($db, "SELECT complaint_id FROM complaints WHERE case_number = ?", [$c['case_number']], 's');
    if ($existing) {
        return (int) $existing['complaint_id'];
    }
    $defaults = [
        'complaint_id' => null, 'case_number' => '', 'complaint_title' => '',
        'submitted_by_account_id' => $headId, 'complainant_name' => '',
        'complainant_gender' => null, 'complainant_age' => null,
        'complainant_address' => null, 'complainant_type' => 'Student',
        'complainant_relationship' => null, 'complainant_employee_no' => null,
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
        'remarks_notes' => $tag, 'outcome' => null, 'legacy_entry_source' => null,
    ];
    $data = array_merge($defaults, $c);
    return seedInsert($db, 'complaints', $data);
}

function seedRespondent(mysqli $db, int $complaintId, array $r): int {
    $defaults = [
        'complaint_id' => $complaintId, 'respondent_type' => 'Student',
        'full_name' => '', 'gender' => null, 'student_no' => null,
        'employee_no' => null, 'college' => null, 'office_department' => null,
        'course_year' => null, 'position' => null, 'affiliation' => null,
        'contact_info' => null, 'details' => null, 'age' => null,
        'birthday' => null, 'email' => null, 'address' => null,
        'year_level' => null, 'invited_at' => null, 'invitation_token' => null,
        'account_id' => null, 'created_at' => date('Y-m-d H:i:s'),
    ];
    return seedInsert($db, 'complaint_respondents', array_merge($defaults, $r));
}

function seedHistory(mysqli $db, int $complaintId, array $h, int $headId): int {
    $defaults = [
        'complaint_id' => $complaintId, 'action' => null, 'previous_status' => null,
        'new_status' => null, 'remarks' => null, 'revision_fields' => null,
        'assigned_coordinator_account_id' => null,
        'reformation_coordinator_account_id' => null,
        'created_by_account_id' => $headId, 'created_at' => date('Y-m-d H:i:s'),
    ];
    return seedInsert($db, 'case_history', array_merge($defaults, $h));
}

$createdOnline = 0;
$createdMigrated = 0;

/* ================================================================ ONLINE CASES ===== */

$onlineCases = [
    [
        'case_number' => 'SDRU-20260112-1001',
        'complaint_title' => 'Cyberbullying in Section Group Chat',
        'complainant_name' => 'Angelica de Guzman',
        'complainant_gender' => 'Female', 'complainant_age' => 20,
        'complainant_address' => 'Brgy. Poblacion, Science City of Muñoz, Nueva Ecija',
        'complainant_student_no' => '23-4101', 'complainant_email' => 'angelica.deguzman@clsu2.edu.ph',
        'complainant_contact' => '09171234101', 'complainant_college' => 'College of Engineering',
        'complainant_course' => 'Bachelor of Science in Information Technology (BSIT)',
        'complainant_year_level' => 'Fourth Year', 'complainant_section' => '4-2',
        'complainant_course_year' => 'Bachelor of Science in Information Technology (BSIT) | 4-2',
        'case_classification' => 'Cyberbullying',
        'incident_datetime' => '2026-01-08 21:40:00',
        'incident_location' => 'Online - Section group chat (Facebook Messenger)',
        'complaint_details' => 'Complainant reported receiving repeated derogatory and insulting messages posted about her in the official section group chat, including mocked photos and false accusations. She requested that the SDRU take action to stop the online harassment.',
        'status' => 'Under Investigation',
        'submitted_at' => '2026-01-12 09:15:00',
        'created_at' => '2026-01-12 09:15:00', 'updated_at' => '2026-02-03 14:20:00',
    ],
    [
        'case_number' => 'SDRU-20260208-1002',
        'complaint_title' => 'Physical Altercation in the Canteen',
        'complainant_name' => 'Mark Anthony Villanueva',
        'complainant_gender' => 'Male', 'complainant_age' => 21,
        'complainant_address' => 'Brgy. San Isidro, Cabanatuan City, Nueva Ecija',
        'complainant_student_no' => '22-4102', 'complainant_email' => 'markanthony.villanueva@clsu2.edu.ph',
        'complainant_contact' => '09171234102', 'complainant_college' => 'College of Science',
        'complainant_course' => 'Bachelor of Science in Computer Science (BSCS)',
        'complainant_year_level' => 'Third Year', 'complainant_section' => '3-1',
        'complainant_course_year' => 'Bachelor of Science in Computer Science (BSCS) | 3-1',
        'case_classification' => 'Physical Assault',
        'incident_datetime' => '2026-02-05 12:30:00',
        'incident_location' => 'University Canteen, main food section',
        'complaint_details' => 'A heated argument over a seat escalated into a physical altercation. The respondent allegedly shoved the complainant and threw a plate, causing minor injuries. Security personnel intervened and separated the parties.',
        'status' => 'Under Investigation',
        'submitted_at' => '2026-02-08 10:00:00',
        'created_at' => '2026-02-08 10:00:00', 'updated_at' => '2026-03-01 09:45:00',
    ],
    [
        'case_number' => 'SDRU-20260301-1003',
        'complaint_title' => 'Unauthorized Use of Student Portal Account',
        'complainant_name' => 'Justine Marie Ramos',
        'complainant_gender' => 'Female', 'complainant_age' => 19,
        'complainant_address' => 'University Dormitory, CLSU Campus, Science City of Muñoz',
        'complainant_student_no' => '25-4103', 'complainant_email' => 'justinemarie.ramos@clsu2.edu.ph',
        'complainant_contact' => '09171234103', 'complainant_college' => 'College of Business and Accountancy',
        'complainant_course' => 'Bachelor of Science in Accountancy (BSAc)',
        'complainant_year_level' => 'Second Year', 'complainant_section' => '2-3',
        'complainant_course_year' => 'Bachelor of Science in Accountancy (BSAc) | 2-3',
        'case_classification' => 'Unauthorized Account Access',
        'incident_datetime' => '2026-02-26 19:10:00',
        'incident_location' => 'Online - CLSU student portal',
        'complaint_details' => 'Complainant discovered that someone logged in to her student portal account without permission, changed her class schedule, and forwarded sensitive documents to an unknown email address. She suspects a classmate who knew her password.',
        'status' => 'Under Investigation',
        'submitted_at' => '2026-03-01 08:30:00',
        'created_at' => '2026-03-01 08:30:00', 'updated_at' => '2026-03-20 11:00:00',
    ],
    [
        'case_number' => 'SDRU-20260325-1004',
        'complaint_title' => 'Plagiarism in Research Proposal',
        'complainant_name' => 'Rafael Bautista',
        'complainant_gender' => 'Male', 'complainant_age' => 22,
        'complainant_address' => 'Brgy. Poblacion Sur, Talavera, Nueva Ecija',
        'complainant_student_no' => '22-4104', 'complainant_email' => 'rafael.bautista@clsu2.edu.ph',
        'complainant_contact' => '09171234104', 'complainant_college' => 'College of Science',
        'complainant_course' => 'Bachelor of Science in Biology (BSBio)',
        'complainant_year_level' => 'Fourth Year', 'complainant_section' => '4-1',
        'complainant_course_year' => 'Bachelor of Science in Biology (BSBio) | 4-1',
        'case_classification' => 'Plagiarism',
        'incident_datetime' => '2026-03-20 15:00:00',
        'incident_location' => 'College of Science, Research Laboratory',
        'complaint_details' => 'The complainant\'s group research proposal was copied almost verbatim by another group and submitted under their names. Similarity report confirmed more than 85% overlap. The complainant seeks proper credit restoration.',
        'status' => 'Under Investigation',
        'submitted_at' => '2026-03-25 14:00:00',
        'created_at' => '2026-03-25 14:00:00', 'updated_at' => '2026-04-05 10:30:00',
    ],
    [
        'case_number' => 'SDRU-20260410-1005',
        'complaint_title' => 'Verbal Harassment of a Classmate',
        'complainant_name' => 'Kathleen Mercado',
        'complainant_gender' => 'Female', 'complainant_age' => 20,
        'complainant_address' => 'Brgy. Balibago, San Jose City, Nueva Ecija',
        'complainant_student_no' => '24-4105', 'complainant_email' => 'kathleen.mercado@clsu2.edu.ph',
        'complainant_contact' => '09171234105', 'complainant_college' => 'College of Education',
        'complainant_course' => 'Bachelor of Secondary Education (BSEd)',
        'complainant_year_level' => 'Third Year', 'complainant_section' => '3-2',
        'complainant_course_year' => 'Bachelor of Secondary Education (BSEd) | 3-2',
        'case_classification' => 'Intimidation, Threat and Harassment',
        'incident_datetime' => '2026-04-06 16:20:00',
        'incident_location' => 'College of Education building, 2nd floor corridor',
        'complaint_details' => 'Complainant alleges repeated verbal harassment and name-calling by a classmate during and after class sessions. The behavior caused her emotional distress and fear of attending class.',
        'status' => 'Under Investigation',
        'submitted_at' => '2026-04-10 09:00:00',
        'created_at' => '2026-04-10 09:00:00', 'updated_at' => '2026-05-02 13:15:00',
    ],
    [
        'case_number' => 'SDRU-20260506-1006',
        'complaint_title' => 'Damaged Laboratory Equipment',
        'complainant_name' => 'Christian Lopez',
        'complainant_gender' => 'Male', 'complainant_age' => 21,
        'complainant_address' => 'Brgy. San Fabian, Santo Domingo, Nueva Ecija',
        'complainant_student_no' => '23-4106', 'complainant_email' => 'christian.lopez@clsu2.edu.ph',
        'complainant_contact' => '09171234106', 'complainant_college' => 'College of Veterinary Science and Medicine',
        'complainant_course' => 'Doctor of Veterinary Medicine (DVM)',
        'complainant_year_level' => 'Fourth Year', 'complainant_section' => '4-1',
        'complainant_course_year' => 'Doctor of Veterinary Medicine (DVM) | 4-1',
        'case_classification' => 'Property Damage',
        'incident_datetime' => '2026-05-02 10:45:00',
        'incident_location' => 'Veterinary Medicine Laboratory 2',
        'complaint_details' => 'A microscope and several glass slides were deliberately damaged after an unsupervised laboratory session. The complainant identified the respondent as being last seen near the equipment before the damage was discovered.',
        'status' => 'Under Investigation',
        'submitted_at' => '2026-05-06 11:30:00',
        'created_at' => '2026-05-06 11:30:00', 'updated_at' => '2026-05-12 15:00:00',
    ],
    [
        'case_number' => 'SDRU-20260601-1007',
        'complaint_title' => 'Theft of Personal Belongings',
        'complainant_name' => 'Samantha Cruz',
        'complainant_gender' => 'Female', 'complainant_age' => 19,
        'complainant_address' => 'University Dormitory, CLSU Campus, Science City of Muñoz',
        'complainant_student_no' => '25-4107', 'complainant_email' => 'samantha.cruz@clsu2.edu.ph',
        'complainant_contact' => '09171234107', 'complainant_college' => 'College of Hospitality Management',
        'complainant_course' => 'Bachelor of Science in Hospitality Management (BSHM)',
        'complainant_year_level' => 'Second Year', 'complainant_section' => '2-1',
        'complainant_course_year' => 'Bachelor of Science in Hospitality Management (BSHM) | 2-1',
        'case_classification' => 'Theft',
        'incident_datetime' => '2026-05-28 07:50:00',
        'incident_location' => 'University Dormitory, Room 204',
        'complaint_details' => 'A mobile phone and cash amounting to PHP 1,500 were stolen from the complainant\'s locker. The respondent, a roommate, was present during the time the theft was believed to have occurred.',
        'status' => 'Under Investigation',
        'submitted_at' => '2026-06-01 08:45:00',
        'created_at' => '2026-06-01 08:45:00', 'updated_at' => '2026-06-10 10:00:00',
    ],
    [
        'case_number' => 'SDRU-20260703-1008',
        'complaint_title' => 'Unauthorized Discussion of Grades',
        'complainant_name' => 'Paolo Mendoza',
        'complainant_gender' => 'Male', 'complainant_age' => 20,
        'complainant_address' => 'Brgy. Central, Guimba, Nueva Ecija',
        'complainant_student_no' => '24-4108', 'complainant_email' => 'paolo.mendoza@clsu2.edu.ph',
        'complainant_contact' => '09171234108', 'complainant_college' => 'College of Agriculture',
        'complainant_course' => 'Bachelor of Science in Agriculture (BSA)',
        'complainant_year_level' => 'Third Year', 'complainant_section' => '3-1',
        'complainant_course_year' => 'Bachelor of Science in Agriculture (BSA) | 3-1',
        'case_classification' => 'Breach of Confidentiality',
        'incident_datetime' => '2026-06-29 13:05:00',
        'incident_location' => 'College of Agriculture, Room 105',
        'complaint_details' => 'A class representative allegedly accessed and shared the complainant\'s grades and personal information without consent through a group chat. The complainant requests an investigation into the breach.',
        'status' => 'Under Investigation',
        'submitted_at' => '2026-07-03 09:20:00',
        'created_at' => '2026-07-03 09:20:00', 'updated_at' => '2026-07-18 14:00:00',
    ],
    [
        'case_number' => 'SDRU-20260805-1009',
        'complaint_title' => 'Disruptive Behavior During Examination',
        'complainant_name' => 'Erica Tolentino',
        'complainant_gender' => 'Female', 'complainant_age' => 20,
        'complainant_address' => 'Brgy. Poblacion, Palayan City, Nueva Ecija',
        'complainant_student_no' => '24-4109', 'complainant_email' => 'erica.tolentino@clsu2.edu.ph',
        'complainant_contact' => '09171234109', 'complainant_college' => 'College of Fisheries',
        'complainant_course' => 'Bachelor of Science in Fisheries (BSF)',
        'complainant_year_level' => 'Third Year', 'complainant_section' => '3-2',
        'complainant_course_year' => 'Bachelor of Science in Fisheries (BSF) | 3-2',
        'case_classification' => 'Disruptive Behavior',
        'incident_datetime' => '2026-08-01 08:00:00',
        'incident_location' => 'College of Fisheries, Examination Room',
        'complaint_details' => 'During a major examination, the respondent repeatedly talked loudly, tapped on the desk, and attempted to copy answers, disrupting the complainant and other examinees. The proctor asked him to leave but he refused initially.',
        'status' => 'Under Investigation',
        'submitted_at' => '2026-08-05 10:30:00',
        'created_at' => '2026-08-05 10:30:00', 'updated_at' => '2026-08-20 09:00:00',
    ],
    [
        'case_number' => 'SDRU-20260901-1010',
        'complaint_title' => 'Graffiti Vandalism on Campus Wall',
        'complainant_name' => 'Denise Rivera',
        'complainant_gender' => 'Female', 'complainant_age' => 21,
        'complainant_address' => 'Brgy. San Roque, Gapan City, Nueva Ecija',
        'complainant_student_no' => '23-4110', 'complainant_email' => 'denise.rivera@clsu2.edu.ph',
        'complainant_contact' => '09171234110', 'complainant_college' => 'College of Arts and Social Sciences',
        'complainant_course' => 'Bachelor of Arts in Social Sciences (BASS)',
        'complainant_year_level' => 'Fourth Year', 'complainant_section' => '4-1',
        'complainant_course_year' => 'Bachelor of Arts in Social Sciences (BASS) | 4-1',
        'case_classification' => 'Property Damage',
        'incident_datetime' => '2026-08-28 22:15:00',
        'incident_location' => 'College of Arts and Social Sciences, outer wall',
        'complaint_details' => 'The complainant, an officer of the college student council, reported that offensive graffiti was painted on the college building wall. Security camera footage identified the respondent near the scene at the reported time.',
        'status' => 'Under Investigation',
        'submitted_at' => '2026-09-01 08:00:00',
        'created_at' => '2026-09-01 08:00:00', 'updated_at' => '2026-09-10 09:30:00',
    ],
];

$onlineRespondents = [
    [19, 'Kevin Navarro', 'Male', 22, '-5001', 'College of Engineering', 'BSCE', 'Fourth Year', '4-1', 'kevin.navarro@clsu2.edu.ph', 'Prowling post and mocking photos in the group chat.'],
    [19, 'Ryan Cabrera', 'Male', 21, '-5002', 'College of Engineering', 'BSIT', 'Fourth Year', '4-2', 'ryan.cabrera@clsu2.edu.ph', 'Caught on CCTV during the physical altercation near the canteen.'],
    [20, 'Jasmine Santos', 'Female', 19, '-5003', 'College of Business and Accountancy', 'BSAc', 'Second Year', '2-2', 'jasmine.santos@clsu2.edu.ph', 'Alleged to have accessed the complainant\'s portal credentials.'],
    [20, 'Miguel Castillo', 'Male', 23, '-5004', 'College of Science', 'BSBio', 'Fourth Year', '4-2', 'miguel.castillo@clsu2.edu.ph', 'Submitted plagiarized research proposal as his own.'],
    [21, 'Angel Torres', 'Female', 20, '-5005', 'College of Education', 'BSEd', 'Third Year', '3-1', 'angel.torres@clsu2.edu.ph', 'Repeated verbal harassment of the complainant in class.'],
    [21, 'Joshua Reyes', 'Male', 21, '-5006', 'College of Veterinary Science and Medicine', 'DVM', 'Fourth Year', '4-2', 'joshua.reyes@clsu2.edu.ph', 'Last seen near the damaged laboratory equipment.'],
    [22, 'Althea Domingo', 'Female', 20, '-5007', 'College of Hospitality Management', 'BSHM', 'Second Year', '2-2', 'althea.domingo@clsu2.edu.ph', 'Roommate present when the theft was discovered.'],
    [22, 'Marvin Salazar', 'Male', 20, '-5008', 'College of Agriculture', 'BSA', 'Third Year', '3-2', 'marvin.salazar@clsu2.edu.ph', 'Shared grades and personal information without consent.'],
    [23, 'Kaye Ocampo', 'Female', 20, '-5009', 'College of Fisheries', 'BSF', 'Third Year', '3-1', 'kaye.ocampo@clsu2.edu.ph', 'Disrupted the examination and attempted to copy answers.'],
    [23, 'Brix Manalo', 'Male', 21, '-5010', 'College of Arts and Social Sciences', 'BASS', 'Fourth Year', '4-2', 'brix.manalo@clsu2.edu.ph', 'Identified near the vandalized campus wall in security footage.'],
];

foreach ($onlineCases as $i => $case) {
    $cid = seedCase($db, $case, $HEAD_ID);
    if (!$cid) {
        echo "Skipped or failed online case {$case['case_number']}\n";
        continue;
    }
    $createdOnline++;

    $resp = $onlineRespondents[$i];
    [$respAge, $respName, $respGender, $respId, $respStudentNo, $respCollege, $respCourse, $respYear, $respSection, $respEmail, $respDetails] = $resp;
    $respondentId = $respId + 10;
    seedRespondent($db, $cid, [
        'respondent_type' => 'Student',
        'full_name' => $respName,
        'gender' => $respGender,
        'age' => $respAge,
        'student_no' => '23' . $respStudentNo,
        'college' => $respCollege,
        'course_year' => $respCourse . ' | ' . $respSection,
        'year_level' => $respYear,
        'contact_info' => '09171234' . ($respId),
        'email' => $respEmail,
        'address' => 'Residence Hall, ' . $respCollege . ', CLSU Campus, Science City of Muñoz, Nueva Ecija',
        'details' => $respDetails,
        'created_at' => $case['created_at'],
    ]);

    seedHistory($db, $cid, [
        'action' => 'Complaint Submission', 'new_status' => 'Submitted',
        'created_by_account_id' => $HEAD_ID, 'created_at' => $case['submitted_at'],
    ], $HEAD_ID);
    seedHistory($db, $cid, [
        'action' => 'Case Verification', 'previous_status' => 'Submitted', 'new_status' => 'Verified',
        'created_by_account_id' => $HEAD_ID,
        'created_at' => date('Y-m-d H:i:s', strtotime($case['submitted_at'] . ' +3 days')),
    ], $HEAD_ID);
    seedHistory($db, $cid, [
        'action' => 'Case Classification', 'new_status' => 'Under Investigation',
        'remarks' => 'Classified as ' . $case['case_classification'] . '.',
        'created_by_account_id' => $HEAD_ID,
        'created_at' => date('Y-m-d H:i:s', strtotime($case['submitted_at'] . ' +5 days')),
    ], $HEAD_ID);
}

/* ================================================================ MIGRATED CASES === */

$migratedCases = [
    [
        'case_number' => 'MIG-2020-0101',
        'complaint_title' => 'Legacy: Cheating During Final Examination',
        'complainant_name' => 'Alicia Fernando',
        'complainant_gender' => 'Female', 'complainant_age' => 20,
        'complainant_student_no' => '2019-0101', 'complainant_email' => 'alicia.fernando@legacy.sicms',
        'complainant_contact' => '09171110101', 'complainant_college' => 'College of Science',
        'complainant_course' => 'BS Biology', 'complainant_year_level' => '3rd Year',
        'complainant_section' => 'A', 'complainant_course_year' => 'BS Biology | A',
        'case_classification' => 'Academic Dishonesty',
        'incident_datetime' => '2020-03-04 08:00:00',
        'incident_location' => 'Science Auditorium',
        'complaint_details' => 'Legacy record: respondent was caught copying answers during the final examination in Genetics. Proctor reported the incident to the SDRU.',
        'status' => 'Resolved',
        'submitted_at' => '2020-03-04 09:00:00',
        'created_at' => '2020-03-04 09:00:00', 'updated_at' => '2020-04-10 15:00:00',
        'case_source' => 'Legacy', 'original_case_date' => '2020-03-04',
        'legacy_outcome' => 'Suspended for one semester.',
        'action_taken' => 'Suspension for one semester; mandatory academic integrity seminar.',
        'resolution_date' => '2020-04-10', 'outcome' => 'Suspended for one semester effective SY 2019-2020.',
        'legacy_entry_source' => 'SDRU Registry Ledger 2020',
    ],
    [
        'case_number' => 'MIG-2020-0102',
        'complaint_title' => 'Legacy: Vandalism of Library Property',
        'complainant_name' => 'Benjamin Santiago',
        'complainant_gender' => 'Male', 'complainant_age' => 19,
        'complainant_student_no' => '2019-0102', 'complainant_email' => 'benjamin.santiago@legacy.sicms',
        'complainant_contact' => '09171110102', 'complainant_college' => 'College of Engineering',
        'complainant_course' => 'BS Civil Engineering', 'complainant_year_level' => '2nd Year',
        'complainant_section' => 'B', 'complainant_course_year' => 'BS Civil Engineering | B',
        'case_classification' => 'Property Damage',
        'incident_datetime' => '2020-06-20 14:30:00',
        'incident_location' => 'University Library, 3rd floor',
        'complaint_details' => 'Legacy record: respondent defaced several library reference books with marker pen and carved initials onto a reading table.',
        'status' => 'Resolved',
        'submitted_at' => '2020-06-20 15:00:00',
        'created_at' => '2020-06-20 15:00:00', 'updated_at' => '2020-07-05 10:00:00',
        'case_source' => 'Legacy', 'original_case_date' => '2020-06-20',
        'legacy_outcome' => 'Restitution of library books and community service.',
        'action_taken' => 'Replaced damaged books and rendered 20 hours of community service.',
        'resolution_date' => '2020-07-05', 'outcome' => 'Books replaced and community service completed.',
        'legacy_entry_source' => 'SDRU Registry Ledger 2020',
    ],
    [
        'case_number' => 'MIG-2021-0201',
        'complaint_title' => 'Legacy: Theft in the Dormitory',
        'complainant_name' => 'Christine Aquino',
        'complainant_gender' => 'Female', 'complainant_age' => 21,
        'complainant_student_no' => '2020-0201', 'complainant_email' => 'christine.aquino@legacy.sicms',
        'complainant_contact' => '09171110201', 'complainant_college' => 'College of Science',
        'complainant_course' => 'BS Chemistry', 'complainant_year_level' => '3rd Year',
        'complainant_section' => 'A', 'complainant_course_year' => 'BS Chemistry | A',
        'case_classification' => 'Theft',
        'incident_datetime' => '2021-03-18 21:00:00',
        'incident_location' => 'University Dormitory, Building C',
        'complaint_details' => 'Legacy record: respondent stole a laptop and other electronic devices from the complainant\'s dormitory room.',
        'status' => 'Resolved',
        'submitted_at' => '2021-03-19 08:00:00',
        'created_at' => '2021-03-19 08:00:00', 'updated_at' => '2021-05-15 11:00:00',
        'case_source' => 'Legacy', 'original_case_date' => '2021-03-19',
        'legacy_outcome' => 'Suspended for two semesters with conditions for readmission.',
        'action_taken' => 'Two-semester suspension and supervised return of stolen items.',
        'resolution_date' => '2021-05-15', 'outcome' => 'Suspension served; stolen items returned.',
        'legacy_entry_source' => 'SDRU Registry Ledger 2021',
    ],
    [
        'case_number' => 'MIG-2021-0202',
        'complaint_title' => 'Legacy: Cyber Defamation',
        'complainant_name' => 'Daniel Francisco',
        'complainant_gender' => 'Male', 'complainant_age' => 20,
        'complainant_student_no' => '2020-0202', 'complainant_email' => 'daniel.francisco@legacy.sicms',
        'complainant_contact' => '09171110202', 'complainant_college' => 'College of Business and Accountancy',
        'complainant_course' => 'BS Accountancy', 'complainant_year_level' => '2nd Year',
        'complainant_section' => 'B', 'complainant_course_year' => 'BS Accountancy | B',
        'case_classification' => 'Cyberbullying',
        'incident_datetime' => '2021-09-10 22:15:00',
        'incident_location' => 'Online - social media platform',
        'complaint_details' => 'Legacy record: respondent posted defamatory statements about the complainant on a public social media account, causing reputational harm.',
        'status' => 'Resolved',
        'submitted_at' => '2021-09-11 09:30:00',
        'created_at' => '2021-09-11 09:30:00', 'updated_at' => '2021-10-20 14:00:00',
        'case_source' => 'Legacy', 'original_case_date' => '2021-09-11',
        'legacy_outcome' => 'Written apology and removal of defamatory posts.',
        'action_taken' => 'Public retraction and written apology; post deleted.',
        'resolution_date' => '2021-10-20', 'outcome' => 'Apology issued and posts removed.',
        'legacy_entry_source' => 'SDRU Registry Ledger 2021',
    ],
    [
        'case_number' => 'MIG-2022-0301',
        'complaint_title' => 'Legacy: Physical Bullying',
        'complainant_name' => 'Eduardo Marquez',
        'complainant_gender' => 'Male', 'complainant_age' => 17,
        'complainant_student_no' => '2021-0301', 'complainant_email' => 'eduardo.marquez@legacy.sicms',
        'complainant_contact' => '09171110301', 'complainant_college' => 'College of Education',
        'complainant_course' => 'BS Elementary Education', 'complainant_year_level' => '1st Year',
        'complainant_section' => 'A', 'complainant_course_year' => 'BS Elementary Education | A',
        'case_classification' => 'Physical Assault',
        'incident_datetime' => '2022-04-12 16:45:00',
        'incident_location' => 'Education Building corridor',
        'complaint_details' => 'Legacy record: respondent repeatedly shoved and verbally harassed the complainant, a younger student, over several weeks.',
        'status' => 'Resolved',
        'submitted_at' => '2022-04-13 10:00:00',
        'created_at' => '2022-04-13 10:00:00', 'updated_at' => '2022-06-01 09:00:00',
        'case_source' => 'Legacy', 'original_case_date' => '2022-04-13',
        'legacy_outcome' => 'Probation for one semester with counseling.',
        'action_taken' => 'Behavioral probation and mandatory counseling sessions.',
        'resolution_date' => '2022-06-01', 'outcome' => 'Probation served; no repeat incidents.',
        'legacy_entry_source' => 'SDRU Registry Ledger 2022',
    ],
    [
        'case_number' => 'MIG-2022-0302',
        'complaint_title' => 'Legacy: Unauthorized Grade Changes',
        'complainant_name' => 'Fiona Reyes',
        'complainant_gender' => 'Female', 'complainant_age' => 22,
        'complainant_student_no' => '2021-0302', 'complainant_email' => 'fiona.reyes@legacy.sicms',
        'complainant_contact' => '09171110302', 'complainant_college' => 'College of Science',
        'complainant_course' => 'BS Computer Science', 'complainant_year_level' => '3rd Year',
        'complainant_section' => 'A', 'complainant_course_year' => 'BS Computer Science | A',
        'case_classification' => 'Fraud',
        'incident_datetime' => '2022-08-15 09:20:00',
        'incident_location' => 'Registrar\'s Office',
        'complaint_details' => 'Legacy record: respondent was found to have tampered with course grade records through a registrar assistant.',
        'status' => 'Resolved',
        'submitted_at' => '2022-08-15 10:30:00',
        'created_at' => '2022-08-15 10:30:00', 'updated_at' => '2022-09-30 13:00:00',
        'case_source' => 'Legacy', 'original_case_date' => '2022-08-15',
        'legacy_outcome' => 'Dismissed from the program.',
        'action_taken' => 'Permanent dismissal from the BS Computer Science program.',
        'resolution_date' => '2022-09-30', 'outcome' => 'Dismissed from program; records restored.',
        'legacy_entry_source' => 'SDRU Registry Ledger 2022',
    ],
    [
        'case_number' => 'MIG-2023-0401',
        'complaint_title' => 'Legacy: Harassment of Female Student',
        'complainant_name' => 'Ginalyn Bautista',
        'complainant_gender' => 'Female', 'complainant_age' => 20,
        'complainant_student_no' => '2022-0401', 'complainant_email' => 'ginalyn.bautista@legacy.sicms',
        'complainant_contact' => '09171110401', 'complainant_college' => 'College of Arts and Social Sciences',
        'complainant_course' => 'AB Psychology', 'complainant_year_level' => '2nd Year',
        'complainant_section' => 'B', 'complainant_course_year' => 'AB Psychology | B',
        'case_classification' => 'Sexual Harassment',
        'incident_datetime' => '2023-05-05 17:30:00',
        'incident_location' => 'College of Arts and Social Sciences, lobby',
        'complaint_details' => 'Legacy record: respondent made repeated inappropriate advances and remarks toward the complainant.',
        'status' => 'Resolved',
        'submitted_at' => '2023-05-06 08:00:00',
        'created_at' => '2023-05-06 08:00:00', 'updated_at' => '2023-06-20 10:00:00',
        'case_source' => 'Legacy', 'original_case_date' => '2023-05-06',
        'legacy_outcome' => 'Probation for one academic year with no-contact order.',
        'action_taken' => 'Behavioral probation and no-contact order issued.',
        'resolution_date' => '2023-06-20', 'outcome' => 'Probation enforced; no further incidents.',
        'legacy_entry_source' => 'SDRU Registry Ledger 2023',
    ],
    [
        'case_number' => 'MIG-2023-0402',
        'complaint_title' => 'Legacy: Academic Plagiarism',
        'complainant_name' => 'Hector Villamor',
        'complainant_gender' => 'Male', 'complainant_age' => 21,
        'complainant_student_no' => '2022-0402', 'complainant_email' => 'hector.villamor@legacy.sicms',
        'complainant_contact' => '09171110402', 'complainant_college' => 'College of Engineering',
        'complainant_course' => 'BS Mechanical Engineering', 'complainant_year_level' => '3rd Year',
        'complainant_section' => 'A', 'complainant_course_year' => 'BS Mechanical Engineering | A',
        'case_classification' => 'Plagiarism',
        'incident_datetime' => '2023-10-10 10:00:00',
        'incident_location' => 'Engineering Faculty Office',
        'complaint_details' => 'Legacy record: respondent submitted a term paper with more than 80% copied content from online sources.',
        'status' => 'Resolved',
        'submitted_at' => '2023-10-11 09:00:00',
        'created_at' => '2023-10-11 09:00:00', 'updated_at' => '2023-11-25 15:00:00',
        'case_source' => 'Legacy', 'original_case_date' => '2023-10-11',
        'legacy_outcome' => 'Written warning and academic integrity seminar.',
        'action_taken' => 'Formal written warning; resubmission of original work.',
        'resolution_date' => '2023-11-25', 'outcome' => 'Warning issued; original work resubmitted.',
        'legacy_entry_source' => 'SDRU Registry Ledger 2023',
    ],
    [
        'case_number' => 'MIG-2024-0501',
        'complaint_title' => 'Legacy: Forgery of Consent Form',
        'complainant_name' => 'Irene Dela Pena',
        'complainant_gender' => 'Female', 'complainant_age' => 20,
        'complainant_student_no' => '2023-0501', 'complainant_email' => 'irene.delapena@legacy.sicms',
        'complainant_contact' => '09171110501', 'complainant_college' => 'College of Science',
        'complainant_course' => 'BS Environmental Science', 'complainant_year_level' => '2nd Year',
        'complainant_section' => 'B', 'complainant_course_year' => 'BS Environmental Science | B',
        'case_classification' => 'Forgery',
        'incident_datetime' => '2024-02-25 09:00:00',
        'incident_location' => 'College of Science, Faculty Room',
        'complaint_details' => 'Legacy record: respondent forged the complainant\'s signature on a dormitory consent form and an academic waiver.',
        'status' => 'Resolved',
        'submitted_at' => '2024-02-26 08:30:00',
        'created_at' => '2024-02-26 08:30:00', 'updated_at' => '2024-04-05 14:00:00',
        'case_source' => 'Legacy', 'original_case_date' => '2024-02-26',
        'legacy_outcome' => 'Suspended for one semester.',
        'action_taken' => 'One-semester suspension and written apology.',
        'resolution_date' => '2024-04-05', 'outcome' => 'Suspension served; apology delivered.',
        'legacy_entry_source' => 'SDRU Registry Ledger 2024',
    ],
    [
        'case_number' => 'MIG-2024-0502',
        'complaint_title' => 'Legacy: Violent Disruption of School Event',
        'complainant_name' => 'Jerome Pascual',
        'complainant_gender' => 'Male', 'complainant_age' => 21,
        'complainant_student_no' => '2023-0502', 'complainant_email' => 'jerome.pascual@legacy.sicms',
        'complainant_contact' => '09171110502', 'complainant_college' => 'College of Agriculture',
        'complainant_course' => 'BS Agriculture', 'complainant_year_level' => '3rd Year',
        'complainant_section' => 'A', 'complainant_course_year' => 'BS Agriculture | A',
        'case_classification' => 'Disruptive Behavior',
        'incident_datetime' => '2024-06-14 19:00:00',
        'incident_location' => 'University Grandstand, foundation day',
        'complaint_details' => 'Legacy record: respondent forcibly disrupted a school foundation day program, pushing staff and damaging stage decorations.',
        'status' => 'Resolved',
        'submitted_at' => '2024-06-15 09:00:00',
        'created_at' => '2024-06-15 09:00:00', 'updated_at' => '2024-07-30 11:00:00',
        'case_source' => 'Legacy', 'original_case_date' => '2024-06-15',
        'legacy_outcome' => 'Community service and written warning.',
        'action_taken' => '40 hours of community service and formal written warning.',
        'resolution_date' => '2024-07-30', 'outcome' => 'Community service completed; warning on record.',
        'legacy_entry_source' => 'SDRU Registry Ledger 2024',
    ],
];

$migratedRespondents = [
    ['Carlos Ilagan', 'Male', 20, '2019-0601', 'College of Science', 'BS Biology', '3rd Year', 'A', 'carlos.ilagan@legacy.sicms'],
    ['Sheena Lozano', 'Female', 19, '2019-0602', 'College of Engineering', 'BS Civil Engineering', '2nd Year', 'B', 'sheena.lozano@legacy.sicms'],
    ['Vincent Mariano', 'Male', 21, '2020-0701', 'College of Science', 'BS Chemistry', '3rd Year', 'A', 'vincent.mariano@legacy.sicms'],
    ['Patricia Rubio', 'Female', 20, '2020-0702', 'College of Business and Accountancy', 'BS Accountancy', '2nd Year', 'B', 'patricia.rubio@legacy.sicms'],
    ['Aldrich Soriano', 'Male', 17, '2021-0801', 'College of Education', 'BS Elementary Education', '1st Year', 'A', 'aldrich.soriano@legacy.sicms'],
    ['Melanie Tagle', 'Female', 22, '2021-0802', 'College of Science', 'BS Computer Science', '3rd Year', 'A', 'melanie.tagle@legacy.sicms'],
    ['Ronald Escalante', 'Male', 20, '2022-0901', 'College of Arts and Social Sciences', 'AB Psychology', '2nd Year', 'B', 'ronald.escalante@legacy.sicms'],
    ['Isabela Del Rosario', 'Female', 21, '2022-0902', 'College of Engineering', 'BS Mechanical Engineering', '3rd Year', 'A', 'isabela.delrosario@legacy.sicms'],
    ['Fernando Quijano', 'Male', 20, '2023-1001', 'College of Science', 'BS Environmental Science', '2nd Year', 'B', 'fernando.quijano@legacy.sicms'],
    ['Marilyn Castro', 'Female', 21, '2023-1002', 'College of Agriculture', 'BS Agriculture', '3rd Year', 'A', 'marilyn.castro@legacy.sicms'],
];

foreach ($migratedCases as $i => $case) {
    $cid = seedCase($db, $case, $HEAD_ID);
    if (!$cid) {
        echo "Skipped or failed migrated case {$case['case_number']}\n";
        continue;
    }
    $createdMigrated++;

    [$respName, $respGender, $respAge, $respStudentNo, $respCollege, $respCourse, $respYear, $respSection, $respEmail] = $migratedRespondents[$i];
    seedRespondent($db, $cid, [
        'respondent_type' => 'Student',
        'full_name' => $respName,
        'gender' => $respGender,
        'age' => $respAge,
        'student_no' => $respStudentNo,
        'college' => $respCollege,
        'course_year' => $respCourse . ' | ' . $respSection,
        'year_level' => $respYear,
        'contact_info' => '09171110' . (301 + $i),
        'email' => $respEmail,
        'address' => 'Residence Hall, ' . $respCollege . ', CLSU Campus, Science City of Muñoz, Nueva Ecija',
        'details' => 'Migrated legacy entry from the ' . $case['legacy_entry_source'] . '.',
        'created_at' => $case['created_at'],
    ]);

    seedHistory($db, $cid, [
        'action' => 'Complaint Submission', 'new_status' => 'Submitted',
        'created_by_account_id' => $HEAD_ID, 'created_at' => $case['submitted_at'],
    ], $HEAD_ID);
    seedHistory($db, $cid, [
        'action' => 'Case Resolution', 'previous_status' => 'Under Investigation', 'new_status' => 'Resolved',
        'remarks' => 'Migrated record marked as resolved per registry ledger.',
        'created_by_account_id' => $HEAD_ID,
        'created_at' => date('Y-m-d H:i:s', strtotime($case['resolution_date'] . ' 15:00:00')),
    ], $HEAD_ID);
}

echo "Seeded: {$createdOnline} online cases, {$createdMigrated} migrated cases.\n";

$connection = $db;
$database = null;
echo "SICMS seed dataset completed.\n";