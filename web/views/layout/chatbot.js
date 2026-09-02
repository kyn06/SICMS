document.addEventListener('DOMContentLoaded', function () {
    const launcher = document.getElementById('sdruchatLauncher');
    const panel = document.getElementById('sdruchatPanel');
    const closeButton = document.getElementById('sdruchatClose');
    const form = document.getElementById('sdruchatForm');
    const input = document.getElementById('sdruchatInput');
    const sendButton = document.getElementById('sdruchatSend');
    const messages = document.getElementById('sdruchatMessages');
    const suggestions = document.getElementById('sdruchatSuggestions');

    if (!launcher || !panel || !input || !messages) {
        return;
    }

    let isSending = false;

    function openChat() {
        panel.classList.add('open');
        launcher.setAttribute('aria-expanded', 'true');

        setTimeout(function () {
            input.focus();
        }, 100);
    }

    function closeChat() {
        panel.classList.remove('open');
        launcher.setAttribute('aria-expanded', 'false');
    }

    launcher.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        if (panel.classList.contains('open')) {
            closeChat();
        } else {
            openChat();
        }
    });

    if (closeButton) {
        closeButton.addEventListener('click', function (event) {
            event.preventDefault();
            closeChat();
        });
    }

    function addMessage(text, sender) {
        const message = document.createElement('div');

        message.className =
            sender === 'user'
                ? 'sdruchat-msg user'
                : 'sdruchat-msg bot';

        message.textContent = text;

        messages.appendChild(message);
        messages.scrollTop = messages.scrollHeight;
    }

    function showTyping() {
        const typing = document.createElement('div');

        typing.id = 'sdruchatTyping';
        typing.className = 'sdruchat-msg bot sdruchat-typing';
        typing.textContent = 'Typing...';

        messages.appendChild(typing);
        messages.scrollTop = messages.scrollHeight;
    }

    function removeTyping() {
        const typing = document.getElementById('sdruchatTyping');

        if (typing) {
            typing.remove();
        }
    }

    async function sendMessage(textFromSuggestion = null) {
        const text = (
            textFromSuggestion !== null
                ? textFromSuggestion
                : input.value
        ).trim();

        if (!text || isSending) {
            return;
        }

        isSending = true;
        input.value = '';

        addMessage(text, 'user');
        showTyping();

        try {
            const apiUrl =
                window.SDRU_CHAT_API ||
                '../../chatbot/api/chat.php';

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: text
                })
            });

            const data = await response.json();

            removeTyping();

            if (data.success) {
                addMessage(data.answer, 'bot');

                if (suggestions && Array.isArray(data.suggestions)) {
                    suggestions.innerHTML = '';

                    data.suggestions.forEach(function (question) {
                        const button = document.createElement('button');

                        button.type = 'button';
                        button.className = 'sdruchat-suggestion';
                        button.textContent = question;

                        suggestions.appendChild(button);
                    });
                }
            } else {
                addMessage(
                    'Sorry, I could not process your question. Please try asking it another way.',
                    'bot'
                );
            }
        } catch (error) {
            console.error('Chatbot error:', error);

            removeTyping();

            addMessage(
                'Sorry, I am unable to answer at the moment. Please try again later.',
                'bot'
            );
        } finally {
            isSending = false;
        }
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            sendMessage();
        });
    }

    if (sendButton) {
        sendButton.addEventListener('click', function (event) {
            event.preventDefault();
            sendMessage();
        });
    }

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    if (suggestions) {
        suggestions.addEventListener('click', function (event) {
            const button =
                event.target.closest('.sdruchat-suggestion');

            if (!button || isSending) {
                return;
            }

            event.preventDefault();

            const question = button.textContent.trim();

            if (question) {
                sendMessage(question);
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && panel.classList.contains('open')) {
            closeChat();
        }
    });
});
