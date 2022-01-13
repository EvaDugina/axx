import editor from "./editor.js";
import apiUrl from "../api.js";
import Sandbox from "../../src/js/sandbox.js";

document.querySelector("#run").addEventListener('click', async e => {
    alert(1);
    const content = new Blob([editor.current.getValue()], {
        type: 'text/plain'
    });

    const body = new FormData();
    body.append('files', content, "a.c");

    const user = "sandbox";
    alert(apiUrl);

    await fetch(`${apiUrl}/sandbox/${Sandbox.id}/upload/${user}`, {method: "POST", body});
});