// <p class="login_option active" id="login_option_useraccount">Benutzeraccount</p><p class="login_option" id="login_option_mailbox">E-Mail-Postfach</p><p class="login_option" id="login_option_customerno">Kundennummer</p>
//<p class="login_option_help">Über Ihren Benutzeraccount legen Sie alle Einstellungen fest.</p>

function useraccountClicked() {
    document.querySelector('#login_option_useraccount').classList.add("active");
    document.querySelector('#login_option_mailbox').classList.remove("active");
    document.querySelector('#login_option_customerno').classList.remove("active");
    document.querySelector('.login_option_help').innerHTML = 'Über Ihren Benutzeraccount legen Sie alle Einstellungen fest.';
    document.querySelector('#username').previousElementSibling.innerHTML = 'Benutzername:';
}

function mailboxClicked() {
    document.querySelector('#login_option_useraccount').classList.remove("active");
    document.querySelector('#login_option_mailbox').classList.add("active");
    document.querySelector('#login_option_customerno').classList.remove("active");
    document.querySelector('.login_option_help').innerHTML = 'Durch die Anmeldung mit Ihrem Postfach können Sie Ihre Weiterleitungen und Abwesenheitsmeldungen einrichten.';
    document.querySelector('#username').previousElementSibling.innerHTML = 'E-Mail-Adresse:';
}

function customernoClicked() {
    document.querySelector('#login_option_useraccount').classList.remove("active");
    document.querySelector('#login_option_mailbox').classList.remove("active");
    document.querySelector('#login_option_customerno').classList.add("active");
    document.querySelector('.login_option_help').innerHTML = 'Mit der Kundennummer können Sie nur Einstellungen setzen, die nicht den Benutzeraccount betreffen.';
    document.querySelector('#username').previousElementSibling.innerHTML = 'Kundennummer:';
}


ready(() => {
    document.querySelector('#login_option_useraccount').addEventListener('click', useraccountClicked);
    document.querySelector('#login_option_mailbox').addEventListener('click', mailboxClicked);
    document.querySelector('#login_option_customerno').addEventListener('click', customernoClicked);
});
