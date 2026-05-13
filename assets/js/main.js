document.addEventListener('DOMContentLoaded', function () {

    // ── 1. SIDEBAR TOGGLE (off-canvas on tablets/mobile) ──────────
    const sidebarToggle  = document.getElementById('sidebarToggle');
    const appSidebar     = document.getElementById('appSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        if (!appSidebar) return;
        appSidebar.classList.add('open');
        sidebarOverlay && sidebarOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        if (!appSidebar) return;
        appSidebar.classList.remove('open');
        sidebarOverlay && sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    sidebarToggle  && sidebarToggle.addEventListener('click', function () {
        appSidebar && appSidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    sidebarOverlay && sidebarOverlay.addEventListener('click', closeSidebar);

    // Close on ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    // ── 2. PUBLIC NAV HAMBURGER ────────────────────────────────────
    const hamburger = document.getElementById('hamburger');
    const navLinks  = document.querySelector('.nav-links');
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function () {
            const open = navLinks.classList.toggle('mobile-open');
            hamburger.setAttribute('aria-expanded', open);
        });
        // Close when a nav link is clicked
        navLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navLinks.classList.remove('mobile-open');
                hamburger.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // ── 3. Browser Pop-up for Duplicates ──────────────────────────
    const params = new URLSearchParams(window.location.search);
    if (params.get('error') === 'duplicate') {
        const name = params.get('value');
        alert("Action Denied: The name '" + name + "' is already taken by another account.");
    }

    // ── 4. Submit Confirmation ────────────────────────────────────
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            if (!confirm("Confirm user profile updates?")) {
                e.preventDefault();
            }
        });
    }
});