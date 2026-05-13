document.addEventListener('DOMContentLoaded', function () {


    // Restrict unauthorized users
    if (typeof userRole !== "undefined" && userRole !== "Admin" && userRole !== "Standard") {
        alert("Access Denied!\n\nOnly Admin and Standard users are allowed to upload documents.");
        window.location.href = "../../dashboard.php";
        return;
    }

    const fileInput       = document.getElementById('fileInput');
    const dropZone        = document.getElementById('dropZone');
    const browseBtn       = document.getElementById('browseBtn');
    const changeFileBtn   = document.getElementById('changeFileBtn');
    const placeholder     = document.getElementById('dropZonePlaceholder');
    const previewArea     = document.getElementById('dropZonePreview');
    const imagePreview    = document.getElementById('imagePreview');
    const fileIconPreview = document.getElementById('fileIconPreview');
    const fileInfoBox     = document.getElementById('fileInfoBox');
    const uploadForm      = document.getElementById('uploadForm');
    const submitBtn       = document.getElementById('submitBtn');

    const MAX_SIZE_BYTES  = 20 * 1024 * 1024; // 20 MB
    const ALLOWED_EXTS    = ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png', 'zip', 'xlsx', 'pptx', 'txt'];

    const FILE_ICONS = {
        pdf:  '📄', docx: '📝', doc: '📝',
        jpg:  '🖼️', jpeg: '🖼️', png: '🖼️',
        zip:  '🗜️', xlsx: '📊', pptx: '📋',
        txt:  '📃'
    };

    // Open file browser when clicking the drop zone or browse link
    if (browseBtn) browseBtn.addEventListener('click', () => fileInput.click());
    if (dropZone)  dropZone.addEventListener('click', function (e) {
        if (e.target === dropZone || e.target === placeholder || e.target.closest('#dropZonePlaceholder')) {
            fileInput.click();
        }
    });
    if (changeFileBtn) changeFileBtn.addEventListener('click', () => fileInput.click());

    // Drag-and-drop events
    if (dropZone) {
        dropZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
        dropZone.addEventListener('dragleave', function () {
            dropZone.classList.remove('drag-over');
        });
        dropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            const droppedFile = e.dataTransfer.files[0];
            if (droppedFile) {
                applyFile(droppedFile);
            }
        });
    }

    // File input change
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files[0]) applyFile(this.files[0]);
        });
    }

    function getExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }

    function formatSize(bytes) {
        if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        return (bytes / 1024).toFixed(1) + ' KB';
    }

    function applyFile(file) {
        const ext = getExtension(file.name);

        // Client-side validation: file type
        if (!ALLOWED_EXTS.includes(ext)) {
            alert('File type ".' + ext + '" is not allowed.\n\nAccepted types: PDF, DOCX, DOC, JPG, PNG, ZIP, XLSX, PPTX, TXT.');
            fileInput.value = '';
            return;
        }

        // Client-side validation: file size
        if (file.size > MAX_SIZE_BYTES) {
            alert('The selected file is too large (' + formatSize(file.size) + ').\n\nMaximum allowed size is 20 MB.');
            fileInput.value = '';
            return;
        }

        // Transfer file to the actual input if dropped via drag-and-drop
        if (fileInput.files.length === 0 || fileInput.files[0] !== file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
        }

        // Show preview area
        placeholder.style.display  = 'none';
        previewArea.style.display  = 'flex';
        imagePreview.style.display = 'none';
        fileIconPreview.textContent = '';

        if (['jpg','jpeg','png'].includes(ext)) {
            // Image preview
            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                fileIconPreview.style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else {
            // File type icon
            fileIconPreview.textContent = FILE_ICONS[ext] || '📁';
            fileIconPreview.style.display = 'block';
        }

        fileInfoBox.innerHTML =
            '<strong>' + escapeHtml(file.name) + '</strong>' +
            '<span>' + formatSize(file.size) + ' &nbsp;·&nbsp; ' + ext.toUpperCase() + '</span>';

        dropZone.classList.add('file-selected');
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Form submission validation
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            const title      = document.getElementById('title');
            const categoryId = document.getElementById('category_id');

            if (!title || title.value.trim() === '') {
                e.preventDefault();
                alert('Please enter a document title before submitting.');
                if (title) title.focus();
                return;
            }

            if (!categoryId || categoryId.value === '') {
                e.preventDefault();
                alert('Please select a category before submitting.');
                if (categoryId) categoryId.focus();
                return;
            }

            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select or drop a file before submitting.');
                return;
            }

            // Show loading state on the button
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.querySelector('span').textContent = 'Uploading...';
            }
        });
    }

    // Show browser alert if redirected back with an error/success message
    const params = new URLSearchParams(window.location.search);
    const alertType = params.get('alert');
    const alertMsg  = params.get('msg');

    if (alertType && alertMsg) {
        if (alertType === 'error') {
            alert('Error: ' + alertMsg);
        } else if (alertType === 'success') {
            alert(alertMsg);
        }
        // Clean the URL without reloading
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
    }

});
