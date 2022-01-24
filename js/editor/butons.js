import editor from "./editor.js";


var list = document.getElementsByClassName("tasks__list")[0];
var listItems = list.querySelectorAll(".tasks__item");
//var cur_id = listItems[0].querySelector(".validationCustom").id;
for (var i = 0; i < listItems.length; i++) {
    setEventListener(listItems[i]);
}

function openFile(event) { 
    var id = this.parentNode.querySelector(".validationCustom").id;
    if (id != editor.id){
        if (editor.id){
            var items = list.querySelectorAll(".validationCustom");
            var name = "";
            for (var i = 0; i < items.length; i++) {
                if(items[i].id == editor.id){
                    name = items[i].value;
                    break;
                }
            }
            saveFile(name, editor.id);
        };
        editor.id = id;
        makeRequest('textdb.php?' + "type=" + "open" + "&" + "id=" + id, "open");
    }
}

function delFile(event) {  
    var id = this.parentNode.querySelector(".validationCustom").id;
    var param = document.location.href.split("?")[1].split("#")[0];
    makeRequest('textdb.php?' + param + "&" + "type=" + "del" + "&" + "id=" + id, "del");
    list.removeChild(this.parentNode);
}

function saveFile(name, id) {
    var text = editor.current.getValue();
    makeRequest('textdb.php?' + "type=" + "save" + "&" + "id=" + id + "&" + "file_name=" + name + "&" + "file=" + encodeURIComponent(text), "save");
}

function setEventListener(listItem) {  
    var id = listItem.querySelector(".validationCustom").id;
    editor.files = null;
    listItem.querySelector("#openFile").addEventListener('click', openFile);
    listItem.querySelector("#delFile").addEventListener('click', delFile);
}


document.querySelector("#language").addEventListener('click', async e => {

    const sel = document.querySelector("#language").value;
    monaco.editor.setModelLanguage(editor.current.getModel(), sel);
});

export default function makeRequest(url, type) {
    var httpRequest = false;
    if (window.XMLHttpRequest) { // Mozilla, Safari, ...
        httpRequest = new XMLHttpRequest();
        if (httpRequest.overrideMimeType) {
            httpRequest.overrideMimeType('text/xml');
            // Читайте ниже об этой строке
        }
    } else if (window.ActiveXObject) { // IE
        try {
            httpRequest = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
            try {
                httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (e) {}
        }
    }

    if (!httpRequest) {
        alert('Не вышло :( Невозможно создать экземпляр класса XMLHTTP ');
        return false;
    }
    if (type == "open"){
        httpRequest.onreadystatechange = function() { alertContents(httpRequest); };
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "save") {
        httpRequest.onreadystatechange = function() { alertContents1(httpRequest); };  
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "new") {
        httpRequest.onreadystatechange = function() { alertContents1(httpRequest); };  
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "del") {
        httpRequest.onreadystatechange = function() { alertContents1(httpRequest); };  
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "get") {
        httpRequest.onreadystatechange = function() { alertContentsGet(httpRequest); };  
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);
    }

}

function alertContents(httpRequest) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
                editor.current.setValue(httpRequest.responseText);
            } else {
                alert('С запросом возникла проблема.');
            }
        }
    }
    catch( e ) {
        alert('Произошло исключение: ' + e.description);
    }

}

function alertContents1(httpRequest) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
            } else {
                alert('С запросом возникла проблема.' + httpRequest.status);
            }
        }
    }
    catch( e ) {
        alert('Произошло исключение: ' + e.description);
    }
}

function alertContentsGet(httpRequest) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
                editor.t = httpRequest.responseText;
            } else {
                alert('С запросом возникла проблема.');
            }
        }
    }
    catch( e ) {
        alert('Произошло исключение: ' + e.description);
    }

}

document.querySelector("#newFile").addEventListener('click', async e => {
    var name = document.querySelector("#newFile").parentNode.querySelector(".validationCustom").value;
    document.querySelector("#newFile").parentNode.querySelector(".validationCustom").value = "Новый файл";
    var entry = document.createElement('li'); 
    entry.className = "tasks__item list-group-item w-100 d-flex justify-content-between px-0";
    entry.innerHTML = "<div class=\"px-1 align-items-center\" style=\"cursor: move;\"><i class=\"fas fa-file-code fa-lg\"></i></div> <input type=\"text\" class=\"form-control-plaintext form-control-sm validationCustom\" value="+ name + " required> <button type=\"button\" class=\"btn btn-sm mx-0 float-right\" id=\"openFile\"><i class=\"fas fa-edit fa-lg\"></i></button><button type=\"button\" class=\"btn btn-sm float-right\" id=\"delFile\"><i class=\"fas fa-times fa-lg\"></i></button>";

    var param = document.location.href.split("?")[1].split("#")[0];
    entry.querySelector(".validationCustom").id = makeRequest('textdb.php?' + param + "&" + "type=" + "new" + "&" + "file_name=" + name, "new");
    setEventListener(entry);

    document.querySelector("#newFile").parentNode.insertAdjacentElement('beforebegin',entry);
});
//makeRequest('textdb.php?' + "type=" + "open" + "&" + "id=" + cur_id, "open");