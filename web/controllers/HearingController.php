<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Hearing.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../services/GoogleCalendarService.php';

class HearingController {
    private $database;
    private $db;
    private $user;
    private $staffRoles = ['super-admin', 'admin', 'sdr staff', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head', 'head of sdru'];

    public function __construct() {
        Security::startSession();

        $this->authenticateStaff();
    }

    public function index() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->handleStatusAction();
        }

        return [
            'user' => $this->user,
            'hearings' => $this->filteredHearings($_GET),
            'calendar' => $this->googleCalendarSummary(),
            'message' => $_SESSION['hearing_message'] ?? null,
            'errors' => $_SESSION['hearing_errors'] ?? [],
        ];
    }

    public function search() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $hearings = $this->filteredHearings($_GET);
            echo json_encode(['success' => true, 'hearings' => $hearings, 'summary' => $this->hearingSummary($hearings)]);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to load hearings.']);
        }
        exit;
    }

    private function filteredHearings(array $input) {
        $search = strtolower(substr(trim((string) ($input['search'] ?? '')), 0, 255));
        $status = substr(trim((string) ($input['status'] ?? '')), 0, 30);
        $year = preg_match('/^\d{4}$/', (string) ($input['year'] ?? '')) ? (string) $input['year'] : '';
        return array_values(array_filter(Hearing::listHearings($this->user), function ($hearing) use ($search, $status, $year) {
            $haystack = strtolower(implode(' ', [$hearing['case_number'] ?? '', $hearing['complainant_name'] ?? '', $hearing['venue'] ?? '']));
            return ($search === '' || str_contains($haystack, $search))
                && ($status === '' || strcasecmp((string) ($hearing['status'] ?? ''), $status) === 0)
                && ($year === '' || substr((string) ($hearing['hearing_datetime'] ?? ''), 0, 4) === $year);
        }));
    }

    private function hearingSummary(array $hearings) {
        return [
            'scheduled' => count(array_filter($hearings, fn($item) => $item['status'] === 'Scheduled')),
            'today' => count(array_filter($hearings, fn($item) => substr($item['hearing_datetime'], 0, 10) === date('Y-m-d'))),
            'completed' => count(array_filter($hearings, fn($item) => $item['status'] === 'Completed')),
            'cancelled' => count(array_filter($hearings, fn($item) => $item['status'] === 'Cancelled')),
        ];
    }

    public function create() {
        $errors = $_SESSION['hearing_errors'] ?? [];
        $old = $_SESSION['hearing_old'] ?? [];

        $requestedComplaintId = (int) ($_GET['complaint_id'] ?? 0);
        if (empty($old['complaint_id']) && $requestedComplaintId > 0 && Hearing::isSchedulableCase($requestedComplaintId, $this->user)) {
            $old['complaint_id'] = $requestedComplaintId;
        }

        unset($_SESSION['hearing_errors'], $_SESSION['hearing_old']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->store();
        }

        return [
            'user' => $this->user,
            'cases' => Hearing::getSchedulableCases($this->user),
            'errors' => $errors,
            'old' => $old,
        ];
    }

    public function edit($hearingId) {
        $hearing = Hearing::findHearing($hearingId);

        if (!$hearing) {
            http_response_code(404);
            echo 'Hearing not found.';
            exit;
        }

        if (!$this->canAccessHearing($hearing)) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        $errors = $_SESSION['hearing_errors'] ?? [];
        $old = $_SESSION['hearing_old'] ?? [];

        unset($_SESSION['hearing_errors'], $_SESSION['hearing_old']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->update($hearingId);
        }

        return [
            'user' => $this->user,
            'hearing' => $hearing,
            'cases' => Hearing::getSchedulableCases($this->user),
            'errors' => $errors,
            'old' => $old,
        ];
    }

    public function clearFlash() {
        unset($_SESSION['hearing_message'], $_SESSION['hearing_errors']);
    }

    private function authenticateStaff() {
        if (!isset($_SESSION['email'])) {
            header('Location: ../auth/login.php');
            exit;
        }

        $this->database = new Database();
        $this->db = $this->database->getConnection();

        User::setConnection($this->db);
        Hearing::setConnection($this->db);
        CaseRecord::setConnection($this->db);
        Notification::setConnection($this->db);
        AuditLog::setConnection($this->db);

        $this->user = User::findByEmail($_SESSION['email']);
        $roleKey = strtolower(str_replace(['_', ' '], '-', $this->user['role'] ?? ''));

        if (!$this->user || $this->user['status'] !== 'active' || !$this->isStaffRole($roleKey)) {
            http_response_code(403);
            echo 'Access denied. Staff account required.';
            exit;
        }
    }

    private function store() {
        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $_SESSION['hearing_errors'] = $errors;
            $_SESSION['hearing_old'] = $_POST;
            header('Location: create.php');
            exit;
        }

        $now = date('Y-m-d H:i:s');

        $hearing = Hearing::schedule([
            'complaint_id' => (int) $_POST['complaint_id'],
            'scheduled_by_account_id' => (int) $this->user['account_id'],
            'hearing_datetime' => date('Y-m-d H:i:s', strtotime($_POST['hearing_datetime'])),
            'venue' => trim($_POST['venue']),
            'google_meet_link' => trim($_POST['google_meet_link'] ?? ''),
            'remarks' => trim($_POST['remarks'] ?? ''),
            'status' => 'Scheduled',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $case = CaseRecord::findCase((int) $_POST['complaint_id']);

        if ($case) {
            Notification::createForUser(
                (int) $case['submitted_by_account_id'],
                'hearing_scheduled',
                'Hearing Scheduled',
                'A hearing for case ' . $case['case_number'] . ' was scheduled on ' . date('M d, Y h:i A', strtotime($hearing['hearing_datetime'])) . '.',
                'web/views/hearings/index.php'
            );
        }

        AuditLog::record(
            $this->user,
            'Hearing Scheduling',
            'Scheduled hearing for ' . ($case['case_number'] ?? ('case #' . (int) $_POST['complaint_id'])) . '.'
        );

        $googleEventId = trim((string) ($_POST['google_event_id'] ?? ''));

        if ($googleEventId !== '' && preg_match('/^[A-Za-z0-9_\-]{3,}$/', $googleEventId)) {
            Hearing::updateHearing((int) $hearing['hearing_id'], [
                'google_event_id' => $googleEventId,
                'updated_at' => $now,
            ]);

            $payload = array_merge($hearing, [
                'case_number'     => $case['case_number'] ?? '',
                'complainant_name' => $case['complainant_name'] ?? '',
            ]);

            $result = $this->googleCalendar()->updateEvent($googleEventId, $payload);

            $calNote = $result['success'] ? ' Calendar event with Google Meet saved.' : ' Note: the calendar event could not be updated.';
        } else {
            $calNote = $this->syncHearingToCalendar($hearing, $case);
        }

        $_SESSION['hearing_message'] = 'Hearing scheduled successfully.' . $calNote;
        header('Location: index.php');
        exit;
    }

    private function update($hearingId) {
        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $_SESSION['hearing_errors'] = $errors;
            $_SESSION['hearing_old'] = $_POST;
            header('Location: edit.php?id=' . $hearingId);
            exit;
        }

        $hearing = Hearing::findHearing($hearingId);
        Hearing::updateHearing($hearingId, [
            'complaint_id' => (int) $_POST['complaint_id'],
            'hearing_datetime' => date('Y-m-d H:i:s', strtotime($_POST['hearing_datetime'])),
            'venue' => trim($_POST['venue']),
            'google_meet_link' => trim($_POST['google_meet_link'] ?? ''),
            'remarks' => trim($_POST['remarks'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        AuditLog::record(
            $this->user,
            'Hearing Updates',
            'Updated hearing for ' . ($hearing['case_number'] ?? ('hearing #' . $hearingId)) . '.'
        );

        $updated = Hearing::findHearing($hearingId);

        if (!empty($hearing['google_event_id'])) {
            $result = $this->googleCalendar()->updateEvent($hearing['google_event_id'], $updated);

            if ($result['success']) {
                $_SESSION['hearing_message'] = 'Hearing updated successfully. Calendar event synced.';
            } else {
                $_SESSION['hearing_message'] = 'Hearing updated successfully, but the calendar event could not be synced.';
            }
        } else {
            $_SESSION['hearing_message'] = 'Hearing updated successfully.' . $this->syncHearingToCalendar($updated, CaseRecord::findCase((int) $updated['complaint_id']));
        }

        header('Location: index.php');
        exit;
    }

    private function handleStatusAction() {
        $hearingId = (int) ($_POST['hearing_id'] ?? 0);
        $action = $_POST['hearing_action'] ?? '';

        if ($hearingId <= 0) {
            $_SESSION['hearing_errors'] = ['Invalid hearing selected.'];
            header('Location: index.php');
            exit;
        }

        $hearing = Hearing::findHearing($hearingId);

        if (!$hearing || !$this->canAccessHearing($hearing)) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        if ($action === 'cancel') {
            Hearing::updateStatus($hearingId, 'Cancelled');
            $this->notifyHearingStatus($hearing, 'Cancelled');
            AuditLog::record($this->user, 'Hearing Updates', 'Cancelled hearing for ' . ($hearing['case_number'] ?? ('hearing #' . $hearingId)) . '.');

            if (!empty($hearing['google_event_id'])) {
                $this->googleCalendar()->deleteEvent($hearing['google_event_id']);
            }

            $_SESSION['hearing_message'] = 'Hearing cancelled.';
        } elseif ($action === 'complete') {
            Hearing::updateStatus($hearingId, 'Completed');
            $this->notifyHearingStatus($hearing, 'Completed');
            AuditLog::record($this->user, 'Hearing Updates', 'Marked hearing for ' . ($hearing['case_number'] ?? ('hearing #' . $hearingId)) . ' as completed.');
            $_SESSION['hearing_message'] = 'Hearing marked as completed.';
        } else {
            $_SESSION['hearing_errors'] = ['Invalid hearing action.'];
        }

        header('Location: index.php');
        exit;
    }

    private function validate(array $post) {
        $errors = [];

        if (empty($post['complaint_id'])) {
            $errors[] = 'Please select a case.';
        } elseif (!Hearing::isSchedulableCase((int) $post['complaint_id'], $this->user)) {
            $errors[] = 'Selected case must be verified or assigned before scheduling a hearing.';
        }

        if (empty(trim($post['hearing_datetime'] ?? ''))) {
            $errors[] = 'Please select hearing date and time.';
        }

        if (empty(trim($post['venue'] ?? ''))) {
            $errors[] = 'Venue is required.';
        }

        if (!empty($post['google_meet_link']) && !filter_var($post['google_meet_link'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid Google Meet link.';
        }

        return $errors;
    }

    private function isStaffRole($roleKey) {
        foreach ($this->staffRoles as $role) {
            if ($roleKey === strtolower(str_replace(['_', ' '], '-', $role))) {
                return true;
            }
        }

        return false;
    }

    private function canAccessHearing(array $hearing) {
        if (strtolower(str_replace(['_', ' '], '-', $this->user['role'] ?? '')) !== 'coordinator') {
            return true;
        }

        $case = CaseRecord::findCase((int) $hearing['complaint_id']);

        return $case && !empty($case['assigned_coordinator_account_id']) && (int) $case['assigned_coordinator_account_id'] === (int) $this->user['account_id'];
    }

    private function notifyHearingStatus($hearing, $status) {
        if (!$hearing) {
            return;
        }

        $case = CaseRecord::findCase((int) $hearing['complaint_id']);

        if (!$case) {
            return;
        }

        Notification::createForUser(
            (int) $case['submitted_by_account_id'],
            'hearing_' . strtolower($status),
            'Hearing ' . $status,
            'The hearing for case ' . $case['case_number'] . ' was marked as ' . $status . '.',
            'web/views/hearings/index.php'
        );
    }

    private function googleCalendar() {
        return GoogleCalendarService::instance($this->db);
    }

    private function syncHearingToCalendar(array $hearing, $case = null) {
        $calendar = $this->googleCalendar();

        if (!$calendar->isConnected() || !$calendar->isConfigured()) {
            return '';
        }

        if (!$case) {
            $case = CaseRecord::findCase((int) $hearing['complaint_id']);
        }

        if (!$case) {
            return '';
        }

        $payload = array_merge($hearing, [
            'case_number'     => $case['case_number'],
            'complainant_name' => $case['complainant_name'],
        ]);

        $result = $calendar->createEvent($payload);

        if ($result['success']) {
            Hearing::updateHearing((int) $hearing['hearing_id'], [
                'google_event_id' => $result['event_id'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return ' Calendar event created.';
        }

        return ' Note: the calendar event could not be created.';
    }

    private function googleCalendarSummary() {
        try {
            $calendar = $this->googleCalendar();
            $connected = $calendar->isConnected();

            if (!$connected) {
                return [
                    'connected' => false,
                    'email' => null,
                    'upcoming' => [],
                ];
            }

            $upcoming = $calendar->upcomingEvents(8);

            return [
                'connected' => true,
                'email' => $calendar->connectedEmail(),
                'upcoming' => $upcoming['success'] ? $upcoming['events'] : [],
            ];
        } catch (Throwable $exception) {
            return [
                'connected' => false,
                'email' => null,
                'upcoming' => [],
            ];
        }
    }
}
