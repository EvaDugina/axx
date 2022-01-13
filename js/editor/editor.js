const defaultEditorValue = `
#include <cstdio>
int main() {
    printf("Hello, world!");
    return 0;
}
`.slice(1,-1);
const defaultEditorName = `main.cpp`
const editor = { current: null, files: null };

export default editor;

require.config({ paths: { vs: '../node_modules/monaco-editor/min/vs' } });
require(['vs/editor/editor.main'], function () {
    editor.current = monaco.editor.create(document.getElementById('container'), {
        //automaticLayout: true,
        value: defaultEditorValue,
        language: 'cpp'
    });
    editor.files = {defaultEditorName: editor.current};
    editor.current.layout();
});