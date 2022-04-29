<?php
require_once("common.php");
require_once("dbqueries.php");
$scripts = null;

$query = select_all_disciplines();
$result = pg_query($dbconnect, $query);
$disciplines = pg_fetch_all($result);

// получение параметров запроса
$user_id = -5; // TODO: get current user id
$page_id = 0;
if (array_key_exists('page', $_REQUEST))
  $page_id = $_REQUEST['page'];
else {
  echo "Некорректное обращение";
  http_response_code(400);
  exit;
}

if (isset($_POST['select-discipline'])) {
  $page_id = $_POST['select-discipline'];
  header('Location:preptable.php?page=' . $page_id);
}

// отправка сообщения
if (array_key_exists('message', $_REQUEST) && array_key_exists('text', $_REQUEST)) {
  $mark = (array_key_exists('mark', $_REQUEST)) ? $_REQUEST['mark'] : null;
  $query = insert_message($_REQUEST['message'], $_REQUEST['text'], $mark, $user_id);
  $result = pg_query($dbconnect, $query);
  echo pg_result_error($result);
  if (!$result) {
    echo "Ошибка запроса";
    http_response_code(500);
    exit;
  }
  $scripts .= "<script>window.history.replaceState(null, document.title, '" . $_SERVER['PHP_SELF'] . "?page=" . $page_id . "');</script>\n";
  // OR remove &message=N in $_SERVER['REQUEST_URI'] 
}

// TODO: check prep access rights to page

$query = select_page_names(1);
$result = pg_query($dbconnect, $query);
$page_names = pg_fetch_all($result);

$query = select_page_name($page_id, 1);
$result = pg_query($dbconnect, $query);
$row = [];
if (!$result || pg_num_rows($result) < 1) {
  echo 'Неверно указана дисциплина';
  http_response_code(400);
  exit;
} else {
  $row = pg_fetch_row($result);
  show_header('Посылки по дисциплине', array('Дэшборд преподавателя' => 'mainpage.php', $row[1]  => 'preptable.php?page=' . $page_id));
}

if ($scripts) echo $scripts; ?>

<!-- MDB -->
<script type="text/javascript" src="js/mdb.min.js"></script>

<!-- jQuery -->
<script type="text/javascript" src="js/jquery-3.5.1.min.js"></script>

<main class="pt-2">
  <div class="container-fluid overflow-hidden">
    <div class="row gy-5">
      <div class="col-8">
        <div class="pt-3">

          <h2 class="text-nowrap">
            Посылки по дисциплине
          </h2>
          <div style="padding-top:10px; padding-bottom:10px; ">
            <select class="form-select" aria-label=".form-select" name="select-discipline" id="selectCourse">

              <?php $i = 1;
              foreach ($page_names as $page_name) {
                if ($row[0] == $page_name['id'])
                  echo '<option selected value="' . $page_name['id'] . '">' . $page_name['names'] . '</option>';
                else
                  echo '<option value="' . $page_name['id'] . '">' . $page_name['names'] . '</option>';
                $i++;
              } ?>

            </select>
          </div>

          <div class="form-outline">
            <i class="fas fa-search trailing"></i>
            <input type="text" id="form1" class="form-control form-icon-trailing" oninput="filterTable(this.value)" />
            <label class="form-label" for="form1">Фильтр по группам, студентам, заданиям, комментариям</label>
          </div>


          <?php
          $query = select_page_tasks($page_id, 1);
          $result = pg_query($dbconnect, $query);
          $tasks = array();
          if (!$result || pg_num_rows($result) < 1){?>
          <div class="pt-3">
            <h5>Задания по этой дисциплине отсутствуют</h5>
          </div>
          
          <?php 
          } else {
            $tasks = pg_fetch_all_assoc($result, PGSQL_ASSOC);
            $group = null;

            $query = select_page_students_grouped($page_id, 1);
            $result = pg_query($dbconnect, $query);
            $students = pg_fetch_all_assoc($result, PGSQL_ASSOC);

            $query = select_page_messages($page_id);
            $result = pg_query($dbconnect, $query);
            $messages = pg_fetch_all_assoc($result, PGSQL_ASSOC);
          ?>
            <div>
              <table class="table table-status" id="table-status-id">
                <thead>
                  <tr class="table-row-header" style="text-align:center;">
                    <th scope="col" colspan="1">Студенты и группы</th>
                    <th scope="col" colspan="<?= count($tasks) + 1 ?>">Задания</th>
                  </tr>
                  <tr>
                    <th scope="col" colspan="1"> </th>
                    <th scope="col" data-mdb-toggle="tooltip" title="Номер варианта">#</th>
                    <?php
                    for ($t = 0; $t < count($tasks); $t++) {
                    ?>
                      <td scope="col" data-mdb-toggle="tooltip" title="<?= $tasks[$t]['title'] ?>"><?= $t + 1 ?></td>
                    <?php
                    }
                    ?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  for ($s = 0; $s < count($students); $s++) {
                    if ($group != $students[$s]['grp']) {
                      $group = $students[$s]['grp'];
                  ?>
                      <tr class="table-row-header">
                        <th scope="row" colspan="1"><?= $group ?></th>
                        <th colspan="1"> </th>
                        <td colspan="<?= count($tasks) ?>"> </td>
                      </tr>
                    <?php
                    }
                    ?>
                    <tr>
                      <th scope="row" data-group="<?= $students[$s]['grp'] ?>"><?= $s + 1 ?>. <?= $students[$s]['fio'] ?></th>
                      <th data-mdb-toggle="tooltip" data-mdb-html="true" title="<?= $students[$s]['vtext'] ?>"><?= $students[$s]['vnum'] ?></th>
                      <?php
                      for ($t = 0; $t < count($tasks); $t++) { // tasks cycle
                        $task_message = null;
                        for ($m = 0; $m < count($messages); $m++) { // search for last student+task message
                          if ($messages[$m]['tid'] == $tasks[$t]['id'] && $messages[$m]['sid'] == $students[$s]['id']) {
                            $task_message = $messages[$m];
                            break;
                          }
                        }
                        if ($task_message == null || $task_message['amark'] == null && $task_message['mtime'] == null) { // no assignment or no answer
                      ?>
                          <td tabindex="-1"> </td>
                        <?php
                        } else if ($task_message['mtime'] != null && $task_message['amark'] == null && $task_message['mfile'] != null) { // have message without mark and with file, can answer
                        ?>
                          <td tabindex="0" onclick="showPopover(this,'<?= $task_message['mid'] ?>')" title="@<?= $task_message['mlogin'] ?> <?= $task_message['mtime'] ?>" data-mdb-content="<a target='_blank' href='<?= $task_message['murl'] ?>'>FILE: <?= $task_message['mfile'] ?></a><br/> <?= $task_message['mtext'] ?><br/> <a href='javascript:answerPress(2,<?= $task_message['mid'] ?>,<?= $task_message['max_mark'] ?>)' type='message' class='btn btn-outline-primary'>Зачесть</a> <a href='javascript:answerPress(0,<?= $task_message['mid'] ?>)' type='message' class='btn btn-outline-primary'>Ответить</a>"><?= $task_message['val'] ?></td>
                        <?php
                        } else if ($task_message['mtime'] != null && $task_message['amark'] == null) { // have message without mark, can answer
                        ?>
                          <td tabindex="0" onclick="showPopover(this,'<?= $task_message['mid'] ?>')" title="@<?= $task_message['mlogin'] ?> <?= $task_message['mtime'] ?>" data-mdb-content="<?= $task_message['mtext'] ?><br/> <a href='javascript:answerPress(2,<?= $task_message['mid'] ?>,<?= $task_message['max_mark'] ?>)' type='message' class='btn btn-outline-primary'>Зачесть</a> <a href='javascript:answerPress(0,<?= $task_message['mid'] ?>)' type='message' class='btn btn-outline-primary'>Ответить</a>"><?= $task_message['val'] ?></td>
                        <?php
                        } else if ($task_message['amark'] != null) { // have mark, not need to answer
                        ?>
                          <td tabindex="0" onclick="showPopover(this)" title="@<?= $task_message['mlogin'] ?> <?= $task_message['mtime'] ?>" data-mdb-content="<?= $task_message['mtext'] ?>"><?= $task_message['val'] ?></td>
                        <?php
                        }
                        ?>
                      <?php
                      } // $tasks cycle
                      ?>
                    </tr>
                  <?php
                  } // students cycle
                  ?>
                </tbody>
              </table>
            </div>
        </div>
      </div>

      <div class="col-4">
        <div id="list-messages" class="p-3 border bg-light" style="overflow-y: scroll; max-height: calc(100vh - 80px);">
          <h5>История сообщений</h5>
          <div id="list-messages-id">
            <?php
            for ($m = 0; $m < count($messages); $m++) { // list all messages
              if ($messages[$m]['mtype'] != null)
                show_message($messages[$m]);
            }
            ?>
          </div>
          <!--<div class="pt-1 pb-1"><button type="button" class="btn btn-outline-primary" data-mdb-toggle="modal" data-mdb-target="#dialogAnswer"><i class="fas fa-paperclip fa-lg"></i> Что-то сделать</button></div>-->
        </div>
      </div>

</main>

<!-- Modal dialog answer -->
<div class="modal fade" id="dialogAnswer" tabindex="-1" aria-labelledby="dialogAnswerLabel" aria-hidden="true">
  <form class="needs-validation" onsubmit="answerSend(this)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dialogAnswerLabel">Ответить студенту</h5>
          <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="form-outline">
            <textarea class="form-control" id="dialogAnswerText" rows="4" name="text" required></textarea>
            <label class="form-label" for="dialogAnswerText">Текст ответа</label>
          </div>
          <input type="hidden" id="dialogAnswerMessageId" name="message" />
          <input type="hidden" name="page" value="<?= $page_id ?>" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Закрыть</button>
          <button type="submit" class="btn btn-primary">Ответить</button>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- Modal dialog mark -->
<div class="modal fade" id="dialogMark" tabindex="-1" aria-labelledby="dialogMarkLabel" aria-hidden="true">
  <form class="needs-validation" onsubmit="answerSend(this)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dialogMarkLabel">Зачесть задание</h5>
          <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="form-outline">
            <input type="number" id="dialogMarkMarkInput" name="mark" class="form-control" required />
            <label class="form-label" for="typeNumber" id="dialogMarkMarkLabel">Оценка</label>
          </div>
          <br />
          <div class="form-outline">
            <textarea class="form-control" id="dialogMarkText" rows="4" name="text" required></textarea>
            <label class="form-label" for="dialogMarkText">Текст ответа</label>
          </div>
          <input type="hidden" id="dialogMarkMessageId" name="message" />
          <input type="hidden" name="page" value="<?= $page_id ?>" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Закрыть</button>
          <button type="submit" class="btn btn-primary" onclick="answerSend(this)">Ответить</button>
        </div>
      </div>
    </div>
  </form>
</div>

<?php }?>

<!-- Custom scripts -->
<script>
  const areaSelectCourse = selectCourse.addEventListener(`change`, (e) => {
    const value = document.getElementById("selectCourse").value;
    document.location.href = 'preptable.php?page=' + value;
    //log(`option desc`, desc);
  });
</script>

<script type="text/javascript">
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

  function answerPress(answer_type, message_id, max_mark) {
    // TODO: implement answer
    console.log('pressed: ', answer_type == 2 ? 'mark' : 'answer', max_mark, message_id);
    if (answer_type == 2) { // mark
      //const dialog = document.getElementById('dialogMark');
      document.getElementById('dialogMarkMessageId').value = message_id;
      document.getElementById('dialogMarkMarkLabel').innerText = 'Оценка (максимум ' + max_mark + ')';
      document.getElementById('dialogMarkText').value = 'Задание зачтено';
      $('#dialogMark').modal('show');
    } else {
      //const dialog = document.getElementById('dialogAnswer');
      document.getElementById('dialogAnswerMessageId').value = message_id;
      document.getElementById('dialogAnswerText').value = '';
      $('#dialogAnswer').modal('show');
    }
  }

  function answerSend(form) {
    $(form)
      .find(':submit')
      .attr('disabled', 'disabled')
      .append(' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
  }

  function answerText(answer_text, message_id) {
    console.log('answer: ', answer_text, message_id);
  }

  function answerMark(answer_text, mark, message_id) {
    console.log('mark: ', answer_text, mark, message_id);
  }
</script>

<!-- End your project here-->
<?php
show_footer(); ?>