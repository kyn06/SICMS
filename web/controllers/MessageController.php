<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Security.php';

class MessageController {
    private $database;
    private $db;
    private $user;

    public function __construct() {
        Security::startSession();

        $this->authenticate();
    }

    public function index() {
        $conversations = Message::conversationsForUser($this->user);
        [$selectedConversationId, $selectedCounterpartId] = self::parseConversationId($_GET['conversation_id'] ?? '');

        if ($selectedConversationId <= 0 && !empty($conversations)) {
            $selectedConversationId = (int) $conversations[0]['complaint_id'];
            $selectedCounterpartId = (int) $conversations[0]['counterpart_account_id'];
        }

        $conversation = $selectedConversationId > 0
            ? $this->conversation($selectedConversationId, $selectedCounterpartId)
            : [
                'case' => null,
                'messages' => [],
                'recipient' => null,
            ];

        if ($selectedConversationId > 0 && !$conversation['case']) {
            header('Location: index.php');
            exit;
        }

        return [
            'user' => $this->user,
            'conversations' => $conversations,
            'candidates' => Message::newConversationCandidates($this->user),
            'selectedConversationId' => $conversation['case']
                ? $selectedConversationId . '-' . ($selectedCounterpartId > 0 ? $selectedCounterpartId : (int) $conversation['recipient']['account_id'])
                : '',
            'case' => $conversation['case'],
            'messages' => $conversation['messages'],
            'recipient' => $conversation['recipient'],
        ];
    }

    public function conversation($complaintId, $counterpartId = 0) {
        return $this->conversationData($complaintId, $counterpartId, true);
    }

    private function conversationData($complaintId, $counterpartId, $markRead) {
        $case = CaseRecord::findCase((int) $complaintId);

        if (!$case || !Message::canAccessConversation($case, $this->user)) {
            return [
                'case' => null,
                'messages' => [],
                'recipient' => null,
            ];
        }

        if ($counterpartId <= 0 || !Message::isValidRecipientForCase($case, $this->user, $counterpartId)) {
            $counterpart = Message::defaultCounterpartForCase($case, $this->user);
        } else {
            $counterpart = Message::counterpartAccount($counterpartId);
        }

        if (!$counterpart) {
            return [
                'case' => null,
                'messages' => [],
                'recipient' => null,
            ];
        }

        $counterpartId = (int) $counterpart['account_id'];

        if ($markRead) {
            Message::markPairMessagesRead((int) $complaintId, (int) $this->user['account_id'], $counterpartId);
        }

        return [
            'case' => $case,
            'messages' => Message::forPair((int) $complaintId, (int) $this->user['account_id'], $counterpartId),
            'recipient' => $counterpart,
        ];
    }

    public function poll() {
        [$caseId, $counterpartId] = self::parseConversationId($_GET['conversation_id'] ?? '');

        $payload = [
            'success' => true,
            'conversations' => Message::conversationsForUser($this->user),
            'candidates' => Message::newConversationCandidates($this->user),
            'currentUserId' => (int) $this->user['account_id'],
        ];

        if ($caseId > 0) {
            $payload['conversation'] = $this->conversationData($caseId, $counterpartId, false);
        }

        $this->json($payload);
    }

    public function conversationJson($complaintId) {
        [$caseId, $counterpartId] = self::parseConversationId($complaintId);
        $conversation = $this->conversation($caseId, $counterpartId);

        if (!$conversation['case']) {
            $this->json([
                'success' => false,
                'message' => 'Conversation not found or access denied.',
            ], 403);
        }

        $this->json([
            'success' => true,
            'conversation' => $conversation,
            'conversations' => Message::conversationsForUser($this->user),
            'currentUserId' => (int) $this->user['account_id'],
        ]);
    }

    public function send() {
        Security::requireCsrfToken();
        $complaintId = (int) ($_POST['complaint_id'] ?? 0);
        $receiverId = (int) ($_POST['receiver_account_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $case = CaseRecord::findCase($complaintId);
        $isAjax = $this->isAjaxRequest();

        if (!$case || !Message::canStartCaseConversation($case, $this->user)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Access denied.'], 403);
            }

            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        if ($message === '' || $receiverId <= 0 || !Message::isValidRecipientForCase($case, $this->user, $receiverId)) {
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'Please select a valid recipient and enter a message.',
                ], 422);
            }

            $_SESSION['case_errors'] = ['Please select a valid recipient and enter a message.'];
            header('Location: ../cases/show.php?id=' . $complaintId);
            exit;
        }

        $attachmentPath = $this->handleAttachmentUpload($complaintId, $isAjax);
        $createdMessage = Message::createMessage($complaintId, (int) $this->user['account_id'], $receiverId, $message, $attachmentPath);

        Notification::notifyNewMessage(
            $receiverId,
            trim($this->user['first_name'] . ' ' . $this->user['last_name']),
            'web/views/messages/index.php?conversation_id=' . $complaintId
        );

        AuditLog::record(
            $this->user,
            'Messages Sent',
            'Sent a message for case ' . ($case['case_number'] ?? ('#' . $complaintId)) . '.'
        );

        if ($isAjax) {
            $this->json([
                'success' => true,
                'message' => $createdMessage,
                'messages' => Message::forPair($complaintId, (int) $this->user['account_id'], $receiverId),
                'conversations' => Message::conversationsForUser($this->user),
            ]);
        }

        $_SESSION['case_message'] = 'Message sent.';
        header('Location: ../cases/show.php?id=' . $complaintId);
        exit;
    }

    public static function parseConversationId($value) {
        $value = trim((string) $value);

        if ($value === '') {
            return [0, 0];
        }

        if (strpos($value, '-') !== false) {
            [$caseId, $counterpartId] = array_pad(explode('-', $value, 2), 2, '0');

            return [(int) $caseId, (int) $counterpartId];
        }

        return [(int) $value, 0];
    }

    public function start() {
        Security::requireCsrfToken();
        $complaintId = (int) ($_POST['complaint_id'] ?? 0);
        $counterpartId = (int) ($_POST['counterpart_account_id'] ?? 0);
        $case = CaseRecord::findCase($complaintId);
        $isAjax = $this->isAjaxRequest();
        $roleKey = strtolower(str_replace(['_', ' '], '-', (string) ($this->user['role'] ?? '')));

        if ($roleKey === 'student') {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Students cannot start new conversations.'], 403);
            }

            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        if (!$case || !Message::canAccessConversation($case, $this->user)
            || $counterpartId <= 0 || !Message::isValidNewConversationCandidate($case, $this->user, $counterpartId)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'This recipient is not available for a new conversation.'], 422);
            }

            http_response_code(422);
            echo 'Invalid recipient.';
            exit;
        }

        Message::openPair($complaintId, (int) $this->user['account_id'], $counterpartId);

        AuditLog::record(
            $this->user,
            'Conversations Started',
            'Started a conversation with ' . Message::accountName($counterpartId) . ' for case ' . ($case['case_number'] ?? ('#' . $complaintId)) . '.'
        );

        if ($isAjax) {
            $this->json([
                'success' => true,
                'message' => 'Conversation started.',
                'conversations' => Message::conversationsForUser($this->user),
                'candidates' => Message::newConversationCandidates($this->user),
            ]);
        }

        $_SESSION['case_message'] = 'Conversation started.';
        header('Location: index.php?conversation_id=' . $complaintId . '-' . $counterpartId);
        exit;
    }

    public function delete() {
        Security::requireCsrfToken();
        $complaintId = (int) ($_POST['complaint_id'] ?? 0);
        $counterpartId = (int) ($_POST['counterpart_account_id'] ?? 0);
        $case = CaseRecord::findCase($complaintId);
        $isAjax = $this->isAjaxRequest();

        if (!$case || !Message::canAccessConversation($case, $this->user)
            || $counterpartId <= 0 || !Message::isValidRecipientForCase($case, $this->user, $counterpartId)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Conversation not found or access denied.'], 403);
            }

            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        Message::hidePair($complaintId, (int) $this->user['account_id'], $counterpartId);

        AuditLog::record(
            $this->user,
            'Messages Deleted',
            'Deleted their copy of the conversation with ' . Message::accountName($counterpartId) . ' for case ' . ($case['case_number'] ?? ('#' . $complaintId)) . '.'
        );

        if ($isAjax) {
            $this->json([
                'success' => true,
                'message' => 'Conversation deleted.',
                'conversations' => Message::conversationsForUser($this->user),
                'candidates' => Message::newConversationCandidates($this->user),
            ]);
        }

        $_SESSION['case_message'] = 'Conversation deleted.';
        header('Location: index.php');
        exit;
    }

    private function authenticate() {
        if (!isset($_SESSION['email'])) {
            header('Location: ../auth/login.php');
            exit;
        }

        $this->database = new Database();
        $this->db = $this->database->getConnection();

        User::setConnection($this->db);
        CaseRecord::setConnection($this->db);
        Message::setConnection($this->db);
        Notification::setConnection($this->db);
        AuditLog::setConnection($this->db);

        $this->user = User::findByEmail($_SESSION['email']);

        if (!$this->user || $this->user['status'] !== 'active') {
            session_unset();
            session_destroy();
            header('Location: ../auth/login.php');
            exit;
        }
    }

    private function isAjaxRequest() {
        return (
            ($_POST['ajax'] ?? '') === '1' ||
            ($_GET['ajax'] ?? '') === '1' ||
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        );
    }

    private function json(array $payload, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    private function handleAttachmentUpload($complaintId, $isAjax) {
        if (empty($_FILES['attachment']) || (int) $_FILES['attachment']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (!Message::supportsAttachments()) {
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'Attachment storage is not enabled. Please run the attachment database migration first.',
                ], 422);
            }

            $_SESSION['case_errors'] = ['Attachment storage is not enabled. Please run the attachment database migration first.'];
            header('Location: ../cases/show.php?id=' . $complaintId);
            exit;
        }

        if ((int) $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Attachment upload failed.'], 422);
            }

            return null;
        }

        if (empty($_FILES['attachment']['tmp_name']) || !is_uploaded_file($_FILES['attachment']['tmp_name'])) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Attachment is not a valid uploaded file.'], 422);
            }

            return null;
        }

        $maxSize = 5 * 1024 * 1024;

        if ((int) $_FILES['attachment']['size'] > $maxSize) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Attachment must be 5MB or smaller.'], 422);
            }

            return null;
        }

        $extension = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if (!in_array($extension, $allowedExtensions, true)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Attachment must be PDF, JPG, PNG, or DOCX.'], 422);
            }

            return null;
        }

        $mimeType = mime_content_type($_FILES['attachment']['tmp_name']);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Attachment has an invalid file content type.'], 422);
            }

            return null;
        }

        $storageDir = dirname(__DIR__, 2) . '/storage/message_attachments';

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $filename = 'message_' . $complaintId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $target = $storageDir . '/' . $filename;

        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $target)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Could not save attachment.'], 500);
            }

            return null;
        }

        return 'storage/message_attachments/' . $filename;
    }
}
