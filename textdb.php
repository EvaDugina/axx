<?php
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() - (60 * 60)));

require_once("common.php");
require_once("dbqueries.php");
require_once("POClasses/File.class.php");
require_once("POClasses/Commit.class.php");
require_once("POClasses/Message.class.php");

$au = new auth_ssh();
checkAuLoggedIN($au);

$file_name = 0;
$assignment = 0;
$responce = 0;
$commit_id = null;
$file_id = 0;

$array_tools_elems = array(
  "build" => "build",
  "cppcheck" => "cppcheck",
  "clang-format" => "clang-format",
  "valgrind" => "valgrind",
  "catch2" => "catch2",

  "pylint" => "pylint",
  "pytest" => "pytest",

  "copydetect" => "copydetect"
);

$folder_for_docker = getenv('HOST_DIR');
if ($folder_for_docker === false) {
  die('Переменная HOST_DIR не задана');
}

$session_id = session_id();
$folder_docker_share_session = "$folder_for_docker/share/" . (($session_id == false) ? "unknown" : $session_id);
if (!file_exists($folder_docker_share_session) || !is_dir($folder_docker_share_session))
  mkdir($folder_docker_share_session, 0777, true);

$User = new User((int)$au->getUserId());

function get_prev_assignments($assignment)
{
  global $dbconnect;
  $aarray = array();

  $query = select_prev_students($assignment);
  $result = pg_query($dbconnect, $query);

  if ($result && pg_num_rows($result) > 0) {
    $i = 0;
    $prev_assign = 0;
    $studlist = "";

    while ($ass = pg_fetch_assoc($result)) {
      if ($ass['aid'] == $prev_assign) {
        $studlist = $studlist . ' ' . $ass['fio'];
      } else {
        if ($prev_assign != 0)
          $aarray[$prev_assign] = $studlist;

        $prev_assign = $ass['aid'];
        $studlist = $ass['fio'];
      }
    }
    if ($prev_assign != 0)
      $aarray[$prev_assign] = $studlist;
  }

  return $aarray;
}

function get_prev_files($assignment)
{
  global $dbconnect;

  $farray = array();
  $aarray = get_prev_assignments($assignment);

  $query = select_prev_files($assignment);
  $result = pg_query($dbconnect, $query);

  while ($file = pg_fetch_assoc($result)) {
    $file_name = $file['assignment_id'] . ' ' . $aarray[$file['assignment_id']] . ' ' . $file['file_name'];
    $fulltext = $file['full_text'];
    array_push($farray, array("name" => $file_name, "text" => $fulltext));
  }

  return $farray;
}

if (array_key_exists('type', $_REQUEST))
  $type = urldecode($_REQUEST['type']);
else {
  echo "Некорректное обращение, отсутсвует ключ 'type'";
  http_response_code(400);
  exit;
}

if (array_key_exists('assignment', $_REQUEST)) {
  $assignment = $_REQUEST['assignment'];
  $Assignment = new Assignment((int)$assignment);
} else {
  echo "Некорректное обращение, отсутсвует ключ 'assignment'";
  http_response_code(400);
  exit;
}

$Commit = null;
if (array_key_exists('commit', $_REQUEST)) {
  $commit_id = urldecode($_REQUEST['commit']);
  $Commit = new Commit($commit_id);
} else {
  if ($au->isStudent())
    $Commit = $Assignment->getLastCommitForStudent();
  else
    $Commit = $Assignment->getLastCommitForTeacher();
  if ($Commit != null)
    $commit_id = $Commit->id;
}
// $result = pg_query($dbconnect, "select max(id) mid from ax.ax_solution_commit where assignment_id = $assignment");
// $result = pg_fetch_assoc($result);
// if ($result === false)
//   $commit_id = 0;
// else
//   $commit_id = $result['mid'];		  

if (array_key_exists('id', $_REQUEST))
  $file_id = urldecode($_REQUEST['id']);

if (array_key_exists('file_name', $_REQUEST))
  $file_name = urldecode($_REQUEST['file_name']);
else if ($file_id != 0) {
  $result = pg_query($dbconnect, "SELECT file_name from ax.ax_file where id = $file_id");
  $result = pg_fetch_assoc($result);
  $file_name = $result['file_name'];
} else if ($type != 'oncheck' && $type != 'tools' && $type != 'console' && $type != 'commit') {
  echo "Некорректное обращение, неизвестная операция";
  http_response_code(400);
  exit;
}

//-----------------------------------------------------------------OPEN-------------------------------------------------------
if ($type == "open") {

  // $responce = "Коммит $commit_id файла $file_name не найден";
  if (array_key_exists('id', $_REQUEST)) {
    $file_id = urldecode($_REQUEST['id']);
  } else {
    echo "Некорректное обращение, отсутствует идентификатор открываемого файла";
    http_response_code(400);
    exit;
  }

  $File = new File($file_id);
  header('Content-Type: text/plain');
  $responce = $File->getFullText();

  // выбираем файл по названию и номеру коммита
  // $result = pg_query($dbconnect, "SELECT ax.ax_file.id, ax.ax_file.file_name, full_text from ax.ax_file INNER JOIN ax.ax_commit_file ON ax.ax_commit_file.file_id = ax.ax_file.id where ax.ax_file.file_name = '$file_name' and ax.ax_commit_file.commit_id = $commit_id");
  // $result = pg_fetch_all($result);
  // foreach ($result as $item) {
  //   if ($item['id'] == $file_id) {
  //     header('Content-Type: text/plain');
  //     $responce = $item['full_text'];
  //   }
  // }
}

//-----------------------------------------------------------------SAVE-------------------------------------------------------
else if ($type == "save") {

  if (array_key_exists('likeid', $_REQUEST)) {
    $File = new File(urldecode($_REQUEST['likeid']));
  } else if ($Commit != null) {
    // $Commit = new Commit($commit_id);
    $File = $Commit->getFileByName($file_name);
  } else {
    echo "Невозможно найти файл, который требуется сохранить";
    http_response_code(400);
    exit;
  }



  // $id = 0;
  // $result = pg_query($dbconnect, "SELECT ax.ax_file.id from ax.ax_file INNER JOIN ax.ax_commit_file ON ax.ax_commit_file.file_id = ax.ax_file.id where file_name = '$file_name' and ax.ax_commit_file.commit_id = $commit_id");
  // $result = pg_fetch_assoc($result);
  // if (count($result) > 0)
  //   $id = $result['id'];
  // else if (array_key_exists('likeid', $_REQUEST))
  //   $id = urldecode($_REQUEST['likeid']);
  // else if (array_key_exists('id', $_REQUEST))
  //   $id = $file_id;
  // else {
  //   echo "Некорректное обращение";
  //   http_response_code(400);
  //   exit;
  // }

  if (array_key_exists('file', $_REQUEST)) {
    $file_text = $_REQUEST['file'];
    if ($file_name != $File->name_without_prefix)
      $File->setName(true, $file_name);
    $File->setFullText($file_text);
    // pg_query($dbconnect, 'UPDATE ax.ax_file SET full_text=$accelquotes$' . $file . '$accelquotes$, file_name=$accelquotes$' . $file_name . '$accelquotes$ where id=' . $id);
  } else {
    $File->setName(true, $file_name);
    // pg_query($dbconnect, 'UPDATE ax.ax_file SET file_name=$accelquotes$' . $file_name . '$accelquotes$ where id=' . $id);
  }

  $responce = $File->getFullText();
}

//-----------------------------------------------------------------NEW--------------------------------------------------------
else if ($type == "new") {

  // $result = pg_query($dbconnect, "select id from students where login='" . $au->getUserLogin() . "'");
  // $result = pg_fetch_all($result);
  // $user_id = $result[0]['id'];

  if ($Commit == null) {
    $Commit = new Commit($assignment, null, $User->id, 0, null);
  }

  // if ($commit_id == 0) {
  //   // создать первый коммит, если его нет
  //   $result = pg_query($dbconnect, "select id from students where login='" . $au->getUserLogin() . "'");
  //   $result = pg_fetch_all($result);
  //   $user_id = $result[0]['id'];

  //   // --- сессий пока нет
  //   $result = pg_query($dbconnect, "insert into ax.ax_solution_commit (assignment_id, session_id, student_user_id, type) values ($assignment, null, $user_id, 0) returning id;");
  //   $result = pg_fetch_all($result);
  //   $commit_id = $result[0]['id'];
  // }

  $File = new File(11, $file_name, null, null);
  $File->setName(true, $file_name);
  // $Commit = new Commit((int)$commit_id);
  $Commit->addFile($File->id);

  $return_values = array(
    "file_id" => $File->id,
    "download_url" => $File->getDownloadLink()
  );

  $responce = json_encode($return_values);
  //   $result = pg_query($dbconnect, "INSERT INTO ax.ax_solution_file (assignment_id, commit_id, file_name, type) VALUES ('$assignment', $commit_id, '$file_name', '11') returning id;");
  //   $result = pg_fetch_assoc($result);
  // if ($result === false) {
  // 	echo "Не удалось сохранить файл $file_name в коммит $commit_id";
  // 	http_response_code(400);
  // 	exit;
  // }
  // else
  // 	$responce = $result['id'];
}

//-----------------------------------------------------------------DEL---------------------------------------------------------
else if ($type == "del") {

  if ($Commit == null) {
    echo "Некорректное обращение: отсутствует идентификатор коммита";
    http_response_code(400);
    exit;
  }

  // тут нужно монотонное возрастание id-шников файлов

  $result = pg_query($dbconnect, "SELECT ax.ax_file.id from ax.ax_file INNER JOIN ax.ax_commit_file ON ax.ax_commit_file.file_id = ax.ax_file.id where file_name = '$file_name' and ax.ax_commit_file.commit_id = $Commit->id");
  $result = pg_fetch_assoc($result);
  if ($result === false) {
    echo "Не удалось найти удаляемый файл";
    http_response_code(400);
    exit;
  } else
    pg_query($dbconnect, "DELETE FROM ax.ax_file WHERE id=" . $result['id']);
  pg_query($dbconnect, "DELETE FROM ax.ax_commit_file WHERE file_id=" . $result['id']);
}
//-----------------------------------------------------------------DEL---------------------------------------------------------
else if ($type == "rename") {

  if ($Commit == null) {
    echo "Некорректное обращение: отсутствует идентификатор коммита";
    http_response_code(400);
    exit;
  }

  $new_file_name = urldecode($_REQUEST['new_file_name']);

  pg_query($dbconnect, "UPDATE ax.ax_file SET file_name = '$new_file_name' WHERE id = $file_id");
}

//---------------------------------------------------------------COMMIT-------------------------------------------------------
else if ($type == "commit") {

  if ($Commit == null) {
    echo "Некорректное обращение: отсутствует идентификатор коммита";
    http_response_code(400);
    exit;
  }

  if (array_key_exists('commit_type', $_REQUEST)) {
    if ($_REQUEST['commit_type'] == "intermediate") {
      $cloneCommit = getCommitCopy($Assignment->id, $User->id, $Commit);
      if ($au->isStudent())
        $type = 0;
      else
        $type = 2;
      $cloneCommit->setType($type);
      // header("Location:editor.php?assignment=" . $Assignment->id);
      $responce = json_encode(array("assignment_id" => $Assignment->id, "commit_id" => $cloneCommit->id));
    } else {
      if ($au->isStudent())
        $Commit->setType(1);
      else
        $Commit->setType(3);
      // header("Location:editor.php?assignment=" . $Assignment->id);
    }
  } else {
    echo "Некорректное обращение: отсутствует тип коммита";
    http_response_code(400);
    exit;
  }
}

//---------------------------------------------------------------ONCHECK-------------------------------------------------------
else if ($type == "oncheck") {

  if ($Commit == null) {
    echo "Некорректное обращение: отсутствует идентификатор коммита";
    http_response_code(400);
    exit;
  }

  $answerCommit = getCommitCopy($Assignment->id, $au->getUserId(), $Commit);
  if ($User->isStudent()) {
    $answerCommit->setType(1);
  } else {
    $answerCommit->setType(3);
  }

  if ($User->isStudent()) {
    $Message = new Message((int)$Assignment->id, 1, $User->id, $User->role, "");
    $Assignment->addMessage($Message->id);
    $Message->setCommit($answerCommit->id);
    $File = new File(10, 'Версия на проверку', "editor.php?assignment=$Assignment->id&commit=$answerCommit->id", null);
    $Message->addFile($File->id);

    // Отправка сообщения-ссылки для преподавателя
    // $linkMessage = new Message((int)$Assignment->id, 3, $User->id, $User->role, null, "editor.php?assignment=$Assignment->id&commit=$answerCommit->id", 2);
    // $Assignment->addMessage($linkMessage->id);
  } else {
    $Message = new Message((int)$Assignment->id, 1, $User->id, $User->role, $Assignment->getLastAnswerMessage()->id, "");
    $Assignment->addMessage($Message->id);
    $Message->setCommit($answerCommit->id);
    $File = new File(10, 'Проверенная версия', "editor.php?assignment=$assignment&commit=$answerCommit->id", null);
    $Message->addFile($File->id);

    // Отправка сообщения-ссылки для студента
    // $linkMessage = new Message((int)$Assignment->id, 3, $User->id, $User->role, null, "editor.php?assignment=$Assignment->id&commit=$answerCommit->id", 3);
    // $Assignment->addMessage($linkMessage->id);
  }

  if ($User->isStudent()) {
    $Assignment->setStatus(1);
  } else {
    $Assignment->setStatus(2);
  }
}

//---------------------------------------------------------------TOOLS-------------------------------------------------------
else if ($type == "tools") {

  // ОЧИСТКА ДИРЕКТОРИИ
  $files = array_diff(scandir($folder_docker_share_session), array('.', '..'));
  foreach ($files as $file) {
    if (!is_dir($folder_docker_share_session . '/' . $file))
      unlink($folder_docker_share_session . '/' . $file);
  }

  // ОЧИСТКА ДИРЕКТОРИИ AUTOTESTING
  $folder_autotesting = $folder_docker_share_session . "/autotesting";
  if (!file_exists($folder_autotesting) || !is_dir($folder_autotesting))
    mkdir($folder_autotesting, 0777, true);
  $files = array_diff(scandir($folder_autotesting), array('.', '..'));
  foreach ($files as $file) {
    unlink($folder_autotesting . '/' . $file);
  }


  if ($Commit == null) {
    echo "Некорректное обращение. Отсутствует идентификатор коммита";
    http_response_code(400);
    exit;
  }

  $result = pg_query($dbconnect,  "select ax.ax_assignment.id aid, ax.ax_task.id tid, ax.ax_assignment.checks achecks, ax.ax_task.checks tchecks " .
    " from ax.ax_assignment inner join ax.ax_task on ax.ax_assignment.task_id = ax.ax_task.id where ax.ax_assignment.id = " . $assignment);
  $row = pg_fetch_assoc($result);
  $checks = $row['achecks'];
  if ($checks == null)
    $checks = $row['tchecks'];
  if ($checks == null)
    $checks = getDefaultChecksPreset();

  $checks = json_decode($checks, true);

  $no_tools_choosed = true;
  foreach ($array_tools_elems as $key) {
    if (!isset($_REQUEST[$key])) {
      $checks['tools'][$key]['enabled'] = false;
      continue;
    }
    $checks['tools'][$key]['enabled'] = str2bool($_REQUEST[$key]);
    if ($checks['tools'][$key]['enabled'])
      $no_tools_choosed = false;
    // Проверяем BIN
    if ($key == $array_tools_elems['catch2'] || $key == $array_tools_elems['pytest']);
    else if ($key == $array_tools_elems['build']) {
      $checks['tools'][$key]['bin'] = ($checks['tools'][$key]['language'] == "C") ? "gcc" : "g++";
    } else
      $checks['tools'][$key]['bin'] = $key;
  }

  if ($no_tools_choosed) {
    echo json_encode(array("internal-error" => "Не выбран ни один инстурмент проверки!"));
    exit;
  }

  // получение файла проверки
  $files_codeTest = array();
  $Task = new Task((int)getTaskByAssignment((int)$assignment));
  if (count($Task->getCodeTestFiles()) < 1) {
    if (array_key_exists($array_tools_elems['catch2'], $checks['tools'])) {
      $checks['tools'][$array_tools_elems['catch2']]['enabled'] = false;
    } else if (array_key_exists($array_tools_elems['pytest'], $checks['tools'])) {
      $checks['tools'][$array_tools_elems['pytest']]['enabled'] = false;
    }
  } else {
    foreach ($Task->getCodeTestFiles() as $File) {
      if (array_key_exists($array_tools_elems['catch2'], $checks['tools'])) {
        $checks["tools"][$array_tools_elems['catch2']]["test_path"] = [$File->name];
      } else if (array_key_exists($array_tools_elems['pytest'], $checks['tools'])) {
        $checks["tools"][$array_tools_elems['pytest']]["test_path"] = [$File->name];
      }
      @unlink($folder_docker_share_session . '/' . $File->name);
      $myfile = fopen($folder_docker_share_session . '/' . $File->name, "w");
      if (!$myfile) {
        echo "Невозможно открыть файл ($File->name) автотеста!";
        http_response_code(500);
        exit;
      }
      fwrite($myfile, $File->getFullText());
      fclose($myfile);
      array_push($files_codeTest, $File->name);
    }

    if (count($files_codeTest) < 1) {
      echo "Не найдены файлы теста!" . $Task->id;
      http_response_code(400);
      exit;
    }
  }

  // Убираем лишние параметры из проверки
  $tools_not_enabled = [];
  foreach ($checks['tools'] as $key => $tool_json) {
    if (!$tool_json['enabled']) {
      array_push($tools_not_enabled, $key);
    }
  }
  foreach ($tools_not_enabled as $key) {
    unset($checks['tools'][$key]);
  }

  $checks = json_encode($checks);
  // var_dump($checks);
  // exit;

  $myfile = fopen($folder_docker_share_session . '/config.json', "w") or die("Невозможно открыть файл конфигурации!");
  fwrite($myfile, $checks);
  fclose($myfile);

  // $result = pg_query($dbconnect,  "SELECT * from ax.ax_solution_file where commit_id = ".$commit_id);
  // $result = pg_query($dbconnect, "SELECT * FROM ax.ax_file INNER JOIN ax.ax_commit_file ON ax.ax_commit_file.file_id = ax.ax_file.id WHERE ax.ax_commit_file.commit_id = $commit_id");
  // while ($row = pg_fetch_assoc($result)) {
  //   $myfile = fopen($folder_docker_share_session . '/' . $row['file_name'], "w") or die("Unable to open file!");
  //   fwrite($myfile, $row['full_text']);
  //   fclose($myfile);
  //   if (strtoupper($row['file_name']) != 'MAKEFILE')
  //     array_push($files, $row['file_name']);
  // }

  $files = array();
  // $Commit = new Commit($commit_id);
  foreach ($Commit->getFiles() as $File) {
    $myfile = fopen($folder_docker_share_session . '/' . $File->name, "w") or die("Невозможно открыть файл ($File->name) проекта!");
    if (!$myfile) {
      echo "Невозможно открыть файл ($File->name) проекта!";
      http_response_code(500);
      exit;
    }
    fwrite($myfile, $File->getFullText());
    fclose($myfile);
    if (strtoupper($File->name) != 'MAKEFILE')
      array_push($files, $File->name);
  }

  if (count($files) < 1) {
    echo "Не найдены файлы коммита " . $Commit->id;
    http_response_code(400);
    exit;
  }

  @unlink($folder_docker_share_session . '/autotesting/for_copydetect');
  @mkdir($folder_docker_share_session . '/autotesting/for_copydetect', 0777, true);
  $Task = new Task(getTaskByAssignment($Assignment->id));
  $prev_files = [];
  foreach ($Task->getAssignments() as $anoutherAssignment) {
    if ($anoutherAssignment->id == $Assignment->id)
      continue;
    $lastCommit = $anoutherAssignment->getLastCommitForTeacher();
    foreach ($lastCommit->getFiles() as $prevFile) {
      array_push($prev_files, array(
        "name" => $anoutherAssignment->getTag() . "_" . $prevFile->name,
        "full_text" => $prevFile->getFullText()
      ));
    }
  }
  foreach ($prev_files as $prev_file) {
    $cyr = [
      'а',
      'б',
      'в',
      'г',
      'д',
      'е',
      'ё',
      'ж',
      'з',
      'и',
      'й',
      'к',
      'л',
      'м',
      'н',
      'о',
      'п',
      'р',
      'с',
      'т',
      'у',
      'ф',
      'х',
      'ц',
      'ч',
      'ш',
      'щ',
      'ъ',
      'ы',
      'ь',
      'э',
      'ю',
      'я',
      'А',
      'Б',
      'В',
      'Г',
      'Д',
      'Е',
      'Ё',
      'Ж',
      'З',
      'И',
      'Й',
      'К',
      'Л',
      'М',
      'Н',
      'О',
      'П',
      'Р',
      'С',
      'Т',
      'У',
      'Ф',
      'Х',
      'Ц',
      'Ч',
      'Ш',
      'Щ',
      'Ъ',
      'Ы',
      'Ь',
      'Э',
      'Ю',
      'Я'
    ];
    $lat = [
      'a',
      'b',
      'v',
      'g',
      'd',
      'e',
      'io',
      'zh',
      'z',
      'i',
      'y',
      'k',
      'l',
      'm',
      'n',
      'o',
      'p',
      'r',
      's',
      't',
      'u',
      'f',
      'h',
      'ts',
      'ch',
      'sh',
      'sht',
      'a',
      'i',
      'y',
      'e',
      'yu',
      'ya',
      'A',
      'B',
      'V',
      'G',
      'D',
      'E',
      'Io',
      'Zh',
      'Z',
      'I',
      'Y',
      'K',
      'L',
      'M',
      'N',
      'O',
      'P',
      'R',
      'S',
      'T',
      'U',
      'F',
      'H',
      'Ts',
      'Ch',
      'Sh',
      'Sht',
      'A',
      'I',
      'Y',
      'e',
      'Yu',
      'Ya'
    ];
    $transname = str_replace($cyr, $lat, $prev_file["name"]);

    $myfile = fopen($folder_docker_share_session . '/autotesting/for_copydetect/' . $transname, "w");
    if (!$myfile) {
      echo "Ошибка создания файла для проверки!";
      http_response_code(500);
      exit;
    }
    fwrite($myfile, $prev_file["full_text"]);
    fclose($myfile);
  }

  // @unlink($folder_docker_share_session . '/output.json');

  $output = null;
  $retval = null;

  // Локальная проверка модуля python_code_check
  // chdir($folder_docker_share_session);
  // exec("python -m python_code_check -c config.json " . implode(' ', $files) . ' 2>&1', $output, $retval);

  $checks = json_decode($checks, true);
  if ((isset($checks['tools'][$array_tools_elems['pylint']]) && $checks['tools'][$array_tools_elems['pylint']]['enabled'])
    || (isset($checks['tools'][$array_tools_elems['pytest']]) && $checks['tools'][$array_tools_elems['pytest']]['enabled'])
  ) {
    // Для отладки: Без удаления контейнера + Зависание контейнера:
    // $command = 'docker run --net=host --rm -v ' . "$folder_docker_share_session" . ':/tmp/work -w=/tmp/work nitori_sandbox bash -c "python_code_check -c config.json ' . implode(' ', $files) . ' && tail -f /dev/null" 2>&1';
    $command = 'docker run --net=host --rm -v ' . "$folder_docker_share_session" . ':/tmp/work -w=/tmp/work nitori_sandbox python_code_check -c config.json ' . implode(' ', $files) . ' 2>&1';
    exec($command, $output, $retval);
  } else {
    // Для отладки: Без удаления контейнера + Зависание контейнера:
    // $command = 'docker run --net=host --rm -v ' . "$folder_docker_share_session" . :/tmp/work -w=/tmp/work nitori_sandbox bash -c "c_code_check -c config.json ' . implode(' ', $files) . ' && tail -f /dev/null" 2>&1';
    $command = 'docker run --net=host --rm -v ' . "$folder_docker_share_session" . ':/tmp/work -w=/tmp/work nitori_sandbox c_code_check -c config.json ' . implode(' ', $files) . ' 2>&1';
    exec($command, $output, $retval);
  }
  //$responce = 'docker run -it --net=host --rm -v '.$folder_docker_share_session.':/tmp nitori_sandbox c_code_check -c config.json -i'.$commit_id.' '.implode(' ', $files);
  //exec('docker run -it --net=host --rm -v '.$folder_docker_share_session.':/tmp -w=/tmp nitori_sandbox c_code_check -c config.json -i '.$commit_id.' '.implode(' ', $files), $output, $retval);
  //echo 'docker run -it --net=host --rm -v '.$folder_docker_share_session.':/tmp -w=/tmp nitori_sandbox c_code_check -c config.json '.implode(' ', $files); exit;
  /* Получение результатов проверки из БД
  //$responce = 'docker run -it --net=host --rm -v '.$folder_docker_share_session.':/tmp nitori_sandbox c_code_check -c config.json -i'.$commit_id.' '.implode(' ', $files);
  //exec('docker run -it --net=host --rm -v '.$folder_docker_share_session.':/tmp -w=/tmp nitori_sandbox c_code_check -c config.json -i '.$commit_id.' '.implode(' ', $files), $output, $retval);
  // exec('docker run --net=host --rm -v ' . $folder_docker_share_session . ':/tmp -v /var/app/utility:/stable -w=/tmp nitori_sandbox c_code_check -c config.json ' . implode(' ', $files) . ' 2>&1', $output, $retval);
  //echo 'docker run -it --net=host --rm -v '.$folder_docker_share_session.':/tmp -w=/tmp nitori_sandbox c_code_check -c config.json '.implode(' ', $files); exit;
  /* Получение результатов проверки из БД
	$result = pg_query($dbconnect,  "select autotest_results from ax.ax_solution_commit where id = ".$commit_id);
	if (!($row = pg_fetch_assoc($result))) {
	  echo "<pre>Ошибка при получении результатов проверок (".$retval."):\n";
	  echo $output;
	  echo "</pre>";
      http_response_code(400);
      exit;
	}
	$responce = $row['autotest_results'];
*/

  /* Получение результатов проверки из файла */
  $file_name = $folder_docker_share_session . '/output.json';
  if (!file_exists($file_name)) {
    echo json_encode(["internal-error" => "Не удалось найти файл output.json в папке с результатами файлов:\n$output"]);
    // var_dump($output);
    http_response_code(500);
    exit;
  }

  $myfile = fopen($folder_docker_share_session . '/output.json', "r");
  if (!$myfile) {
    echo json_encode(["internal-error" => "Не удалось получить результаты проверки из файла: \n$output"]);
    // echo "Не удалось получить результаты проверки из файла:<br>";
    // var_dump($output);
    http_response_code(500);
    exit;
  }
  $responce = fread($myfile, filesize($folder_docker_share_session . '/output.json'));
  fclose($myfile);

  // $myfile = fopen($folder_docker_share_session . '/output_pytest.txt', "r");
  // if (!$myfile) {
  //   echo "Не удалось получить результаты проверки из файла:<br>";
  //   http_response_code(500);
  //   exit;
  // }
  // $responce .= fread($myfile, filesize($folder_docker_share_session . '/output_pytest.txt'));
  // fclose($myfile);

  pg_query($dbconnect, 'update ax.ax_solution_commit set autotest_results = $accelquotes$' . $responce . '$accelquotes$ where id = ' . $Commit->id);
  /**/

  header('Content-Type: application/json');
}

//---------------------------------------------------------------CONSOLE-------------------------------------------------------
else if ($type == "console") {
  if (!array_key_exists('tool', $_REQUEST)) {
    echo "Отсутсвует ключ 'tool'";
    http_response_code(400);
    exit;
  }
  $tool =  $_REQUEST['tool'];

  $ext = "txt";
  if ($tool == $array_tools_elems['cppcheck'] || $tool == $array_tools_elems['clang-format'])
    $ext = "xml";

  $file_name = $folder_docker_share_session . '/output_' . $tool . '.' . $ext;
  if (!file_exists($file_name)) {
    echo "Перезапустите проверку!";
    http_response_code(200);
    exit;
  }

  $myfile = fopen($file_name, "r");
  if (!$myfile) {
    echo "Перезапустите проверку!";
    http_response_code(200);
    exit;
  }
  $text = htmlspecialchars(fread($myfile, filesize($file_name)));
  fclose($myfile);

  $text = mb_convert_encoding($text, "UTF-8", "auto");
  $len_char = strlen(htmlspecialchars("'"));
  if ($tool == $array_tools_elems['build'] && $text[0] == 'b') {
    $text = substr($text, 1 + $len_char, strlen($text) - 1 - $len_char * 2);
    if ($text == "")
      $text = "Пустой полный вывод!";
  }
  $responce = replaceBytes($text);

  header('Content-Type: text/plain');
}

function replaceBytes($str)
{
  return preg_replace_callback(
    '/\\\\x([0-9A-Fa-f]{2})/',
    function ($matches) {
      return "";
    },
    $str
  );
}


//-----------------------------------------------------------------------------------------------------------------------------
?>
<?= $responce ?>
