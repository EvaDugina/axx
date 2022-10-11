let form_taskEdit  = document.getElementById('form-taskEdit');

let input_Title = document.getElementById('input-title');
let error_Title = document.getElementById('error-input-title');

let textArea_Description = document.getElementById('textArea-description');
let error_Description = document.getElementById('error-textArea-description');

let inputRadio_individual = document.getElementById('input-deligate-by-individual');
let inputRadio_group = document.getElementById('input-deligate-by-group');

let button_save = document.getElementById('submit-save');
let button_delete = document.getElementById('submit-delete');


if(input_Title){
  input_Title.addEventListener('input', function (event) {
    // Каждый раз, когда пользователь что-то вводит,
    // мы проверяем, являются ли поля формы валидными

    if (input_Title.value) {
      // Если на момент валидации какое-то сообщение об ошибке уже отображается,
      // если поле валидно, удаляем сообщение
      error_Title.textContent = ''; // Сбросить содержимое сообщения
      error_Title.className = 'error-input'; // Сбросить визуальное состояние сообщения
    } else {
      // Если поле не валидно, показываем правильную ошибку
      showError();
    }
  });
}

if(textArea_Description){
  textArea_Description.addEventListener('input', function (event) {
    // Каждый раз, когда пользователь что-то вводит,
    // мы проверяем, являются ли поля формы валидными

    if (textArea_Description.value) {
      // Если на момент валидации какое-то сообщение об ошибке уже отображается,
      // если поле валидно, удаляем сообщение
      error_Description.textContent = ''; // Сбросить содержимое сообщения
      error_Description.className = 'error-input'; // Сбросить визуальное состояние сообщения
    } else {
      // Если поле не валидно, показываем правильную ошибку
      showError();
    }
  });
}

if(form_taskEdit){
  form_taskEdit.addEventListener('submit', function (event) {

    // Если нажата кнопка "Сохранить"
    button_save.addEventListener('click', function (event) {
      if(!input_Title.value /*|| !textArea_Description.value*/) {
        // Если поля не заполнены, отображаем соответствующее сообщение об ошибке
        showError();
        // Затем предотвращаем стандартное событие отправки формы
        event.preventDefault();
      }

      // Проверка прикреплённых студентов
      // Если задан finish_limit - должны быть и заданы студенты
      if(!checkStudentCheckboxes() && (inputRadio_individual.checked || inputRadio_group.checked)) {
        let error_execution = document.getElementById('error-choose-executor');
        error_execution.textContent = "Не выбраны пользователи";
        error_execution.className = 'error-input active';

        event.preventDefault();
      }
    });

  });
}

function showError() {
  if(!input_Title.value) {
    error_Title.textContent = "Не заполненное поле <Названия задания>";
    error_Title.className = 'error-input active';
  }
  /*if(!textArea_Description.value) {
    error_Description.textContent = "Не заполненное поле <Описания задания>";
    error_Description.className = 'error-input active';
  }*/
}


// СКРИПТ ИЗМЕНЕНИЯ ЦВЕТА РАДИО-КНОПОК
inputRadio_individual.addEventListener('click', function (event) {
  console.log("НАЖАТА КНОПКА: НАЗНАЧИТЬ ИНДИВИДУАЛЬНО");
  if (inputRadio_group.parentElement.classList.contains('btn-primary')){
    inputRadio_group.parentElement.classList.remove('btn-primary');
    inputRadio_group.parentElement.classList.add('btn-outline-default');
    console.log("ЭТАП 1 ЗАКОНЧЕН");
  } 
  if (inputRadio_individual.parentElement.classList.contains('btn-outline-default')){
    inputRadio_individual.parentElement.classList.remove('btn-outline-default');
    inputRadio_individual.parentElement.classList.add('btn-primary');
    console.log("ЭТАП 2 ЗАКОНЧЕН");
  }

});
inputRadio_group.addEventListener('click', function (event) {
  console.log("НАЖАТА КНОПКА: НАЗНАЧИТЬ ПО ГРУППАМ");
  if (inputRadio_individual.parentElement.classList.contains('btn-primary')){
    inputRadio_individual.parentElement.classList.remove('btn-primary');
    inputRadio_individual.parentElement.classList.add('btn-outline-default');
    console.log("ЭТАП 1 ЗАКОНЧЕН");
  } 
  if (inputRadio_group.parentElement.classList.contains('btn-outline-default')){
    inputRadio_group.parentElement.classList.remove('btn-outline-default');
    inputRadio_group.parentElement.classList.add('btn-primary');
    console.log("ЭТАП 2 ЗАКОНЧЕН");
  }
});

let input_files = document.getElementById('task-files');
input_files.addEventListener('click', function (event) {
  console.log("НАЖАТА КНОПКА: ПРИЛОЖИТЬ ФАЙЛЫ");
  if (input_files.parentElement.classList.contains('btn-primary')){
    input_files.parentElement.classList.remove('btn-primary');
    input_files.parentElement.classList.add('btn-outline-default');
    console.log("ЭТАП 1 ЗАКОНЧЕН");
  } 
  if (input_files.parentElement.classList.contains('btn-outline-default')){
    input_files.parentElement.classList.remove('btn-outline-default');
    input_files.parentElement.classList.add('btn-primary');
    console.log("ЭТАП 2 ЗАКОНЧЕН");
  }
});
// Показывает количество прикрепленных для отправки файлов
$('#task-files').on('change', function() {
  $('#files-count').html(this.files.length);
});



//СКРИПТ "НАЗНАЧЕНИЯ ИСПОЛНИТЕЛЕЙ"
function checkStudentCheckboxes(){
  var accordion = $('.js-accordion');
  const accordion_student_elems = accordion.find('.form-check');
  for (let i = 0; i < accordion_student_elems.length; i++) {
    //console.log(student);
    if(accordion_student_elems[i].children[0].checked) {
      console.log('id: ' + accordion_student_elems[i].children[0].id);
      return true;
    }
  }
  console.log("Ничего не выбрано");
  return false;
}

// Проставить автоматические галочки на студентов
function markStudentElements(group_id){
  
}


// СКРИПТ ВКЛЮЧЕНИЯ / ОТКЛЮЧЕНИЯ ПОЛЕЙ КОДА ОШИБКИ И ЧЕГО_ТО ТАКОГО. НЕ ПОНЯЛ ДО КОНЦА
let tools = document.getElementById("tools");
let task_select = document.getElementById("task-type");
  
if(task_select){
  let select_change = function(){
    
    //alert(task_select.value);
    if(task_select.value == 0)
      tools.classList.add("d-none");
    else 
      tools.classList.remove("d-none");
  }

  task_select.addEventListener("change", select_change);

  //var type = <?php echo json_encode($task["type"]); ?>;
  //alert(type);

  //task_select.selectedIndex = parseInt(type);
  select_change();
}



// ACCORDION SCRIPT 

var accordion = (function(){
  var $accordion = $('.js-accordion');
  var $accordion_header = $accordion.find('.js-accordion-header');
  var $accordion_item = $('.js-accordion-item');

  // default settings 
  var settings = {
    // animation speed
    speed: 400,
    
    // close all other accordion items if true
    oneOpen: false
  };
    
  return {
    // pass configurable object literal
    init: function($settings) {
        $accordion_header.on('click', function() {
        accordion.toggle($(this));
      });
      
      $.extend(settings, $settings); 
      
      // ensure only one accordion is active if oneOpen is true
      if(settings.oneOpen && $('.js-accordion-item.active').length > 1) {
        $('.js-accordion-item.active:not(:first)').removeClass('active');
      }
      
      // reveal the active accordion bodies
      $('.js-accordion-item.active').find('> .js-accordion-body').show();
    },
    toggle: function($this) {
            
      if(settings.oneOpen && $this[0] != $this.closest('.js-accordion').find('> .js-accordion-item.active > .js-accordion-header')[0]) {
        $this.closest('.js-accordion')
              .find('> .js-accordion-item') 
              .removeClass('active')
              .find('.js-accordion-body')
              .slideUp()
      }
      
      // show/hide the clicked accordion item
      $this.closest('.js-accordion-item').toggleClass('active');
      $this.next().stop().slideToggle(settings.speed);
    }
  }
})();

$(document).ready(function(){
  accordion.init({ speed: 300, oneOpen: false });
});
