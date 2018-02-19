function setup_copy_buttons() {
    $('button.copyurl').each(
        function (i, obj) {
            $(obj).click(function () {
                var id=this.id;
                input = $('#'+id+'_url')[0];
                input.focus();
                input.select();
                document.execCommand("copy");
            });
        }
    );
}

$(function () {
    setup_copy_buttons();
});
