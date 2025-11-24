// Função para abrir/fechar sidebar (declarada globalmente)
window.toggleSidebar = function () {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');

    if (sidebar && backdrop) {
        const isOpen = sidebar.classList.toggle('show');
        backdrop.classList.toggle('show');

        // Atualizar ícone do botão
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = isOpen ? 'fas fa-times' : 'fas fa-bars';
            }
        }

        // Bloquear scroll do body quando aberto
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    // Criar botão toggle se não existir
    if (!document.querySelector('.sidebar-toggle-btn') && sidebar) {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'sidebar-toggle-btn';
        toggleButton.setAttribute('aria-label', 'Abrir menu');
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        toggleButton.addEventListener('click', toggleSidebar);
        document.body.appendChild(toggleButton);
    }


    if (backdrop) {
        backdrop.addEventListener('click', toggleSidebar);
    }

    // Fechar sidebar quando clicar em um link
    const sidebarLinks = sidebar?.querySelectorAll('.nav-link');
    sidebarLinks?.forEach(link => {
        link.addEventListener('click', () => {
            if (sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        });
    });

    // Fechar sidebar com tecla ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar?.classList.contains('show')) {
            toggleSidebar();
        }
    });
});