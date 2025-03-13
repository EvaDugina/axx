// import {TEXT_WITH_MARK} from "STRING_CONSTANTS.js";

const TEXT_WITH_MARK = "Задание проверено. \nОценка: ";

const areaSelectCourse = selectCourse.addEventListener(`change`, (e) => {
  const value = document.getElementById("selectCourse").value;
  document.location.href = 'preptable.php?page=' + value;
  //log(`option desc`, desc);
});

$('.toggle-accordion').click(function (e) {
  e.preventDefault();

  // console.log('Нажатие на элемент: ' + $(this).attr("class"));

  accordionClick($(this));
});

function accordionClick(li) {
  if (li.next().hasClass('show')) {
    console.log('Закрытие себя');
    li.next().removeClass('show');
    li.next().slideUp();
    // if (li.hasClass("accordion-button"))
    $('#div-accordion-collapse-' + li[0].id).addClass("collapsed");

  } else {
    console.log('Закрытие всех остальных элементов');
    li.parent().parent().find('div .inner-accordion').removeClass('show');
    li.parent().parent().find('div .inner-accordion').slideUp();
    li.parent().parent().find('div .accordion-button').addClass("collapsed");

    console.log('Открытие себя');
    li.next().toggleClass('show');
    li.next().slideToggle();
    // if (li.hasClass("accordion-button"))
    $('#div-accordion-collapse-' + li[0].id).removeClass("collapsed");

  }
}

function accordionShow($this) {
  if ($this.next().hasClass('show')) {
  } else {
    console.log('Открытие себя');
    $this.next().toggleClass('show');
    $this.next().slideToggle();
  }
}

function accordionHide($this) {
  if ($this.next().hasClass('hide')) {
  } else {
    console.log('Закрытие себя');
    $this.next().removeClass('show');
    $this.next().slideUp();
  }
}



function filterTableByGroupsAndStudents(value) {
  if (value.trim() === '') {
    // console.log("пустое поле");
    $('#table-status-id').find('tbody>tr').show();
    $('#list-messages-id').find('.message').show();
    $('#accordion-student-list').find('.toggle-accordion').each(function () {
      $(this).parent().show();
      accordionHide($(this));
    });
  } else {
    let group_flag = false;
    $('#table-status-id').find('tbody>tr').each(function () {
      if (value == "") {
        $(this).show();
      } else {
        if ($(this).data('type') == "student") {
          // console.log("FIO", $(this).data('student'));
          if ($(this).data('student').toLowerCase().indexOf(value.toLowerCase()) >= 0) {
            // console.log("SHOW!");
            // console.log($(this).data('group'));
            $(this).show();
            $($(this).data('group')).show();
          } else if (!group_flag) {
            $(this).hide();
          }
        } else if ($(this).data('type') == "group") {
          // console.log("GROUP NAME", $(this).data('group'));
          if ($(this).data('group').toLowerCase().indexOf(value.toLowerCase()) >= 0) {
            group_flag = true;
            // console.log("SHOW!");
            $(this).show();
            // console.log($(this).data('value'));

            let next = $(this).next();
            while (next.data('type') == 'student') {
              next.show();
              next = next.next();
            }
          } else {
            group_flag = false;
            // $(this).hide();
          }
        }
      }
    });
    $('#list-messages-id').find('.message').each(function () {
      $(this).toggle($(this).html().toLowerCase().indexOf(value.toLowerCase()) >= 0);
    });
    $('#accordion-student-list').find('.toggle-accordion').each(function () {
      if ($(this).html().toLowerCase().indexOf(value.toLowerCase()) >= 0) {
        $(this).parent().show();
        accordionShow($(this));
      } else {
        $(this).parent().hide();
      }
    });
  }
}


function filterTableByTasks(value) {
  if (value.trim() === '') {
    Array.from($("#table-status-id thead>tr")[1].children).forEach(function (element) {
      element.hidden = false;
    });

    $('#table-status-id').find('tbody>tr').each(function () {
      if ($(this).data('type') == "group") {
        $(this).find('td').each(function () {
          $(this).attr('colspan', array_opened_tasks.length);
        });
      } else if ($(this).data('type') == "student") {
        let index3 = 0;
        $(this).find('td').each(function () {
          if (array_opened_tasks.includes(index3)) {
            $(this).show();
          } else {
            $(this).hide();
          }
          index3++;
        });
      }
    });

  } else {

    // Проверяем совпадение с заданиями
    let array_opened_tasks = [];
    let index1 = 0;
    Array.from($("#table-status-id thead>tr")[1].children).forEach(function (element) {
      if (element.tagName == "TD" && element.dataset.title.toLowerCase().indexOf(value.toLowerCase()) >= 0) {
        array_opened_tasks.push(index1);
      }
      index1++;
    });

    Array.from($("#table-status-id thead>tr")[0].children)[2].colSpan = array_opened_tasks.length;

    let index2 = 0;
    Array.from($("#table-status-id thead>tr")[1].children).forEach(function (element) {
      if (element.tagName == "TD" && !array_opened_tasks.includes(index2)) {
        element.hidden = true;
      } else {
        element.hidden = false;
      }
      index2++;
    });

    // Отображаем нужные колонки
    $('#table-status-id').find('tbody>tr').each(function () {
      if ($(this).data('type') == "group") {
        $(this).find('td').each(function () {
          $(this).attr('colspan', array_opened_tasks.length);
        });
      } else if ($(this).data('type') == "student") {
        let index3 = 0;
        $(this).find('td').each(function () {
          if (array_opened_tasks.includes(index3)) {
            $(this).show();
          } else {
            $(this).hide();
          }
          index3++;
        });
      }
    });

  }
}

function showTdPopover(element) {

  // console.log(element.getAttribute('data-mdb-content'));

  // $('[data-toggle="popover"]').popover();

  $(element).popover({
    html: true,
    delay: 250, // без этого ничего не работает и popover сразу закрывается
    trigger: 'focus',
    placement: 'bottom',
    sanitize: false,
    title: element.getAttribute('title'),
    content: element.getAttribute('data-mdb-content')
  }).popover('show');

  $('.popover-dismiss').popover({
    trigger: 'focus'
  });
}

var assignment_id = null;
var user_id = null;
var sender_user_type = null;
var reply_id = null;

function answerPress(answer_type, message_id, f_assignment_id, f_user_id, mark_type = null, max_mark = null, current_mark = null) {
  assignment_id = f_assignment_id;
  user_id = f_user_id;
  reply_id = message_id;
  // TODO: implement answer
  // console.log('pressed: ', answer_type == 2 ? 'mark' : 'answer', max_mark, message_id);
  if (answer_type == 2) { // mark
    //const dialog = document.getElementById('dialogMark');

    if (current_mark != null && current_mark.trim() != "") {
      $('#dialogCheckTask-div-reject-check').removeClass("d-none");
    } else
      $('#dialogCheckTask-div-reject-check').addClass("d-none");

    if (mark_type == "оценка") {
      $('#dialogCheckTask-div-check-word').addClass("d-none");
      $('#dialogCheckTask-div-mark').removeClass("d-none");
    } else if (mark_type == "зачёт") {
      $('#dialogCheckTask-div-check-word').removeClass("d-none");
      $('#dialogCheckTask-div-mark').addClass("d-none");
    }

    $('#dialogCheckTask-select-mark').empty();

    let option = document.createElement("option");
    option.value = -1;
    option.hidden = true;
    $('#dialogCheckTask-select-mark').append(option);

    for (let i = 1; i <= max_mark; i++) {
      let option = document.createElement("option");
      option.value = i;
      option.innerHTML = i;
      $('#dialogCheckTask-select-mark').append(option);
    }
    // document.getElementById('dialogMarkMarkInput').max = max_mark;
    // document.getElementById('dialogMarkMarkLabel').innerText = 'Оценка (максимум ' + max_mark + ')';
    $('#dialogCheckTask').modal('show');


  } else {
    //const dialog = document.getElementById('dialogAnswer');
    document.getElementById('dialogAnswerMessageId').value = message_id;
    document.getElementById('dialogAnswerText').value = '';
    $('#dialogAnswer').modal('show');
  }
}

function unblockAssignment($assignment_id) {

}




let form_taskCheck = document.getElementById('form-mark');
if (form_taskCheck) {
  form_taskCheck.addEventListener('submit', function (event) {
    event.preventDefault();
    console.log("ОБРАБОТКА НАЖАТИЯ КНОПКИ SUBMIT");
    // Проверка прикреплённых студентов
    // Если задан finish_limit - должны быть и заданы студенты
    let mark = checkMarkInputs('dialogMarkMarkInput');
    if (mark == -1) {
      let error_execution = document.getElementById('error-input-mark');
      error_execution.textContent = "Некорректная оценка";
      error_execution.className = 'error-input active';
      return -1;
    }

    let message = checkMessageInput('dialogMarkText');
    if (message == -1) {
      document.getElementById('label-dialogMarkText').innerHTML = "";
      document.getElementById('dialogMarkText').value = TEXT_WITH_MARK + mark;
      message = TEXT_WITH_MARK + mark;
    }

    sendMessage(form_taskCheck, message, 2, mark);
    //answerSend(form_taskCheck);
    return 1;
  });
}

let form_taskAnswer = document.getElementById('form-answer');
if (form_taskAnswer) {
  form_taskAnswer.addEventListener('submit', function (event) {
    event.preventDefault();

    let message = checkMessageInput('dialogAnswerText');
    if (message == -1) {
      let error_execution = document.getElementById('error-input-mark');
      error_execution.textContent = "Пустое сообщение";
      error_execution.className = 'error-input active';
      return -1;
    }

    sendMessage(form_taskAnswer, message, 0, null);
    //answerSend(form_taskCheck);
    return 1;
  });
}

function checkMarkInputs(id) {
  let input_mark = document.getElementById(id);

  let mark = input_mark.value;
  let max_mark = input_mark.max;

  if (isNaN(parseInt(mark)) || mark <= 0 || mark > max_mark) {
    console.log("Оценка заполнена неверно");
    return -1;
  }

  return mark;
}

function checkMessageInput(id) {
  let input_text = document.getElementById(id);

  let message_text = input_text.value;

  if (!message_text || message_text == "") {
    console.log("Текст сообщения пустой");
    return -1;
  }

  return message_text;
}

// function answerSend(form) {
//     //console.log($(form).find(':submit').getAttribute("class"));
//     $(form)
//         .find(':submit')
//         .attr('disabled', 'disabled')
//         .append(' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
// }

function answerText(answer_text, message_id) {
  console.log('answer: ', answer_text, message_id);
}

function answerMark(answer_text, mark, message_id) {
  console.log('mark: ', answer_text, mark, message_id);
}

function sendMessage(form, userMessage, typeMessage, mark = null, func_success = console.log, func_complete = console.log) {

  var formData = new FormData();
  formData.append('assignment_id', assignment_id);
  formData.append('user_id', user_id);
  formData.append('message_text', userMessage);
  formData.append('type', typeMessage);
  formData.append('flag_preptable', true);
  if (reply_id != null) {
    formData.append('reply_id', reply_id);
  }
  if (typeMessage == 2 && mark) {
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
    dataType: 'html',
    success: console.log("SUCCESS!"),
    complete: function () {
      form.submit();
    }
  });

  return true;
}