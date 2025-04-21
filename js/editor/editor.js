import * as Y from 'yjs';
import { WebsocketProvider } from 'y-websocket';
import { MonacoBinding } from 'y-monaco';
import { storeInHash, loadFromHash } from "../hashStorage.js";
import { wsApiUrl, httpApiUrl } from '../api.js';

const editor = { current: null, id: null };
var FILES_POSITIONS = [];

// Работа с обводкой окна редактора

export function blockEditor() {
    $('#div-shell-editor').removeClass("monaco-border-editable");
    $('#div-shell-editor').addClass("monaco-border-not-editable");
}

export function unblockEditor() {
    $('#div-shell-editor').addClass("monaco-border-editable");
    $('#div-shell-editor').removeClass("monaco-border-not-editable");
}

export function isBlocked() {
    return $('#div-shell-editor').hasClass("monaco-border-not-editable");
}

export function isReadOnly() {
    return IS_EDITABLE;
}

// 
// 
// 

export default editor;

export function getEditorId() {
    return editor.id;
}

export function setEditorId(new_id) {
    editor.id = new_id;
}

export function getEditorValue() {
    return editor.current.getValue();
}

export function setEditorLanguage(language) {
    monaco.editor.setModelLanguage(editor.current.getModel(), language)
}

export function setEditorValue(new_text) {
    editor.current.setValue(new_text);
}

// Работы с позициями курсора в файлах

export function setFocusToCurrent() {
    editor.current.focus();
    editor.current.setPosition(FILES_POSITIONS[getEditorId()]);
    editor.current.revealLineInCenter(FILES_POSITIONS[getEditorId()].lineNumber);
}

export function createFilePosition(file_id) {
    FILES_POSITIONS[file_id] = {
        lineNumber: 0,
        column: 0,
    };
}

export function updateCurrentFilePosition(file_id) {
    FILES_POSITIONS[file_id] = editor.current.getPosition();
}

export function removeFilePosition(file_id) {
    delete FILES_POSITIONS[file_id];
}

function isEditorReady() {
    return editor.current != null;
}

// 
// 
// 

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

export async function waitForEditor() {
    while (!isEditorReady()) {
        await sleep(1000);
    }
}

require.config({ paths: { vs: '../../node_modules/monaco-editor/min/vs' } });
require(['vs/editor/editor.main'], function () {
    (async () => {
        let { editorId = null } = loadFromHash();
        if (!editorId) {
            const res = await fetch(`${httpApiUrl}/editor/`, { method: "POST" });
            editorId = (await res.json()).id;
            storeInHash({ editorId });
        }
        editor.id = editorId;

        const ydocument = new Y.Doc();
        const provider = new WebsocketProvider(`${wsApiUrl}/editor/ws`, editorId, ydocument);
        const type = ydocument.getText('monaco');

        console.log("Создание Monaco Editor...");
        editor.current = monaco.editor.create(document.getElementById('container'), {
            language: 'cpp',
            insertSpaces: false,
            readOnly: isReadOnly(),
            unicodeHighlight: {
                ambiguousCharacters: false,
            },
            minimap: { enabled: false }
        });
        editor.current.layout();
        $('#container').addClass("d-none");

        console.log("Monaco Editor создан:", editor.current);
    })();
});
