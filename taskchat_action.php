<?php
require_once("settings.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("POClasses/Commit.class.php");

if (!isset($_POST['assignment_id']) || !isset($_POST['user_id'])) {
  exit();
}

// Находим sender_user_type (3 - студент, 2 - преподаватель)
$user_id = $_POST['user_id'];
$assignment_id = $_POST['assignment_id'];


$User = new User((int)$user_id);
$Assignment = new Assignment((int)$assignment_id);


// ПЕРЕСЫЛКА СООБЩЕНИЯ
if (isset($_POST['resendMessages'])) {
  $full_text = "";
  $selected_messages = json_decode($_POST['selected_messages']);
  foreach ($selected_messages as $message) {
    $Message = new Message((int)$Assignment->id, 0, $User->id, $User->role);
    $Assignment->addMessage($Message->id);
    $resendedMessage = new Message((int)$message);
    $Message->setResendedFromId((int)$resendedMessage->getMainResendedMessage()->id);
  }
}

if (isset($_POST['deleteMessages'])) {
  $selected_messages = json_decode($_POST['selected_messages']);
  foreach ($selected_messages as $message) {
    $Assignment->deleteMessage((int)$message);
  }
}


// ОТПРАВКА СООБЩЕНИЯ
if (isset($_POST['type']) && isset($_POST['message_text'])) {

  $full_text = "";
  $full_text = rtrim($_POST['message_text']);

  if ($_POST['type'] ==  1) {
    // ПРИКРЕПЛЕНИЕ ОТВЕТА К ЗАДАНИЮ


    $Commit = new Commit((int)$assignment_id, null, (int)$user_id, 1, null);

    $Message_answer = new Message((int)$assignment_id, 1, $User->id, $User->role, $full_text);
    $Assignment->addMessage($Message_answer->id);
    $Message_answer->setCommit($Commit->id);

    $Message_link = new Message((int)$assignment_id, 3, $User->id, $User->role, null, "editor.php?assignment=$Assignment->id&commit=$Commit->id", 2);
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
      for ($i = 0; $i < count($_FILES['files']['tmp_name']); $i++) {
        if (!is_uploaded_file($_FILES['files']['tmp_name'][$i])) {
          continue;
        } else {
          array_push($files, [
            'name' => $_FILES['files']['name'][$i],
            'tmp_name' => $_FILES['files']['tmp_name'][$i],
            'size' => $_FILES['files']['size'][$i]
          ]);
        }
      }
      add_files_to_message($Commit->id, $Message_answer->id, $files, 11);
    }
  } else if ($_POST['type'] == 2) {
    // Оценивание задания

    $Message_last_answer = $Assignment->getLastAnswerMessage();
    $Message = new Message((int)$assignment_id, 2, $User->id, $User->role, $Message_last_answer->id, $full_text);
    $Assignment->addMessage($Message->id);
  } else if ($_POST['type'] == 0) {
    // ОБЫЧНОЕ СООБЩЕНИЕ


    if (isset($_POST['flag-preptable'])) {
      $Message = new Message((int)$assignment_id, 0, $User->id, $User->role, $POST['reply_id'], $full_text);
      $Assignment->addMessage($Message->id);
    } else {
      $Message = new Message((int)$assignment_id, 0, $User->id, $User->role, $full_text);
      $Assignment->addMessage($Message->id);
    }

    $files = array();
    if (isset($_FILES['files'])) {
      for ($i = 0; $i < count($_FILES['files']['tmp_name']); $i++) {
        if (!is_uploaded_file($_FILES['files']['tmp_name'][$i])) {
          continue;
        } else {
          array_push($files, [
            'name' => $_FILES['files']['name'][$i],
            'tmp_name' => $_FILES['files']['tmp_name'][$i],
            'size' => $_FILES['files']['size'][$i]
          ]);
        }
      }
      add_files_to_message(null, $Message->id, $files, 0);
    }
  }
}




if (isset($_POST['mark'])) {
  // echo "ОЦЕНИВАНИЕ ЗАДАНИЯ";
  $Assignment->setMark($_POST['mark']);
}




if (isset($_POST['flag_preptable']) && $_POST['flag_preptable']) {
  exit;
}




/*echo "UPDATE AFTER ACTION";*/
// if (isset($_POST['load_status']) && $_POST['load_status'] == 'new_only')
//   updateNewMessages($assignment_id, $sender_user_type, $user_id);
// else
if (!isset($_POST['resendMessages']))
  update_chat($assignment_id, $user_id);
else
  update_chat($assignment_id, $user_id);


















// Делает запись сообщения и вложений в БД
// type: 0 - переговоры, 2 - оценка
// Возвращает id добавленного сообщения
function update_chat($assignment_id, $user_id)
{
  echo '<div id="content">';
  $Assignment = new Assignment((int)$assignment_id);
  showMessages($Assignment, $user_id, $Assignment->getMessages(), $Assignment->getFirstUnreadedMessage($user_id));
  echo '</div>';
}

function showMessages($Assignment, $user_id, $messages, $min_new_message_id)
{

  $User = new User((int)$user_id);
  foreach ($messages as $message) {
    showMessage($message, $User, $min_new_message_id);
  }
?>

  <?php
}

function showMessage($Message, $User, $min_new_message_id, $isResended = false)
{

  $sender_User = new User((int)$Message->sender_user_id);
  $senderUserFI = $sender_User->getFI();
  $isNewMessage = $Message->id == $min_new_message_id;
  $isAuthor = $Message->sender_user_id == $User->id;
  $isVisible = $Message->isVisible($User->role);

  // Прижимаем сообщения текущего пользователя к правой части экрана
  $float_class = $isAuthor ? 'float-right' : '';
  // Если студент написал сообщение, то у всех студентов сообщение подсвечивается синим, 
  // пока один из преподов его не прочитает(прочитать = прогрузить страницу с чатом). И наоборот
  $isReadedBySelf = $Message->isReadedByUser($User->id);
  $isReadedBySomebody = $Message->isReadedBySombody($User->id);
  $background_color_class = (!$isReadedBySomebody) ? 'background-color-blue' : '';

  // if ($message->isFirstUnreaded($user_id)) {

  if ($Message->isResended()) {
    if ($isNewMessage) { ?>
      <div id="new-messages" style="width: 100%; text-align: center">Новые сообщения</div>
    <?php } ?>
    <div id="message-<?= $Message->id ?>" class="d-flex flex-column p-2 align-items-<?= ($isAuthor) ? "end" : "start" ?>">
      <div id="btn-message-<?= $Message->id ?>" class="btn btn-outline-<?= ($isAuthor) ? "primary" : "dark" ?> shadow-none text-black <?= $background_color_class ?> 
        d-flex flex-column h-auto mb-1 align-items-<?= ($isAuthor) ? "end" : "start" ?>" style="height: fit-content;" oncontextmenu="onContextMenu(event, <?= $Message->id ?>, <?= $Message->sender_user_id == $User->id ?>)" onclick=" selectMessage(<?= $Message->id ?>, <?= $Message->sender_user_id == $User->id ?>)">
        <div class="d-flex align-self-<?= ($isAuthor) ? "end" : "start" ?> mb-1">
          <div class="d-flex align-items-center align-self-<?= ($isAuthor) ? "end" : "start" ?>">
            <i>переслано от &nbsp;</i>
            <strong style="text-transform: uppercase;">
              <?= $senderUserFI ?>
            </strong>
          </div>
        </div>
        <?php $resendedMessage = new Message((int)$Message->resended_from_id);
        showMessage($resendedMessage, $User, $min_new_message_id, true); ?>
      </div>
      <div class="mb-2 align-self-<?= ($isAuthor) ? "end" : "start" ?>">
        <p class="p-0 m-0 "><small class="d-flex align-items-center">
            <?= $Message->getConvertedDateTime() ?>
            <?php if (!$isReadedBySomebody) { ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check align-self-center" viewBox="0 0 16 16">
                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z" />
              </svg>
            <?php } else { ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-all align-self-center" viewBox="0 0 16 16">
                <path d="M8.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L2.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093L8.95 4.992zm-.92 5.14.92.92a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 1 0-1.091-1.028L9.477 9.417l-.485-.486z" />
              </svg>
            <?php } ?>
          </small></p>
      </div>
    </div>
  <?php } else if ($isResended) { ?>
    <div id="message-<?= $Message->id ?>" class="d-flex flex-column p-2">
      <div id="btn-message-<?= $Message->id ?>" class="btn btn-outline-<?= ($isAuthor) ? "primary" : "dark" ?> shadow-none text-black <?= $background_color_class ?> 
      d-flex flex-column w-100 h-auto mb-1 " style="<?php if ($Message->type == 1) echo "border-color: green;";
                                                    else if ($Message->type == 2) echo "border-color: red;" ?>">
        <div class="align-self-<?= ($isAuthor) ? "end" : "start" ?> mb-1" style="text-transform: uppercase;">
          <p class="m-0 p-0"><strong>
              <?= $senderUserFI ?>
            </strong> </p>
        </div>
        <div class="align-self-<?= ($isAuthor) ? "end d-flex flex-row-reverse flex-wrap" : "start d-flex flex-wrap" ?>" style="<?= ($Message->type == 3) ? "text-transform: uppercase;" : "" ?>">
          <?php showMessageFiles($Message->getFiles(), $isAuthor); ?>
        </div>
        <div class="align-self-<?= ($isAuthor) ? "end" : "start" ?>">
          <?php
          if ($Message->full_text != '') {
          ?>
            <p id="p-message-<?= $Message->id ?>-text" class="p-0 m-0 h6 text-<?= ($isAuthor) ? "end" : "start" ?>">
              <?php
              if ($Message->type == 3) { // если ссылка
              ?>
                <a href="<?= $Message->full_text ?>" onclick="event.stopPropagation()">Проверить код</a>
              <?php } else { ?>
                <?= getTextWithTagBrAfterLines(stripslashes(htmlspecialchars($Message->full_text))) ?>
                <br>
              <?php }
              ?>
            </p>
          <?php
          } ?>
        </div>
      </div>
      <div class="mb-2 align-self-<?= ($isAuthor) ? "start" : "end" ?>">
        <p class="p-0 m-0 "><small class="d-flex align-items-center">
            <?= $Message->getConvertedDateTime() ?>
          </small></p>
      </div>
    </div>
    <?php } else if ($isVisible) {
    if ($isNewMessage) { ?>
      <div id="new-messages" style="width: 100%; text-align: center">Новые сообщения</div>
    <?php } ?>
    <div id="message-<?= $Message->id ?>" class="<?= $float_class ?> d-flex flex-column p-2" style="height: fit-content; max-width: 75%; min-width: 30%;">
      <button id="btn-message-<?= $Message->id ?>" class="btn btn-outline-<?= ($isAuthor) ? "primary" : "dark" ?> shadow-none text-black <?= $background_color_class ?> 
      d-flex flex-column w-100 h-auto mb-1 " style="<?php if ($Message->type == 1) echo "border-color: green;";
                                                    else if ($Message->type == 2) echo "border-color: red;" ?> text-transform: unset;" oncontextmenu="onContextMenu(event, <?= $Message->id ?>, <?= $Message->sender_user_id == $User->id ?>)" onclick=" selectMessage(<?= $Message->id ?>, <?= $Message->sender_user_id == $User->id ?>)">
        <div class="d-flex align-self-<?= ($isAuthor) ? "end" : "start" ?> mb-1" style="text-transform: uppercase;">
          <p class="m-0 p-0"><strong>
              <?= $senderUserFI ?>
            </strong> </p>
        </div>
        <div class="align-self-<?= ($isAuthor) ? "end d-flex flex-row-reverse flex-wrap" : "start d-flex flex-wrap" ?>" style="<?= ($Message->type == 3) ? "text-transform: uppercase;" : "" ?>">
          <?php showMessageFiles($Message->getFiles(), $isAuthor); ?>
        </div>
        <div class="align-self-<?= ($isAuthor) ? "end" : "start" ?>">
          <?php
          if ($Message->full_text != '') {
          ?>
            <p id="p-message-<?= $Message->id ?>-text" class="p-0 m-0 h6 text-<?= ($isAuthor) ? "end" : "start" ?>">
              <?php
              if ($Message->type == 3) { // если ссылка
              ?>
                <a href="<?= $Message->full_text ?>" onclick="event.stopPropagation()">Проверить код</a>
              <?php } else { ?>
                <?= getTextWithTagBrAfterLines(stripslashes(htmlspecialchars($Message->full_text))) ?>
                <br>
              <?php }
              ?>
            </p>
          <?php
          } ?>
        </div>
      </button>
      <div class="mb-2 align-self-<?= ($isAuthor) ? "start" : "end" ?>">
        <p class="p-0 m-0 "><small class="d-flex align-items-center">
            <?= $Message->getConvertedDateTime() ?>
            <?php if (!$isReadedBySomebody) { ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z" />
              </svg>
            <?php } else { ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-all" viewBox="0 0 16 16">
                <path d="M8.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L2.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093L8.95 4.992zm-.92 5.14.92.92a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 1 0-1.091-1.028L9.477 9.417l-.485-.486z" />
              </svg>
            <?php } ?>
          </small></p>
      </div>
    </div>
    <div class="clear"></div>

  <?php
  }
  if (!$isReadedBySelf) {
    $Message->setReadedDeliveryStatus($User->id);
  }
}




// function updateNewMessages($assignment_id, $sender_user_type, $user_id)
// {
//   // Содержимое этого div'а JS вставляет в окно чата на taskchat.php 
//   $Assignment = new Assignment((int)$assignment_id);
//   visualNewMessages($Assignment->getNewMessagesByUser($user_id));
// }
// TODO: Объединить ф-ции
function visualNewMessages($Messages)
{
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

    <div id="message-<?= $message->id ?>" class="chat-box-message <?= $float_class ?>" style="height: auto;">
      <div class="chat-box-message chat-box-message-wrapper <?= $background_color_class ?>">
        <strong><?= $User->getFI() ?></strong> </br>
        <?php
        if ($message->full_text != '') {
          if ($message->type == 3) { // если ссылка 
        ?>
            <a href="<?= $message->full_text ?>" onclick="event.stopPropagation()">Проверить код</a>
          <?php } else
            echo stripslashes(htmlspecialchars($message->full_text)) . "<br>";
        }
        foreach ($message->getFiles() as $File) {
          $file_ext = $File->getExt();
          if (in_array($file_ext, getImageFileTypes())) { ?>
            <img src="<?= $File->download_url ?>" class="rounded <?= $float_class ?> w-100 mb-1" alt="...">
          <?php } else { ?>
            <a href="<?= $File->download_url ?>" class="task-desc-wrapper-a ms-0" target="_blank" onclick="event.stopPropagation()">
              <i class="fa-solid fa-file"></i><?= $File->name ?>
            </a>
        <?php }
        } ?>
      </div>
      <div class="chat-box-message-date mb-2">
        <?= $message->date_time ?>
      </div>
    </div>
    <div class="clear"></div>
<?php
    $message->setReadedDeliveryStatus($user_id);
  }
}


function add_files_to_message($commit_id, $message_id, $files, $type)
{
  // Файлы с этими расширениями надо хранить в БД
  for ($i = 0; $i < count($files); $i++) {
    addFileToMessage($commit_id, $message_id, $files[$i]['name'], $files[$i]['tmp_name'], $type);
  }
}

function addFileToMessage($commit_id, $message_id, $file_name, $file_tmp_name, $type)
{
  $Message = new Message((int)$message_id);

  $file_id = addFileToObject($Message, $file_name, $file_tmp_name, $type);

  // Добавление файла в ax.ax_solution_file, если сообщение - ответ на задание
  if ($commit_id != null) {
    $Commit = new Commit((int)$commit_id);
    $Commit->addFile($file_id);
    $Message->setCommit($Commit->id);
  }
}
