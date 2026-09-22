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
        $conversations = Message::threadsForUser($this->user);
        $counterpartId = (int) ($_GET['conversation_id'] ?? 0);

        if ($counterpartId <= 0 && !empty($conversations)) {
            $counterpartId = (int) $conversations[0]['counterpart_account_id'];
        }

        $conversation = $counterpartId > 0
            ? $this->conversationData($counterpartId, true)
            : [
                'recipient' => null,
                'messages' => [],
                'cases' => [],
                'isStaffPeer' => false,
            ];

        return [
            'user' => $this->user,
            'conversations' => $conversations,
            'candidates' => Message::newConversationCandidates($this->user),
            'selectedConversationId' => $conversation['recipient'] ? (string) $counterpartId : '',
            'recipient' => $conversation['recipient'],
            'messages' => $conversation['messages'],
            'cases' => $conversation['cases'],
            'isStaffPeer' => $conversation['isStaffPeer'],
        ];
    }

    public function conversation($counterpartId) {
        return $this->conversationData((int) $counterpartId, true);
    }

    private function conversationData($counterpartId, $markRead) {
        $counterpart = Message::counterpartAccount($counterpartId);

        if (!$counterpart || Message::isValidMessagingPeer($this->user, $counterpartId) === null) {
            return [
                'recipient' => null,
                'messages' => [],
                'cases' => [],
                'isStaffPeer' => false,
            ];
        }

        $counterpartId = (int) $counterpart['account_id'];

        if ($markRead) {
            Message::markPairMessagesRead((int) $this->user['account_id'], $counterpartId);
        }

        return [
            'recipient' => $counterpart,
            'messages' => Message::forPair((int) $this->user['account_id'], $counterpartId),
            'cases' => Message::casesForComplainant($counterpartId),
            'isStaffPeer' => Message::isStaffRole($counterpart['role']),
        ];
    }

    public function poll() {
        $counterpartId = (int) ($_GET['conversation_id'] ?? 0);

        $payload = [
            'success' => true,
            'conversations' => Message::threadsForUser($this->user),
            'candidates' => Message::newConversationCandidates($this->user),
            'currentUserId' => (int) $this->user['account_id'],
        ];

        if ($counterpartId > 0) {
            $payload['conversation'] = $this->conversationData($counterpartId, false);
        }

        $this->json($payload);
    }

    public function conversationJson($conversationId) {
        $conversation = $this->conversation((int) $conversationId);

        if (!$conversation['recipient']) {
            $this->json([
                'success' => false,
                'message' => 'Conversation not found or access denied.',
            ], 403);
        }

        $this->json([
            'success' => true,
            'conversation' => $conversation,
            'conversations' => Message::threadsForUser($this->user),
            'currentUserId' => (int) $this->user['account_id'],
        ]);
    }

    public function send() {
        Security::requireCsrfToken();
        $complaintId = (int) ($_POST['complaint_id'] ?? 0);
        $receiverId = (int) ($_POST['receiver_account_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $isAjax = $this->isAjaxRequest();

        if ($complaintId > 0) {
            $case = CaseRecord::findCase($complaintId);

            if (!$case || !Message::canAccessCaseMessages($case, $this->user)) {
                if ($isAjax) {
                    $this->json(['success' => false, 'message' => 'Access denied.'], 403);
                }

                http_response_code(403);
                echo 'Access denied.';
                exit;
            }
        }

        $peer = Message::isValidMessagingPeer($this->user, $receiverId);

        if ($message === '' || $receiverId <= 0 || !$peer) {
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'Please select a valid recipient and enter a message.',
                ], 422);
            }

            $_SESSION['case_errors'] = ['Please select a valid recipient and enter a message.'];
            header('Location: ' . ($complaintId > 0 ? '../cases/show.php?id=' . $complaintId : 'index.php'));
            exit;
        }

        $attachmentPath = $this->handleAttachmentUpload($complaintId, $isAjax);
        $createdMessage = Message::createMessage((int) $this->user['account_id'], $receiverId, $message, $attachmentPath);

        Notification::notifyNewMessage(
            $receiverId,
            trim($this->user['first_name'] . ' ' . $this->user['last_name']),
            'web/views/messages/index.php?conversation_id=' . (int) $this->user['account_id']
        );

        AuditLog::record(
            $this->user,
            'Messages Sent',
            'Sent a direct message to ' . Message::accountName($receiverId) . ($complaintId > 0 ? ' for case #' . $complaintId : '') . '.'
        );

        if ($isAjax) {
            $this->json([
                'success' => true,
                'message' => $createdMessage,
                'messages' => Message::forPair((int) $this->user['account_id'], $receiverId),
                'conversations' => Message::threadsForUser($this->user),
            ]);
        }

        $_SESSION['case_message'] = 'Message sent.';
        header('Location: ../cases/show.php?id=' . $complaintId);
        exit;
    }

    public function start() {
        Security::requireCsrfToken();
        $counterpartId = (int) ($_POST['counterpart_account_id'] ?? 0);
        $isAjax = $this->isAjaxRequest();

        $peer = Message::isValidMessagingPeer($this->user, $counterpartId);
        $canStart = $counterpartId > 0 && Message::canStartThread($this->user, $counterpartId);

        if (!$canStart || !$peer) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'This recipient is not available for a new conversation.'], 422);
            }

            http_response_code(422);
            echo 'Invalid recipient.';
            exit;
        }

        Message::startThread((int) $this->user['account_id'], $counterpartId);

        AuditLog::record(
            $this->user,
            'Conversations Started',
            'Started a conversation with ' . Message::accountName($counterpartId) . '.'
        );

        if ($isAjax) {
            $this->json([
                'success' => true,
                'message' => 'Conversation started.',
                'conversations' => Message::threadsForUser($this->user),
                'candidates' => Message::newConversationCandidates($this->user),
            ]);
        }

        $_SESSION['case_message'] = 'Conversation started.';
        header('Location: index.php?conversation_id=' . $counterpartId);
        exit;
    }

    public function delete() {
        Security::requireCsrfToken();
        $counterpartId = (int) ($_POST['counterpart_account_id'] ?? 0);
        $isAjax = $this->isAjaxRequest();

        if ($counterpartId <= 0 || !Message::accountThreadExists((int) $this->user['account_id'], $counterpartId)) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Conversation not found or access denied.'], 403);
            }

            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        Message::hidePair((int) $this->user['account_id'], $counterpartId);

        AuditLog::record(
            $this->user,
            'Messages Deleted',
            'Deleted their copy of the conversation with ' . Message::accountName($counterpartId) . '.'
        );

        if ($isAjax) {
            $this->json([
                'success' => true,
                'message' => 'Conversation deleted.',
                'conversations' => Message::threadsForUser($this->user),
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

        $filename = 'message_' . (int) $complaintId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
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