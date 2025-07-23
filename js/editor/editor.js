import * as Y from 'yjs';
import { WebsocketProvider } from 'y-websocket';
import { MonacoBinding } from 'y-monaco';
import { storeInHash, loadFromHash } from "../hashStorage.js";
import { wsApiUrl, httpApiUrl } from '../api.js';

const editor = { current: null, id: null };
var FILES_POSITIONS = [];
var PREVIOUS_VALUE = null;
var IS_CHANGED = false;
var IS_READY = false;

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

export function hideCode() {
    $('#container').addClass("d-none");
}

export function showCode() {
    $('#container').removeClass("d-none");
}

export function isReadOnly() {
    return IS_EDITABLE;
}

// 
// 
// 

export default editor;

export function isEditorChanged() {
    return IS_CHANGED;
}

export function getEditorId() {
    return editor.id;
}

export function setEditorId(new_id) {
    editor.id = new_id;
    resetEditorChanges()
}

export function setEditorPreviousValue(previous_value) {
    PREVIOUS_VALUE = previous_value;
}

export function getEditorValue() {
    return editor.current.getValue();
}

export function setEditorLanguage(language) {
    monaco.editor.setModelLanguage(editor.current.getModel(), language)
}

export function setEditorValue(new_text) {
    if (PREVIOUS_VALUE == null)
        PREVIOUS_VALUE = new_text;
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

export function resetEditorChanges(isChecksStart = false) {
    setEditorPreviousValue(null)
    IS_CHANGED = false;
    if (isChecksStart)
        setEditorPreviousValue(getEditorValue());
}

function isEditorReady() {
    return IS_READY;
}

function isFocused() {
    return editor.current.hasTextFocus();
}

// 
// 
// 

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

export async function waitForEditor() {
    while (!isEditorReady()) {
        // console.log("Ожидание запуска реадктора кода...")
        await sleep(500);
    }
}

export async function loadEditor() {
    console.log("Загрузка редактора кода...")
    document.getElementById("spinner-load-editor").classList.remove("d-none");
    await waitForEditor();
    document.getElementById("spinner-load-editor").classList.add("d-none");
    console.log("Редактор кода загружен!")

}


require.config({
    paths: { vs: './node_modules/monaco-editor/min/vs' }
});
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

        // console.log("Создание Monaco Editor...");
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

        // Добавление команды для сочетания клавиш Ctrl + S
        editor.current.addCommand(monaco.KeyCode.KeyS | monaco.KeyMod.CtrlCmd, function () {
            $('#btn-save').click();
        });

        editor.current.onDidChangeModelContent((event) => {
            let newValue = editor.current.getValue();

            if (PREVIOUS_VALUE == null || PREVIOUS_VALUE == newValue)
                return;

            event.changes.forEach(change => {
                // console.log('Изменение:', {
                //     from: change.range.startLineNumber,
                //     to: change.range.endLineNumber,
                //     text: change.text
                // });
            });

            setEditorPreviousValue(newValue);
            IS_CHANGED = true;
        });

        IS_READY = true;
        hideCode();

        console.log("Monaco Editor создан:", editor.current);
    })();
});

loadEditor();