$(function() {
  $(".buttonset input[type=submit]").remove();
  $(".buttonset").buttonset();
  $(".buttonset .disabled").buttonset("option", "disabled", true);
  
  $(".buttonset input").click( function() {
    $(this).closest("form").submit();
    });
  });
