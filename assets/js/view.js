document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('previewModal');
    const previewBody = document.getElementById('previewBody');
    const previewTitle = document.getElementById('previewTitle');
    const closeBtn = document.querySelector('.close-modal');

    // Attach click events to all "View" links in the table
    document.querySelectorAll('a[title="View Document"]').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            // Get data from the table row
            const row = this.closest('tr');
            const title = row.querySelector('strong').innerText;
            const fileUrl = this.getAttribute('href'); // e.g., view.php?id=10

            // For a real preview, we often need the actual file path 
            // In a professional DMS, view.php should return the file stream
            openPreview(title, fileUrl);
        });
    });

    function openPreview(title, url) {
        previewTitle.innerText = title;
        previewBody.innerHTML = '<div class="loader">Loading Preview...</div>';
        modal.style.display = 'block';

        // Fetching the preview content
        // Note: For PDFs/Images, we point the source to a handler that outputs the file
        const extension = title.split('.').pop().toLowerCase();

        if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
            previewBody.innerHTML = `<img src="${url}&stream=true" class="preview-img">`;
        } else if (extension === 'pdf') {
            previewBody.innerHTML = `<iframe src="${url}&stream=true" width="100%" height="500px"></iframe>`;
        } else {
            previewBody.innerHTML = `
                <div class="no-preview">
                    <p>Direct preview is not available for .${extension} files.</p>
                    <a href="${url.replace('view.php', 'download.php')}" class="btn-primary">Download to View</a>
                </div>`;
        }
    }

    closeBtn.onclick = () => modal.style.display = 'none';
    window.onclick = (event) => {
        if (event.target == modal) modal.style.display = 'none';
    };
});