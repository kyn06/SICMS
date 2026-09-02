<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../helpers/Security.php';

class NotificationController {
    private $database;
    private $db;
    private $user;

    public function __construct() {
        Security::startSession();

        $this->authenticate();
    }

    public function index() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->handleAction();
        }

        return [
            'user' => $this->user,
            'notifications' => Notification::allForUser((int) $this->user['account_id']),
            'unreadCount' => Notification::unreadCount((int) $this->user['account_id']),
        ];
    }

    public function search() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $filters = ['search' => substr(trim((string) ($_GET['search'] ?? '')), 0, 255), 'read' => in_array($_GET['read'] ?? '', ['read', 'unread'], true) ? $_GET['read'] : ''];
            $items = Notification::filteredForUser((int) $this->user['account_id'], $filters);
            echo json_encode(['success' => true, 'notifications' => $items, 'total' => count($items), 'unread' => Notification::unreadCount((int) $this->user['account_id'])]);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to load notifications.']);
        }
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
        Notification::setConnection($this->db);

        $this->user = User::findByEmail($_SESSION['email']);

        if (!$this->user || $this->user['status'] !== 'active') {
            session_unset();
            session_destroy();
            header('Location: ../auth/login.php');
            exit;
        }
    }

    private function handleAction() {
        $action = $_POST['notification_action'] ?? '';

        if ($action === 'mark_one') {
            Notification::markAsRead((int) ($_POST['notification_id'] ?? 0), (int) $this->user['account_id']);
        } elseif ($action === 'mark_all') {
            Notification::markAllAsRead((int) $this->user['account_id']);
        }

        header('Location: index.php');
        exit;
    }
}
