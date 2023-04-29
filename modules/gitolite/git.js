function setup_copy_buttons() {
    document.querySelectorAll('button.copyurl').forEach(
        obj => {
            obj.addEventListener("click", (e) => {
                var id=e.currentTarget.id;
                input = document.querySelector('#'+id+'_url');
                input.focus();
                input.select();
                document.execCommand("copy");
            });
        }
    );
}

ready(() => {
    setup_copy_buttons();
});
