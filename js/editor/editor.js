const editor = { current: null, id: null };
var FILES_POSITIONS = [];

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

require.config({ paths: { vs: '../node_modules/monaco-editor/min/vs' } });
require(['vs/editor/editor.main'], function () {
    editor.current = monaco.editor.create(document.getElementById('container'), {
        language: 'cpp',
        insertSpaces: false,
        readOnly: isReadOnly(),
        unicodeHighlight: {
            ambiguousCharacters: false,
        },
    });
    editor.current.layout();
    $('#container').addClass("d-none");
});