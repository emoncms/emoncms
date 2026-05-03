// Detect when sticky element becomes stuck - simple scroll detection
function updateStickyState() {
    const stickyControls = document.querySelector('.sticky-controls');
    if (!stickyControls) return;

    const isStuck = window.scrollY > 0;
    stickyControls.classList.toggle('is-stuck', isStuck);
}

document.addEventListener('DOMContentLoaded', function() {
    updateStickyState();
    window.addEventListener('scroll', updateStickyState, { passive: true });

    // Dropdown toggle — replaces Bootstrap 2 data-toggle="dropdown"
    document.addEventListener('click', function(e) {
        var toggle = e.target.closest('[data-toggle="dropdown"]');
        if (toggle) {
            e.preventDefault();
            var dropdown = toggle.closest('.dropdown');
            var isOpen = dropdown.classList.contains('open');
            document.querySelectorAll('.dropdown.open').forEach(function(d) {
                d.classList.remove('open');
            });
            if (!isOpen) dropdown.classList.add('open');
        } else if (!e.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown.open').forEach(function(d) {
                d.classList.remove('open');
            });
        }
    });

    // Native dialog modal manager used by module UIs.
    (function initNativeModals() {
        function updateBodyModalState() {
            var openDialog = document.querySelector('dialog.ec-modal[open]');
            document.body.classList.toggle('has-modal-open', !!openDialog);
        }

        function resolveDialog(target) {
            if (!target) return null;
            if (typeof target === 'string') {
                var id = target.charAt(0) === '#' ? target.substring(1) : target;
                target = document.getElementById(id);
            }
            if (!(target instanceof HTMLDialogElement)) return null;
            return target;
        }

        function openModal(target) {
            var dialog = resolveDialog(target);
            if (!dialog || dialog.open) return;
            dialog.showModal();
            updateBodyModalState();
            dialog.dispatchEvent(new CustomEvent('modal:shown'));
        }

        function closeModal(target) {
            var dialog = resolveDialog(target);
            if (!dialog || !dialog.open) return;
            dialog.close();
        }

        window.emoncmsModal = {
            open: openModal,
            close: closeModal,
            isOpen: function(target) {
                var dialog = resolveDialog(target);
                return !!(dialog && dialog.open);
            }
        };

        document.addEventListener('click', function(e) {
            var openBtn = e.target.closest('[data-modal-open]');
            if (openBtn) {
                e.preventDefault();
                openModal(openBtn.getAttribute('data-modal-open'));
                return;
            }

            var closeBtn = e.target.closest('[data-modal-close]');
            if (closeBtn) {
                e.preventDefault();
                var parentDialog = closeBtn.closest('dialog.ec-modal');
                if (parentDialog) closeModal(parentDialog);
            }
        });

        document.querySelectorAll('dialog.ec-modal').forEach(function(dialog) {
            dialog.addEventListener('click', function(e) {
                var rect = dialog.getBoundingClientRect();
                var isBackdropClick = (
                    e.clientX < rect.left ||
                    e.clientX > rect.right ||
                    e.clientY < rect.top ||
                    e.clientY > rect.bottom
                );
                if (isBackdropClick && dialog.dataset.backdrop !== 'static') {
                    closeModal(dialog);
                }
            });

            dialog.addEventListener('close', function() {
                updateBodyModalState();
                dialog.dispatchEvent(new CustomEvent('modal:hidden'));
            });
        });
    })();
});