var helper = {
  // (A1) ARRAY BUFFER TO BASE 64
  atb : b => {
    let u = new Uint8Array(b), s = "";
    for (let i=0; i<u.byteLength; i++) { s += String.fromCharCode(u[i]); }
    return btoa(s);
  },

  // (A2) BASE 64 TO ARRAY BUFFER
  bta : o => {
    let pre = "=?BINARY?B?", suf = "?=";
    for (let k in o) { if (typeof o[k] == "string") {
      let s = o[k];
      if (s.substring(0, pre.length)==pre && s.substring(s.length - suf.length)==suf) {
        let b = window.atob(s.substring(pre.length, s.length - suf.length)),
        u = new Uint8Array(b.length);
        for (let i=0; i<b.length; i++) { u[i] = b.charCodeAt(i); }
        o[k] = u.buffer;
      }
    } else { helper.bta(o[k]); }}
  },

  // (A3) AJAX FETCH
  ajax : (url, data, after) => {
    let form = new FormData();
    for (let [k,v] of Object.entries(data)) { form.append(k,v); }
    fetch(url, { method: "POST", body: form })
    .then(res => res.text())
    .then(res => after(res))
    .catch(err => { alert("ERROR!"); console.error(err); });
  }
};

function passkey_register() {
  helper.ajax("../loginsecurity/passkey_ajax", {
    req : "getCreateArgs",
    handle : document.getElementById('passkey_handle') ? document.getElementById('passkey_handle').value : null
  }, async (res) => {
    try {
      res = JSON.parse(res);
      helper.bta(res);
      passkey_register_send(await navigator.credentials.create(res));
    } catch (e) { alert("Error"); console.error(e); }
  });
}

function passkey_register_send(cred) {
  helper.ajax("../loginsecurity/passkey_ajax", {
    req : "processCreate",
    transport : cred.response.getTransports ? cred.response.getTransports() : null,
    client : cred.response.clientDataJSON ? helper.atb(cred.response.clientDataJSON) : null,
    attest : cred.response.attestationObject ? helper.atb(cred.response.attestationObject) : null
  }, handle_register_result)
}

function handle_register_result(res) {
    if (res == "OK") {
        // Alles okay
        location.reload();
    } else {
        alert(res);
    }
}

function passkey_validate(login=false) {
  helper.ajax("../loginsecurity/passkey_ajax", {
    req : "getGetArgs"
  }, async (res) => {
    try {
      res = JSON.parse(res);
      helper.bta(res);
      passkey_validate_send(await navigator.credentials.get(res), login);
    } catch (e) { alert("Error"); console.error(e); }
  });
}

// (C2) SEND TO SERVER & VALIDATE
function passkey_validate_send(cred, login) {
    helper.ajax("../loginsecurity/passkey_ajax", {
    req : "processGet",
    login : login,
    id : cred.rawId ? helper.atb(cred.rawId) : null,
    client : cred.response.clientDataJSON ? helper.atb(cred.response.clientDataJSON) : null,
    auth : cred.response.authenticatorData ? helper.atb(cred.response.authenticatorData) : null,
    sig : cred.response.signature ? helper.atb(cred.response.signature) : null,
    user : cred.response.userHandle ? helper.atb(cred.response.userHandle) : null
  }, handle_validate_result)
}

function handle_validate_result(res) {
    if (res == "OK") {
        // Alles okay
        location.reload();
    } else {
        alert(res);
    }
}
