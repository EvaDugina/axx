
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

function showPopover(element, message_id) {
//console.log(element);
$(element).popover({
    html: true,
    delay: 250,
    trigger: 'focus',
    placement: 'bottom',
    sanitize: false,
    title: element.getAttribute('title'),
    content: element.getAttribute('data-mdb-content')
    })
    //.on('inserted.bs.popover', function(e){
    //    var p = document.getElementById(e.target.getAttribute('aria-describedby'));
    //    $(p).find('a').click(function(args){answerPress(args.currentTarget,message_id);}); 
    //    //$(element).popover('dispose');});
    //})
    .popover('show');
$('.popover-dismiss').popover({
    trigger: 'focus'
});
}

var assignment_id = -1;
var user_id = -1;
var sender_user_type = -1;
function answerPress(answer_type, message_id, max_mark=null, f_assignment_id=null, f_user_id=null, f_sender_user_type=null) {
    assignment_id = f_assignment_id;
    user_id = f_user_id;
    sender_user_type = f_sender_user_type;
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

    console.log("ОБРАБОТКА НАЖАТИЯ КНОПКИ SUBMIT");
    // Проверка прикреплённых студентов
    // Если задан finish_limit - должны быть и заданы студенты
    let check = checkInputs();
    if(check == -1) {
        let error_execution = document.getElementById('error-input-mark');
        error_execution.textContent = "Некорректная оценка";
        error_execution.className = 'error-input active';
        event.preventDefault();
        return -1;
    } else if (check == -2) {
        document.getElementById('label-dialogMarkText').innerHTML = "";
        document.getElementById('dialogMarkText').value = "Задание проверено.";
    } 

    let mark = document.getElementById('dialogMarkMarkInput').value;
    let messsage_text = document.getElementById('dialogMarkText').value;
    sendMessage(messsage_text, null, 2, assignment_id, user_id, sender_user_type, mark, true);
    //answerSend(form_taskCheck);
    return 1;
});
}

function checkInputs(){
    let input_mark = document.getElementById('dialogMarkMarkInput');
    let input_text = document.getElementById('dialogMarkText');

    let mark = input_mark.value;
    let max_mark = input_mark.max;
    let message_text = input_text.value;

    if (parseInt(mark) == NaN || mark <= 0 || mark > max_mark) {
        console.log("Оценка заполнена неверно");
        return -1;
    }

    if (!message_text ||  message_text == ""){
        console.log("Текст сообщения пустой");
        return -2;
    }

    return 1;
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
