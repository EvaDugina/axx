function ajaxPageCreate() {
    var formData = new FormData();

    formData.append('flag-createPage', true);

    let page_id = null;

    $.ajax({
        type: "POST",
        url: 'pageedit_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function (response) {
            response = response.replace(/(\r\n|\n|\r)/gm, "").trim();
            response = JSON.parse(response);
            page_id = response.page_id;
            console.log(page_id);
        },
        complete: function () {
        }
    });


    return page_id;
}