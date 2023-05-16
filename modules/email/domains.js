ready(() => {
    document.querySelectorAll('.buttonset input[type=submit]').forEach(e => e.remove());
    document.querySelectorAll('input[type=submit].hidden').forEach(e => e.remove());
    document.querySelectorAll('.buttonset').forEach(el => {
        el.addEventListener('change', (e) => {
            e.currentTarget.closest('form').submit();
        });
    });
    document.querySelectorAll('.autosubmit').forEach(el => {
        el.addEventListener('change', (e) => {
            e.currentTarget.closest('form').submit();
        });
    });
})
