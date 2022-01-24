import editor from "./editor.js";
import apiUrl from "../api.js";
import Sandbox from "../../src/js/sandbox.js";
import makeRequest from "./butons.js";
//запуск в консоли. 
function saveAll() {
    var list = document.getElementsByClassName("tasks__list")[0];
    var items = list.querySelectorAll(".validationCustom");
    var name = "";
    for (var i = 0; i < items.length-1; i++) {
        name = items[i].value;
        if(items[i].id == editor.id){
            var text = editor.current.getValue();
            makeRequest('textdb.php?' + "type=" + "save" + "&" + "id=" + items[i].id + "&" + "file_name=" + name + "&" + "file=" + encodeURIComponent(text), "save");
        }
        else {          
            makeRequest('textdb.php?' + "type=" + "save" + "&" + "id=" + items[i].id + "&" + "file_name=" + name, "save");
        }
    }
}

document.querySelector("#run").addEventListener('click', async e => {
    saveAll();
    var list = document.getElementsByClassName("tasks__list")[0];
    var items = list.querySelectorAll(".validationCustom");
    var name = "";
    for (var i = 0; i < items.length; i++) {
        makeRequest('textdb.php?' + "type=" + "open" + "&" + "id=" + items[i].id, "get");
        const content = new Blob([editor.t], {
            type: 'text/plain'
        });

        const body = new FormData();
        body.append('files', content, items[i].value);
        const user = "sandbox";

        await fetch(`${apiUrl}/sandbox/${Sandbox.id}/upload/${user}`, {method: "POST", body});
    }
});

document.querySelector("#check").addEventListener('click', async e => {
    alert(apiUrl);
    const content = new Blob([editor.current.getValue()], {
        type: 'text/plain'
    });

    const body = new FormData();
    body.append('files', content, "a.c");

    const user = "sandbox";

    await fetch(`${apiUrl}/sandbox/${Sandbox.id}/upload/${user}`, {method: "POST", body});
});

window.onbeforeunload = closingCode;
function closingCode(){
   saveAll();
   return null;
}