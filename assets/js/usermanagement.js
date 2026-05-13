// Handles real-time filtering of the user table based on name or email.
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('userSearch');
    const userTable = document.getElementById('userTable');

    if (searchInput && userTable) {
        searchInput.addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const rows = userTable.querySelectorAll('tbody tr.user-row');

            rows.forEach(row => {
                const nameCell = row.querySelector('.user-name');
                const emailCell = row.querySelector('.user-email');

                if (nameCell && emailCell) {
                    const nameText = nameCell.textContent.toLowerCase();
                    const emailText = emailCell.textContent.toLowerCase();

                    if (nameText.includes(filter) || emailText.includes(filter)) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                }
            });
        });
    }
});