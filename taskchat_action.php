<?php
require_once("settings.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("messageHandler.php");
require_once("POClasses/Commit.class.php");

if (!isset($_POST['assignment_id']) || !isset($_POST['user_id'])) {
  exit;
}

// Находим sender_user_type (3 - студент, 2 - преподаватель)
$user_id = $_POST['user_id'];
$assignment_id = $_POST['assignment_id'];


$User = new User((int)$user_id);
$Assignment = new Assignment((int)$assignment_id);


// ОТПРАВКА СООБЩЕНИЯ
if (isset($_POST['type']) && isset($_POST['message_text'])) {
  
  $full_text = "";
  $full_text = rtrim($_POST['message_text']);

  if ($_POST['type'] ==  1) {
    // ПРИКРЕПЛЕНИЕ ОТВЕТА К ЗАДАНИЮ


    $Commit = new Commit((int)$assignment_id, null, (int)$user_id, null, null);

    $Message_answer = new Message((int)$assignment_id, 1, $User->id, $User->role, null, $full_text, 0, 0);
    $Assignment->addMessage($Message_answer->id);
    $Message_answer->setCommit($Commit->id);
    
    $Message_link = new Message((int)$assignment_id, 3, $User->id, $User->role, null, "editor.php?assignment=$Assignment->id&commit=$Commit->id", 0, 2);
    $Assignment->addMessage($Message_link->id);
    
    $Assignment->setStatus(1);

    $delay = -1;
    if ($Assignment->finish_limit) {
      $date_db = convert_timestamp_to_date($Assignment->finish_limit);
      $date_now = get_now_date("d-m-Y");  
      $delay = ($date_db >= $date_now) ? 0 : 1;
    }
    $Assignment->setDelay($delay);

    $files = array();
    if (isset($_FILES['files'])) {
      for($i=0; $i < count($_FILES['files']['tmp_name']); $i++) {
        if(!is_uploaded_file($_FILES['files']['tmp_name'][$i])){
          continue;
        } else {
          array_push($files, ['name' => $_FILES['files']['name'][$i], 'tmp_name' => $_FILES['files']['tmp_name'][$i], 
                  'size' => $_FILES['files']['size'][$i]]);
        }
      }
      add_files_to_message($Commit->id, $Message_answer->id, $files, 11);
    }


  } else if ($_POST['type'] == 2) {
    // Оценивание задания

    $Message_last_answer = $Assignment->getLastAnswerMessage();
    $Message = new Message((int)$assignment_id, 2, $User->id, $User->role, $Message_last_answer->id, $full_text, 0, 0);


  } else if ($_POST['type'] == 0) {
    // ОБЫЧНОЕ СООБЩЕНИЕ


    if(isset($_POST['flag-preptable'])) {
      $Message = new Message((int)$assignment_id, 0, $User->id, $User->role, $POST['reply_id'], $full_text, 0, 0);
    } else {
      $Message = new Message((int)$assignment_id, 0, $User->id, $User->role, null, $full_text, 0, 0);
    }

    $files = array(); 
    if (isset($_FILES['files'])) {
      for($i=0; $i < count($_FILES['files']['tmp_name']); $i++) {
        if(!is_uploaded_file($_FILES['files']['tmp_name'][$i])){
          continue;
        } else {
          array_push($files, ['name' => $_FILES['files']['name'][$i], 'tmp_name' => $_FILES['files']['tmp_name'][$i], 
                  'size' => $_FILES['files']['size'][$i]]);
        }
      }
      add_files_to_message(null, $Message->id, $files, 0);
    }
  }
}




if (isset($_POST['mark'])) {
  // Оценивание задания
  // echo "ОЦЕНИВАНИЕ ЗАДАНИЯ";
  $query = update_ax_assignment_mark($assignment_id, $_POST['mark']);
  $result = pg_query($dbconnect, $query);
}




if (isset($_POST['flag_preptable']) && $_POST['flag_preptable']){
  exit;
}




/*echo "UPDATE AFTER ACTION";*/
// if (isset($_POST['load_status']) && $_POST['load_status'] == 'new_only')
//   updateNewMessages($assignment_id, $sender_user_type, $user_id);
// else
update_chat($assignment_id, $user_id);

















  

// Делает запись сообщения и вложений в БД
// type: 0 - переговоры, 2 - оценка
// Возвращает id добавленного сообщения
function update_chat($assignment_id, $user_id){
  echo '<div id="content">';
  $Assignment = new Assignment((int)$assignment_id);
  showMessages($Assignment->getMessages(), $Assignment->getFirstUnreadedMessage($user_id));
  echo '</div>';
}

function showMessages($messages, $min_new_message_id) {
	global $user_id;

  $User = new User((int)$user_id);
	foreach ($messages as $message) {
    $sender_User = new User((int)$message->sender_user_id);
		// Прижимаем сообщения текущего пользователя к правой части экрана
		$float_class = $message->sender_user_id == $user_id ? 'float-right' : ''; 
		// Если студент написал сообщение, то у всех студентов сообщение подсвечивается синим, 
		// пока один из преподов его не прочитает(прочитать = прогрузить страницу с чатом). И наоборот
    $message_delivery_status = $message->status;
    // $message_delivery_status = $message->isReadedAtLeastByOne();
		$background_color_class = ($message_delivery_status == 0) ? 'background-color-blue' : '';
		// if ($message->isFirstUnreaded($user_id)) {
    if ($message->id == $min_new_message_id)
			echo '<div id="new-messages" style="width: 100%; text-align: center">Новые сообщения</div>';
    
    if ($message->visibility == 0 || $message->visibility == $User->role) {
    ?>
    <div id="message-<?=$message->id?>" class="<?=$float_class?> d-flex flex-column p-2" 
    style="height: fit-content; max-width: 60%; min-width: 30%;">
      <button id="btn-message-<?=$message->id?>" 
      class="btn btn-outline-<?=($message->sender_user_id == $user_id) ? "primary" : "dark"?> shadow-none text-black <?=$background_color_class?> d-flex flex-column w-100 h-auto mb-1" 
      style="<?php if ($message->type == 1) echo "border-color: green;"; 
      else if ($message->type == 2) echo "border-color: red;"?>" onclick="selectMessage(<?=$message->id?>)">
        <div class="d-flex align-self-<?=($message->sender_user_id == $user_id) ? "end" : "start"?> mb-1">
          <strong>
            <?=$sender_User->getFI()?>
          </strong> 
        </div>
        </br>
        <div class="align-self-<?=($message->sender_user_id == $user_id) ? "end" : "start"?>">
          <?php 
          if ($message->full_text != '') {
            if ($message->type == 3){ // если ссылка
              echo '<a href="'.$message->full_text.'">Проверить код</a>';
            } else
              echo stripslashes(htmlspecialchars($message->full_text)) . "<br>";
          }
          showFiles($message->getFiles());
          ?>
        </div>
      </button>
      <div class="mb-2 align-self-<?=($message->sender_user_id == $user_id) ? "start" : "end"?>">
        <?=$message->getConvertedDateTime()?>
      </div>
    </div>
    <div class="clear"></div>

      <?php 
      if ($message_delivery_status == 0 && $message->sender_user_type != $User->role) {
        // $message->setReadedDeliveryStatus($user_id);
        $message->setStatus(1);
      }
    }
  }
}




function updateNewMessages($assignment_id, $sender_user_type, $user_id){
  // Содержимое этого div'а JS вставляет в окно чата на taskchat.php 
  $Assignment = new Assignment((int)$assignment_id);
  visualNewMessages($Assignment->getNewMessagesByUser($user_id));
}
// TODO: Объединить ф-ции
function visualNewMessages($Messages) {
  global $user_id;

	foreach ($Messages as $message) {
    $User = new User((int)$message->sender_user_id);

		// Прижимаем сообщения текущего пользователя к правой части экрана
		$float_class = $message->sender_user_id == $user_id ? 'float-right' : ''; 
		// Если студент написал сообщение, то у всех студентов сообщение подсвечивается синим, 
		// пока один из преподов его не прочитает(прочитать = прогрузить страницу с чатом). И наоборот
		$background_color_class = 'background-color-blue';
		echo '<div id="new-messages" style="width: 100%; text-align: center">Новые сообщения</div>';
		?>

		<div id="message-<?=$message->id?>" class="chat-box-message <?=$float_class?>" style="height: auto;">
			<div class="chat-box-message chat-box-message-wrapper <?=$background_color_class?>">
        <strong><?=$User->getFI()?></strong> </br>
				<?php 
				if ($message->full_text != '') {
          if ($message->type == 3){ // если ссылка
            echo '<a href="'.$message->full_text.'">Проверить код</a>';
          } else
					  echo stripslashes(htmlspecialchars($message->full_text)) . "<br>";
				}
				foreach ($message->getFiles() as $File) {
          $file_ext = $File->getFileExt();   
          if (in_array($file_ext, getImageFileTypes())) { ?>
            <img src="<?=$File->download_url?>" class="rounded <?=$float_class?> w-100 mb-1" alt="...">
          <?php } else {?>
					  <a href="<?=$File->download_url?>" class="task-desc-wrapper-a" target="_blank">
              <i class="fa-solid fa-file"></i><?=$File->name?>
            </a>
				  <?php }
        }?>
			</div>
			<div class="chat-box-message-date mb-2">
				<?=$message->date_time?>
			</div>
		</div>
		<div class="clear"></div>
	<?php 
  $message->setReadedDeliveryStatus($user_id);
  }
}


function add_files_to_message($commit_id, $message_id, $files, $type){
  // Файлы с этими расширениями надо хранить в БД
  for ($i = 0; $i < count($files); $i++) {
    addFileToMessage($commit_id, $message_id, $files[$i]['name'], $files[$i]['tmp_name'], $type);
  }
}

function addFileToMessage($commit_id, $message_id, $file_name, $file_tmp_name, $type){
  $Message = new Message((int)$message_id);

  $file_id = addFileToObject($Message, $file_name, $file_tmp_name, $type);

  // Добавление файла в ax_solution_file, если сообщение - ответ на задание
  if ($commit_id != null) {
    $Commit = new Commit((int)$commit_id);
    $Commit->addFile($file_id);
    $Message->setCommit($Commit->id);
  }
}