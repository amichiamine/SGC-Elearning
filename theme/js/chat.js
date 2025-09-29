document.addEventListener('DOMContentLoaded', () => {
    const chatContainer = document.getElementById('chat-container');
    if (!chatContainer) {
        return;
    }

    const courseId = chatContainer.dataset.courseId;
    const messagesContainer = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('chat-message-input');

    // --- Fonctions pour appeler l'API ---

    const fetchMessages = async () => {
        try {
            const response = await fetch(`/api/v1/courses/${courseId}/chat`);
            if (!response.ok) {
                throw new Error('Erreur réseau ou du serveur.');
            }
            const messages = await response.json();
            renderMessages(messages);
        } catch (error) {
            console.error('Erreur lors de la récupération des messages:', error);
            messagesContainer.innerHTML = '<p>Impossible de charger les messages.</p>';
        }
    };

    const postMessage = async (message) => {
        try {
            const response = await fetch(`/api/v1/courses/${courseId}/chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: message })
            });
            if (!response.ok) {
                throw new Error('Erreur lors de l\'envoi du message.');
            }
            // Après l'envoi, on rafraîchit immédiatement les messages
            fetchMessages();
        } catch (error) {
            console.error('Erreur lors de l\'envoi du message:', error);
        }
    };

    // --- Fonctions pour mettre à jour l'interface ---

    const renderMessages = (messages) => {
        messagesContainer.innerHTML = ''; // Vider les anciens messages
        messages.forEach(msg => {
            const messageElement = document.createElement('div');
            messageElement.classList.add('chat-message');

            const userSpan = document.createElement('span');
            userSpan.classList.add('chat-user');
            userSpan.textContent = `${msg.username}: `;

            const textSpan = document.createElement('span');
            textSpan.classList.add('chat-text');
            textSpan.textContent = msg.message_text;

            messageElement.appendChild(userSpan);
            messageElement.appendChild(textSpan);
            messagesContainer.appendChild(messageElement);
        });
        // Scroller jusqu'en bas
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    };

    // --- Écouteurs d'événements ---

    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const message = messageInput.value.trim();
        if (message) {
            postMessage(message);
            messageInput.value = ''; // Vider le champ après envoi
        }
    });

    // --- Initialisation ---

    // Charger les messages au démarrage
    fetchMessages();

    // Rafraîchir les messages toutes les 5 secondes
    setInterval(fetchMessages, 5000);
});