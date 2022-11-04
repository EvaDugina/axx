const editor = { current: null, id: 0};

export default editor;

require.config({ paths: { vs: '../node_modules/monaco-editor/min/vs' } });
require(['vs/editor/editor.main'], function () {
    editor.current = monaco.editor.create(document.getElementById('container'), {
        language: 'cpp',
		insertSpaces: false
    });
    editor.current.layout();
});
