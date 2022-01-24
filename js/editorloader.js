import editor from "./editor/editor.js";
import "./editor/butons.js";
//import "../src/js/sandbox.js";
import "./editor/sandbox.js";

window.onresize = () => editor.current?.layout();