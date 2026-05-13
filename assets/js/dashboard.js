document.addEventListener('DOMContentLoaded', () => {
    // 1. Live Search Filter
    const searchInput = document.querySelector('.search-bar input');
    const tableRows = document.querySelectorAll('.modern-table tbody tr');

    searchInput.addEventListener('input', (e) => {
        const value = e.target.value.toLowerCase();
        tableRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });

    // 2. Tab Navigation
    const tabs = document.querySelectorAll('.header-nav-tabs span');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
        });
    });

    // 3. Simple animation for stats
    const values = document.querySelectorAll('.stat-value');
    values.forEach(val => {
        val.style.opacity = '0';
        setTimeout(() => {
            val.style.transition = 'opacity 1s ease-in';
            val.style.opacity = '1';
        }, 300);
    });
});