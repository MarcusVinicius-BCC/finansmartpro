document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');


    if (backdrop) {
        backdrop.addEventListener('click', toggleSidebar);
    }

    // Fechar sidebar quando clicar em um link (mobile)
    const sidebarLinks = sidebar?.querySelectorAll('.nav-link');
    sidebarLinks?.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        });
    });

    // Adicionar botão de toggle no mobile se não existir
    /* if (!toggleBtn && sidebar) {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'sidebar-toggle btn btn-primary position-fixed';
        toggleButton.style.cssText = 'top: 1rem; left: 1rem; z-index: 1029;';
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        toggleButton.addEventListener('click', toggleSidebar);
        document.body.appendChild(toggleButton);
    } */
});