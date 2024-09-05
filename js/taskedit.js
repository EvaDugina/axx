// let form_taskEdit  = document.getElementById('form-taskEdit');

let input_Title = document.getElementById('input-title');
let error_Title = document.getElementById('error-input-title');

let textArea_Description = document.getElementById('textArea-description');
let error_Description = document.getElementById('error-textArea-description');

// let inputRadio_individual = document.getElementById('input-deligate-by-individual');
// let inputRadio_group = document.getElementById('input-deligate-by-group');

// let button_save = document.getElementById('submit-save');
// let button_delete = document.getElementById('submit-archive');


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


// СКРИПТ ВКЛЮЧЕНИЯ / ОТКЛЮЧЕНИЯ ПОЛЕЙ КОДА ОШИБКИ И ЧЕГО_ТО ТАКОГО. НЕ ПОНЯЛ ДО КОНЦА
let tools = document.getElementById("tools");
let task_select = document.getElementById("task-type");
  
if(task_select){
  let select_change = function(){
    
    //alert(task_select.value);
    if(task_select.value != 1)
      tools.classList.add("d-none");
    else
      tools.classList.remove("d-none");

    if(task_select.value == 2)
      $('#div-mark-type').addClass("d-none");
    else
      $('#div-mark-type').removeClass("d-none");
    
    task_select.addEventListener("change", select_change);
  }


  //var type = <?php echo json_encode($task["type"]); ?>;
  //alert(type);

  //task_select.selectedIndex = parseInt(type);
  select_change();
}


function showError() {
  if(!input_Title.value) {
    // error_Title.textContent = "Не заполненное поле <Названия задания>";
    error_Title.className = 'error-input active';
  }
  /*if(!textArea_Description.value) {
    error_Description.textContent = "Не заполненное поле <Описания задания>";
    error_Description.className = 'error-input active';
  }*/
}


// if(form_taskEdit){
//   form_taskEdit.addEventListener('submit', function (event) {

//     // Если нажата кнопка "Сохранить"
//     button_save.addEventListener('click', function (event) {
//       if(!input_Title.value /*|| !textArea_Description.value*/) {
//         // Если поля не заполнены, отображаем соответствующее сообщение об ошибке
//         showError();
//         // Затем предотвращаем стандартное событие отправки формы
//         event.preventDefault();
//       }

//       // Проверка прикреплённых студентов
//       // Если задан finish_limit - должны быть и заданы студенты
//       if(!checkStudentCheckboxes() && (inputRadio_individual.checked || inputRadio_group.checked)) {
//         let error_execution = document.getElementById('error-choose-executor');
//         error_execution.textContent = "Не выбраны пользователи";
//         error_execution.className = 'error-input active';

//         event.preventDefault();
//       }
//     });

//   });
// }



// // СКРИПТ ИЗМЕНЕНИЯ ЦВЕТА РАДИО-КНОПОК
// inputRadio_individual.addEventListener('click', function (event) {
//   console.log("НАЖАТА КНОПКА: НАЗНАЧИТЬ ИНДИВИДУАЛЬНО");
//   if (inputRadio_group.parentElement.classList.contains('btn-primary')){
//     inputRadio_group.parentElement.classList.remove('btn-primary');
//     inputRadio_group.parentElement.classList.add('btn-outline-default');
//     console.log("ЭТАП 1 ЗАКОНЧЕН");
//   } 
//   if (inputRadio_individual.parentElement.classList.contains('btn-outline-default')){
//     inputRadio_individual.parentElement.classList.remove('btn-outline-default');
//     inputRadio_individual.parentElement.classList.add('btn-primary');
//     console.log("ЭТАП 2 ЗАКОНЧЕН");
//   }

// });
// inputRadio_group.addEventListener('click', function (event) {
//   console.log("НАЖАТА КНОПКА: НАЗНАЧИТЬ ПО ГРУППАМ");
//   if (inputRadio_individual.parentElement.classList.contains('btn-primary')){
//     inputRadio_individual.parentElement.classList.remove('btn-primary');
//     inputRadio_individual.parentElement.classList.add('btn-outline-default');
//     console.log("ЭТАП 1 ЗАКОНЧЕН");
//   } 
//   if (inputRadio_group.parentElement.classList.contains('btn-outline-default')){
//     inputRadio_group.parentElement.classList.remove('btn-outline-default');
//     inputRadio_group.parentElement.classList.add('btn-primary');
//     console.log("ЭТАП 2 ЗАКОНЧЕН");
//   }
// });



// //СКРИПТ "НАЗНАЧЕНИЯ ИСПОЛНИТЕЛЕЙ"
// function checkStudentCheckboxes(){
//   var accordion = $('.js-accordion');
//   const accordion_student_elems = accordion.find('.form-check');
//   for (let i = 0; i < accordion_student_elems.length; i++) {
//     //console.log(student);
//     if(accordion_student_elems[i].children[0].checked) {
//       console.log('id: ' + accordion_student_elems[i].children[0].id);
//       return true;
//     }
//   }
//   console.log("Ничего не выбрано");
//   return false;
// }

// // Проставить автоматические галочки на студентов
// function markStudentElements(group_id){
  
// }


// // ACCORDION SCRIPT 

// let array_accordion_groups_inputs = document.getElementsByClassName("input-group");
// let array_accordion_students_inputs = document.getElementsByClassName("input-student");

// //console.log(array_accordion_groups_inputs);

// for(let i = 0; i < array_accordion_groups_inputs.length; i ++) {
//   // console.log(array_accordion_groups_inputs[i]);
//   array_accordion_groups_inputs[i].addEventListener('change', function() {
//     if (this.checked) {
//       // console.log("Checkbox group is checked..");

//     } else {

//     }
//   });
// }

// for(let i = 0; i < array_accordion_students_inputs.length; i ++) {
//   // console.log(array_accordion_students_inputs[i]);
//   array_accordion_students_inputs[i].addEventListener('change', function() {
//     if (this.checked) {
//       // console.log("Checkbox group is checked..");
//     } else {
      

//     }
//   });
// }



/*var accordion = (function(){
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

      var group_id = $this.find('> .js-accordion-item').id;
      var input = document.getElementById("group-" + group_id);
      var stat_group = document.getElementById("group-" + group_id + "-stat");

      console.log("CONSOLE LOG");
            
      if(settings.oneOpen && $this[0] != $this.closest('.js-accordion').find('> .js-accordion-item.active > .js-accordion-header')[0]) {
        $this.closest('.js-accordion')
              .find('> .js-accordion-item') 
              .removeClass('active')
              .find('.js-accordion-body')
              .slideUp();
        input.prop('checked', false); 
        stat_group.textContent = parseInt(stat_group.textContent.split('/')[0])-1 + "/" + stat_group.textContent.split('/')[1];
      }
      
      // show/hide the clicked accordion item
      $this.closest('.js-accordion-item').toggleClass('active');
      $this.next().stop().slideToggle(settings.speed);
    }
  }
})();

$(document).ready(function(){
  accordion.init({ speed: 300, oneOpen: false });
});*/
