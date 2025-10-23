// Detect when sticky element becomes stuck - simple scroll detection
function updateStickyState() {
    const stickyControls = document.querySelector('.sticky-controls');
    if (!stickyControls) return;
    
    const isStuck = window.scrollY > 0;
    stickyControls.classList.toggle('is-stuck', isStuck);
}

document.addEventListener('DOMContentLoaded', function() {
    // Set initial state
    updateStickyState();
    
    // Update on scroll
    window.addEventListener('scroll', updateStickyState, { passive: true });
});