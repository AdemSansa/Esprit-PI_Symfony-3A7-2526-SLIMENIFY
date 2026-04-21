import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["messagesContainer", "input"];

    connect() {
        // Guard: no active conversation on index page
        if (!this.hasMessagesContainerTarget) {
            return;
        }

        this.conversationId = this.messagesContainerTarget.dataset.conversationId;
        this.currentRole    = this.messagesContainerTarget.dataset.currentRole;
        this.renderedIds    = new Set();
        this.isFetching     = false;

        if (this.conversationId) {
            this.scrollToBottom();
            this.startPolling();
        }
    }

    disconnect() {
        this.stopPolling();
    }

    startPolling() {
        this.pollInterval = setInterval(() => this.fetchMessages(), 1000);
    }

    stopPolling() {
        if (this.pollInterval) clearInterval(this.pollInterval);
    }

    async fetchMessages() {
        // isFetching guard prevents concurrent fetches from double-rendering
        if (!this.conversationId || this.isFetching) return;
        this.isFetching = true;
        try {
            const response = await fetch(`/chat/api/${this.conversationId}/messages`);
            if (!response.ok) return;

            const messages = await response.json();
            const newMessages = messages.filter(m => !this.renderedIds.has(m.id));
            if (newMessages.length > 0) {
                this.renderMessages(messages);
                messages.forEach(m => this.renderedIds.add(m.id));
                this.scrollToBottom();
            }
        } catch (error) {
            console.error('Error polling messages:', error);
        } finally {
            this.isFetching = false;
        }
    }

    async send(event) {
        event.preventDefault();
        const content = this.inputTarget.value.trim();
        if (!content) return;

        const btn = this.element.querySelector('.chat-send-btn');
        if (btn) btn.disabled = true;

        try {
            const response = await fetch(`/chat/api/${this.conversationId}/send`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content: content })
            });

            if (response.ok) {
                const newMsg = await response.json();
                this.inputTarget.value = '';
                // Append directly — avoids race condition with polling fetchMessages()
                if (!this.renderedIds.has(newMsg.id)) {
                    this.renderedIds.add(newMsg.id);
                    this.appendMessage(newMsg);
                    this.scrollToBottom();
                }
            } else {
                const err = await response.text();
                console.error('Send failed, status:', response.status, err);
            }
        } catch (error) {
            console.error('Error sending message:', error);
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    appendMessage(msg) {
        const wrapper = document.createElement('div');
        wrapper.className = `chat-bubble-wrapper ${msg.senderType === this.currentRole ? 'mine' : 'theirs'}`;

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.innerText = msg.content;

        if (msg.createdAt) {
            const ts = document.createElement('div');
            ts.className = 'chat-timestamp';
            ts.innerText = msg.createdAt;
            bubble.appendChild(ts);
        }

        wrapper.appendChild(bubble);

        if (this.currentRole === 'therapist' && msg.senderType === 'user' && msg.sensitivityLevel && msg.sensitivityLevel !== 'low') {
            wrapper.appendChild(this.createAiBadge(msg.sensitivityLevel, msg.aiAnalysis));
        }

        this.messagesContainerTarget.appendChild(wrapper);
    }

    renderMessages(messages) {
        this.messagesContainerTarget.innerHTML = '';
        messages.forEach(msg => this.appendMessage(msg));
    }

    createAiBadge(level, analysis) {
        const configs = {
            medium:   { icon: '🟡', label: 'Anxiété légère',  color: '#b7791f', bg: '#fefcbf', border: '#f6e05e' },
            high:     { icon: '🔴', label: 'Détresse élevée', color: '#c53030', bg: '#fff5f5', border: '#fc8181' },
            critical: { icon: '🚨', label: 'ALERTE CRITIQUE', color: '#fff',    bg: '#c53030', border: '#9b2c2c' },
        };
        const cfg = configs[level] || configs['medium'];

        const badge = document.createElement('div');
        badge.className = `ai-badge ai-badge--${level}`;
        badge.style.cssText = `
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 6px; padding: 5px 12px;
            background: ${cfg.bg}; color: ${cfg.color};
            border: 1px solid ${cfg.border};
            border-radius: 20px; font-size: 0.75rem; font-weight: 700; max-width: 100%;
            ${level === 'critical' ? 'animation: criticalPulse 1.5s ease infinite;' : ''}
        `;
        badge.innerHTML = `<span>${cfg.icon} ${cfg.label}</span>${analysis ? `<span style="font-weight:400;opacity:0.85;">— ${analysis}</span>` : ''}`;
        return badge;
    }

    scrollToBottom() {
        if (this.hasMessagesContainerTarget) {
            this.messagesContainerTarget.scrollTop = this.messagesContainerTarget.scrollHeight;
        }
    }
}
