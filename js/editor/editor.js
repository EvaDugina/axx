import * as Y from 'yjs';
import { WebsocketProvider } from 'y-websocket';
import { MonacoBinding } from 'y-monaco';
import { storeInHash, loadFromHash } from "../hashStorage.js";
import {wsApiUrl, httpApiUrl} from '../api.js';

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



require.config({ paths: { vs: '../../node_modules/monaco-editor/min/vs' } });
require(['vs/editor/editor.main'], function () {
    (async () => {
        let {editorId = null} = loadFromHash();
        if(!editorId) {
            const res = await fetch(`${httpApiUrl}/editor/`, {method: "POST"});
            editorId = (await res.json()).id;
            storeInHash({editorId});
        }
        editor.id = editorId;
    
        const ydocument = new Y.Doc();
        const provider = new WebsocketProvider(`${wsApiUrl}/editor/ws`, editor.id, ydocument);
        const type = ydocument.getText('monaco');

        console.log("Создание Monaco Editor...");
        editor.current = monaco.editor.create(document.getElementById('container'), {
            language: 'cpp',
            insertSpaces: false,
            readOnly: typeof read_only !== 'undefined' ? read_only : false,
            unicodeHighlight: { ambiguousCharacters: false },
            value: ` `,
            minimap: { enabled: false }
        });
        console.log("Monaco Editor создан:", editor.current);
            })();    
        });



// // require(['vs/editor/editor.main'], function () {
// //     editor.current = monaco.editor.create(document.getElementById('container'), {
// //         language: 'cpp',
// //         insertSpaces: false,
// //         readOnly: read_only,
// //         unicodeHighlight: {
// //             ambiguousCharacters: false,
// //         },

// //     });
// //     editor.current.layout();
// //     // $('#container').addClass("d-none");
// });
