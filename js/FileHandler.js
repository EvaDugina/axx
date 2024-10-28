export const MAX_FILE_SIZE = 5242880;

const LANGUAGES = {
    CPP: 'cpp', C: 'c', PYTHON: 'python', JAVASCRIPT: 'javascript', PHP: 'php',
    HTML: 'html', CSS: 'css',
    DEFAULT: 'plaintext'
};

const cpp_language_exts = ["cpp", "h"];
const c_language_exts = ["c"];
const py_language_exts = ["py"];
const php_language_exts = ["php"];
const html_language_exts = ["html"];
const javascript_language_exts = ["javascript"];
const css_language_exts = ["css"];

function findLanguage(file_ext) {
    if (cpp_language_exts.includes(file_ext))
        return LANGUAGES.CPP;
    if (c_language_exts.includes(file_ext))
        return LANGUAGES.C;
    else if (py_language_exts.includes(file_ext))
        return LANGUAGES.PYTHON;
    else if (php_language_exts.includes(file_ext))
        return LANGUAGES.PHP;
    else if (html_language_exts.includes(file_ext))
        return LANGUAGES.HTML;
    else if (javascript_language_exts.includes(file_ext))
        return LANGUAGES.JAVASCRIPT;
    else if (css_language_exts.includes(file_ext))
        return LANGUAGES.CSS;
    else
        return LANGUAGES.DEFAULT;
}


export function getFileExt(file_name) {
    let file_name_splitted = file_name.split(".");
    if (file_name_splitted.length == 2)
        return file_name_splitted[1];
    return "txt";
}

export function getFileLanguage(file_name) {
    return findLanguage(getFileExt(file_name));
}

export function getCMDCompilationCommand(file_ext) {
    if (isGNUCompilation(file_ext))
        return "make";
    if (isPYCompilation(file_ext))
        return "python3";
    return null;

}

function isGNUCompilation(file_ext) {
    if (file_ext == "cpp" || file_ext == "c" || file_ext == "h")
        return true;
    return false;
}

function isPYCompilation(file_ext) {
    if (file_ext == "py")
        return true;
    return false;
}