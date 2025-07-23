import editor from "./editor.js";
import { makeRequest, saveEditedFile, saveActiveFile, openFile, getActiveFileName, synchFilesWithConsole } from "./butons.js";
import { getCMDCompilationCommand, getFileExt } from "../FileHandler.js";
import httpApiUrl from "../api.js";
import Sandbox from "../sandbox.js";
//import alertify from "./alertifyjs/alertify.js";
//запуск в консоли. 

function saveAll() {
    var param = document.location.href.split("?")[1].split("#")[0];
    if (param == '') param = 'void';
    var list = document.getElementsByClassName("tasks__list")[0];
    var items = list.querySelectorAll(".validationCustom");
    var name = "";
    // TODO: Изменить на отправку только commit_id
    // let file_ids = [];
    for (var i = 0; i < items.length - 1; i++) {
        name = items[i].value;
        // file_ids.push(items[i].id);
        if (items[i].id == editor.id) {
            var text = editor.current.getValue();
            makeRequest('textdb.php?' + param + "&type=save&id=" + items[i].id + "&file_name=" + name + "&file=" + encodeURIComponent(text), "save");
        }
        else {
            makeRequest('textdb.php?' + param + "&type=save&id=" + items[i].id + "&file_name=" + name, "save");
        }
    }
}

function getProjectCompilationCommandThroughProjectFiles(file_names) {
    for (var i = 0; i < file_names.length; i++) {
        let fileExt = getFileExt(file_names[i]);
        let cmdCommand = getCMDCompilationCommand(fileExt);
        if (cmdCommand != null)
            return cmdCommand;
    }
    return null;
}

document.querySelector("#run").addEventListener('click', async e => {
    saveActiveFile();
    synchFilesWithConsole();

    let activeFileExt = getFileExt(getActiveFileName());
    let cmdCommand = getCMDCompilationCommand(activeFileExt);
    if (cmdCommand == null)
        cmdCommand = getProjectCompilationCommandThroughProjectFiles(file_names);
    if (cmdCommand == null)
        alert("Не удалось определить команду консоли для сборки проекта!")

    // if (t) {
    //var resp = await (await fetch(`${apiUrl}/sandbox/${Sandbox.id}/cmd`, {method: "POST", body: JSON.stringify({cmd: "make -f "+items[t].value}), headers: {'Content-Type': 'application/json'}})).json();
    // }
    // alert(t);

    // var resp = await (await fetch(`${httpApiUrl}/sandbox/${Sandbox.id}/cmd`, {
    //     method: "POST", body: JSON.stringify({ cmd: "make " }), headers: { 'Content-Type': 'application/json' }
    // })).json();

    var resp = await (await fetch(`${httpApiUrl}/sandbox/${Sandbox.id}/cmd`, {
        method: "POST", body: JSON.stringify({ cmd: cmdCommand }), headers: { 'Content-Type': 'application/json' }
    })).json();

    var entry = document.createElement("div");
    var l = resp['stdout']
    if (resp['stderr']) {
        l = resp['stdout'] + "\n Ошибка " + resp['stderr'];
        // alert(l + "\nEXIT_CODE: " + resp['exitCode']);
    }

    openCity('Console');

    entry.innerHTML = '<pre class="bg-dark text-light mt-2">Результат ' + cmdCommand + ': ' + l + ' </pre>';
    document.querySelector("#terminal").insertAdjacentElement('afterend', entry);
});

document.querySelector("#check").addEventListener('click', async e => {
    saveActiveFile();
    $('#check').prop("disabled", true);
    // setTimeout(function () {
    //     endAnimationButtonCheck();
    // }, 2000);
    // saveEditedFile();
    var param = document.location.href.split("?")[1].split("#")[0];
    if (param == '') param = 'void';
    makeRequest('textdb.php?' + param + "&type=oncheck", "oncheck");
});

function endAnimationButtonCheck() {
    $('#check').prop("disabled", false);
}

function funonload() {
    var list = document.getElementsByClassName("tasks__list")[0];
    var listItems = list.querySelectorAll(".tasks__item");
    if (listItems.length > 0) {
        // var id = listItems[0].querySelector(".validationCustom").id;
        // listItems[0].className += " active_file";
        // var param = document.location.href.split("?")[1].split("#")[0];
        // if (param == '') param = 'void';
        // // makeRequest('textdb.php?' + param + "&type=open&id=" + id, "open");
        // editor.id = id;
        // $('#container').removeClass("d-none");
        openFile(null, listItems[0]);
    }
}
window.onload = funonload;


window.onbeforeunload = closingCode;
function closingCode() {
    saveAll();
    return null;
}