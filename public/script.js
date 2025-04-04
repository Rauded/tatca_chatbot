document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('chat-form');
    const input = document.getElementById('user-input');
    const chatBox = document.getElementById('chat-box');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const message = input.value.trim();
        if (!message) return;

        appendMessage('You', message);
        input.value = '';

        try {
            const response = await fetch('/api/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
            });
            const data = await response.json();
            appendMessage('Bot', data.reply);
        } catch (error) {
            appendMessage('Error', 'Failed to get response.');
        }
    });

    function appendMessage(sender, text) {
        const msg = document.createElement('div');
        msg.className = 'message';
        msg.innerHTML = `<strong>${sender}:</strong> ${text}`;
        chatBox.appendChild(msg);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
});