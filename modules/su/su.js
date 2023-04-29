// Without jQuery


ready(() => { 
  /* Do things after DOM has fully loaded */ 
   const searchBox = document.getElementById('query');
    const datalist = document.getElementById("suggestions");

searchBox.addEventListener("input", function(event) { 

    const timer = setTimeout(function () { 
        var sr = event.target.value;
        if (sr.length < 3) {
            return;
        }

        const request = new Request('su_ajax?term='+sr);
        fetch(request)
            .then((response) => response.json())
            .then((data) => {
                if (searchBox.value) { //src not cleaned, backspace removed
                    datalist.replaceChildren(...searchResult(data));
                }
            });

    }, 200);

    window.addEventListener('input', function (e) {
        if (e.inputType == 'insertReplacementText') {
            query = document.querySelector('#query').value;
            if (query[0] == 'u' || query[0] == 'c') {
                window.location.href = "?do="+query;
            }
        }
    }, false);

});

function searchResult(result){
    mylist = [];
    result.forEach((x)=>{
        if(!x)return;
        mylist.push(createListItem(x))
    })

    return mylist;
}

function createListItem(x){
    const option = document.createElement('option') 
    option.value = x.id;
    option.innerText = x.value;
    return option
}



 document.querySelector("#query").focus();
});

