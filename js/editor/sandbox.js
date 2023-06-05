import editor from "./editor.js";
import {makeRequest, saveEditedFile, saveActiveFile} from "./butons.js";
import apiUrl from "../api.js";
import Sandbox from "../../src/js/sandbox.js";
//import alertify from "./alertifyjs/alertify.js";
//запуск в консоли. 

function saveAll() {
	var param = document.location.href.split("?")[1].split("#")[0];
	if (param == '') param = 'void';
    var list = document.getElementsByClassName("tasks__list")[0];
    var items = list.querySelectorAll(".validationCustom");
    var name = "";
    for (var i = 0; i < items.length-1; i++) {
        name = items[i].value;
        if(items[i].id == editor.id){
            var text = editor.current.getValue();
            makeRequest('textdb.php?' + param + "&type=save&id=" + items[i].id + "&file_name=" + name + "&file=" + encodeURIComponent(text), "save");
        }
        else {          
            makeRequest('textdb.php?' + param + "&type=save&id=" + items[i].id + "&file_name=" + name, "save");
        }
    }
}

document.querySelector("#run").addEventListener('click', async e => {
    saveActiveFile();
    saveAll();
	var param = document.location.href.split("?")[1].split("#")[0];
	if (param == '') param = 'void';
    var list = document.getElementsByClassName("tasks__list")[0];
    var items = list.querySelectorAll(".validationCustom");
    var name = "";
    var t = 0;
    for (var i = 0; i < items.length-1; i++) {
        //if(items[i].value.split(".")[items[i].value.split(".").length-1] == "makefile" ^ items[i].value.split(".")[items[i].value.split(".").length-1] == "make"){
        //    t = i;
        //}
        makeRequest(['textdb.php?' + param + "&type=open&id=" + items[i].id, items[i].value], "get");
    }

    //if(t){
        //var resp = await (await fetch(`${apiUrl}/sandbox/${Sandbox.id}/cmd`, {method: "POST", body: JSON.stringify({cmd: "make -f "+items[t].value}), headers: {'Content-Type': 'application/json'}})).json();
    var resp = await (await fetch(`${apiUrl}/sandbox/${Sandbox.id}/cmd`, {method: "POST", body: JSON.stringify({cmd: "make "}), headers: {'Content-Type': 'application/json'}})).json();
    //}
    //alert(resp['stdout']+",\n"+resp['stderr']+",\n"+resp['exitCode']);
    //alert(t);
    var entry = document.createElement("div"); 
    var l = resp['stdout']
    if (resp['stderr']){
        l = resp['stdout']+"\n Ошибка "+resp['stderr'];
    }
    entry.innerHTML = '<pre>Результат Makefile: '+ l +' </pre>';
    document.querySelector("#terminal").insertAdjacentElement('afterend',entry);
});

document.querySelector("#check").addEventListener('click', async e => {
    saveEditedFile();
    var param = document.location.href.split("?")[1].split("#")[0];
	if (param == '') param = 'void';
    makeRequest('textdb.php?' + param + "&type=oncheck", "oncheck");
});

function funonload() {
    var list = document.getElementsByClassName("tasks__list")[0];
    var listItems = list.querySelectorAll(".tasks__item");
    var id = listItems[0].querySelector(".validationCustom").id;
    listItems[0].className += " active_file";
    var param = document.location.href.split("?")[1].split("#")[0];
	if (param == '') param = 'void';
    makeRequest('textdb.php?' + param + "&type=open&id=" + id, "open");
    editor.id = id;
} 
window.onload = funonload;


window.onbeforeunload = closingCode;
function closingCode(){
   saveAll();
   return null;
}