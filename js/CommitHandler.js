function ajaxCommitDelete(commit_id) {
    var formData = new FormData();

    if (commit_id != null)
        formData.append('commit_id', commit_id);
    else
        return;

    formData.append('flag-deleteCommit', true);

    let ajaxResponse = null;

    $.ajax({
        type: "POST",
        url: 'editor_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            ajaxResponse = "SUCCESS";
        },
        complete: function () {
        }
    });

    return ajaxResponse;
}