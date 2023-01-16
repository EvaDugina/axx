import editor from "./editor.js";
import apiUrl from "../api.js";
import Sandbox from "../../src/js/sandbox.js";


var list = document.getElementsByClassName("tasks__list")[0];
var listItems = list.querySelectorAll(".tasks__item");
for (var i = 0; i < listItems.length; i++) {
    setEventListener(listItems[i]);
}

function openFile(event) {
    var id = this.parentNode.querySelector(".validationCustom").id;
    if (id != editor.id){
        if (editor.id){
            for (var i = 0; i < listItems.length; i++) {
                listItems[i].className = listItems[i].className.replace(" active_file", "");
            }
            var items = list.querySelectorAll(".validationCustom");
            var name = "";
            for (var i = 0; i < items.length; i++) {
                if(items[i].id == editor.id){
                    name = items[i].value;
                }
                else if(items[i].id == id){
                    listItems[i].className += " active_file";
                }
            }
            saveFile(name, editor.id);
        };
        editor.id = id;
		var param = document.location.href.split("?")[1].split("#")[0];
		if (param == '') param = 'void';
        makeRequest('textdb.php?' + param + "&type=open&id=" + id, "open");
    }
}

function delFile(event) {  
    var id = this.parentNode.querySelector(".validationCustom").id;
    var param = document.location.href.split("?")[1].split("#")[0];
	if (param == '') param = 'void';
    makeRequest('textdb.php?' + param + "&type=del&id=" + id, "del");
    list.removeChild(this.parentNode);
    listItems = list.querySelectorAll(".tasks__item");
}

function saveFile(name, id) {
    var text = editor.current.getValue();
    var param = document.location.href.split("?")[1].split("#")[0];
	if (param == '') param = 'void';
    makeRequest(['textdb.php?' + param + "&type=save&likeid=" + id + "&" + "file_name=" + name, text], "save");
}

function saveEditedFile() {
    var items = list.querySelectorAll(".validationCustom");
    var name = "";
    for (var i = 0; i < items.length; i++) {
        if(items[i].id == editor.id){
            name = items[i].value;
        }
    }

    var text = editor.current.getValue();
    var param = document.location.href.split("?")[1].split("#")[0];
	if (param == '') param = 'void';
    makeRequest(['textdb.php?' + param + "&type=save&likeid=" + editor.id + "&" + "file_name=" + name, text], "save");
}

function setEventListener(listItem) {  
    var id = listItem.querySelector(".validationCustom").id;
    listItem.querySelector("#openFile").addEventListener('click', openFile);
    listItem.querySelector("#delFile").addEventListener('click', delFile);
}

document.querySelector("#language").addEventListener('click', async e => {

    const sel = document.querySelector("#language").value;
    monaco.editor.setModelLanguage(editor.current.getModel(), sel);
});

document.querySelector("#startTools").addEventListener('click', async e => {
	document.querySelector('#startTools').innerText = "Идет проверка...";
	document.querySelector('#startTools').disabled = true;
    saveEditedFile();
    var param = document.location.href.split("?")[1].split("#")[0];
    makeRequest('textdb.php?' + param + "&type=tools" +
				"&build="+document.querySelector("#buildcheck_enabled").checked + 
				"&cppcheck="+document.querySelector("#cppcheck_enabled").checked + 
				"&clang="+document.querySelector("#clangformat_enabled").checked + 
				"&valgrind="+document.querySelector("#valgrind_enabled").checked + 
				"&test="+document.querySelector("#autotest_enabled").checked + 
				"&copy="+document.querySelector("#copydetect_enabled").checked, 
				"tools");
});


function makeRequest(url, type) {
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
        //httpRequest.onreadystatechange = function() { alertContents1(httpRequest); };  
        const body = new FormData();
        body.append('file', url[1]);
        fetch(url[0], {method: "POST", body});
        //httpRequest.open('GET', encodeURI(url), true);
        //httpRequest.send(null);
    }
    else if (type == "new") {
        httpRequest.onreadystatechange = function() { alertContentsNew(httpRequest); };  
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "del") {
        httpRequest.onreadystatechange = function() { alertContents1(httpRequest); };  
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "get") {
        httpRequest.onreadystatechange = function() { alertContentsGet(httpRequest, url[1]); };  
        httpRequest.open('GET', encodeURI(url[0]), false);
        httpRequest.send(null);
    }
    else if (type == "oncheck") {
        httpRequest.onreadystatechange = function() { alertContentsCheck(httpRequest, url); };  
        httpRequest.open('POST', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "tools") {
        httpRequest.onreadystatechange = function() { alertContentsTools(httpRequest, url); };  
        httpRequest.open('POST', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "ws") {
        alert("ws");
        httpRequest.onreadystatechange = function() { alertContents2(httpRequest, url); };  
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

function alertContentsCheck(httpRequest) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
				alert('Новая копия проекта отправлена. Вы продолжаете работу со своей копией');
				document.location.href = "taskchat.php?assignment=" + document.getElementById('check').getAttribute('assignment');
            } else {
                alert('С запросом возникла проблема.' + httpRequest.status);
            }
        }
    }
    catch( e ) {
        alert('Произошло исключение: ' + e.description);
    }
}

function alertContents2(httpRequest) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
                alert(httpRequest.responseText);
            } else {
                alert('С запросом возникла проблема.' + httpRequest.status);
            }
        }
    }
    catch( e ) {
        alert('Произошло исключение: ' + e.description);
    }
}
 
function alertContentsNew(httpRequest) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
                listItems[listItems.length-1].querySelector(".validationCustom").id = httpRequest.responseText;
            } else {
                alert('С запросом возникла проблема.' + httpRequest.status);
            }
        }
    }
    catch( e ) {
        alert('Произошло исключение: ' + e.description);
    }
}


async function alertContentsGet(httpRequest, name) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
                const content = new Blob([httpRequest.responseText], {
                    type: 'text/plain'
                });


                const body = new FormData();
                body.append('files', content, name);
                const user = "sandbox";
                await fetch(`${apiUrl}/sandbox/${Sandbox.id}/upload/${user}`, {method: "POST", body});
            } else {
                alert('С запросом возникла проблема.');
            }
        }
    }
    catch( e ) {
        alert('Произошло исключение: ' + e.description);
    }

}

function showCheckResults(jsonResults) {
	var results = JSON.parse(jsonResults);	
	
    //document.querySelector("#build_result").innerHTML = results.tools.valgrind.enabled;
    // cpp-check

    var cppcheck_summ = 0;

    for (check in results.tools.cppcheck.checks)
    {
        var check_struct = results.tools.cppcheck.checks[check];
        document.querySelector("#cppcheck_" + check_struct.check).innerHTML = check_struct.result;
        cppcheck_summ += check_struct.result;
    }

    var cppcheck_result_color = 'green';

    for (check in results.tools.cppcheck.checks)
    {
        var check_struct = results.tools.cppcheck.checks[check];
        switch (check_struct.outcome)
        {
            case 'fail':
                cppcheck_result_color = 'yellow';
                break;	
            case 'reject':
                cppcheck_result_color = 'red';
                break;		
        }
        if (check_struct.outcome == 'reject')
        {
            break;
        }
    }

    document.querySelector("#cppcheck_result").className = 
	    document.querySelector("#cppcheck_result").className.replace(" rb-red", "").
		    replace(" rb-yellow", "").replace(" rb-green", "") + " rb-" + cppcheck_result_color;

    document.querySelector("#cppcheck_result").innerHTML = cppcheck_summ;

    // clang-format
	var clang_format = (new Map(Object.entries(results.tools))).get("clang-format");
    document.querySelector("#clangformat_result").innerHTML = clang_format.check.result;
    document.querySelector("#clangformat_result_inner").innerHTML = clang_format.check.result;

    // valgrind

    for (check in results.tools.valgrind.checks)
    {
        var check_struct = results.tools.valgrind.checks[check];
        document.querySelector("#valgrind_" + check_struct.check).innerHTML = check_struct.result;
        document.querySelector("#valgrind_" + check_struct.check + "_inner").innerHTML = check_struct.result;
    }

    // copydetect
    document.querySelector("#copydetect_result").innerHTML = results.tools.copydetect.check.result;
    document.querySelector("#copydetect_result_inner").innerHTML = results.tools.copydetect.output;
    //document.querySelector("#copydetect_result_inner").innerHTML = results.tools.copydetect_result.result;

}

function alertContentsTools(httpRequest) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
				showCheckResults(httpRequest.responseText);
				document.querySelector('#startTools').innerText = "ЗАПУСТИТЬ ПРОВЕРКИ";
				document.querySelector('#startTools').disabled = false;
            } else {
                alert('С запросом возникла проблема.' + httpRequest.status);
				document.querySelector('#startTools').innerText = "ЗАПУСТИТЬ ПРОВЕРКИ";
				document.querySelector('#startTools').disabled = false;
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

    var param = document.location.href.split("?")[1].split("#")[0];
	if (param == '') param = 'void';

    entry.innerHTML = '<div class="px-1 align-items-center" style="cursor: move;"><i class="fas fa-file-code fa-lg"></i></div>\
        <input type="text" class="form-control-plaintext form-control-sm validationCustom" id="'+0+'" value="'+name+'" required>\
        <button type="button" class="btn btn-sm mx-0 float-right" id="openFile"><i class="fas fa-edit fa-lg"></i></button>\
        <button type="button" class="btn btn-sm float-right" id="delFile"><i class="fas fa-times fa-lg"></i></button>';
    setEventListener(entry);
    document.querySelector("#newFile").parentNode.insertAdjacentElement('beforebegin',entry);

    listItems = list.querySelectorAll(".tasks__item");
    makeRequest('textdb.php?' + param + "&type=new&file_name=" + name, "new");
});

export {makeRequest, saveEditedFile};