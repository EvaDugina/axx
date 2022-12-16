// import {TEXT_WITH_MARK} from "STRING_CONSTANTS.js";

TEXT_WITH_MARK = "Задание проверено. \nОценка: ";


const areaSelectCourse = selectCourse.addEventListener(`change`, (e) => {
const value = document.getElementById("selectCourse").value;
document.location.href = 'preptable.php?page=' + value;
//log(`option desc`, desc);
});

$('.toggle-accordion').click(function(e) {
e.preventDefault();

console.log('Нажатие на элемент: ' + $(this).attr("class"));

var $this = $(this);
//var $id_icon = "icon-down-right-" + $(this).attr("id");
//var $i = document.getElementById($id_icon);

//console.log($id_icon);
//console.log('Поиск: ' + $i.nodeName);

if ($this.next().hasClass('show')) {
    console.log('Закрытие себя');
    //$i.classList.remove('fa-caret-down');
    //$i.classList.add('fa-caret-right');
    $this.next().removeClass('show');
    $this.next().slideUp();

} else {
    console.log('Закрытие всех остальных элементов');
    $this.parent().parent().find('div .inner-accordion').removeClass('show');
    $this.parent().parent().find('div .inner-accordion').slideUp();
    
    console.log('Открытие себя');
    $this.next().toggleClass('show');
    //$i.classList.remove('fa-caret-right');
    //$i.classList.add('fa-caret-down');
    $this.next().slideToggle();
    
}
});

$(function() {
//$('[data-toggle="tooltip"]').tooltip();
//$('[data-toggle="popover"]').popover();      
});

$(document).ready(function() {
//$("#table-status-id>a").click(function(sender){alert(sender)});
//console.log( "ready!" );
});

function filterTable(value) {
if (value.trim() === '') {
    $('#table-status-id').find('tbody>tr').show();
    $('#list-messages-id').find('.message').show();
} else {
    $('#table-status-id').find('tbody>tr').each(function() {
    $(this).toggle($(this).html().toLowerCase().indexOf(value.toLowerCase()) >= 0);
    });
    $('#list-messages-id').find('.message').each(function() {
    $(this).toggle($(this).html().toLowerCase().indexOf(value.toLowerCase()) >= 0);
    });
}
}

function showPopover(element) {
//console.log(element);

$(element).popover({
    html: true,
    delay: 250,
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

function answerPress(answer_type, message_id, f_assignment_id, f_user_id, max_mark = null) {
    assignment_id = f_assignment_id;
    user_id = f_user_id;
    reply_id = message_id;
    // TODO: implement answer
    // console.log('pressed: ', answer_type == 2 ? 'mark' : 'answer', max_mark, message_id);
    if (answer_type == 2) { // mark
        //const dialog = document.getElementById('dialogMark');
        document.getElementById('dialogMarkMessageId').value = message_id;
        document.getElementById('dialogMarkMarkInput').max = max_mark;
        document.getElementById('dialogMarkMarkLabel').innerText = 'Оценка (максимум ' + max_mark + ')';
        $('#dialogMark').modal('show');
    } else {
        //const dialog = document.getElementById('dialogAnswer');
        document.getElementById('dialogAnswerMessageId').value = message_id;
        document.getElementById('dialogAnswerText').value = '';
        $('#dialogAnswer').modal('show');
    }
}


let form_taskCheck = document.getElementById('form-mark');
if(form_taskCheck){
form_taskCheck.addEventListener('submit', function (event) {
  event.preventDefault();
  console.log("ОБРАБОТКА НАЖАТИЯ КНОПКИ SUBMIT");
  // Проверка прикреплённых студентов
  // Если задан finish_limit - должны быть и заданы студенты
  let mark = checkMarkInputs('dialogMarkMarkInput');
  if(mark == -1) {
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
if(form_taskAnswer){
  form_taskAnswer.addEventListener('submit', function (event) {
    event.preventDefault();

    let message = checkMessageInput('dialogAnswerText');
    if(message == -1) {
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

function checkMarkInputs(id){
    let input_mark = document.getElementById(id);
    
    let mark = input_mark.value;
    let max_mark = input_mark.max;
    
    if (parseInt(mark) == NaN || mark <= 0 || mark > max_mark) {
      console.log("Оценка заполнена неверно");
      return -1;
    }

    return mark;
}

function checkMessageInput(id){
  let input_text = document.getElementById(id);

  let message_text = input_text.value;

  if (!message_text ||  message_text == ""){
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

function sendMessage(form, userMessage, typeMessage, mark=null, func_success=console.log, func_complete=console.log) {
      
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
        dataType : 'html',
        success: console.log("SUCCESS!"),
        complete: function() {
          form.submit();
        }
      });
  
      return true;
  }