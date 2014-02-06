
$(function () {
$("#query").autocomplete({
    source: "su_ajax",
    select: function( event, ui ) {
      if (ui.item) {
        window.location.href = "?do="+ui.item.id;
      }
}
 });
});
