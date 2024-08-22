const editor = { current: null, id: 0 };

export default editor;

export function getEditorId() {
    return editor.id;
}

export function getEditorValue() {
    return editor.current.getValue();
}

export function setEditorLanguage(language) {
    monaco.editor.setModelLanguage(editor.current.getModel(), language)
}

export function setEditorId(new_id) {
    editor.id = new_id;
}

export function setEditorValue(new_text) {
    editor.current.setValue(new_text);
}



require.config({ paths: { vs: '../node_modules/monaco-editor/min/vs' } });
require(['vs/editor/editor.main'], function () {
    editor.current = monaco.editor.create(document.getElementById('container'), {
        language: 'cpp',
        insertSpaces: false,
        readOnly: read_only,
        unicodeHighlight: {
            ambiguousCharacters: false,
        },

    });
    editor.current.layout();
    $('#container').addClass("d-none");
});
