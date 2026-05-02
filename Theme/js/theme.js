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
});