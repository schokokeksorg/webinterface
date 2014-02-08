function redirect(status) {
  if (status == "ok") {
    window.location.reload();
  } else {
    window.location.href="../../certlogin/";
  }
}

$(function () {
  $.get("../../certlogin/ajax.php", redirect);
}
);
