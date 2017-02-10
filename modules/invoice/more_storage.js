$(function() {
  $(".buttonset input[type=submit]").remove();
  $(".buttonset").buttonset();
  
  $(".buttonset input").click( function() {
    $(this).closest("form").submit();
    });
  });
