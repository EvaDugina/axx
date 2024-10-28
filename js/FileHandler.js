export const MAX_FILE_SIZE = 5242880;

const LANGUAGES = ajaxGetEditorLanguages();

function ajaxGetEditorLanguages() {
    var formData = new FormData();
    formData.append('flag', "flag-getEditorLanguages");

    var languages = {};
    $.ajax({
        type: "POST",
        url: 'editor_action.php#content',
        cache: false,
        async: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            languages = JSON.parse(response.trim());
        },
        complete: function () {
        }
    });
    return languages;
}

function findLanguage(file_ext) {
    for (let [key, value] of Object.entries(LANGUAGES)) {
        if (value.exts.includes(file_ext) || value.exts.length == 0)
            return value.monaco_editor_name
    }
}


export function getFileExt(file_name) {
    let file_name_splitted = file_name.split(".");
    if (file_name_splitted.length == 2)
        return file_name_splitted[1];
    return "txt";
}

export function getFileLanguageForMonacoEditor(file_name) {
    return findLanguage(getFileExt(file_name));
}

export function getCMDCompilationCommand(file_ext) {
    for (let [key, value] of Object.entries(LANGUAGES)) {
        if (value.exts.includes(file_ext))
            return value.cmd
    }
    return null;
}