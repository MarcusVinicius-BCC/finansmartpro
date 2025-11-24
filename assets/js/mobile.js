/**
 * FinanSmart Pro - Mobile Utilities
 * Utilitários para responsividade mobile (sem controle de sidebar - agora em sidebar.js)
 */

(function () {
    'use strict';

    // Swipe gesture para abrir/fechar sidebar
    function setupSwipeGestures() {
        let touchStartX = 0;
        let touchEndX = 0;

        document.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        document.addEventListener('touchend', function (e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const swipeThreshold = 100;
            const sidebar = document.getElementById('sidebar');

            if (!sidebar) return;

            const isOpen = sidebar.classList.contains('show');

            // Swipe right (abrir)
            if (touchEndX > touchStartX + swipeThreshold && !isOpen && touchStartX < 50) {
                if (window.toggleSidebar) window.toggleSidebar();
            }

            // Swipe left (fechar)
            if (touchStartX > touchEndX + swipeThreshold && isOpen) {
                if (window.toggleSidebar) window.toggleSidebar();
            }
        }
    }

    // Tornar tabelas responsivas
    function makeTablesResponsive() {
        const tables = document.querySelectorAll('table:not(.table-responsive table)');

        tables.forEach(table => {
            // Verificar se já está em wrapper
            if (table.parentElement.classList.contains('table-responsive')) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        });
    }

    // Adicionar classe aos botões para mobile
    function setupMobileButtons() {
        const actionButtons = document.querySelectorAll('.d-flex .btn, .text-end .btn');

        actionButtons.forEach(btn => {
            if (window.innerWidth < 576) {
                btn.classList.add('btn-block-mobile');
            }
        });
    }

    // Inicializar
    function init() {
        // Aguardar DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        setupSwipeGestures();
        makeTablesResponsive();
        setupMobileButtons();

        console.log('✅ Mobile utilities inicializado');
    }

    // Auto-executar
    init();

    // Exportar funções úteis
    window.FinanSmartMobile = {
        makeTablesResponsive: makeTablesResponsive
    };
})();
