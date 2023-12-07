function passkey_login() {
    passkey_validate(true);
}


async function check_passkey() {
    if (window.PublicKeyCredential &&  
        PublicKeyCredential.isConditionalMediationAvailable) {
        const newButton = document.createElement('button');
        newButton.textContent = 'Mit Passkey/FIDO2-Gerät anmelden!';
        const newPara = document.createElement('p');
        newPara.appendChild(newButton)
        certlogin = document.getElementById("certlogin")
        certlogin.parentNode.insertBefore(newPara, certlogin)
        newButton.addEventListener('click', passkey_login);
    }
}


ready(() => {
    check_passkey();
});
