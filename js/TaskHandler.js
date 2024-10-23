// import { MAX_FILE_SIZE } from "./FileHandler.js";

var MAX_FILE_SIZE = 5242880;



function ajaxTaskCreate(page_id) {
    var formData = new FormData();

    if (page_id != null)
        formData.append('page_id', page_id);
    else
        return;

    formData.append('flag-createTask', true);

    let task_id = null;

    $.ajax({
        type: "POST",
        url: 'taskedit_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            response = response.replace(/(\r\n|\n|\r)/gm, "").trim();
            response = JSON.parse(response);
            task_id = response.task_id;
        },
        complete: function () {
        }
    });

    return task_id;
}



function ajaxTaskSave(task_id, new_title = null, new_type = null, new_mark_type = null, new_mark_max = null, new_description = null, new_extCodeTest = null, new_codeTest = null, new_codeCheck = null) {
    var formData = new FormData();

    formData.append('task_id', task_id);
    formData.append('action', 'save');

    if (new_title == null && new_type == null && new_mark_type == null && new_mark_max == null && new_description == null && new_extCodeTest == null && new_codeTest == null && new_codeCheck == null)
        return "EMPTY";

    if (new_title != null)
        formData.append('title', new_title);
    if (new_type != null)
        formData.append('type', new_type);
    if (new_mark_type != null)
        formData.append('markType', new_mark_type);
    if (new_mark_max)
        formData.append('markMax', new_mark_max);
    if (new_description != null)
        formData.append('description', new_description);
    if (new_extCodeTest != null)
        formData.append('extCodeTest', new_extCodeTest);
    if (new_codeTest != null)
        formData.append('codeTest', new_codeTest);
    if (new_codeCheck != null)
        formData.append('codeCheck', new_codeCheck);


    let ajaxResponse = null;

    $.ajax({
        type: "POST",
        url: 'taskedit_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            response = response.replace(/(\r\n|\n|\r)/gm, "").trim();
            ajaxResponse = response;
        },
        complete: function () {
        }
    });

    return ajaxResponse;
}



function ajaxTaskAddFiles(task_id, files) {
    var formData = new FormData();

    formData.append('task_id', task_id);
    formData.append('flag-addFiles', true);

    let string_permitted_file_names = [];
    files.forEach((file) => {
        if (file['size'] < MAX_FILE_SIZE * 0.8) {
            formData.append('add-files[]', file);
        } else {
            string_permitted_file_names.push(file['name']);
        }
    });

    let ajaxResponse = {};
    ajaxResponse['permitted_file_names'] = string_permitted_file_names;

    if (formData.getAll('add-files[]').length < 1) {
        return ajaxResponse;
    }

    $.ajax({
        type: "POST",
        url: 'taskedit_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            response = response.replace(/(\r\n|\n|\r)/gm, "").trim();
            ajaxResponse['response'] = response;
        },
        complete: function () {
        }
    });

    return ajaxResponse;
}



function ajaxTaskChangeFileVisibility(task_id, file_id, new_visibility) {
    var formData = new FormData();

    formData.append('task_id', task_id);
    formData.append('file_id', file_id);
    formData.append('new_visibility', new_visibility);
    formData.append('flag-editFileVisibility', true);

    let ajaxResponse = null;

    $.ajax({
        type: "POST",
        url: 'taskedit_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            response = response.replace(/(\r\n|\n|\r)/gm, "").trim();
            ajaxResponse = [];
            if (response != "")
                ajaxResponse['svg'] = response;
        },
        complete: function () {

        }
    });

    return ajaxResponse;
}



function ajaxTaskChangeFileType(task_id, file_id, new_type) {
    var formData = new FormData();

    formData.append('task_id', task_id);
    formData.append('file_id', file_id);
    formData.append('new_type', new_type);
    formData.append('flag-editFileType', true);

    let ajaxResponse = null;

    $.ajax({
        type: "POST",
        url: 'taskedit_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            ajaxResponse = [];
            response = response.replace(/(\r\n|\n|\r)/gm, "").trim();
            if (response != "") {
                if (response == "ERROR: NO_MORE_FILES_CODE")
                    ajaxResponse['error'] = "NO_MORE_FILES_CODE";
                else if (response == "ERROR: EXT_FOR_CODE_PROJECT")
                    ajaxResponse['error'] = "EXT_FOR_CODE_PROJECT";
                else if (response == "ERROR: EXT_FOR_CODE_TEST")
                    ajaxResponse['error'] = "EXT_FOR_CODE_TEST";
                else
                    ajaxResponse['svg'] = response;
            }
        },
        complete: function () {

        }
    });

    return ajaxResponse;
}



function ajaxTaskDeleteFile(task_id, file_id) {
    var formData = new FormData();

    formData.append('task_id', task_id);
    formData.append('file_id', file_id);
    formData.append('flag-deleteFile', true);

    let ajaxResponse = null;

    $.ajax({
        type: "POST",
        url: 'taskedit_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            response = response.replace(/(\r\n|\n|\r)/gm, "").trim();
            ajaxResponse = response;
        },
        complete: function () {

        }
    });

    return ajaxResponse;
}



function ajaxTaskArchive(task_id) {
    var formData = new FormData();

    formData.append('task_id', task_id);
    formData.append('action', 'archive');


    let ajaxResponse = null;

    $.ajax({
        type: "POST",
        url: 'taskedit_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            response = response.replace(/(\r\n|\n|\r)/gm, "").trim();
            ajaxResponse = response;
        },
        complete: function () {
        }
    });

    return ajaxResponse;
}



function ajaxTaskReArchive(task_id) {
    var formData = new FormData();

    formData.append('task_id', task_id);
    formData.append('action', 'rearchive');

    let ajaxResponse = null;

    $.ajax({
        type: "POST",
        url: 'taskedit_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            response = response.replace(/(\r\n|\n|\r)/gm, "").trim();
            ajaxResponse = response;
        },
        complete: function () {

        }
    });

    return ajaxResponse;
}

