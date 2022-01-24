const editor = { current: null, id: 0, t: null };

export default editor;

require.config({ paths: { vs: '../node_modules/monaco-editor/min/vs' } });
require(['vs/editor/editor.main'], function () {
    editor.current = monaco.editor.create(document.getElementById('container'), {
        language: 'cpp'
    });
    editor.current.layout();
});