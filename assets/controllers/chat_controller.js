import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["messagesContainer", "input"];

    connect() {
        this.conversationId = this.messagesContainerTarget.dataset.conversationId;
        this.currentRole = this.messagesContainerTarget.dataset.currentRole;
        this.lastMessageCount = 0;

        if (this.conversationId) {
            this.scrollToBottom();
            this.startPolling();
        }
    }

    disconnect() {
        this.stopPolling();
    }

    startPolling() {
        this.pollInterval = setInterval(() => {
            this.fetchMessages();
        }, 3000);
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }

    async fetchMessages() {
        try {
            const response = await fetch(`/chat/api/${this.conversationId}/messages`);
            if (!response.ok) return;

            const messages = await response.json();

            if (messages.length > this.lastMessageCount) {
                this.renderMessages(messages);
                this.lastMessageCount = messages.length;
                this.scrollToBottom();
            }
        } catch (error) {
            console.error("Error polling messages", error);
        }
    }

    async send(event) {
        event.preventDefault();
        const content = this.inputTarget.value.trim();
        if (!content) return;

        this.inputTarget.value = '';

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
                this.fetchMessages();
            }
        } catch (error) {
            console.error("Error sending message", error);
        }
    }

    renderMessages(messages) {
        this.messagesContainerTarget.innerHTML = '';

        messages.forEach(msg => {
            const isMine = msg.senderType === this.currentRole;

            const wrapper = document.createElement('div');
            wrapper.className = `chat-bubble-wrapper ${isMine ? 'mine' : 'theirs'}`;

            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble';
            bubble.innerText = msg.content;

            if (msg.createdAt) {
                const timeStr = document.createElement('div');
                timeStr.className = 'chat-timestamp';
                timeStr.innerText = msg.createdAt;
                bubble.appendChild(timeStr);
            }

            wrapper.appendChild(bubble);

            // Show AI analysis badge only for therapists, on patient messages
            if (this.currentRole === 'therapist' && msg.senderType === 'user' && msg.sensitivityLevel && msg.sensitivityLevel !== 'low') {
                const badge = this.createAiBadge(msg.sensitivityLevel, msg.aiAnalysis);
                wrapper.appendChild(badge);
            }

            this.messagesContainerTarget.appendChild(wrapper);
        });
    }

    createAiBadge(level, analysis) {
        const badge = document.createElement('div');
        badge.className = `ai-badge ai-badge--${level}`;

        const configs = {
            medium:   { icon: '🟡', label: 'Anxiété légère',     color: '#b7791f', bg: '#fefcbf', border: '#f6e05e' },
            high:     { icon: '🔴', label: 'Détresse élevée',    color: '#c53030', bg: '#fff5f5', border: '#fc8181' },
            critical: { icon: '🚨', label: 'ALERTE CRITIQUE',    color: '#fff',    bg: '#c53030', border: '#9b2c2c' },
        };

        const cfg = configs[level] || configs['medium'];

        badge.style.cssText = `
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 6px; padding: 5px 12px;
            background: ${cfg.bg}; color: ${cfg.color};
            border: 1px solid ${cfg.border};
            border-radius: 20px; font-size: 0.75rem; font-weight: 700;
            max-width: 100%;
            ${level === 'critical' ? 'animation: criticalPulse 1.5s ease infinite;' : ''}
        `;

        badge.innerHTML = `
            <span>${cfg.icon} ${cfg.label}</span>
            ${analysis ? `<span style="font-weight:400; opacity:0.85;">— ${analysis}</span>` : ''}
        `;

        return badge;
    }

    scrollToBottom() {
        this.messagesContainerTarget.scrollTop = this.messagesContainerTarget.scrollHeight;
    }
}
