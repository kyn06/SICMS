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
        $selectedConversationId = (int) ($_GET['conversation_id'] ?? 0);

        if ($selectedConversationId <= 0 && !empty($conversations)) {
            $selectedConversationId = (int) $conversations[0]['complaint_id'];
        }

        $conversation = $selectedConversationId > 0 ? $this->conversation($selectedConversationId) : [
            'case' => null,
            'messages' => [],
            'recipients' => [],
        ];

        if ($selectedConversationId > 0 && !$conversation['case']) {
            header('Location: index.php');
            exit;
        }

        return [
            'user' => $this->user,
            'conversations' => $conversations,
            'selectedConversationId' => $selectedConversationId,
            'case' => $conversation['case'],
            'messages' => $conversation['messages'],
            'recipients' => $conversation['recipients'],
        ];
    }

    public function conversation($complaintId) {
        $case = CaseRecord::findCase((int) $complaintId);

        if (!$case || !Message::canAccessConversation($case, $this->user)) {
            return [
                'case' => null,
                'messages' => [],
                'recipients' => [],
            ];
        }

        Message::markCaseMessagesRead((int) $complaintId, (int) $this->user['account_id']);

        return [
            'case' => $case,
            'messages' => Message::forCaseForUser((int) $complaintId, (int) $this->user['account_id']),
            'recipients' => Message::getRecipientsForCase($case, $this->user),
        ];
    }

    public function conversationJson($complaintId) {
        $conversation = $this->conversation((int) $complaintId);

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
                'messages' => Message::forCaseForUser($complaintId, (int) $this->user['account_id']),
                'conversations' => Message::conversationsForUser($this->user),
            ]);
        }

        $_SESSION['case_message'] = 'Message sent.';
        header('Location: ../cases/show.php?id=' . $complaintId);
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
