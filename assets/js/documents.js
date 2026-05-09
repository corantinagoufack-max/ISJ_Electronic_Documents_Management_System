(function () {
    'use strict';
    /* 1. Show browser alert from URL params                               */
    const params = new URLSearchParams(window.location.search);
    const alertType = params.get('alert');
    const alertMsg = params.get('msg');

    if (alertType && alertMsg) {
        const prefix = alertType === 'error' ? 'Error: ' : 'Success: ';
        alert(prefix + decodeURIComponent(alertMsg));

        // Remove the alert params from the URL without reloading the page
        const cleanParams = new URLSearchParams(params);
        cleanParams.delete('alert');
        cleanParams.delete('msg');
        const newUrl = window.location.pathname +
            (cleanParams.toString() ? '?' + cleanParams.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
    }

    /* 2. Delete confirmation  */

    window.confirmDelete = function (docTitle) {
        return window.confirm(
            'Are you sure you want to permanently delete this document?\n\n' +
            '"' + docTitle + '"\n\n' +
            'This action cannot be undone.'
        );
    };

})();