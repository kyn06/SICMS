<?php
require_once __DIR__ . '/../../controllers/MessageController.php';

header('Cache-Control: no-store, max-age=0');

$controller = new MessageController();

if (($_GET['ajax'] ?? '') === 'conversation') {
    $controller->conversationJson($_GET['conversation_id'] ?? '');
}

if (($_GET['ajax'] ?? '') === 'poll') {
    $controller->poll();
}

$viewData = $controller->index();

$user = $viewData['user'];
$conversations = $viewData['conversations'];
$candidates = $viewData['candidates'];
$selectedConversationId = $viewData['selectedConversationId'];
$messages = $viewData['messages'];
$recipient = $viewData['recipient'];
$cases = $viewData['cases'];
$isStaffPeer = $viewData['isStaffPeer'];

$roleKey = strtolower(str_replace(['_', ' '], '-', (string) ($user['role'] ?? '')));
$canStartConversation = $roleKey !== 'student';

function h($value) {
    return htmlspecialchars((string) $value);
}

function normalize_role_label($role) {
    $roleKey = strtolower(str_replace(['_', ' '], '-', (string) $role));

    return match ($roleKey) {
        'head-of-sdru', 'sdru-head' => 'Head SDRU',
        'sdr-staff', 'sdru-staff' => 'Staff',
        'admin', 'super-admin' => 'Administrator',
        'student' => 'Student',
        default => ucwords(str_replace('-', ' ', $roleKey)),
    };
}

function conversation_person(array $conversation) {
    $name = trim(($conversation['counterpart_first_name'] ?? '') . ' ' . ($conversation['counterpart_last_name'] ?? ''));

    return [
        'name' => $name !== '' ? $name : 'SDRU',
        'role' => normalize_role_label($conversation['counterpart_role'] ?? ''),
    ];
}

function initials($name) {
    $words = preg_split('/\s+/', trim((string) $name));
    $first = strtoupper(substr($words[0] ?? 'S', 0, 1));
    $second = strtoupper(substr($words[1] ?? '', 0, 1));

    return $first . $second;
}

function preview_text($text) {
    $text = trim(preg_replace('/\s+/', ' ', (string) $text));

    if ($text === '') {
        return 'No messages yet.';
    }

    return strlen($text) > 40 ? substr($text, 0, 37) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; }
        body { align-items: stretch; background: #eef2ec; display: block; justify-content: flex-start; min-height: 100vh; padding: 0; }
        .messenger-shell { display: grid; grid-template-columns: 300px minmax(0, 1fr); height: calc(100vh - 76px); }
        .conversation-sidebar { background: #fff; border-right: 1px solid #dce5da; display: flex; flex-direction: column; min-width: 0; }
        .sidebar-top { border-bottom: 1px solid #edf4eb; padding: 18px 16px 14px; position: relative; }
        .new-conversation-btn { align-items: center; background: transparent; border: 0; border-radius: 8px; color: #123c1b; cursor: pointer; display: inline-flex; font-size: 22px; height: 34px; justify-content: center; position: absolute; right: 12px; top: 14px; width: 34px; }
        .new-conversation-btn:hover, .new-conversation-btn.open { background: #dfe8dc; color: #1A9D00; }
        .candidate-popover-overlay { align-items: center; background: rgba(10, 28, 14, 0.55); display: flex; inset: 0; justify-content: center; position: fixed; z-index: 90; }
        .candidate-popover { background: #fff; border: 1px solid #dce5da; border-radius: 12px; box-shadow: 0 18px 48px rgba(0, 0, 0, 0.25); display: flex; flex-direction: column; max-height: min(75vh, 480px); max-width: calc(100vw - 32px); overflow: hidden; width: 380px; z-index: 95; }
        .conversation-filter-group { display: grid; gap: 6px; grid-template-columns: repeat(3, 1fr); margin-top: 10px; }
        .conversation-filter-btn { background: #eef2ec; border: 1px solid #dce5da; border-radius: 8px; color: #123c1b; cursor: pointer; font: inherit; font-size: 12px; font-weight: 700; padding: 8px 0; text-align: center; transition: background 0.15s ease, color 0.15s ease; }
        .conversation-filter-btn:hover { background: #dfe8dc; }
        .conversation-filter-btn.active { background: #123c1b; border-color: #123c1b; color: #fff; }
        .popover-header { align-items: center; border-bottom: 1px solid #edf4eb; color: #123c1b; display: flex; font-size: 13px; justify-content: space-between; padding: 13px 14px; }
        .popover-close { background: transparent; border: 0; color: #687365; cursor: pointer; font-size: 13px; padding: 2px 4px; }
        .popover-close:hover { color: #b3261e; }
        .popover-hint { color: #687365; font-size: 11.5px; line-height: 1.4; margin: 0; padding: 10px 14px 4px; }
        .popover-search { padding: 8px 14px 6px; }
        .candidate-list { border-top: 1px solid #f0f5ee; margin-top: 6px; overflow-y: auto; padding: 6px; }
        .candidate-item { align-items: center; background: transparent; border: 0; border-bottom: 1px solid #f0f5ee; cursor: pointer; display: grid; gap: 10px; grid-template-columns: 36px minmax(0, 1fr); padding: 8px 9px; text-align: left; width: 100%; }
        .candidate-item:last-child { border-bottom: 0; }
        .candidate-item:hover { background: #eef8ec; }
        .candidate-item .avatar { font-size: 12px; height: 36px; width: 36px; }
        .candidate-name { color: #172017; display: block; font-size: 13px; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .candidate-meta { color: #687365; display: block; font-size: 11.5px; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .candidate-empty { color: #667162; font-size: 12px; padding: 16px 10px; text-align: center; }
        .sidebar-top h1 { color: #123c1b; font-size: 20px; margin: 0 0 12px; }
        .search { background: #f1f4f0; border: 1px solid #dce5da; border-radius: 999px; color: #172017; font: inherit; padding: 10px 14px; width: 100%; }
        .conversation-list { overflow-y: auto; padding: 8px; }
        .conversation-item { position: relative; }
        .conversation-card { align-items: center; border: 0; border-radius: 8px; background: transparent; cursor: pointer; display: grid; grid-template-columns: 44px minmax(0, 1fr) auto; gap: 10px; padding: 10px; text-align: left; width: 100%; }
        .conversation-card:hover, .conversation-card.active { background: #eef8ec; }
        .conversation-menu-btn { align-items: center; background: transparent; border: 0; border-radius: 50%; color: #687365; cursor: pointer; display: inline-flex; font-size: 16px; height: 26px; justify-content: center; position: absolute; right: 4px; top: 4px; width: 26px; z-index: 2; }
        .conversation-menu-btn:hover, .conversation-menu-btn.open { background: #dfe8dc; color: #123c1b; }
        .conversation-menu { background: #fff; border: 1px solid #dce5da; border-radius: 8px; box-shadow: 0 10px 24px rgba(18, 60, 27, 0.14); min-width: 190px; padding: 6px; position: absolute; right: 8px; top: 32px; z-index: 40; }
        .conversation-menu-delete { align-items: center; background: transparent; border: 0; border-radius: 6px; color: #b3261e; cursor: pointer; display: flex; font: inherit; font-size: 13px; font-weight: 600; gap: 8px; padding: 8px 10px; text-align: left; white-space: nowrap; width: 100%; }
        .conversation-menu-delete:hover { background: #fdecea; }
        .avatar { align-items: center; background: #dfe8dc; border-radius: 50%; color: #123c1b; display: inline-flex; font-size: 14px; font-weight: 700; height: 42px; justify-content: center; width: 42px; }
        .avatar.large { font-size: 16px; height: 48px; width: 48px; }
        .conversation-main { min-width: 0; }
        .name { color: #172017; display: block; font-size: 14px; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .role { color: #687365; display: block; font-size: 12px; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .preview { color: #536052; display: block; font-size: 12px; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .conversation-meta { align-items: flex-end; display: flex; flex-direction: column; gap: 6px; min-width: 52px; padding-right: 18px; }
        .time { color: #6f7a6c; font-size: 11px; white-space: nowrap; }
        .badge { background: #1A9D00; border-radius: 999px; color: #fff; font-size: 11px; font-weight: 700; min-width: 20px; padding: 3px 6px; text-align: center; }
        .chat { background: #f7faf6; display: grid; grid-template-rows: auto 1fr auto; min-width: 0; }
        .chat-header { align-items: center; background: #fff; border-bottom: 1px solid #dce5da; display: flex; gap: 12px; justify-content: space-between; padding: 14px 20px; }
        .chat-person { align-items: center; display: flex; gap: 12px; min-width: 0; }
        .chat-person-text { min-width: 0; }
        .thread-info-btn { align-items: center; background: #eef2ec; border: 0; border-radius: 50%; color: #123c1b; cursor: pointer; display: inline-flex; flex: 0 0 36px; font-size: 16px; height: 36px; justify-content: center; width: 36px; }
        .thread-info-btn:hover, .thread-info-btn.open { background: #dfe8dc; }
        .thread-info-btn:disabled { cursor: not-allowed; opacity: 0.4; }
        .thread-info-panel { background: #fff; border: 1px solid #dce5da; border-radius: 12px; box-shadow: 0 16px 40px rgba(18, 60, 27, 0.18); max-height: min(70vh, 520px); overflow-y: auto; padding: 8px; position: absolute; right: 18px; top: 60px; width: 330px; z-index: 70; }
        .chat-header { position: relative; }
        .thread-info-head { align-items: center; border-bottom: 1px solid #edf4eb; display: flex; gap: 10px; margin-bottom: 6px; padding: 10px 10px 12px; }
        .thread-info-head strong { color: #172017; display: block; font-size: 14px; }
        .thread-info-head span:not(.avatar) { color: #687365; display: block; font-size: 12px; margin-top: 2px; }
        .info-case { border-bottom: 1px solid #f0f5ee; padding: 10px; }
        .info-case:last-child { border-bottom: 0; }
        .info-case-number { align-items: center; color: #1A6D00; display: flex; font-size: 13px; font-weight: 800; gap: 7px; margin-bottom: 7px; text-decoration: none; }
        .info-case-number:hover { text-decoration: underline; }
        .info-grid { display: grid; gap: 7px; }
        .info-grid>div { display: flex; gap: 8px; justify-content: space-between; }
        .info-grid em { color: #687365; font-size: 11px; font-style: normal; font-weight: 700; text-transform: uppercase; }
        .info-grid strong { color: #172017; font-size: 12px; text-align: right; }
        .info-note { align-items: center; color: #687365; display: flex; flex-direction: column; font-size: 12px; gap: 6px; padding: 22px 14px; text-align: center; }
        .info-note i { font-size: 22px; }
        .info-note.empty i { color: #c9d4c6; }
        .dashboard-link { background: #123c1b; border-radius: 8px; color: #fff; padding: 9px 12px; text-decoration: none; white-space: nowrap; }
        .chat-body { overflow-y: auto; padding: 22px 24px; }
        .date-separator { align-items: center; color: #7a8577; display: flex; font-size: 12px; gap: 12px; justify-content: center; margin: 16px 0; }
        .date-separator::before, .date-separator::after { background: #dce5da; content: ""; height: 1px; max-width: 120px; flex: 1; }
        .message-row { display: flex; margin: 7px 0 12px; }
        .message-row.outgoing { justify-content: flex-end; }
        .bubble-wrap { max-width: min(70%, 640px); }
        .message-bubble { background: #e8e8e8; border-radius: 18px 18px 18px 6px; color: #172017; line-height: 1.5; padding: 10px 13px; white-space: pre-wrap; word-break: break-word; }
        .message-row.outgoing .message-bubble { background: #e8f7d9; border-radius: 18px 18px 6px 18px; }
        .attachment-link { color: #123c1b; display: block; font-size: 12px; font-weight: 700; margin-top: 8px; text-decoration: none; }
        .message-time { color: #747e71; font-size: 11px; margin: 4px 6px 0; }
        .message-row.outgoing .message-time { text-align: right; }
        .empty-state { color: #667162; padding: 40px 20px; text-align: center; }
        .composer { align-items: flex-end; background: #fff; border-top: 1px solid #dce5da; display: grid; grid-template-columns: auto minmax(0, 1fr) auto; gap: 10px; padding: 14px 18px; }
        .attachment-btn, .send-btn { border: 0; border-radius: 999px; cursor: pointer; font: inherit; height: 42px; width: 42px; }
        .attachment-btn { background: #eef2ec; color: #123c1b; font-size: 20px; }
        .send-btn { background: #1A9D00; color: #fff; font-weight: 700; }
        .message-input { background: #f1f4f0; border: 1px solid #dce5da; border-radius: 22px; font: inherit; line-height: 1.4; max-height: 130px; min-height: 42px; padding: 11px 14px; resize: none; width: 100%; }
        .recipient-note { color: #6f7a6c; font-size: 12px; grid-column: 2 / 3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .recipient-note.recipient-error { color: #b3261e; white-space: normal; }
        .attachment-name { color: #6f7a6c; font-size: 12px; grid-column: 2 / 3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .modal-overlay { align-items: center; background: rgba(10, 28, 14, 0.55); display: flex; inset: 0; justify-content: center; position: fixed; z-index: 100; }
        .modal-box { background: #fff; border-radius: 12px; box-shadow: 0 18px 48px rgba(0, 0, 0, 0.25); max-width: 400px; padding: 22px 24px; width: calc(100% - 40px); }
        .modal-box h3 { color: #123c1b; font-size: 17px; margin: 0 0 8px; }
        .modal-box p { color: #536052; font-size: 13px; line-height: 1.5; margin: 0 0 18px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .modal-actions button { border: 0; border-radius: 8px; cursor: pointer; font: inherit; font-size: 13px; font-weight: 700; padding: 9px 16px; }
        .modal-cancel { background: #eef2ec; color: #172017; }
        .modal-cancel:hover { background: #dfe8dc; }
        .modal-confirm { background: #b3261e; color: #fff; }
        .modal-confirm:hover { background: #99201a; }
        .hidden { display: none; }
        @media (max-width: 1400px) {
            .messenger-shell { grid-template-columns: 1fr; height: auto; min-height: 100vh; max-width: 100%; }
            .conversation-sidebar { border-bottom: 1px solid #dce5da; border-right: 0; max-height: 42vh; }
            .chat { min-height: 58vh; }
            .bubble-wrap { max-width: 86%; }
            .dashboard-link { display: none; }
        }
    </style>
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <link rel="stylesheet" href="../layout/messages.css">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="app-content messages-content">
        <?php $pageTitle = 'Messages'; require __DIR__ . '/../layout/topbar.php'; ?>
    <div class="messenger-shell">
        <aside class="conversation-sidebar">
            <div class="sidebar-top">
                <?php if ($canStartConversation): ?>
                    <button class="new-conversation-btn" id="newConversationBtn" type="button" title="Start a new conversation" aria-label="Start a new conversation" aria-expanded="false"><i class="bi bi-plus-square"></i></button>
                <?php endif; ?>
                <div class="conversation-title"><div><h1>Messages</h1><span><?= count($conversations) ?> conversation<?= count($conversations) === 1 ? '' : 's' ?></span></div></div>
                <label class="conversation-search" for="conversationSearch">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input class="search" id="conversationSearch" type="search" placeholder="Search conversations" autocomplete="off">
                </label>

                <?php if ($canStartConversation): ?>
                <div class="conversation-filter-group" id="conversationFilterGroup">
                    <button class="conversation-filter-btn active" id="filterAll" type="button" data-filter="all">All</button>
                    <button class="conversation-filter-btn" id="filterComplainant" type="button" data-filter="complainant">Complainant</button>
                    <button class="conversation-filter-btn" id="filterStaffs" type="button" data-filter="staffs">Staffs</button>
                </div>
            <?php endif; ?>
            </div>

            <?php if ($canStartConversation): ?>
                <div class="candidate-popover-overlay hidden" id="candidatePopoverOverlay" role="presentation">
                    <div class="candidate-popover" id="candidatePopover" role="dialog" aria-label="Start a new conversation">
                        <div class="popover-header">
                            <strong><i class="bi bi-chat-plus-dots"></i> Start a new conversation</strong>
                            <button class="popover-close" id="candidateClose" type="button" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <p class="popover-hint">Search any student or staff. Searching a case number will show the complainant.</p>
                        <div class="popover-search"><input class="search" id="candidateSearch" type="search" placeholder="Search name, role, or case number" autocomplete="off"></div>
                        <div class="candidate-list" id="candidateList"></div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="conversation-list" id="conversationList">
                <?php if (empty($conversations)): ?>
                    <div class="empty-state">No conversations yet.</div>
                <?php endif; ?>

                <?php foreach ($conversations as $conversation): ?>
                    <?php $person = conversation_person($conversation); ?>
                    <div class="conversation-item">
                        <button
                            class="conversation-card <?= ($selectedConversationId === (string) $conversation['counterpart_account_id']) ? 'active' : '' ?>"
                            type="button"
                            data-conversation-id="<?= (int) $conversation['counterpart_account_id'] ?>"
                            data-role="<?= h($conversation['counterpart_role']) ?>"
                            data-search="<?= h(strtolower($person['name'] . ' ' . $person['role'])) ?>">
                            <span class="avatar"><?= h(initials($person['name'])) ?></span>
                            <span class="conversation-main">
                                <span class="name"><?= h($person['name']) ?></span>
                                <span class="role"><?= h($person['role']) ?></span>
                                <span class="preview"><?= h(preview_text($conversation['latest_message'] ?? '')) ?></span>
                            </span>
                            <span class="conversation-meta">
                                <span class="time" data-time="<?= h($conversation['latest_message_at'] ?? '') ?>"></span>
                                <?php if ((int) $conversation['unread_total'] > 0): ?>
                                    <span class="badge"><?= (int) $conversation['unread_total'] ?></span>
                                <?php endif; ?>
                            </span>
                        </button>
                        <button class="conversation-menu-btn" type="button" aria-label="Conversation options" aria-haspopup="true" data-conversation-id="<?= (int) $conversation['counterpart_account_id'] ?>"><i class="bi bi-three-dots"></i></button>
                        <div class="conversation-menu hidden" data-menu-for="<?= (int) $conversation['counterpart_account_id'] ?>">
                            <button class="conversation-menu-delete" type="button" data-conversation-id="<?= (int) $conversation['counterpart_account_id'] ?>" data-counterpart-name="<?= h($person['name']) ?>"><i class="bi bi-trash3"></i> Delete conversation</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="conversation-search-empty hidden" id="conversationSearchEmpty"><i class="bi bi-search"></i><span>No conversations found.</span></div>
        </aside>

        <section class="chat">
            <header class="chat-header">
                <div class="chat-person" id="chatHeader">
                    <span class="avatar large" id="chatAvatar">SD</span>
                    <div class="chat-person-text">
                        <div class="name" id="chatName">Select a conversation</div>
                        <div class="role" id="chatRole">Choose a conversation to start messaging.</div>
                    </div>
                </div>
                <button class="thread-info-btn" id="threadInfoBtn" type="button" title="Conversation details" aria-label="Conversation details" aria-expanded="false" disabled><i class="bi bi-info-circle"></i></button>
                <div class="thread-info-panel hidden" id="threadInfoPanel" role="dialog" aria-label="Conversation details">
                    <div class="thread-info-head">
                        <span class="avatar" id="infoAvatar">SD</span>
                        <div>
                            <strong id="infoName">Select a conversation</strong>
                            <span id="infoRole">&nbsp;</span>
                        </div>
                    </div>
                    <div id="infoBody"><div class="info-note"><i class="bi bi-person-x"></i>No details available.</div></div>
                </div>
            </header>

            <div class="chat-body" id="chatBody">
                <div class="chat-empty"><i class="bi bi-chat-square-text"></i><strong>Select a conversation</strong><span>Choose a person from the conversation list.</span></div>
            </div>

            <form class="composer" id="messageForm" method="POST" action="send.php" enctype="multipart/form-data">
                <?= Security::csrfField() ?>
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="complaint_id" id="complaintId" value="0">
                <button class="attachment-btn" id="attachmentButton" type="button" title="Attach file" aria-label="Attach file" disabled><i class="bi bi-paperclip"></i></button>
                <input class="hidden" id="attachmentInput" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx">
                <input type="hidden" name="receiver_account_id" id="recipientInput" value="<?= (int) ($recipient['account_id'] ?? 0) ?>">
                <textarea class="message-input" id="messageInput" name="message" rows="2" placeholder="Write a message" disabled></textarea>
                <button class="send-btn" id="sendButton" type="submit" title="Send message" aria-label="Send message" disabled><i class="bi bi-send-fill"></i></button>
                <div class="recipient-note" id="recipientNote">Select a conversation to start messaging.</div>
                <div class="attachment-name hidden" id="attachmentName"></div>
            </form>
        </section>
    </div>
        </div>
    </div>

    <div class="modal-overlay hidden" id="deleteModal">
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
            <h3 id="deleteModalTitle">Delete this conversation?</h3>
            <p id="deleteModalText">This will permanently remove your messages in this conversation. This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="modal-cancel" id="deleteCancel" type="button">Cancel</button>
                <button class="modal-confirm" id="deleteConfirm" type="button">Delete</button>
            </div>
        </div>
    </div>

    <script>
        const currentUserId = <?= (int) $user['account_id'] ?>;
        const currentUserRole = <?= json_encode((string) $user['role']) ?>;
        let conversations = <?= json_encode($conversations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let candidates = <?= json_encode($candidates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let selectedConversationId = <?= json_encode((string) $selectedConversationId) ?>;
        let selectedRecipient = <?= json_encode($recipient, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let selectedCases = <?= json_encode($cases, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let selectedIsStaff = <?= $isStaffPeer ? 'true' : 'false' ?>;
        let messages = <?= json_encode($messages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        const conversationList = document.getElementById('conversationList');
        const conversationSearch = document.getElementById('conversationSearch');
        const conversationSearchEmpty = document.getElementById('conversationSearchEmpty');
        const chatBody = document.getElementById('chatBody');
        const chatAvatar = document.getElementById('chatAvatar');
        const chatName = document.getElementById('chatName');
        const chatRole = document.getElementById('chatRole');
        const messageForm = document.getElementById('messageForm');
        const complaintId = document.getElementById('complaintId');
        const messageInput = document.getElementById('messageInput');
        const recipientInput = document.getElementById('recipientInput');
        const recipientNote = document.getElementById('recipientNote');
        const attachmentButton = document.getElementById('attachmentButton');
        const attachmentInput = document.getElementById('attachmentInput');
        const attachmentName = document.getElementById('attachmentName');
        const sendButton = document.getElementById('sendButton');
        const deleteModal = document.getElementById('deleteModal');
        const deleteModalText = document.getElementById('deleteModalText');
        const deleteCancel = document.getElementById('deleteCancel');
        const deleteConfirm = document.getElementById('deleteConfirm');
        const newConversationBtn = document.getElementById('newConversationBtn');
        const candidatePopover = document.getElementById('candidatePopover');
        const candidateClose = document.getElementById('candidateClose');
        const candidateSearch = document.getElementById('candidateSearch');
        const candidateList = document.getElementById('candidateList');
        const threadInfoBtn = document.getElementById('threadInfoBtn');
        const threadInfoPanel = document.getElementById('threadInfoPanel');
        const infoAvatar = document.getElementById('infoAvatar');
        const infoName = document.getElementById('infoName');
        const infoRole = document.getElementById('infoRole');
        const infoBody = document.getElementById('infoBody');
        let searchTimer;
        let pendingDeleteId = null;
        let conversationFilter = 'all';

        function normalizeRole(role) {
            const key = String(role || '').toLowerCase().replaceAll('_', '-').replaceAll(' ', '-');
            const labels = {
                'head-of-sdru': 'Head SDRU',
                'sdru-head': 'Head SDRU',
                'sdr-staff': 'Staff',
                'sdru-staff': 'Staff',
                'admin': 'Administrator',
                'super-admin': 'Administrator',
                'student': 'Student'
            };

            return labels[key] || key.replaceAll('-', ' ').replace(/\b\w/g, letter => letter.toUpperCase());
        }

        function personForConversation(conversation) {
            const name = `${conversation.counterpart_first_name || ''} ${conversation.counterpart_last_name || ''}`.trim();

            return {
                name: name || 'SDRU',
                role: normalizeRole(conversation.counterpart_role || '')
            };
        }

        function initials(name) {
            const words = String(name || 'SD').trim().split(/\s+/);
            return `${words[0]?.[0] || 'S'}${words[1]?.[0] || ''}`.toUpperCase();
        }

        function parseDate(value) {
            return new Date(String(value || '').replace(' ', 'T'));
        }

        function isSameDay(left, right) {
            return left.getFullYear() === right.getFullYear() && left.getMonth() === right.getMonth() && left.getDate() === right.getDate();
        }

        function cardTime(value) {
            if (!value) {
                return '';
            }

            const date = parseDate(value);
            const now = new Date();
            const yesterday = new Date();
            yesterday.setDate(now.getDate() - 1);

            if (isSameDay(date, now)) {
                return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            }

            if (isSameDay(date, yesterday)) {
                return 'Yesterday';
            }

            const dayDiff = Math.round((now - date) / 86400000);

            if (dayDiff < 7) {
                return date.toLocaleDateString([], { weekday: 'long' });
            }

            return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
        }

        function messageTime(value) {
            return parseDate(value).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }

        function separatorLabel(value) {
            const date = parseDate(value);
            const now = new Date();
            const yesterday = new Date();
            yesterday.setDate(now.getDate() - 1);

            if (isSameDay(date, now)) {
                return 'Today';
            }

            if (isSameDay(date, yesterday)) {
                return 'Yesterday';
            }

            const dayDiff = Math.round((now - date) / 86400000);

            if (dayDiff < 7) {
                return date.toLocaleDateString([], { weekday: 'long' });
            }

            return date.toLocaleDateString([], { month: 'long', day: 'numeric', year: 'numeric' });
        }

        function preview(text) {
            const clean = String(text || '').replace(/\s+/g, ' ').trim();
            return clean ? (clean.length > 40 ? `${clean.slice(0, 37)}...` : clean) : 'No messages yet.';
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        function renderConversationList() {
            if (!conversations.length) {
                conversationList.innerHTML = '<div class="empty-state">No conversations yet.</div>';
                return;
            }

            conversationList.innerHTML = conversations.map(conversation => {
                const person = personForConversation(conversation);
                const threadId = String(conversation.counterpart_account_id);
                const isActive = threadId === String(selectedConversationId);
                const unread = Number(conversation.unread_total || 0);
                const searchable = `${person.name} ${person.role}`.toLowerCase();

                return `
                    <div class="conversation-item">
                        <button class="conversation-card ${isActive ? 'active' : ''}" type="button" data-conversation-id="${escapeHtml(threadId)}" data-role="${escapeHtml(conversation.counterpart_role)}" data-search="${escapeHtml(searchable)}">
                            <span class="avatar">${escapeHtml(initials(person.name))}</span>
                            <span class="conversation-main">
                                <span class="name">${escapeHtml(person.name)}</span>
                                <span class="role">${escapeHtml(person.role)}</span>
                                <span class="preview">${escapeHtml(preview(conversation.latest_message))}</span>
                            </span>
                            <span class="conversation-meta">
                                <span class="time">${escapeHtml(cardTime(conversation.latest_message_at))}</span>
                                ${unread > 0 ? `<span class="badge">${unread}</span>` : ''}
                            </span>
                        </button>
                        <button class="conversation-menu-btn" type="button" aria-label="Conversation options" aria-haspopup="true" data-conversation-id="${escapeHtml(threadId)}"><i class="bi bi-three-dots"></i></button>
                        <div class="conversation-menu hidden" data-menu-for="${escapeHtml(threadId)}">
                            <button class="conversation-menu-delete" type="button" data-conversation-id="${escapeHtml(threadId)}" data-counterpart-name="${escapeHtml(person.name)}"><i class="bi bi-trash3"></i> Delete conversation</button>
                        </div>
                    </div>
                `;
            }).join('');

            filterConversations();
        }

        function renderHeader() {
            const conversation = conversations.find(item => String(item.counterpart_account_id) === String(selectedConversationId));
            const person = conversation ? personForConversation(conversation) : { name: 'Select a conversation', role: 'Choose a conversation to start messaging.' };

            chatAvatar.textContent = initials(person.name);
            chatName.textContent = person.name;
            chatRole.textContent = person.role;
        }

        function renderThreadInfo() {
            const conversation = selectedConversationId
                ? conversations.find(item => String(item.counterpart_account_id) === String(selectedConversationId))
                : null;
            const person = conversation ? personForConversation(conversation) : null;

            if (!person) {
                infoAvatar.textContent = '--';
                infoName.textContent = 'Select a conversation';
                infoRole.textContent = '&nbsp;';
                infoBody.innerHTML = '<div class="info-note"><i class="bi bi-person-x"></i>No details available.</div>';
                threadInfoBtn.disabled = true;
                return;
            }

            threadInfoBtn.disabled = false;
            infoAvatar.textContent = initials(person.name);
            infoName.textContent = person.name;
            infoRole.textContent = selectedRecipient
                ? `${normalizeRole(selectedRecipient.role)}${selectedRecipient.email ? ' · ' + selectedRecipient.email : ''}`
                : person.role;

            if (selectedIsStaff) {
                infoBody.innerHTML = '<div class="info-note"><i class="bi bi-person-badge"></i>Staff conversation — not linked to any case.</div>';
                return;
            }

            if (!selectedCases || !selectedCases.length) {
                infoBody.innerHTML = '<div class="info-note empty"><i class="bi bi-inbox"></i>This complainant has no case yet.</div>';
                return;
            }

            infoBody.innerHTML = selectedCases.map(item => {
                const coordinator = `${item.coord_first_name || ''} ${item.coord_last_name || ''}`.trim() || 'Not assigned';
                const statusKey = String(item.status || '').toLowerCase().replace(/[^a-z0-9]+/g, '-');

                return `
                    <div class="info-case">
                        <a class="info-case-number" href="../cases/show.php?id=${encodeURIComponent(item.complaint_id)}"><i class="bi bi-folder2-open"></i>${escapeHtml(item.case_number)}</a>
                        <div class="info-grid">
                            <div><em>Classification</em><strong>${escapeHtml(item.case_classification || '-')}</strong></div>
                            <div><em>Status</em><strong><span class="status" data-status="${statusKey}">${escapeHtml(item.status || '-')}</span></strong></div>
                            <div><em>Coordinator</em><strong>${escapeHtml(coordinator)}</strong></div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function syncComposerState() {
            const hasConversation = Number(recipientInput.value) > 0;
            const canSend = hasConversation && messageInput.value.trim() !== '';
            messageInput.disabled = !hasConversation;
            attachmentButton.disabled = !hasConversation;
            sendButton.disabled = !canSend;
        }

        function renderRecipientNote() {
            recipientNote.classList.remove('recipient-error');

            if (!selectedRecipient || Number(selectedRecipient.account_id) <= 0) {
                recipientNote.textContent = selectedConversationId
                    ? 'No receiver available for this conversation.'
                    : 'Select a conversation to start messaging.';
                return;
            }

            const name = `${selectedRecipient.first_name || ''} ${selectedRecipient.last_name || ''}`.trim() || 'SDRU';
            recipientNote.textContent = `Messages in this conversation go to ${name} (${normalizeRole(selectedRecipient.role)}).`;
        }

        function renderMessages() {
            if (!selectedConversationId) {
                chatBody.innerHTML = '<div class="chat-empty"><i class="bi bi-chat-square-text"></i><strong>Select a conversation</strong><span>Choose a person from the conversation list.</span></div>';
                return;
            }

            if (!messages.length) {
                chatBody.innerHTML = '<div class="chat-empty"><i class="bi bi-chat-dots"></i><strong>No messages yet</strong><span>Start the conversation below.</span></div>';
                return;
            }

            let lastDate = '';
            const chronologicalMessages = [...messages].sort((left, right) => {
                const leftTime = parseDate(left.created_at).getTime();
                const rightTime = parseDate(right.created_at).getTime();

                if (leftTime !== rightTime) {
                    return leftTime - rightTime;
                }

                return Number(left.message_id || 0) - Number(right.message_id || 0);
            });

            chatBody.innerHTML = chronologicalMessages.map(message => {
                const dateLabel = separatorLabel(message.created_at);
                const separator = dateLabel !== lastDate ? `<div class="date-separator">${escapeHtml(dateLabel)}</div>` : '';
                lastDate = dateLabel;

                const isOutgoing = Number(message.sender_account_id) === Number(currentUserId);
                const attachment = message.attachment ? `<a class="attachment-link" href="../../../${escapeHtml(message.attachment)}" target="_blank">View attachment</a>` : '';

                return `
                    ${separator}
                    <div class="message-row ${isOutgoing ? 'outgoing' : 'incoming'}">
                        <div class="bubble-wrap">
                            <div class="message-bubble">${escapeHtml(message.message)}${attachment}</div>
                            <div class="message-time">${escapeHtml(messageTime(message.created_at))}</div>
                        </div>
                    </div>
                `;
            }).join('');

            requestAnimationFrame(() => { chatBody.scrollTop = chatBody.scrollHeight; });
        }

        function renderAll() {
            renderConversationList();
            renderHeader();
            renderThreadInfo();
            renderRecipientNote();
            renderMessages();
            syncComposerState();
            document.querySelectorAll('[data-time]').forEach(item => {
                item.textContent = cardTime(item.dataset.time);
            });
        }

        async function openConversation(id) {
            const response = await fetch(`index.php?ajax=conversation&conversation_id=${encodeURIComponent(id)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (!data.success) {
                return;
            }

            selectedConversationId = String(id);
            selectedRecipient = data.conversation.recipient;
            selectedCases = data.conversation.cases || [];
            selectedIsStaff = Boolean(data.conversation.isStaffPeer);
            messages = data.conversation.messages;
            conversations = data.conversations;
            complaintId.value = 0;
            recipientInput.value = selectedRecipient ? selectedRecipient.account_id : '';
            history.replaceState(null, '', `index.php?conversation_id=${selectedConversationId}`);
            renderAll();
        }

        function filterConversations() {
            const term = conversationSearch.value.trim().toLowerCase();
            let visible = 0;

            document.querySelectorAll('.conversation-card').forEach(card => {
                const role = String(card.dataset.role || '').toLowerCase();
                const isStudent = role === 'student';
                const matchesType = conversationFilter === 'complainant'
                    ? isStudent
                    : conversationFilter === 'staffs'
                        ? !isStudent
                        : true;
                const hidden = !matchesType || (term !== '' && !card.dataset.search.includes(term));
                card.classList.toggle('hidden', hidden);
                if (!hidden) visible++;
            });

            conversationSearchEmpty.classList.toggle('hidden', visible > 0 || conversations.length === 0);
        }

        function setConversationFilter(type) {
            conversationFilter = type;
            document.querySelectorAll('.conversation-filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.filter === type);
            });
            filterConversations();
        }

        function closeAllMenus() {
            document.querySelectorAll('.conversation-menu:not(.hidden)').forEach(menu => menu.classList.add('hidden'));
            document.querySelectorAll('.conversation-menu-btn.open').forEach(btn => btn.classList.remove('open'));
        }

        conversationList.addEventListener('click', event => {
            const menuButton = event.target.closest('.conversation-menu-btn');

            if (menuButton) {
                const menu = conversationList.querySelector(`[data-menu-for="${menuButton.dataset.conversationId}"]`);
                const wasOpen = menu && !menu.classList.contains('hidden');
                closeAllMenus();

                if (menu && !wasOpen) {
                    menu.classList.remove('hidden');
                    menuButton.classList.add('open');
                }

                return;
            }

            const deleteOption = event.target.closest('.conversation-menu-delete');

            if (deleteOption) {
                closeAllMenus();
                openDeleteConfirm(deleteOption.dataset.conversationId, deleteOption.dataset.counterpartName);
                return;
            }

            const card = event.target.closest('.conversation-card');

            if (card) {
                openConversation(card.dataset.conversationId);
                return;
            }

            closeAllMenus();
        });

        document.addEventListener('click', event => {
            if (!event.target.closest('.conversation-item')) {
                closeAllMenus();
            }

            if (!event.target.closest('#threadInfoPanel') && !event.target.closest('#threadInfoBtn')) {
                threadInfoPanel.classList.add('hidden');
                threadInfoBtn.classList.remove('open');
                threadInfoBtn.setAttribute('aria-expanded', 'false');
            }

            if (!event.target.closest('#candidatePopover') && !event.target.closest('#newConversationBtn')) {
                closeNewConversationPopover();
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeAllMenus();
                threadInfoPanel.classList.add('hidden');
                threadInfoBtn.classList.remove('open');
                threadInfoBtn.setAttribute('aria-expanded', 'false');
                closeNewConversationPopover();
                deleteModal.classList.add('hidden');
                pendingDeleteId = null;
            }
        });

        function openDeleteConfirm(threadId, counterpartName) {
            pendingDeleteId = String(threadId);
            deleteModalText.textContent = `This will permanently remove your messages with ${counterpartName} in this conversation. This action cannot be undone.`;
            deleteModal.classList.remove('hidden');
        }

        function closeDeleteConfirm() {
            deleteModal.classList.add('hidden');
            pendingDeleteId = null;
        }

        deleteCancel.addEventListener('click', closeDeleteConfirm);

        deleteModal.addEventListener('click', event => {
            if (event.target === deleteModal) {
                closeDeleteConfirm();
            }
        });

        deleteConfirm.addEventListener('click', async () => {
            if (!pendingDeleteId) {
                return;
            }

            const csrfInput = messageForm.querySelector('input[name="csrf_token"]');
            const formData = new FormData();
            formData.append('counterpart_account_id', pendingDeleteId);

            if (csrfInput) {
                formData.append('csrf_token', csrfInput.value);
            }

            const response = await fetch('delete.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await response.json();

            if (!data.success) {
                closeDeleteConfirm();
                return;
            }

            conversations = data.conversations;
            candidates = data.candidates;

            if (conversations.some(item => String(item.counterpart_account_id) === String(selectedConversationId))) {
                renderAll();
            } else if (conversations.length) {
                await openConversation(String(conversations[0].counterpart_account_id));
            } else {
                selectedConversationId = '';
                selectedRecipient = null;
                selectedCases = [];
                selectedIsStaff = false;
                messages = [];
                recipientInput.value = '';
                complaintId.value = 0;
                history.replaceState(null, '', 'index.php');
                renderAll();
            }

            closeDeleteConfirm();
        });

        conversationSearch.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(filterConversations, 250);
        });

        messageInput.addEventListener('input', syncComposerState);

        messageInput.addEventListener('keydown', event => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                messageForm.requestSubmit();
            }
        });

        messageForm.addEventListener('submit', async event => {
            event.preventDefault();

            if (!selectedConversationId || messageInput.value.trim() === '' || recipientInput.value === '') {
                return;
            }

            sendButton.disabled = true;

            try {
                const formData = new FormData(messageForm);

                const response = await fetch(messageForm.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                let data = null;

                try {
                    data = await response.json();
                } catch (parseError) {
                    recipientNote.textContent = `Could not send message (server error ${response.status}).`;
                    recipientNote.classList.add('recipient-error');
                    return;
                }

                if (!data.success) {
                    recipientNote.textContent = data.message || 'Could not send message.';
                    recipientNote.classList.add('recipient-error');
                    return;
                }

                recipientNote.classList.remove('recipient-error');
                messageInput.value = '';
                attachmentInput.value = '';
                attachmentName.textContent = '';
                attachmentName.classList.add('hidden');
                messages = data.messages;
                conversations = data.conversations;
                renderAll();
            } catch (error) {
                recipientNote.textContent = 'Could not send message. Check your connection and try again.';
                recipientNote.classList.add('recipient-error');
            } finally {
                syncComposerState();
            }
        });

        attachmentButton.addEventListener('click', () => attachmentInput.click());

        attachmentInput.addEventListener('change', () => {
            const file = attachmentInput.files[0];

            if (!file) {
                attachmentName.textContent = '';
                attachmentName.classList.add('hidden');
                return;
            }

            attachmentName.textContent = file.name;
            attachmentName.classList.remove('hidden');
        });

        function candidateMeta(candidate) {
            const role = normalizeRole(candidate.counterpart_role);
            const caseList = candidate.cases || [];

            if (candidate.counterpart_role === 'student') {
                if (!caseList.length) {
                    return `${role} | No case yet`;
                }

                return `${role} | ${caseList.map(item => item.case_number).join(', ')}`;
            }

            return role;
        }

        function renderCandidates() {
            if (!candidateList) {
                return;
            }

            if (!candidates.length) {
                candidateList.innerHTML = '<div class="candidate-empty">No available recipients.</div>';
                return;
            }

            const term = (candidateSearch?.value || '').trim().toLowerCase();

            const visible = candidates.filter(candidate => {
                if (!term) {
                    return true;
                }

                const name = `${candidate.counterpart_first_name || ''} ${candidate.counterpart_last_name || ''}`.trim().toLowerCase();
                const caseNumbers = (candidate.cases || []).map(item => item.case_number).join(' ').toLowerCase();
                const haystack = `${name} ${normalizeRole(candidate.counterpart_role)} ${caseNumbers}`.toLowerCase();

                return haystack.includes(term);
            });

            if (!visible.length) {
                candidateList.innerHTML = '<div class="candidate-empty">No recipients match your search.</div>';
                return;
            }

            candidateList.innerHTML = visible.map(candidate => {
                const name = `${candidate.counterpart_first_name || ''} ${candidate.counterpart_last_name || ''}`.trim() || 'SDRU';

                return `
                    <button class="candidate-item" type="button" data-thread-id="${String(candidate.counterpart_account_id)}">
                        <span class="avatar">${escapeHtml(initials(name))}</span>
                        <span>
                            <span class="candidate-name">${escapeHtml(name)}</span>
                            <span class="candidate-meta">${escapeHtml(candidateMeta(candidate))}</span>
                        </span>
                    </button>
                `;
            }).join('');
        }

        function closeNewConversationPopover() {
            candidatePopoverOverlay?.classList.add('hidden');
            newConversationBtn?.classList.remove('open');
            newConversationBtn?.setAttribute('aria-expanded', 'false');
        }

        if (newConversationBtn) {
            newConversationBtn.addEventListener('click', () => {
                const willOpen = candidatePopoverOverlay.classList.contains('hidden');

                if (willOpen) {
                    renderCandidates();
                    candidatePopoverOverlay.classList.remove('hidden');
                    newConversationBtn.classList.add('open');
                    newConversationBtn.setAttribute('aria-expanded', 'true');
                    candidateSearch.value = '';
                    candidateSearch.focus();
                } else {
                    closeNewConversationPopover();
                }
            });
        }

        document.querySelectorAll('.conversation-filter-btn').forEach(btn => {
            btn.addEventListener('click', () => setConversationFilter(btn.dataset.filter));
        });

        if (candidateClose) {
            candidateClose.addEventListener('click', closeNewConversationPopover);
        }

        if (candidateSearch) {
            candidateSearch.addEventListener('input', renderCandidates);
        }

        if (candidateList) {
            candidateList.addEventListener('click', async event => {
                const item = event.target.closest('.candidate-item');

                if (!item) {
                    return;
                }

                const counterpartId = item.dataset.threadId;
                const csrfInput = messageForm.querySelector('input[name="csrf_token"]');
                const formData = new FormData();
                formData.append('counterpart_account_id', counterpartId);

                if (csrfInput) {
                    formData.append('csrf_token', csrfInput.value);
                }

                const response = await fetch('start.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await response.json();

                if (!data.success) {
                    return;
                }

                conversations = data.conversations;
                candidates = data.candidates;
                closeNewConversationPopover();
                await openConversation(counterpartId);
            });
        }

        if (threadInfoBtn) {
            threadInfoBtn.addEventListener('click', () => {
                const willOpen = threadInfoPanel.classList.contains('hidden');
                threadInfoPanel.classList.toggle('hidden', !willOpen);
                threadInfoBtn.classList.toggle('open', willOpen);
                threadInfoBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });
        }

        renderAll();

        const POLL_INTERVAL = 5000;
        let lastConversationsSnapshot = JSON.stringify(conversations);
        let lastCandidatesSnapshot = JSON.stringify(candidates);
        let lastMessagesSnapshot = JSON.stringify(messages);
        let pollInFlight = false;

        async function pollUpdates() {
            if (pollInFlight || document.hidden) {
                return;
            }

            pollInFlight = true;

            try {
                const params = new URLSearchParams({ ajax: 'poll' });

                if (selectedConversationId) {
                    params.set('conversation_id', selectedConversationId);
                }

                const response = await fetch(`index.php?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (!data.success) {
                    return;
                }

                const conversationsChanged = JSON.stringify(data.conversations) !== lastConversationsSnapshot;
                const candidatesChanged = JSON.stringify(data.candidates) !== lastCandidatesSnapshot;
                let threadChanged = false;

                if (selectedConversationId && data.conversation && data.conversation.recipient) {
                    threadChanged = JSON.stringify(data.conversation.messages) !== lastMessagesSnapshot;
                }

                if (!conversationsChanged && !candidatesChanged && !threadChanged) {
                    return;
                }

                conversations = data.conversations;
                candidates = data.candidates;
                lastConversationsSnapshot = JSON.stringify(conversations);
                lastCandidatesSnapshot = JSON.stringify(candidates);

                if (threadChanged && selectedConversationId) {
                    await openConversation(selectedConversationId);
                    lastMessagesSnapshot = JSON.stringify(messages);
                } else {
                    renderAll();
                }
            } catch (error) {
                // Network hiccup - retry on the next tick.
            } finally {
                pollInFlight = false;
            }
        }

        setInterval(pollUpdates, POLL_INTERVAL);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                pollUpdates();
            }
        });
    </script>
</body>

</html>