function ajaxAssignmentMark(assignment_id, mark, user_id) {
    var formData = new FormData();

    if (assignment_id != null)
        formData.append('assignment_id', assignment_id);
    else
        return;

    formData.append('mark', mark);
    formData.append('user_id', user_id);

    formData.append('flag-markAssignment', true);

    let ajaxResponse = null;

    $.ajax({
        type: "POST",
        url: 'taskassign_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            response = response.replace(/(\r\n|\n|\r)/gm, "").trim();
            ajaxResponse = {};
            ajaxResponse['svg-checked'] = response;
        },
        complete: function () {
        }
    });

    return ajaxResponse;
}