ready(() => {
    if (document.querySelector('#clear')) {
        document.querySelector('#clear').addEventListener("click", () => { 
            document.querySelector('#filter').value = '';
            document.querySelector('#vhosts_filter').submit();
        });
    }
});
