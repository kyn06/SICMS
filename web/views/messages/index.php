<?php
require_once __DIR__ . '/../../controllers/MessageController.php';

$controller = new MessageController();

if (($_GET['ajax'] ?? '') === 'conversation') {
    $controller->conversationJson((int) ($_GET['conversation_id'] ?? 0));
}

$viewData = $controller->index();

$user = $viewData['user'];
$conversations = $viewData['conversations'];
$selectedConversationId = $viewData['selectedConversationId'];
$case = $viewData['case'];
$messages = $viewData['messages'];
$recipients = $viewData['recipients'];

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

function conversation_person(array $conversation, array $user) {
    $roleKey = strtolower(str_replace(['_', ' '], '-', (string) $user['role']));

    if ($roleKey === 'student') {
        $name = trim(($conversation['coordinator_first_name'] ?? '') . ' ' . ($conversation['coordinator_last_name'] ?? ''));

        return [
            'name' => $name !== '' ? $name : 'SDRU Staff',
            'role' => normalize_role_label($conversation['coordinator_role'] ?? 'Staff'),
        ];
    }

    $name = trim(($conversation['submitter_first_name'] ?? '') . ' ' . ($conversation['submitter_last_name'] ?? ''));

    return [
        'name' => $name !== '' ? $name : ($conversation['complainant_name'] ?? 'Student'),
        'role' => normalize_role_label($conversation['submitter_role'] ?? 'Student'),
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
        .messenger-shell { display: grid; grid-template-columns: 300px minmax(0, 1fr); height: calc(100vh - 76px); width: 100%; }
        .conversation-sidebar { background: #fff; border-right: 1px solid #dce5da; display: flex; flex-direction: column; min-width: 0; }
        .sidebar-top { border-bottom: 1px solid #edf4eb; padding: 18px 16px 14px; }
        .sidebar-top h1 { color: #123c1b; font-size: 20px; margin: 0 0 12px; }
        .search { background: #f1f4f0; border: 1px solid #dce5da; border-radius: 999px; color: #172017; font: inherit; padding: 10px 14px; width: 100%; }
        .conversation-list { overflow-y: auto; padding: 8px; }
        .conversation-card { align-items: center; border: 0; border-radius: 8px; background: transparent; cursor: pointer; display: grid; grid-template-columns: 44px minmax(0, 1fr) auto; gap: 10px; padding: 10px; text-align: left; width: 100%; }
        .conversation-card:hover, .conversation-card.active { background: #eef8ec; }
        .avatar { align-items: center; background: #dfe8dc; border-radius: 50%; color: #123c1b; display: inline-flex; font-size: 14px; font-weight: 700; height: 42px; justify-content: center; width: 42px; }
        .avatar.large { font-size: 16px; height: 48px; width: 48px; }
        .conversation-main { min-width: 0; }
        .name { color: #172017; font-size: 14px; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .role { color: #687365; font-size: 12px; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .preview { color: #536052; font-size: 12px; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .conversation-meta { align-items: flex-end; display: flex; flex-direction: column; gap: 6px; min-width: 52px; }
        .time { color: #6f7a6c; font-size: 11px; white-space: nowrap; }
        .badge { background: #1A9D00; border-radius: 999px; color: #fff; font-size: 11px; font-weight: 700; min-width: 20px; padding: 3px 6px; text-align: center; }
        .chat { background: #f7faf6; display: grid; grid-template-rows: auto 1fr auto; min-width: 0; }
        .chat-header { align-items: center; background: #fff; border-bottom: 1px solid #dce5da; display: flex; gap: 12px; justify-content: space-between; padding: 14px 20px; }
        .chat-person { align-items: center; display: flex; gap: 12px; min-width: 0; }
        .chat-person-text { min-width: 0; }
        .online { align-items: center; color: #6f7a6c; display: flex; font-size: 12px; gap: 6px; margin-top: 3px; }
        .online-dot { background: #1A9D00; border-radius: 50%; display: inline-block; height: 8px; width: 8px; }
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
        .recipient-select { background: #fff; border: 1px solid #dce5da; border-radius: 8px; color: #172017; font: inherit; grid-column: 2 / 3; padding: 8px 10px; width: 100%; }
        .attachment-name { color: #6f7a6c; font-size: 12px; grid-column: 2 / 3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .hidden { display: none; }
        @media (max-width: 820px) {
            .messenger-shell { grid-template-columns: 1fr; height: auto; min-height: 100vh; }
            .conversation-sidebar { border-bottom: 1px solid #dce5da; border-right: 0; max-height: 42vh; }
            .chat { min-height: 58vh; }
            .bubble-wrap { max-width: 86%; }
            .dashboard-link { display: none; }
        }
    </style>
    <link rel="stylesheet" href="../layout/system.css">
    <link rel="stylesheet" href="../layout/messages.css">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="app-content messages-content">
    <button class="messages-sidebar-toggle" type="button" aria-label="Open navigation" aria-controls="app-sidebar" aria-expanded="false">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>
    <main class="messenger-shell">
        <aside class="conversation-sidebar">
            <div class="sidebar-top">
                <div class="conversation-title"><div><h1>Case Conversations</h1><span><?= count($conversations) ?> conversation<?= count($conversations) === 1 ? '' : 's' ?></span></div></div>
                <label class="conversation-search" for="conversationSearch">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input class="search" id="conversationSearch" type="search" placeholder="Search name or case number" autocomplete="off">
                </label>
            </div>

            <div class="conversation-list" id="conversationList">
                <?php if (empty($conversations)): ?>
                    <div class="empty-state">No case conversations yet.</div>
                <?php endif; ?>

                <?php foreach ($conversations as $conversation): ?>
                    <?php $person = conversation_person($conversation, $user); ?>
                    <button
                        class="conversation-card <?= ((int) $conversation['complaint_id'] === (int) $selectedConversationId) ? 'active' : '' ?>"
                        type="button"
                        data-conversation-id="<?= (int) $conversation['complaint_id'] ?>"
                        data-search="<?= h(strtolower($person['name'] . ' ' . $person['role'] . ' ' . $conversation['case_number'] . ' ' . $conversation['complainant_name'] . ' ' . ($conversation['coordinator_first_name'] ?? '') . ' ' . ($conversation['coordinator_last_name'] ?? ''))) ?>">
                        <span class="avatar"><?= h(initials($person['name'])) ?></span>
                        <span class="conversation-main">
                            <span class="name"><?= h($person['name']) ?></span>
                            <span class="role"><strong><?= h($conversation['case_number']) ?></strong> | <?= h($conversation['complainant_name']) ?></span>
                            <span class="preview"><?= h(preview_text($conversation['latest_message'] ?? '')) ?></span>
                        </span>
                        <span class="conversation-meta">
                            <span class="time" data-time="<?= h($conversation['latest_message_at'] ?? $conversation['submitted_at']) ?>"></span>
                            <?php if ((int) $conversation['unread_total'] > 0): ?>
                                <span class="badge"><?= (int) $conversation['unread_total'] ?></span>
                            <?php endif; ?>
                        </span>
                    </button>
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
                        <div class="role" id="chatRole">Messages are linked to case records.</div>
                        <div class="conversation-type"><i class="bi bi-shield-lock"></i> Case-linked communication</div>
                    </div>
                </div>
            </header>

            <section class="case-summary" id="caseSummary" aria-label="Case summary">
                <div><span>Case Number</span><strong id="summaryCaseNumber">Not selected</strong></div>
                <div><span>Student</span><strong id="summaryStudent">Not selected</strong></div>
                <div><span>Classification</span><strong id="summaryClassification">Not selected</strong></div>
                <div><span>Status</span><strong><span class="status" id="summaryStatus">Not selected</span></strong></div>
                <div><span>Coordinator</span><strong id="summaryCoordinator">Not assigned</strong></div>
            </section>

            <div class="chat-body" id="chatBody">
                <div class="chat-empty"><i class="bi bi-chat-square-text"></i><strong>Select a conversation</strong><span>Choose a case from the conversation list.</span></div>
            </div>

            <form class="composer" id="messageForm" method="POST" action="send.php" enctype="multipart/form-data">
                <?= Security::csrfField() ?>
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="complaint_id" id="complaintId" value="<?= (int) $selectedConversationId ?>">
                <button class="attachment-btn" id="attachmentButton" type="button" title="Attach file" aria-label="Attach file"><i class="bi bi-paperclip"></i></button>
                <input class="hidden" id="attachmentInput" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx">
                <input type="hidden" name="receiver_account_id" id="recipientInput">
                <textarea class="message-input" id="messageInput" name="message" rows="2" placeholder="Write a message" required></textarea>
                <button class="send-btn" id="sendButton" type="submit" title="Send message" aria-label="Send message" disabled><i class="bi bi-send-fill"></i></button>
                <select class="recipient-select hidden" id="recipientSelect" aria-label="Message recipient"></select>
                <div class="recipient-note" id="recipientNote">Select a conversation to choose a receiver.</div>
                <div class="attachment-name hidden" id="attachmentName"></div>
            </form>
        </section>
    </main>
        </div>
    </div>

    <script>
        document.querySelector('.messages-sidebar-toggle')?.addEventListener('click', function () {
            const open = document.body.classList.toggle('sidebar-open');
            this.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    </script>
    <script src="<?= h(app_url('web/views/layout/system.js')) ?>" defer></script>
    <script>
        const currentUserId = <?= (int) $user['account_id'] ?>;
        const currentUserRole = <?= json_encode((string) $user['role']) ?>;
        let conversations = <?= json_encode($conversations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let selectedConversationId = <?= (int) $selectedConversationId ?>;
        let selectedCase = <?= json_encode($case, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let messages = <?= json_encode($messages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let recipients = <?= json_encode($recipients, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

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
        const recipientSelect = document.getElementById('recipientSelect');
        const recipientNote = document.getElementById('recipientNote');
        const attachmentButton = document.getElementById('attachmentButton');
        const attachmentInput = document.getElementById('attachmentInput');
        const attachmentName = document.getElementById('attachmentName');
        const sendButton = document.getElementById('sendButton');
        const summaryCaseNumber = document.getElementById('summaryCaseNumber');
        const summaryStudent = document.getElementById('summaryStudent');
        const summaryClassification = document.getElementById('summaryClassification');
        const summaryStatus = document.getElementById('summaryStatus');
        const summaryCoordinator = document.getElementById('summaryCoordinator');
        let searchTimer;

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
            const roleKey = String(currentUserRole || '').toLowerCase().replaceAll('_', '-').replaceAll(' ', '-');

            if (roleKey === 'student') {
                const name = `${conversation.coordinator_first_name || ''} ${conversation.coordinator_last_name || ''}`.trim();

                return {
                    name: name || 'SDRU Staff',
                    role: normalizeRole(conversation.coordinator_role || 'Staff')
                };
            }

            const name = `${conversation.submitter_first_name || ''} ${conversation.submitter_last_name || ''}`.trim();

            return {
                name: name || conversation.complainant_name || 'Student',
                role: normalizeRole(conversation.submitter_role || 'Student')
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
                conversationList.innerHTML = '<div class="empty-state">No case conversations yet.</div>';
                return;
            }

            conversationList.innerHTML = conversations.map(conversation => {
                const person = personForConversation(conversation);
                const isActive = Number(conversation.complaint_id) === Number(selectedConversationId);
                const unread = Number(conversation.unread_total || 0);
                const searchable = `${person.name} ${person.role} ${conversation.case_number} ${conversation.complainant_name || ''} ${conversation.coordinator_first_name || ''} ${conversation.coordinator_last_name || ''}`.toLowerCase();

                return `
                    <button class="conversation-card ${isActive ? 'active' : ''}" type="button" data-conversation-id="${conversation.complaint_id}" data-search="${escapeHtml(searchable)}">
                        <span class="avatar">${escapeHtml(initials(person.name))}</span>
                        <span class="conversation-main">
                            <span class="name">${escapeHtml(person.name)}</span>
                            <span class="role"><strong>${escapeHtml(conversation.case_number)}</strong> | ${escapeHtml(conversation.complainant_name || 'Student')}</span>
                            <span class="preview">${escapeHtml(preview(conversation.latest_message))}</span>
                        </span>
                        <span class="conversation-meta">
                            <span class="time">${escapeHtml(cardTime(conversation.latest_message_at || conversation.submitted_at))}</span>
                            ${unread > 0 ? `<span class="badge">${unread}</span>` : ''}
                        </span>
                    </button>
                `;
            }).join('');

            filterConversations();
        }

        function renderHeader() {
            const conversation = conversations.find(item => Number(item.complaint_id) === Number(selectedConversationId));
            const person = conversation ? personForConversation(conversation) : { name: 'Select a conversation', role: 'Messages are linked to case records.' };

            chatAvatar.textContent = initials(person.name);
            chatName.textContent = person.name;
            chatRole.textContent = person.role;
        }

        function renderCaseSummary() {
            const coordinator = selectedCase
                ? `${selectedCase.coordinator_first_name || ''} ${selectedCase.coordinator_last_name || ''}`.trim()
                : '';
            summaryCaseNumber.textContent = selectedCase?.case_number || 'Not selected';
            summaryStudent.textContent = selectedCase?.complainant_name || 'Not selected';
            summaryClassification.textContent = selectedCase?.case_classification || 'Not selected';
            summaryStatus.textContent = selectedCase?.status || 'Not selected';
            summaryStatus.dataset.status = String(selectedCase?.status || '').toLowerCase().replace(/[^a-z0-9]+/g, '-');
            summaryCoordinator.textContent = coordinator || 'Not assigned';
        }

        function syncComposerState() {
            const hasConversation = Number(selectedConversationId) > 0;
            const canSend = hasConversation && recipientInput.value !== '' && messageInput.value.trim() !== '';
            messageInput.disabled = !hasConversation;
            attachmentButton.disabled = !hasConversation;
            sendButton.disabled = !canSend;
        }

        function preferredRecipient() {
            if (!selectedCase || !recipients.length) {
                return null;
            }

            const roleKey = String(currentUserRole || '').toLowerCase().replaceAll('_', '-').replaceAll(' ', '-');

            if (roleKey === 'student') {
                const coordinatorId = Number(selectedCase.assigned_coordinator_account_id || 0);
                const assignedCoordinator = recipients.find(recipient => Number(recipient.account_id) === coordinatorId);

                return assignedCoordinator || recipients[0] || null;
            }

            const studentId = Number(selectedCase.submitted_by_account_id || 0);
            const student = recipients.find(recipient => Number(recipient.account_id) === studentId);

            return student || recipients[0] || null;
        }

        function isStudentRole() {
            return String(currentUserRole || '').toLowerCase().replaceAll('_', '-').replaceAll(' ', '-') === 'student';
        }

        function renderRecipients() {
            const recipient = preferredRecipient();

            if (!recipient) {
                recipientInput.value = '';
                recipientSelect.innerHTML = '';
                recipientSelect.classList.add('hidden');
                recipientNote.textContent = 'No receiver available for this conversation.';
                recipientNote.classList.remove('hidden');
                return;
            }

            if (isStudentRole()) {
                recipientInput.value = recipient.account_id;
                recipientSelect.innerHTML = '';
                recipientSelect.classList.add('hidden');
                recipientNote.textContent = `Sending to ${recipient.first_name} ${recipient.last_name} (${normalizeRole(recipient.role)})`;
                recipientNote.classList.remove('hidden');
                return;
            }

            recipientSelect.innerHTML = recipients.map(item => {
                const name = `${item.first_name} ${item.last_name}`.trim();
                const selected = Number(item.account_id) === Number(recipientInput.value || recipient.account_id) ? 'selected' : '';

                return `<option value="${item.account_id}" ${selected}>${escapeHtml(name)} - ${escapeHtml(normalizeRole(item.role))}</option>`;
            }).join('');
            recipientInput.value = recipientSelect.value || recipient.account_id;
            recipientSelect.classList.remove('hidden');
            recipientNote.textContent = 'Choose a student, coordinator, staff member, or Head SDRU recipient.';
            recipientNote.classList.remove('hidden');
        }

        function renderMessages() {
            if (!selectedConversationId) {
                chatBody.innerHTML = '<div class="chat-empty"><i class="bi bi-chat-square-text"></i><strong>Select a conversation</strong><span>Choose a case from the conversation list.</span></div>';
                return;
            }

            if (!messages.length) {
                chatBody.innerHTML = '<div class="chat-empty"><i class="bi bi-chat-dots"></i><strong>No messages yet</strong><span>Start the case conversation below.</span></div>';
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
            renderCaseSummary();
            renderRecipients();
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

            selectedConversationId = Number(id);
            selectedCase = data.conversation.case;
            messages = data.conversation.messages;
            recipients = data.conversation.recipients;
            conversations = data.conversations;
            complaintId.value = selectedConversationId;
            history.replaceState(null, '', `index.php?conversation_id=${selectedConversationId}`);
            renderAll();
        }

        function filterConversations() {
            const term = conversationSearch.value.trim().toLowerCase();
            let visible = 0;

            document.querySelectorAll('.conversation-card').forEach(card => {
                const hidden = term !== '' && !card.dataset.search.includes(term);
                card.classList.toggle('hidden', hidden);
                if (!hidden) visible++;
            });
            conversationSearchEmpty.classList.toggle('hidden', visible > 0 || conversations.length === 0);
        }

        conversationList.addEventListener('click', event => {
            const card = event.target.closest('.conversation-card');

            if (card) {
                openConversation(card.dataset.conversationId);
            }
        });

        conversationSearch.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(filterConversations, 250);
        });

        recipientSelect.addEventListener('change', () => {
            recipientInput.value = recipientSelect.value;
            syncComposerState();
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

            const formData = new FormData(messageForm);

            const response = await fetch(messageForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await response.json();

            if (!data.success) {
                return;
            }

            messageInput.value = '';
            attachmentInput.value = '';
            attachmentName.textContent = '';
            attachmentName.classList.add('hidden');
            messages = data.messages;
            conversations = data.conversations;
            renderAll();
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

        renderAll();
    </script>
</body>

</html>
