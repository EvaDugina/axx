

// После первой загрузки скролим страницу вниз
$('body, html').scrollTop($('body, html').prop('scrollHeight'));

$('#user-message').on('input', function() {
    if ($(this).val() != '') {
    $(this).css('height', '88.8px');
    $('body, html').scrollTop($('body, html').prop('scrollHeight'));
  }
  else {
    $(this).css('height', '37.6px');
  }
});


/* Логика скрола на странице
Открываем страницу - страница скролится вниз, чат скролится до последнего непрочитанного сообщения
Отправляем сообщение - чат скролится вниз
Приходит сообщение от собеседника - появляется плашка "Новые сообщения"
*/


// Показывает количество прикрепленных для отправки файлов
$('#user-files').on('change', function() {
  // TODO: Сделать удаление числа, если оно 0
  if (this.files.length != 0)
    $('#files-count').html(this.files.length);
  else
    $('#files-count').html(this.files.length);
});

// Показывает количество прикрепленных для отправки файлов
$('#user-answer-files').on('change', function() {
  // TODO: Сделать удаление числа, если оно 0
  $('#files-answer-count').html(this.files.length);
});




function func_ajax_success(response){
  $("#chat-box").html(response);
      
  if (typeMessage == 1) {
    let now = new Date();
    $("#label-task-status-text").text("Ожидает проверки");
    $("#span-answer-date").text(formatDate(now));
  } else if (typeMessage == 2) {
    let now = new Date();
    $("#flexCheckDisabled").prop("checked", true);
    $("#label-task-status-text").text("Выполнено");
    $("#span-answer-date").text(formatDate(now));
    $("#span-text-mark").text("Оценка: ");
  }
}

function func_ajax_complete(){
  // Скролим чат вниз после отправки сообщения
  $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
}

function formatDate(date) {
  let dayOfMonth = date.getDate();
  let month = date.getMonth() + 1;
  let year = date.getFullYear();
  let hour = date.getHours();
  let minutes = date.getMinutes();
  let diffMs = new Date() - date;
  let diffSec = Math.round(diffMs / 1000);
  let diffMin = diffSec / 60;
  let diffHour = diffMin / 60;

  // форматирование
  year = year.toString().slice(-2);
  month = month < 10 ? '0' + month : month;
  dayOfMonth = dayOfMonth < 10 ? '0' + dayOfMonth : dayOfMonth;
  hour = hour < 10 ? '0' + hour : hour;
  minutes = minutes < 10 ? '0' + minutes : minutes;

  return `${dayOfMonth}.${month}.${year} ${hour}:${minutes}`;
}