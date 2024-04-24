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


export function determinFileLanguage(file_name) {
    let file_name_splitted = file_name.split(".");
    if (file_name_splitted.length != 2)
        throw new Error("не удалось определить язык файла: " + file_name + ". \nНекорректное имя файла!");

    let file_ext = file_name_splitted[1];
    return findLanguage(file_ext);



}