function sendMessage(userMessage, userFiles, typeMessage, 
assignment_id, user_id, sender_user_type, mark=null, flag_preptable=false, func_success=console.log, func_complete=console.log) {
    if ($.trim(userMessage) == '' && userFiles.val() == '') { 
    //   console.log("ПОЛНОСТЬЮ ПУСТОЕ СООБЩЕНИЕ");
      return false; 
    }
    
    var formData = new FormData();
    formData.append('assignment_id', assignment_id);
    formData.append('user_id', user_id);
    formData.append('sender_user_type', sender_user_type);
    formData.append('message_text', userMessage);
    formData.append('type', typeMessage);
    formData.append('flag_preptable', flag_preptable);
    if(userFiles){
      formData.append('MAX_FILE_SIZE', 5242880); // TODO Максимальный размер загружаемых файлов менять тут. Сейчас 5мб
      $.each(userFiles[0].files, function(key, input) {
        if (typeMessage == 0)
          formData.append('message_files[]', input);
        else if (typeMessage == 1)
          formData.append('answer_files[]', input);
      });
    } else if (typeMessage == 2 && mark) {
      formData.append('mark', mark);
    }

    console.log('message_text =' + userMessage);
    console.log('type =' + typeMessage);
    console.log(Array.from(formData));

    $.ajax({
      type: "POST",
      url: 'taskchat_action.php #content',
      cache: false,
      contentType: false,
      processData: false,
      data: formData,
      dataType : 'html',
      success: console.log("SUCCESS!"),
      complete: console.log("COMPLETE!")
    });

    return true;
}