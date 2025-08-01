import * as Editor from "./editor.js";
import httpApiUrl from "../api.js";
import Sandbox from "../sandbox.js";
import * as FileHandler from "../FileHandler.js";

export { makeRequest, getActiveFileName, saveEditedFile, saveActiveFile, openFile, synchFilesWithConsole };

var list = document.getElementsByClassName("tasks__list")[0];
var listItems = list.querySelectorAll(".tasks__item");
for (var i = 0; i < listItems.length; i++) {
    setEventListener(listItems[i]);
}

$("#btn-save").on('click', handleButtonSave);
$("#btn-synch").on('click', handleButtonSynch);
$("#btn-new-commit").on('click', addNewCommit);
$("#btn-newFile").on('click', newFile);
$("#div-history-commit-btns").children().each(function () { });

var ARRAY_FILES = [];
listItems.forEach(element => {
    let name = element.querySelector(".validationCustom").value;
    let id = element.querySelector(".validationCustom").id;
    ARRAY_FILES.push(name);
    Editor.createFilePosition(id);
});

var conlist = [];
setSwitchCon();
function setSwitchCon() {
    conlist = document.querySelectorAll(".switchcon");
    for (var i = 0; i < conlist.length; i++) {
        conlist[i].addEventListener('click', async e => {
            if (e.target.className == 'switchcon')
                switchCon(e.target.id);
        });
    }
}

function updateListItems() {
    list = document.getElementsByClassName("tasks__list")[0];
    listItems = list.querySelectorAll(".tasks__item");
    for (var i = 0; i < listItems.length; i++) {
        setEventListener(listItems[i]);
    }
}

function getActiveFileName() {
    if (Editor.getEditorId()) {
        // for (var i = 0; i < listItems.length; i++) {
        //     listItems[i].className = listItems[i].className.replace(" active_file", "");
        // }
        var items = list.querySelectorAll(".validationCustom");
        for (var i = 0; i < items.length; i++) {
            if (items[i].id == Editor.getEditorId()) {
                // listItems[i].className = listItems[i].classList.add("active_file");
                return items[i].value;
            }
        }
    }
    return null;
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function handleButtonSave() {
    $('#btn-save').addClass("active");
    $('#btn-save').prop("disabled", true);
    $('#spinner-save').removeClass("d-none");
    saveActiveFile();
}

async function handleButtonSynch() {
    let svg = document.getElementById('svg-btn-synch');
    $('#btn-synch').prop("disabled", true);
    synchFilesWithConsole();
    svg.animate(
        [
            { transform: 'rotate(0deg)' },
            { transform: 'rotate(360deg)' }
        ],
        {
            duration: 1000, // 1 секунда
            fill: 'forwards', // Сохранить конечное состояние
            easing: 'linear' // Равномерная скорость
        }
    );
    await sleep(1000);
    $('#btn-synch').prop("disabled", false);
}

function synchFilesWithConsole() {
    var param = document.location.href.split("?")[1].split("#")[0];
    if (param == '') param = 'void';

    var list = document.getElementsByClassName("tasks__list")[0];
    var items = list.querySelectorAll(".validationCustom");
    var t = 0;
    let file_names = [];
    for (var i = 0; i < items.length - 1; i++) {
        //if(items[i].value.split(".")[items[i].value.split(".").length-1] == "makefile" ^ items[i].value.split(".")[items[i].value.split(".").length-1] == "make"){
        //    t = i;
        //}
        makeRequest(['textdb.php?' + param + "&type=open&id=" + items[i].id, items[i].value], "get");
        file_names.push(items[i].value);
    }
}

function saveActiveFile() {
    let activeFileName = getActiveFileName();
    saveFile(activeFileName, Editor.getEditorId());
}

function addNewCommit() {
    saveActiveFile();
    var param = document.location.href.split("?")[1].split("#")[0];
    if (param == '') param = 'void';
    makeRequest('textdb.php?' + param + "&type=commit&commit_type=intermediate&status=empty", "commit");
}

function getLanguageByFileName(file_name) {
    return FileHandler.getFileLanguageForMonacoEditor(file_name);
}

function changeEditorLanguage(new_file_language) {
    Editor.setEditorLanguage(new_file_language);
    document.querySelector("#language").value = new_file_language;
}

// function addNewAnswerCommit() {
//     saveActiveFile();
//     var param = document.location.href.split("?")[1].split("#")[0];
//     if (param == '') param = 'void';
//     makeRequest('textdb.php?' + param + "&type=commit&commit_type=answer&status=clone", "commit");
// }

async function openFile(event = null, listItem = null) {

    await Editor.waitForEditor();

    let thisListItem = listItem;
    if (thisListItem == null)
        thisListItem = this;

    Editor.showCode();
    // if (user_role == 3)
    // $('#check').prop("disabled", false);
    var id = thisListItem.querySelector(".validationCustom").id;
    let editor_id = Editor.getEditorId();
    if (editor_id != null)
        Editor.updateCurrentFilePosition(editor_id);

    if (id != editor_id) {

        Editor.blockEditor();

        var items = list.querySelectorAll(".validationCustom");

        if (editor_id) {
            let index = getIndexById(editor_id);
            if (index != null) {
                listItems[index].classList.remove("active_file");

                let name = items[index].value;
                saveFile(name, editor_id);
            }
        }

        editor_id = id;
        Editor.setEditorId(editor_id);
        let index = getIndexById(editor_id);
        listItems[index].classList.add("active_file");
        let input_file = listItems[index].querySelector("#div-fileName > input");

        let new_file_name = input_file.value;
        changeEditorLanguage(getLanguageByFileName(new_file_name));

        var param = document.location.href.split("?")[1].split("#")[0];
        if (param == '') param = 'void';
        makeRequest('textdb.php?' + param + "&type=open&id=" + id, "open");
    } else {
        // Editor.setFocusToCurrent();
    }
}

function getIndexById(id) {
    let items = list.querySelectorAll(".validationCustom");
    for (let i = 0; i < items.length; i++) {
        if (items[i].id == Editor.getEditorId())
            return i;
    }
    return null;

}

function delFile(event) {
    event.stopPropagation();
    var li = this.parentNode;
    var id = li.querySelector(".validationCustom").id;

    // Блокируем редактор, когда удаление текущего файла
    if (id == Editor.getEditorId())
        Editor.blockEditor();

    ARRAY_FILES.splice(parseInt(li.dataset.orderid), 1);
    if (ARRAY_FILES.length < 1)
        $('#check').prop("disabled", true);

    Editor.removeFilePosition(id);

    var param = document.location.href.split("?")[1].split("#")[0];
    if (param == '') param = 'void';
    makeRequest('textdb.php?' + param + "&type=del&id=" + id, "del");
    list.removeChild(this.parentNode);

    listItems = list.querySelectorAll(".tasks__item");
    if (listItems.length > 0) {
        if (id == Editor.getEditorId())
            openFile(null, listItems[0]);
    }
    else {
        Editor.hideCode();
        // if (user_role == 3)
        //     $('#check').prop("disabled", true);
    }
    Editor.setFocusToCurrent();

}

function renameFile(event) {

    let li = this.parentNode.parentNode.parentNode.parentNode;
    let input = li.querySelector(".validationCustom");
    let last_name = input.value;

    input.type = "text";
    input.className = "form-control validationCustom input-file editing";
    input.style.cursor = "text";
    input.setSelectionRange(last_name.length, last_name.length);

    input = removeAllListeners(input);
    input.focus();
    input.setSelectionRange(input.value.length, input.value.length);

    input.addEventListener("keydown", { handleEvent: handleInputFileName, li_id: li.dataset.orderid, input: input, last_name: last_name, type: "keydown" }, true);
    input.addEventListener("blur", { handleEvent: handleInputFileName, li_id: li.dataset.orderid, input: input, last_name: last_name, type: "blur" }, true);
}

function removeAllListeners(element) {
    const newElement = element.cloneNode(true);
    element.parentNode.replaceChild(newElement, element);
    return newElement;
}

var eventListenerLastName = "";

function handleInputFileName(event) {

    if (((event.key == "Enter" && this.type == "keydown") || this.type == "blur") && this.input.classList.contains("editing")) {

        let li_id = parseInt(this.li_id);
        let input = this.input;
        let last_name = this.last_name;

        input.value = input.value.trim();
        let new_name = input.value;
        event.preventDefault();
        event.stopPropagation();

        // console.log(event.target)

        if (eventListenerLastName != new_name) {
            eventListenerLastName = new_name;
        } else {
            input.type = "button";
            input.className = "form-control-plaintext form-control-sm validationCustom input-file not-editing";
            return;
        }

        if (last_name != new_name && !checkOriginalFileName(new_name, li_id)) {
            alert("Введите оригинальное имя файла!");
            input.value = last_name;
        } else {
            input.type = "button";
            input.className = "form-control-plaintext form-control-sm validationCustom input-file not-editing";
            var id = input.id;
            var param = document.location.href.split("?")[1].split("#")[0];
            if (param == '') param = 'void';
            makeRequest('textdb.php?' + param + "&type=rename&new_file_name=" + new_name + "&id=" + id, "rename");

            listItems = list.querySelectorAll(".tasks__item");

            ARRAY_FILES[li_id] = new_name;
        }

        // Смена языка окна редактора кода в зависимости от расширения
        changeEditorLanguage(getLanguageByFileName(new_name));

        // Editor.setFocusToCurrent();

    }
}



function saveFile(name, id) {
    var text = Editor.getEditorValue();
    var param = document.location.href.split("?")[1].split("#")[0];
    if (param == '') param = 'void';
    makeRequest(['textdb.php?' + param + "&type=save&likeid=" + id + "&" + "file_name=" + name, text], "save");
    codeChanges();
}

function saveEditedFile() {
    var items = list.querySelectorAll(".validationCustom");
    var name = "";
    for (var i = 0; i < items.length; i++) {
        if (items[i].id == Editor.getEditorId()) {
            name = items[i].value;
        }
    }

    var text = Editor.getEditorValue();
    var param = document.location.href.split("?")[1].split("#")[0];
    if (param == '') param = 'void';
    makeRequest(['textdb.php?' + param + "&type=save&likeid=" + Editor.getEditorId() + "&" + "file_name=" + name, text], "save");
}

function setEventListener(listItem) {
    var id = listItem.querySelector(".validationCustom").id;

    listItem.addEventListener('click', openFile);

    let btn_delFile = listItem.querySelector("#delFile");
    if (btn_delFile) btn_delFile.addEventListener('click', delFile);

    let btns_renamefile = listItem.querySelector("#a-renameFile");
    if (btns_renamefile) btns_renamefile.addEventListener('click', renameFile);
}

document.querySelector("#language").addEventListener('click', async e => {
    const sel = document.querySelector("#language").value;
    changeEditorLanguage(sel);
});

function start_check() {
    document.querySelector('#startTools').innerText = "Идет проверка...";
    document.querySelector('#startTools').disabled = true;
}

function end_check() {
    document.querySelector('#startTools').innerText = "ЗАПУСТИТЬ ПРОВЕРКИ";
    document.querySelector('#startTools').disabled = false;
}

let startTools = document.querySelector("#startTools");
if (startTools != null) {
    startTools.addEventListener('click', async e => {
        start_check();
        saveEditedFile();
        var param = document.location.href.split("?")[1].split("#")[0];

        let request_text = "";

        let array_tools_elems = {
            "build_enabled": "build",
            "cppcheck_enabled": "cppcheck",
            "clang-format_enabled": "clang-format",
            "valgrind_enabled": "valgrind",
            "catch2_enabled": "catch2",
            "pylint_enabled": "pylint",
            "pytest_enabled": "pytest",
            "copydetect_enabled": "copydetect"
        }

        let all_tools_is_false = true;
        for (const key in array_tools_elems) {
            if (document.querySelector("#" + key)) {
                let is_coosed = document.querySelector("#" + key).checked;
                request_text += "&" + array_tools_elems[key] + "=" + is_coosed;
                if (is_coosed)
                    all_tools_is_false = false;
            }
            else
                request_text += "&" + array_tools_elems[key] + "=false";
        }

        if (all_tools_is_false) {
            end_check();
            alert("Не выбран ни один инструемнт проверки!")
            return;
        }

        makeRequest('textdb.php?' + param + "&type=tools" + request_text, "tools");

        resetCodeChanges();
    });
}


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
            } catch (e) { }
        }
    }

    if (!httpRequest) {
        alert('Не вышло :( Невозможно создать экземпляр класса XMLHTTP ');
        return false;
    }
    if (type == "open") {
        httpRequest.onreadystatechange = function () {
            alertContents(httpRequest);
        };
        httpRequest.open('GET', encodeURI(url), false);
        httpRequest.send(null);
        return 1;
    }
    else if (type == "save") {
        //httpRequest.onreadystatechange = function() { alertContents1(httpRequest); };  
        const body = new FormData();
        body.append('file', url[1])
        fetch(url[0], { method: "POST", body }).then(function (response) {
            $('#spinner-save').addClass("d-none");
            $('#btn-save').removeClass("active");
            $('#btn-save').prop("disabled", false);
        });
    }
    else if (type == "new") {
        httpRequest.onreadystatechange = function () { alertContentsNew(httpRequest); };
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "del") {
        httpRequest.onreadystatechange = function () { alertContents1(httpRequest); };
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "rename") {
        // httpRequest.onreadystatechange = function() { alertContentsRename(httpRequest); };  
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "commit") {
        httpRequest.onreadystatechange = function () {
            if (httpRequest.readyState == 4 && httpRequest.status == 200) {
                // let response = JSON.parse(httpRequest.responseText);
                // window.location = "editor.php?assignment=" + response.assignment_id + "&commit=" + response.commit_id;
                // window.location.href = httpRequest.responceURL;
                document.location.href = "editor.php?assignment=" + document.getElementById('check').getAttribute('assignment');
            }
        };
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);

    }
    else if (type == "get") {
        httpRequest.onreadystatechange = function () { alertContentsGet(httpRequest, url[1]); };
        httpRequest.open('GET', encodeURI(url[0]), false);
        httpRequest.send(null);
    }
    else if (type == "oncheck") {
        httpRequest.onreadystatechange = function () { alertContentsCheck(httpRequest, url); };
        httpRequest.open('POST', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "tools") {
        // alert(encodeURI(url))
        httpRequest.onreadystatechange = function () { alertContentsTools(httpRequest, url); };
        httpRequest.open('POST', encodeURI(url), true);
        httpRequest.send(null);
    }
    else if (type == "ws") {
        alert("ws");
        httpRequest.onreadystatechange = function () { alertContents2(httpRequest, url); };
        httpRequest.open('GET', encodeURI(url), true);
        httpRequest.send(null);
    } else {
        httpRequest.onreadystatechange = function () {
            var con = document.getElementById(type);
            let response = httpRequest.responseText.trim();

            function decodeEscapeSequences(response) {
                return response.replace(/\\n/g, '\n');
            }

            response = decodeEscapeSequences(response);

            // console.log("response", response);

            con.innerHTML = response
        }
        httpRequest.open('POST', encodeURI(url), true);
        httpRequest.send(null);
    }
}

function alertContents(httpRequest) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
                Editor.setEditorValue(httpRequest.responseText.trim());
                if (!Editor.isReadOnly()) {
                    Editor.unblockEditor();
                    Editor.setFocusToCurrent();
                }
            } else {
                alert('С запросом возникла проблема.');
            }
        }
    }
    catch (e) {
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
    catch (e) {
        alert('Произошло исключение: ' + e.description);
    }
}

function alertContentsCheck(httpRequest) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
                // alert('Код отправлен на проверку!');
                // if (user_role == 3)
                //     $('#check').prop("disabled", false);
                $('#dialogSuccess').modal('show');
                // document.location.href = "editor.php?assignment=" + document.getElementById('check').getAttribute('assignment');
                // document.location.reload();
            } else {
                alert('С запросом возникла проблема.' + httpRequest.status);
            }
        }
    }
    catch (e) {
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
    catch (e) {
        alert('Произошло исключение: ' + e.description);
    }
}

function alertContentsNew(httpRequest) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
                let response = JSON.parse(httpRequest.responseText);
                listItems[listItems.length - 1].querySelector(".validationCustom").id = response.file_id;
                listItems[listItems.length - 1].querySelector(".validationCustom").disabled = false;
                listItems[listItems.length - 1].querySelector(".a-save-file").href = response.download_url;
                Editor.createFilePosition(response.file_id);
                listItems[listItems.length - 1].click();
            } else {
                alert('С запросом возникла проблема.' + httpRequest.status);
            }
        }
    }
    catch (e) {
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
                await fetch(`${httpApiUrl}/sandbox/${Sandbox.id}/upload/${user}`, {
                    method: "POST", body
                })
                    .then(response => console.log(response))
                    .catch(error => console.error("CORS Error:", error));;
            } else {
                alert('С запросом возникла проблема.');
            }
        }
    }
    catch (e) {
        alert('Произошло исключение: ' + e.description);
    }
}

function getCheckInfo(checks, checkname) {
    for (var check in checks) {
        var check_struct = checks[check];
        if (check_struct.check == checkname) {
            return check_struct;
        }
    }
}

function parseCheckResult(results) {
    var formData = new FormData();
    formData.append('flag', "flag-getToolsHtml");
    formData.append('config-tools', JSON.stringify(CONFIG_TOOLS));
    formData.append('output-tools', JSON.stringify(results));
    $.ajax({
        type: "POST",
        url: 'editor_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            // console.log(response);
            $('#div-check-results').html(response.trim());
            setSwitchCon();
        },
        complete: function () {
            // Скролим чат вниз при появлении новых сообщений
            // $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
        }
    });
}

function showCheckResults(jsonResults) {

    try {
        var results = JSON.parse(jsonResults);
        console.log(results);
        if ("internal-error" in results) {
            alert('С запросом возникла проблема: \n' + results["internal-error"]);
        } else {
            parseCheckResult(results);
        }

    }
    catch (e) {
        alert('С запросом возникла проблема: ' + 500);
        console.log(jsonResults);
    }
}

function alertContentsTools(httpRequest) {
    try {
        if (httpRequest.readyState == 4) {
            if (httpRequest.status == 200) {
                showCheckResults(httpRequest.responseText.trim());
            } else {
                alert('С запросом возникла проблема: ' + httpRequest.status);
                console.log(httpRequest.responseText.trim());
            }

            end_check();

            // Обновляем все отрытые фотографии подробного вывода
            var conlist = document.querySelectorAll(".switchcon");
            for (var i = 0; i < conlist.length; i++) {
                if (conlist[i].nextSibling.style.display != "none") {
                    switchCon(conlist[i].id);
                }
            }
        }
    }
    catch (e) {
        alert('Произошло исключение: ' + e.description);
    }
}

function newFile() {
    let nameFile = checkNameField();
    if (nameFile != null) {
        var entry = document.createElement('li');
        entry.id = "openFile";
        entry.className = "tasks__item list-group-item w-100 d-flex justify-content-between px-0";
        entry.style.cursor = "pointer";
        entry.dataset.orderid = ARRAY_FILES.length;
        entry.innerHTML = '\
        <div class="px-1 align-items-center text-primary">\
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-medical-fill" viewBox="0 0 16 16">\
                <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zm-3 2v.634l.549-.317a.5.5 0 1 1 .5.866L7 7l.549.317a.5.5 0 1 1-.5.866L6.5 7.866V8.5a.5.5 0 0 1-1 0v-.634l-.549.317a.5.5 0 1 1-.5-.866L5 7l-.549-.317a.5.5 0 0 1 .5-.866l.549.317V5.5a.5.5 0 1 1 1 0zm-2 4.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1zm0 2h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1z"/>\
            </svg>\
        </div>';

        entry.innerHTML += '\
        <div id="div-fileName" class="px-1" style="width: 55%;"> \
        <input type="button" class="form-control-plaintext form-control-sm validationCustom" \
        id="0" value="'+ nameFile + '" disabled style="cursor: pointer; outline:none;">\
        </div>\
        <div class="dropdown align-items-center h-100 me-1" id="btn-group-moreActionsWithFile">\
            <button class="btn btn-primary py-1 px-2" type="button" id="ul-dropdownMenu-moreActionsWithFile"\
            data-mdb-toggle="dropdown" aria-expanded="false">\
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">\
                <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>\
            </svg>\
            </button>\
            <ul class="dropdown-menu" aria-labelledby="ul-dropdownMenu-moreActionsWithFile">\
            <li>\
                <a class="dropdown-item align-items-center" id="a-renameFile">\
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pen-fill" viewBox="0 0 16 16">\
                    <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001z"/>\
                </svg>\
                &nbsp;\
                Переименовать\
                </a>\
            </li>\
            <li>\
                <a class="dropdown-item align-items-center a-save-file" href="" target="_blank">\
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">\
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>\
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>\
                </svg>\
                &nbsp;\
                Скачать\
                </a>\
            </li>\
            </ul>\
        </div>\
        <button type="button" class="btn btn-link float-right mx-1 py-0 px-2" id="delFile"><i class="fas fa-times fa-lg"></i></button>\
        </li>';

        setEventListener(entry);
        document.getElementById("div-add-new-file").insertAdjacentElement('beforebegin', entry);
        listItems = list.querySelectorAll(".tasks__item");

        ARRAY_FILES.push(nameFile);
        $('#check').prop("disabled", false);

        var param = document.location.href.split("?")[1].split("#")[0];
        if (param == '') param = 'void';
        makeRequest('textdb.php?' + param + "&type=new&file_name=" + nameFile, "new");
    }
}

function checkNameField() {
    let nameFile = $('#input-name-newFile').val();
    if (!nameFile) {
        $('#input-name-newFile').addClass("is-invalid");
        $('#div-name-newFile-error').removeClass("d-none");
        $('#div-name-newFile-error').text("Не введено имя файла!");
        return null;
    } else {
        let checkOriginal = checkOriginalFileName(nameFile);
        if (!checkOriginal) {
            $('#input-name-newFile').addClass("is-invalid");
            $('#div-name-newFile-error').removeClass("d-none");
            $('#div-name-newFile-error').text("Файл с таким именем уже существует!");
            return null;
        } else {
            $('#input-name-newFile').removeClass("is-invalid");
            $('#div-name-newFile-error').addClass("d-none");
            $('#input-name-newFile').val("");
            return nameFile;
        }
    }
}
function checkOriginalFileName(nameFile, skipElementInOrder = null) {
    let flag = true;
    let index = 0;
    ARRAY_FILES.forEach(name => {
        if (name == nameFile) {
            if (skipElementInOrder != null || skipElementInOrder != index) {
                flag = false;
                return;
            }
        }
        index++;
    });
    return flag;
}

if (document.querySelector("#newFile")) {
    document.querySelector("#newFile").addEventListener('click', async e => {
        var name = document.querySelector("#newFile").parentNode.querySelector(".validationCustom").value;
        document.querySelector("#newFile").parentNode.querySelector(".validationCustom").value = "Новый файл";
        var entry = document.createElement('li');
        entry.className = "tasks__item list-group-item w-100 d-flex justify-content-between px-0";
        entry.dataset.orderid = ARRAY_FILES.length;

        var param = document.location.href.split("?")[1].split("#")[0];
        if (param == '') param = 'void';

        entry.innerHTML = '\
        <div class="px-1 align-items-center text-primary">\
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-medical-fill" viewBox="0 0 16 16">\
                <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zm-3 2v.634l.549-.317a.5.5 0 1 1 .5.866L7 7l.549.317a.5.5 0 1 1-.5.866L6.5 7.866V8.5a.5.5 0 0 1-1 0v-.634l-.549.317a.5.5 0 1 1-.5-.866L5 7l-.549-.317a.5.5 0 0 1 .5-.866l.549.317V5.5a.5.5 0 1 1 1 0zm-2 4.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1zm0 2h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1z"/>\
            </svg>\
        </div>\
        <textarea type="text" class="form-control-plaintext form-control-sm validationCustom"\
        id="'+ 0 + '" value="' + name + '" style="resize: none;" disabled style="cursor: pointer;" rows="1" cols="13" autofocus autofocus>' + name + '</textarea>';

        //TODO: WIP добавление файла в проект!
        //TODO: WIP переименовывание файла проекта!

        // entry.innerHTML = '<div class="px-1 align-items-center" style="cursor: move;"><i class="fas fa-file-code fa-lg"></i></div>\
        //     <input type="text" class="form-control-plaintext form-control-sm validationCustom" id="'+0+'" value="'+name+'" required>\
        //     <button type="button" class="btn btn-sm mx-0 float-right" id="openFile"><i class="fas fa-edit fa-lg"></i></button>\
        //     <button type="button" class="btn btn-sm float-right" id="delFile"><i class="fas fa-times fa-lg"></i></button>';
        setEventListener(entry);
        document.querySelector("#newFile").parentNode.insertAdjacentElement('beforebegin', entry);
        entry.lastChild.focus();

        listItems = list.querySelectorAll(".tasks__item");
        makeRequest('textdb.php?' + param + "&type=new&file_name=" + name, "new");

    });
}

function switchCon(n) {
    var label = document.getElementById(n);
    let id = label.attributes.for.value;
    var con = document.getElementById(id);
    var displaySetting = con.style.display;

    if (displaySetting == 'block') {
        document.getElementById('button-full-screen-' + id).classList.add("d-none");
        label.innerHTML = '+ показать полный вывод';
        con.style.display = 'none';
    } else {
        document.getElementById('button-full-screen-' + id).classList.remove("d-none");
        label.innerHTML = '&ndash; скрыть полный вывод';
        var param = document.location.href.split("?")[1].split("#")[0];
        makeRequest('textdb.php?' + param + "&type=console&tool=" + id,
            id);
        con.style.display = 'block';
    }
}

function codeChanges() {
    if (Editor.isEditorChanged()) {
        codeChanged();
    }
}

function codeChanged() {
    let span_checks_old = document.getElementById("span-checks-old")
    if (span_checks_old)
        span_checks_old.classList.remove("d-none");
}

function resetCodeChanges() {
    Editor.resetEditorChanges(true);
    let span_checks_old = document.getElementById("span-checks-old")
    if (span_checks_old)
        span_checks_old.classList.add("d-none");
}