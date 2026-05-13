const tags = [];
const tagBox = document.getElementById('tagBox');
const emailInput = document.getElementById('emailInput');
const emailsHidden = document.getElementById('emailsHidden');
const form = document.getElementById('shareForm');

function addTag(email) {
    email = email.trim().toLowerCase();
    if (!email || tags.includes(email) || !email.includes('@')) return;
    tags.push(email);
    renderTags();
    emailInput.value = '';
}

function removeTag(email) {
    const i = tags.indexOf(email);
    if (i > -1) tags.splice(i, 1);
    renderTags();
}

function renderTags() {
    tagBox.querySelectorAll('.user-tag').forEach(el => el.remove());
    tags.forEach(email => {
        const span = document.createElement('span');
        span.className = 'user-tag';
        span.innerHTML = email + '<button type="button" onclick="removeTag(\'' + email + '\')">×</button>';
        tagBox.insertBefore(span, emailInput);
    });
}

emailInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addTag(this.value);
    }
});

form.addEventListener('submit', function (e) {
    if (emailInput.value.trim()) addTag(emailInput.value);
    if (tags.length === 0) {
        e.preventDefault();
        alert('Please enter at least one email.');
        return;
    }
    emailsHidden.value = tags.join(',');
});