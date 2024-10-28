import * as Editor from "./editor.js";
import apiUrl from "../api.js";
import Sandbox from "../../src/js/sandbox.js";
import * as FileHandler from "../FileHandler.js";

export { makeRequest, saveEditedFile, saveActiveFile, openFile };

var list = document.getElementsByClassName("tasks__list")[0];
var listItems = list.querySelectorAll(".tasks__item");
for (var i = 0; i < listItems.length; i++) {
    setEventListener(listItems[i]);
}

$("#btn-save").on('click', handleButtonSave);
$("#btn-commit").on('click', addNewIntermediateCommit);
$("#btn-newFile").on('click', newFile);
$("#div-history-commit-btns").children().each(function () {

});

var array_files = [];
listItems.forEach(element => {
    array_files.push(element.querySelector(".validationCustom").value);
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

export function getActiveFileName() {
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

function handleButtonSave() {
    $('#btn-save').addClass("active");
    $('#btn-save').prop("disabled", true);
    $('#spinner-save').removeClass("d-none");
    saveActiveFile();
}

function saveActiveFile() {
    let activeFileName = getActiveFileName();
    saveFile(activeFileName, Editor.getEditorId());
}

function addNewIntermediateCommit() {
    saveActiveFile();
    var param = document.location.href.split("?")[1].split("#")[0];
    if (param == '') param = 'void';
    makeRequest('textdb.php?' + param + "&type=commit&commit_type=intermediate&status=empty", "commit");
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

function openFile(event = null, listItem = null) {
    let thisListItem = listItem;
    if (thisListItem == null)
        thisListItem = this;

    $('#container').removeClass("d-none");
    if (user_role == 3)
        $('#check').prop("disabled", false);
    var id = thisListItem.querySelector(".validationCustom").id;
    let editor_id = Editor.getEditorId();
    if (id != editor_id) {
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
        let new_file_language = FileHandler.getFileLanguageForMonacoEditor(new_file_name);
        changeEditorLanguage(new_file_language);

        var param = document.location.href.split("?")[1].split("#")[0];
        if (param == '') param = 'void';
        makeRequest('textdb.php?' + param + "&type=open&id=" + id, "open");
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

    array_files.splice(parseInt(li.dataset.orderid), 1);

    var param = document.location.href.split("?")[1].split("#")[0];
    if (param == '') param = 'void';
    makeRequest('textdb.php?' + param + "&type=del&id=" + id, "del");
    list.removeChild(this.parentNode);

    listItems = list.querySelectorAll(".tasks__item");
    if (listItems.length > 0) {
        openFile(null, listItems[0]);

    }
    else {
        $('#container').addClass("d-none");
        if (user_role == 3)
            $('#check').prop("disabled", true);
    }



}

function renameFile(event) {

    let li = this.parentNode.parentNode.parentNode.parentNode;
    let input = li.querySelector(".validationCustom");
    let last_name = input.value;

    input.type = "text";
    input.className = "form-control validationCustom";
    input.style.cursor = "text";
    input.setSelectionRange(last_name.length, last_name.length);
    input.focus();

    input.removeEventListener("keydown", handleInputFileName);

    input.addEventListener("keydown", { handleEvent: handleInputFileName, li_id: li.dataset.orderid, input: input, last_name: last_name, type: "keydown" }, true);
    input.addEventListener("blur", { handleEvent: handleInputFileName, li_id: li.dataset.orderid, input: input, last_name: last_name, type: "blur" }, true);
}

var eventListenerLastName = "";

function handleInputFileName(event) {

    if ((event.key == "Enter" && this.type == "keydown") || this.type == "blur") {

        let li_id = parseInt(this.li_id);
        let input = this.input;
        let last_name = this.last_name;

        let new_name = input.value;
        event.preventDefault();
        event.stopPropagation();

        // console.log(event.target)

        if (eventListenerLastName != new_name) {
            eventListenerLastName = new_name;
        } else {
            input.type = "button";
            input.className = "form-control-plaintext form-control-sm validationCustom";
            input.style.cursor = "pointer";
            return;
        }

        if (last_name != new_name && !checkOriginalFileName(new_name, li_id)) {
            alert("Введите оригинальное имя файла!");
        } else {
            input.type = "button";
            input.className = "form-control-plaintext form-control-sm validationCustom";
            input.style.cursor = "pointer";

            var id = input.id;
            var param = document.location.href.split("?")[1].split("#")[0];
            if (param == '') param = 'void';
            makeRequest('textdb.php?' + param + "&type=rename&new_file_name=" + new_name + "&id=" + id, "rename");

            listItems = list.querySelectorAll(".tasks__item");

            array_files[li_id] = new_name;
        }
    }
}



function saveFile(name, id) {
    var text = Editor.getEditorValue();
    var param = document.location.href.split("?")[1].split("#")[0];
    if (param == '') param = 'void';
    makeRequest(['textdb.php?' + param + "&type=save&likeid=" + id + "&" + "file_name=" + name, text], "save");

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

let startTools = document.querySelector("#startTools");
if (startTools != null) {
    startTools.addEventListener('click', async e => {
        document.querySelector('#startTools').innerText = "Идет проверка...";
        document.querySelector('#startTools').disabled = true;
        saveEditedFile();
        var param = document.location.href.split("?")[1].split("#")[0];

        let request_text = "";
        if (document.querySelector("#buildcheck_enabled"))
            request_text += "&build=" + document.querySelector("#buildcheck_enabled").checked;
        if (document.querySelector("#cppcheck_enabled"))
            request_text += "&cppcheck=" + document.querySelector("#cppcheck_enabled").checked;
        if (document.querySelector("#clangformat_enabled"))
            request_text += "&clang=" + document.querySelector("#clangformat_enabled").checked;
        if (document.querySelector("#valgrind_enabled"))
            request_text += "&valgrind=" + document.querySelector("#valgrind_enabled").checked;
        if (document.querySelector("#pylint_enabled"))
            request_text += "&pylint=" + document.querySelector("#pylint_enabled").checked;
        if (document.querySelector("#pytest_enabled"))
            request_text += "&pytest=" + document.querySelector("#pytest_enabled").checked;
        if (document.querySelector("#autotests_enabled"))
            request_text += "&test=" + document.querySelector("#autotests_enabled").checked;
        if (document.querySelector("#copydetect_enabled"))
            request_text += "&copy=" + document.querySelector("#copydetect_enabled").checked;

        console.log("REQUEST: ", 'textdb.php?' + param + "&type=tools" + request_text)

        makeRequest('textdb.php?' + param + "&type=tools" + request_text, "tools");
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
            con.innerHTML = httpRequest.responseText.trim();
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
                if (user_role == 3)
                    $('#check').prop("disabled", false);
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
                await fetch(`${apiUrl}/sandbox/${Sandbox.id}/upload/${user}`, { method: "POST", body });
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

function parseBuild(results) {

    if (!("outcome" in results.tools.build)) {
        document.querySelector("#build_result").className =
            document.querySelector("#build_result").className.replace(" rb-red", "").
                replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-gray";
        document.querySelector("#build_result").innerHTML = 'Build не удался';
        document.querySelector("#build_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
        return;
    }
    switch (results.tools.build.outcome) {
        case 'pass':
            break;
        case 'fail':
            document.querySelector("#build_result").className =
                document.querySelector("#build_result").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-red";
            document.querySelector("#build_result").innerHTML = 'Ошибка исполнения';
            document.querySelector("#build_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
            return;
        case 'skip':
            document.querySelector("#build_result").className =
                document.querySelector("#build_result").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "");
            document.querySelector("#build_result").innerHTML = '';
            document.querySelector("#build_body").innerHTML = 'Проверка пропущена.';
            return;
    }

    var check_struct = results.tools.build.check;
    var boxColor = '';
    var boxText = '';
    var bodyText = '';

    switch (check_struct.outcome) {
        case 'pass':
            boxColor = 'green';
            boxText = 'Успех';
            bodyText = 'Проект был собран успешно.'
            break;
        case 'reject':
            boxColor = 'red';
            boxText = 'Неудача';
            bodyText = 'В процессе сборки были обнаружены ошибки.'
            break;
        case 'fail':
            boxColor = 'yellow';
            boxText = 'Неудача';
            bodyText = 'Ошибка проверки.'
            break;
    }

    document.querySelector("#build_result").className =
        document.querySelector("#build_result").className.replace(" rb-red", "").
            replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-" + boxColor;
    document.querySelector("#build_result").innerHTML = boxText;
    document.querySelector("#build_body").innerHTML = bodyText;
}

function parseCppCheck(results) {

    if (!("outcome" in results.tools.cppcheck)) {
        document.querySelector("#cppcheck_result").className =
            document.querySelector("#cppcheck_result").className.replace(" rb-red", "").
                replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-gray";
        document.querySelector("#cppcheck_result").innerHTML = 'Build не удался';
        document.querySelector("#cppcheck_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
        return;
    }

    switch (results.tools.cppcheck.outcome) {
        case 'pass':
            break;
        case 'fail':
            document.querySelector("#cppcheck_result").className =
                document.querySelector("#cppcheck_result").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-red";
            document.querySelector("#cppcheck_result").innerHTML = 'Ошибка исполнения';
            document.querySelector("#cppcheck_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
            return;
        case 'skip':
            document.querySelector("#cppcheck_result").className =
                document.querySelector("#cppcheck_result").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "");
            document.querySelector("#cppcheck_result").innerHTML = '';
            document.querySelector("#cppcheck_body").innerHTML = 'Проверка пропущена.';
            return;
    }

    var bodyText = '';
    var sumOfErrors = 0;
    var boxColor = 'green';

    for (var check in results.tools.cppcheck.checks) {
        var check_struct = results.tools.cppcheck.checks[check];
        bodyText += check_struct.check + ' : ' + check_struct.result + '<br>';
        sumOfErrors += check_struct.result;
    }

    for (var check in results.tools.cppcheck.checks) {
        var check_struct = results.tools.cppcheck.checks[check];
        switch (check_struct.outcome) {
            case 'fail':
                boxColor = 'yellow';
                break;
            case 'reject':
                boxColor = 'red';
                break;
        }
        if (check_struct.outcome == 'reject') {
            break;
        }
    }

    document.querySelector("#cppcheck_result").className =
        document.querySelector("#cppcheck_result").className.replace(" rb-red", "").
            replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-" + boxColor;
    document.querySelector("#cppcheck_result").innerHTML = sumOfErrors;
    document.querySelector("#cppcheck_body").innerHTML = bodyText;
}

function parseClangFormat(results) {
    var clang_format = (new Map(Object.entries(results.tools))).get("clang-format");

    if (!("outcome" in clang_format)) {
        document.querySelector("#clangformat_result").className =
            document.querySelector("#clangformat_result").className.replace(" rb-red", "").
                replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-gray";
        document.querySelector("#clangformat_result").innerHTML = 'Build не удался';
        document.querySelector("#clangformat_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
        return;
    }

    switch (clang_format.outcome) {
        case 'pass':
            break;
        case 'fail':
            document.querySelector("#clangformat_result").className =
                document.querySelector("#clangformat_result").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-red";
            document.querySelector("#clangformat_result").innerHTML = 'Ошибка исполнения';
            document.querySelector("#clangformat_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
            return;
        case 'skip':
            document.querySelector("#clangformat_result").className =
                document.querySelector("#clangformat_result").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "");
            document.querySelector("#clangformat_result").innerHTML = '';
            document.querySelector("#clangformat_body").innerHTML = 'Проверка пропущена.';
            return;
    }

    var check_struct = clang_format.check;
    var boxColor = '';

    switch (check_struct.outcome) {
        case 'pass':
            boxColor = 'green';
            break;
        case 'reject':
            boxColor = 'red';
            break;
        case 'fail':
            boxColor = 'yellow';
            break;
    }

    document.querySelector("#clangformat_result").className =
        document.querySelector("#clangformat_result").className.replace(" rb-red", "").
            replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-" + boxColor;
    document.querySelector("#clangformat_result").innerHTML = check_struct.result;
    document.querySelector("#clangformat_body").innerHTML = 'Замечаний линтера: ' + check_struct.result + '<br>';
}

function parseValgrind(results) {

    if (!("outcome" in results.tools.valgrind)) {
        document.querySelector("#valgrind_leaks").className =
            document.querySelector("#valgrind_leaks").className.replace(" rb-red", "").
                replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "");
        document.querySelector("#valgrind_leaks").innerHTML = '';
        document.querySelector("#valgrind_errors").className =
            document.querySelector("#valgrind_errors").className.replace(" rb-red", "").
                replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-gray";
        document.querySelector("#valgrind_errors").innerHTML = 'Build не удался';
        document.querySelector("#valgrind_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
        return;
    }

    switch (results.tools.valgrind.outcome) {
        case 'pass':
            break;
        case 'fail':
            document.querySelector("#valgrind_leaks").className =
                document.querySelector("#valgrind_leaks").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "");
            document.querySelector("#valgrind_leaks").innerHTML = '';
            document.querySelector("#valgrind_errors").className =
                document.querySelector("#valgrind_errors").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-red";
            document.querySelector("#valgrind_errors").innerHTML = 'Ошибка исполнения';
            document.querySelector("#valgrind_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
            return;
        case 'skip':
            document.querySelector("#valgrind_leaks").className =
                document.querySelector("#valgrind_leaks").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "");
            document.querySelector("#valgrind_leaks").innerHTML = '';
            document.querySelector("#valgrind_errors").className =
                document.querySelector("#valgrind_errors").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "");
            document.querySelector("#valgrind_errors").innerHTML = '';
            document.querySelector("#valgrind_body").innerHTML = 'Проверка пропущена.';
            return;
    }

    var leaks = getCheckInfo(results.tools.valgrind.checks, 'leaks');
    var errors = getCheckInfo(results.tools.valgrind.checks, 'errors');
    var leaksColor = '';
    var errorsColor = '';

    var resBody = '';

    switch (leaks.outcome) {
        case 'pass':
            leaksColor = 'green';
            break;
        case 'reject':
            leaksColor = 'red';
            break;
        case 'fail':
            leaksColor = 'yellow';
            break;
    }

    switch (errors.outcome) {
        case 'pass':
            errorsColor = 'green';
            break;
        case 'reject':
            errorsColor = 'red';
            break;
        case 'fail':
            errorsColor = 'yellow';
            break;
    }

    resBody += 'Утечки памяти: ' + leaks.result + '<br>';
    resBody += 'Ошибки памяти: ' + errors.result + '<br>';

    document.querySelector("#valgrind_leaks").className =
        document.querySelector("#valgrind_leaks").className.replace(" rb-red", "").
            replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-" + leaksColor;
    document.querySelector("#valgrind_leaks").innerHTML = leaks.result;
    document.querySelector("#valgrind_errors").className =
        document.querySelector("#valgrind_errors").className.replace(" rb-red", "").
            replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-" + errorsColor;;
    document.querySelector("#valgrind_errors").innerHTML = errors.result;
    document.querySelector("#valgrind_body").innerHTML = resBody;
}

function parseAutoTests(results) {

    if (!("outcome" in results.tools.autotests)) {
        document.querySelector("#autotests_result").className =
            document.querySelector("#autotests_result").className.replace(" rb-red", "").
                replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-gray";
        document.querySelector("#autotests_result").innerHTML = 'Build не удался';
        document.querySelector("#autotests_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
        return;
    }

    switch (results.tools.autotests.outcome) {
        case 'pass':
            break;
        case 'fail':
            document.querySelector("#autotests_result").className =
                document.querySelector("#autotests_result").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-red";
            document.querySelector("#autotests_result").innerHTML = 'Ошибка исполнения';
            document.querySelector("#autotests_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
            return;
        case 'skip':
            document.querySelector("#autotests_result").className =
                document.querySelector("#autotests_result").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "");
            document.querySelector("#autotests_result").innerHTML = '';
            document.querySelector("#autotests_body").innerHTML = 'Проверка пропущена.';
            return;
    }

    var boxColor = '';
    var boxText = '';
    var bodyText = '';

    var check_struct = results.tools.autotests.check;

    switch (check_struct.outcome) {
        case 'pass':
            boxColor = 'green';
            boxText = 'Успех';
            break;
        case 'reject':
            boxColor = 'red';
            boxText = 'Неудача';
            break;
        case 'fail':
            boxColor = 'yellow';
            boxText = 'Неудача';
            break;
    }

    bodyText += 'Тестов провалено: ' + check_struct.errors + '<br>';
    bodyText += 'Проверок провалено: ' + check_struct.failures + '<br>';

    document.querySelector("#autotests_result").className =
        document.querySelector("#autotests_result").className.replace(" rb-red", "").
            replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-" + boxColor;
    document.querySelector("#autotests_result").innerHTML = boxText;
    document.querySelector("#autotests_body").innerHTML = bodyText;
}

function parseCopydetect(results) {

    if (!("outcome" in results.tools.copydetect)) {
        document.querySelector("#copydetect_result").className =
            document.querySelector("#copydetect_result").className.replace(" rb-red", "").
                replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-gray";
        document.querySelector("#copydetect_result").innerHTML = 'Build не удался';
        document.querySelector("#copydetect_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
        return;
    }

    switch (results.tools.copydetect.outcome) {
        case 'pass':
            break;
        case 'fail':
            document.querySelector("#copydetect_result").className =
                document.querySelector("#copydetect_result").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-red";
            document.querySelector("#copydetect_result").innerHTML = 'Ошибка исполнения';
            document.querySelector("#copydetect_body").innerHTML = 'При выполнении проверки произошла критическая ошибка.';
            return;
        case 'skip':
            document.querySelector("#copydetect_result").className =
                document.querySelector("#copydetect_result").className.replace(" rb-red", "").
                    replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "");
            document.querySelector("#copydetect_result").innerHTML = '';
            document.querySelector("#copydetect_body").innerHTML = 'Проверка пропущена.';
            return;
    }

    var boxColor = '';
    var boxText = '';
    var bodyText = 'Пока что тут пусто.';

    var check_struct = results.tools.copydetect.check;

    switch (check_struct.outcome) {
        case 'pass':
            boxColor = 'green';
            break;
        case 'reject':
            boxColor = 'red';
            break;
        case 'fail':
            boxColor = 'yellow';
            break;
    }

    boxText = check_struct.result;

    document.querySelector("#copydetect_result").className =
        document.querySelector("#copydetect_result").className.replace(" rb-red", "").
            replace(" rb-yellow", "").replace(" rb-green", "").replace(" rb-gray", "") + " rb-" + boxColor;
    document.querySelector("#copydetect_result").innerHTML = boxText;
    document.querySelector("#copydetect_body").innerHTML = bodyText;
}

function showCheckResults(jsonResults) {

    var results = JSON.parse(jsonResults);

    parseCheckResult(results);

    // parseBuild(results);
    // parseCppCheck(results);
    // parseClangFormat(results);
    // parseValgrind(results);
    // parseAutoTests(results);
    // parseCopydetect(results);
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

            document.querySelector('#startTools').innerText = "ЗАПУСТИТЬ ПРОВЕРКИ";
            document.querySelector('#startTools').disabled = false;

            // Обновляем все отрытые фотографии подробного вывода
            var conlist = document.querySelectorAll(".switchcon");
            for (var i = 0; i < conlist.length; i++) {
                if (conlist[i].nextSibling.style.display != "none")
                    switchCon(conlist[i].id);
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
        entry.dataset.orderid = array_files.length;
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

        array_files.push(nameFile);

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
    array_files.forEach(name => {
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
        entry.dataset.orderid = array_files.length;

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
    var con = document.getElementById(label.attributes.for.value);
    var displaySetting = con.style.display;

    if (displaySetting == 'block') {
        label.innerHTML = '+ показать полный вывод';
        con.style.display = 'none';
    } else {
        label.innerHTML = '&ndash; скрыть полный вывод';
        var param = document.location.href.split("?")[1].split("#")[0];
        makeRequest('textdb.php?' + param + "&type=console&tool=" + label.attributes.for.value,
            label.attributes.for.value);
        con.style.display = 'block';
    }
}