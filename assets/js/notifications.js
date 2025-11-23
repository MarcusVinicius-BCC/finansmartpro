// Notification System
let notificationCheckInterval;

function loadNotifications() {
    fetch('api/notificacoes.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notificacoes);
                updateNotificationCount(data.notificacoes.length);
            }
        })
        .catch(error => console.error('Erro ao carregar notificações:', error));
}

function displayNotifications(notificacoes) {
    const listContainer = document.getElementById('notificationList');
    const emptyMessage = document.getElementById('emptyNotifications');

    // Limpar notificações antigas (mantendo header e divider)
    const items = listContainer.querySelectorAll('.notification-item');
    items.forEach(item => item.remove());

    if (notificacoes.length === 0) {
        emptyMessage.style.display = 'block';
        return;
    }

    emptyMessage.style.display = 'none';

    notificacoes.forEach(notif => {
        const li = document.createElement('li');
        li.className = `notification-item tipo-${notif.tipo} ${notif.lida ? '' : 'unread'}`;
        li.onclick = () => markAsRead(notif.id);

        const iconClass = notif.tipo === 'orcamento' ? 'fa-wallet' : 'fa-bullseye';

        li.innerHTML = `
            <div class="d-flex">
                <div class="notification-icon">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="notification-title">${notif.titulo}</div>
                    <div class="notification-message">${notif.mensagem}</div>
                    <div class="notification-time">${formatNotificationTime(notif.data_criacao)}</div>
                </div>
            </div>
        `;

        listContainer.appendChild(li);
    });
}

function updateNotificationCount(count) {
    const badge = document.getElementById('notificationCount');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

function markAsRead(notificationId) {
    fetch('api/notificacoes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=mark_read&id=${notificationId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
            }
        })
        .catch(error => console.error('Erro ao marcar notificação como lida:', error));
}

function markAllRead() {
    fetch('api/notificacoes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=mark_all_read'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
            }
        })
        .catch(error => console.error('Erro ao marcar todas como lidas:', error));
}

function generateNotifications() {
    fetch('api/notificacoes.php?action=generate')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`${data.notifications_created} notificações geradas`);
                loadNotifications();
            }
        })
        .catch(error => console.error('Erro ao gerar notificações:', error));
}

function formatNotificationTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // diferença em segundos

    if (diff < 60) return 'Agora mesmo';
    if (diff < 3600) return `${Math.floor(diff / 60)} min atrás`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d atrás`;

    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
}

// Inicializar sistema de notificações quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function () {
    // Verificar se o elemento existe (usuário logado)
    if (document.getElementById('notificationCount')) {
        // Carregar notificações imediatamente
        loadNotifications();

        // Gerar novas notificações
        generateNotifications();

        // Atualizar a cada 2 minutos
        notificationCheckInterval = setInterval(loadNotifications, 120000);

        // Gerar notificações a cada 10 minutos
        setInterval(generateNotifications, 600000);
    }
});

// Função global para marcar todas como lidas (chamada do HTML)
window.markAllRead = markAllRead;
