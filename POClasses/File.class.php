<?php
require_once("./settings.php");

class File
{

  public $id = null;
  public $type = null;  // тип файла (0 - просто файл, 1 - шаблон проекта, 2 - код теста, 3 - эталонный код, 
  // 10 - просто файл с результатами, 11 - файл проекта)
  // 21 - иконка пользователя, 22 - иконка раздела
  public $name = null, $download_url = null, $full_text = null;
  public $visibility = null; // 0 - не видно студенту, 1 - видно всем 
  public $status = null; // 0 - не удалённый файл, 2 - удалённый файл

  public $name_without_prefix = null;


  public function __construct()
  {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1) {
      $this->id = (int)$args[0];

      $query = queryGetFileInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $file = pg_fetch_assoc($result);

      if ($file) {
        $this->name = $file['file_name'];
        $this->type = (int)$file['type'];
        if (isset($file['visibility']))
          $this->visibility = (int)$file['visibility'];
        $this->download_url = $file['download_url'];
        if ($this->download_url != null) {
          $this->name_without_prefix = deleteRandomPrefix($this->name);
        } else {
          $this->name_without_prefix = $this->name;
        }
        $this->full_text = $file['full_text'];
        $this->status = (int)$file['status'];
      }
    } else if ($count_args == 2) {
      $this->type = (int)$args[0];
      if ($this->type == 2 || $this->type == 3)
        $this->visibility = 0;
      else
        $this->visibility = 1;
      $this->name_without_prefix = $args[1];
      $this->name = addRandomPrefix($this->name_without_prefix);
      $this->status = 0;

      $this->pushNewToDB();
    } else if ($count_args == 4) {
      $this->type = (int)$args[0];
      if ($this->type == 2 || $this->type == 3)
        $this->visibility = 0;
      else
        $this->visibility = 1;
      $this->name_without_prefix = $args[1];
      $this->download_url = $args[2];
      $this->full_text = $args[3];

      if ($this->download_url != null)
        $this->name = addRandomPrefix($this->name_without_prefix);
      else
        $this->name = $this->name_without_prefix;

      $this->status = 0;

      $this->pushNewToDB();
    } else {
      die('Неверные аргументы в конструкторе File');
    }
  }


  // GETTERS

  public function getExt()
  {
    return strtolower(preg_replace('#.{0,}[.]#', '', $this->name_without_prefix));
  }

  public function getFullText()
  {
    if ($this->download_url != null) {
      return getFileContentByPath($this->download_url);
    } else {
      return $this->full_text;
    }
  }

  function getDownloadLink()
  {
    if ($this->download_url == null) {
      return 'download_file.php?file_id=' . $this->id;
    }

    // Если файл лежит на сервере
    else if (!preg_match('#^http[s]{0,1}://#', $this->download_url)) {
      if (strpos($this->download_url, 'editor.php') === false)
        return 'download_file.php?file_path=' . $this->download_url;
      else
        return $this->download_url;
    }

    // Такого не может быть
    else {
      return null;
    }
  }

  function getNameWithoutPrefixAndExt()
  {
    return preg_replace("/\.[^.]+$/", "", $this->name_without_prefix);
  }

  function getMainInfoAsTextForDowload()
  {
    $this->full_text = addslashes($this->getFullText());
    $this->name = addslashes($this->name);
    return queryInsertFileWithFullTextWithDeclaredVariablePageId($this);
  }

  function isVisible()
  {
    return $this->visibility == 1;
  }

  function isAttached()
  {
    return isAttachedType($this->type);
  }

  function isProjectTemplate()
  {
    return isProjectTemplateType($this->type);
  }

  function isCodeTest()
  {
    return isCodeTestType($this->type);
  }

  function isCodeCheckTest()
  {
    return isCodeCheckTestType($this->type);
  }

  function isInUploadDir()
  {
    return $this->download_url != null && isInUploadDir($this->download_url);
  }


  // -- END GETTERS

  // SETTERS

  public function setName($isWithoutPrefix, $name)
  {
    global $dbconnect;

    if ($isWithoutPrefix) {
      if ($this->isInUploadDir()) {
        $this->name = addRandomPrefix($name);
      } else {
        $this->name = $name;
      }
      $this->name_without_prefix = $name;
    } else {
      $this->name = $name;
      $this->name_without_prefix = deleteRandomPrefix($this->name);
    }

    if ($this->isInUploadDir()) {
      $this->setNameInUpload($this->name);
    }

    $query = "UPDATE ax.ax_file SET file_name = \$antihype1\$$this->name\$antihype1\$
                WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function setNameInUpload($new_file_name_with_prefix)
  {
    $new_download_url = getUploadFileDir() . $new_file_name_with_prefix;
    setFileNameByPath($this->download_url, $new_download_url);
    $this->setDownloadUrl($new_download_url);
  }

  public function setDownloadUrl($download_url)
  {
    global $dbconnect;

    $this->download_url = $download_url;
    $query = "UPDATE ax.ax_file SET download_url = '$this->download_url'
                WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function setFullText($full_text)
  {
    global $dbconnect;

    if (!$this->isInUploadDir()) {
      if ($this->full_text != $full_text)
        $this->full_text = $full_text;
      $query = "UPDATE ax.ax_file SET full_text = \$antihype1\$$this->full_text\$antihype1\$
                  WHERE id = $this->id;
      ";

      pg_query($dbconnect, $query) or pg_query($dbconnect, mb_convert_encoding($query, 'UTF-8', 'CP1251')) or die('Ошибка запроса: ' . pg_last_error());
    } else {
      $this->setFullTextInUpload($full_text);
    }
  }
  private function setFullTextInUpload($full_text)
  {
    if ($this->getFullText() != $full_text) {
      setFileFullTextByPath($this->download_url, $full_text);
    }
  }
  public function setType($type)
  {
    global $dbconnect;

    $this->type = (int)$type;

    $query = "UPDATE ax.ax_file SET type = $this->type
              WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    if ($this->type == 2 || $this->type == 3)
      $this->visibility = 0;
    else
      $this->visibility = 1;

    $this->setVisibility($this->visibility);
  }
  public function setVisibility($visibility)
  {
    global $dbconnect;

    $this->visibility = (int)$visibility;

    $query = "UPDATE ax.ax_file SET visibility = $this->visibility
              WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // -- END SETTERS




  // WORK WITH FILE

  public function pushNewToDB()
  {
    global $dbconnect;

    $query = getQueryInsertFile($this);

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushChangesToDB()
  {
    global $dbconnect;

    if (isset($this->download_url)) {
      $query = "UPDATE ax.ax_file SET type = $this->type, visibility = $this->visibility, file_name = \$antihype1\$$this->name\$antihype1\$, 
                download_url = '$this->download_url'
                WHERE id = $this->id;
      ";
    } else if (isset($this->full_text)) {
      $query = "UPDATE ax.ax_file SET type = $this->type, visibility = $this->visibility, file_name = \$antihype1\$$this->name\$antihype1\$,  
                full_text = \$antihype1\$$this->full_text\$antihype1\$
                WHERE id = $this->id;
      ";
    } else {
      exit("Incorrect File->pushChangesToDB()");
    }

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function pushAllChangesToDB()
  {
    global $dbconnect;

    if ($this->isInUploadDir()) {
      $query = "UPDATE ax.ax_file
                SET type=$this->type, visibility=$this->visibility, file_name=\$antihype1\$$this->name\$antihype1\$, 
                download_url='$this->download_url', full_text=null, status=$this->status
                WHERE id = $this->id;
      ";
    } else if ($this->full_text != null) {
      $query = "UPDATE ax.ax_file
                SET type=$this->type, visibility=$this->visibility, file_name=\$antihype1\$$this->name_without_prefix\$antihype1\$, 
                full_text=\$antihype1\$$this->full_text\$antihype1\$, download_url=null, status=$this->status 
                WHERE id = $this->id;
      ";
    } else {
      $query = "UPDATE ax.ax_file
                SET type=$this->type, visibility=$this->visibility, file_name=\$antihype1\$$this->name_without_prefix\$antihype1\$, 
                full_text=null, download_url=null, status=$this->status 
                WHERE id = $this->id;
      ";
    }
    pg_query($dbconnect, $query) or pg_query($dbconnect, mb_convert_encoding($query, 'UTF-8', 'CP1251')) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB()
  {
    global $dbconnect;

    deleteFile($this->download_url);

    $query = "UPDATE ax.ax_file SET status = 2 WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }


  public function copy($file_id)
  {
    $File = new File((int)$file_id);

    $this->type = $File->type;

    $this->status = $File->status;

    if (isInUploadDir($File->download_url)) {
      // $this->name_without_prefix = $File->name_without_prefix;
      // $this->name = addRandomPrefix($File->name_without_prefix);
      // $this->download_url = getUploadFileDir() . $this->name;
      $this->name = $File->name_without_prefix;
      $this->name_without_prefix = $File->name_without_prefix;
      $this->download_url = "";
      // $this->full_text = $File->getFullText();
      $this->full_text = "";
    } else {
      $this->name = $File->name;
      $this->name_without_prefix = $File->name_without_prefix;
      $this->download_url = "";
      $this->full_text = $File->full_text;
    }

    $this->pushAllChangesToDB();

    // Если файлы на сервере
    if ($this->isInUploadDir()) {
      $this->createFileInUpload($File->getFullText());
    }
  }

  function createFileInUpload($full_text)
  {
    $file_dir = getUploadFileDir();
    $file_path = $file_dir . $this->name;

    $myfile = fopen($file_path, "w") or die("Unable to open file!");
    fwrite($myfile, $full_text);
    fclose($myfile);

    // $this->setName(false, $this->name);
  }

  // -- END WORK WITH FILE

}


///

function isAttachedType($type)
{
  return $type == 0;
}

function isProjectTemplateType($type)
{
  return $type == 1;
}

function isCodeTestType($type)
{
  return $type == 2;
}

function isCodeCheckTestType($type)
{
  return $type == 3;
}

///

function deleteFile($download_url)
{
  if ($download_url != "" && file_exists($download_url))
    unlink($download_url);
}

function getCompressedFileName($file_name, $num_symbols = 20)
{
  $len = mb_strlen($file_name, 'UTF-8');
  if (mb_strlen($file_name, 'UTF-8') > $num_symbols)
    return mb_substr($file_name, 0, $num_symbols, 'UTF-8') . "...";
  return $file_name;
}


// Добавление рандомного префикса к названию файла, чтобы избежать оибки добавления файлов с одинаковым названием
function addRandomPrefix($file_name)
{
  return randPrefix() .  $file_name;
}

// Декодирование префиксного названия файла
function deleteRandomPrefix($db_file_name)
{
  return preg_replace('#[0-9]{0,}_#', '', $db_file_name, 1);
}

// Генерация префикса для уникальности названий файлов, которые хранятся на сервере
function randPrefix()
{
  return time() . mt_rand(0, 9999) . mt_rand(0, 9999) . '_';
}

function getUploadFileDir()
{
  return 'upload_files/';
}

function getAvailableCodeTestsExtsWithNames()
{
  return array('cpp' => "C++", 'c' => "C", 'py' => "Python");
}

function getAvailableCodeTestsFilesExts()
{
  return array('cpp', 'c', 'py');
}

function getSpecialFileTypes()
{
  return array('cpp', 'c', 'h', 'txt', 'py');
}

function getImageFileTypes()
{
  return array('img', 'png', 'jpeg', 'jpg', 'gif');
}

function getMaxFileSize()
{
  return 5242880;
}

function setFileFullTextByPath($file_path, $full_text)
{
  if (strpos($file_path, "editor.php?") !== false)
    return "";
  file_put_contents($file_path, $full_text);
}

function setFileNameByPath($file_last_path, $file_new_path)
{
  if (strpos($file_last_path, "editor.php?") !== false)
    return "";
  rename($file_last_path, $file_new_path);
}

function getFileContentByPath($file_path)
{
  if (strpos($file_path, "editor.php?") !== false)
    return "";
  $file_full_text = file_get_contents($file_path);
  // $file_full_text = stripcslashes($file_full_text);
  // $file_full_text = preg_replace('#\'#', '\'\'', $file_full_text);
  return $file_full_text;
}

function convertWebFilesToFiles($name_files)
{
  $files = array();
  for ($i = 0; $i < count($_FILES[$name_files]['tmp_name']); $i++) {
    if (!is_uploaded_file($_FILES[$name_files]['tmp_name'][$i])) {
      continue;
    } else {
      array_push($files, [
        'name' => $_FILES[$name_files]['name'][$i],
        'tmp_name' => $_FILES[$name_files]['tmp_name'][$i]
      ]);
    }
  }
  return $files;
}



// function isInUploadDir($file_ext)
// {
//   return !in_array($file_ext, getSpecialFileTypes());
// }

function isInUploadDir($download_url)
{
  return strpos($download_url, getUploadFileDir()) !== false;
}


// Object это Message или Task 
function addFilesToObject($Object, $WEB_FILES, $type)
{
  // Файлы с этими расширениями надо хранить в БД
  for ($i = 0; $i < count($WEB_FILES); $i++) {
    addFileToObject($Object, $WEB_FILES[$i]['name'], $WEB_FILES[$i]['tmp_name'], $type);
  }
}
function addFileToObject($Object, $file_name, $file_tmp_name, $type)
{

  $store_in_db = getSpecialFileTypes();

  $File = new File($type, $file_name);

  $file_ext = $File->getExt();
  $file_dir = getUploadFileDir();
  $file_path = $file_dir . $File->name;

  // Перемещаем файл пользователя из временной директории сервера в директорию $file_dir
  if (move_uploaded_file($file_tmp_name, $file_path)) {

    // Если файлы такого расширения надо хранить на сервере, добавляем в БД путь к файлу на сервере
    if (!in_array($file_ext, $store_in_db)) {

      $File->setDownloadUrl($file_path);
      $Object->addFile($File->id);
    } else { // Если файлы такого расширения надо хранить в БД, добавляем в БД полный текст файла

      $File->setName(false, $File->name_without_prefix);
      $file_full_text = getFileContentByPath($file_path);
      $File->setFullText($file_full_text);
      $Object->addFile($File->id);
      unlink($file_path);
    }

    return $File->id;
  } else {
    exit("Ошибка загрузки файла");
  }
}


function getSVGByFileType($type)
{
  echo getStringSVGByFileType($type);
}

function getStringSVGByFileType($type)
{
  switch ($type) {
    case 0:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-fill" viewBox="0 0 16 16">
        <path d="M4 0h5.293A1 1 0 0 1 10 .293L13.707 4a1 1 0 0 1 .293.707V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zm5.5 1.5v2a1 1 0 0 0 1 1h2l-3-3z" />
      </svg>';
    case 1:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-code" viewBox="0 0 16 16">
        <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5z"/>
        <path d="M8.646 6.646a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L10.293 9 8.646 7.354a.5.5 0 0 1 0-.708m-1.292 0a.5.5 0 0 0-.708 0l-2 2a.5.5 0 0 0 0 .708l2 2a.5.5 0 0 0 .708-.708L5.707 9l1.647-1.646a.5.5 0 0 0 0-.708"/>
      </svg>';
    case 2:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-braces-asterisk" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M1.114 8.063V7.9c1.005-.102 1.497-.615 1.497-1.6V4.503c0-1.094.39-1.538 1.354-1.538h.273V2h-.376C2.25 2 1.49 2.759 1.49 4.352v1.524c0 1.094-.376 1.456-1.49 1.456v1.299c1.114 0 1.49.362 1.49 1.456v1.524c0 1.593.759 2.352 2.372 2.352h.376v-.964h-.273c-.964 0-1.354-.444-1.354-1.538V9.663c0-.984-.492-1.497-1.497-1.6M14.886 7.9v.164c-1.005.103-1.497.616-1.497 1.6v1.798c0 1.094-.39 1.538-1.354 1.538h-.273v.964h.376c1.613 0 2.372-.759 2.372-2.352v-1.524c0-1.094.376-1.456 1.49-1.456v-1.3c-1.114 0-1.49-.362-1.49-1.456V4.352C14.51 2.759 13.75 2 12.138 2h-.376v.964h.273c.964 0 1.354.444 1.354 1.538V6.3c0 .984.492 1.497 1.497 1.6M7.5 11.5V9.207l-1.621 1.621-.707-.707L6.792 8.5H4.5v-1h2.293L5.172 5.879l.707-.707L7.5 6.792V4.5h1v2.293l1.621-1.621.707.707L9.208 7.5H11.5v1H9.207l1.621 1.621-.707.707L8.5 9.208V11.5z"/>
      </svg>';
    case 3:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-check-fill" viewBox="0 0 16 16">
  <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1m1.354 4.354-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708.708"/>
</svg>';
    case 10:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-lock2-fill" viewBox="0 0 16 16">
        <path d="M7 7a1 1 0 0 1 2 0v1H7z"/>
        <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1M10 7v1.076c.54.166 1 .597 1 1.224v2.4c0 .816-.781 1.3-1.5 1.3h-3c-.719 0-1.5-.484-1.5-1.3V9.3c0-.627.46-1.058 1-1.224V7a2 2 0 1 1 4 0"/>
      </svg>';
    case 11:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-fill" viewBox="0 0 16 16">
        <path d="M4 0h5.293A1 1 0 0 1 10 .293L13.707 4a1 1 0 0 1 .293.707V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zm5.5 1.5v2a1 1 0 0 0 1 1h2l-3-3z" />
      </svg>';
  }
}




// ФУНКЦИИ ЗАПРОСОВ К БД

function queryGetFileInfo($file_id)
{
  return "SELECT * FROM ax.ax_file WHERE id = $file_id;
  ";
}

function getQueryInsertFile($File)
{
  if ($File->full_text == null && $File->download_url != null) {
    return queryInsertFileWithDownloadUrl($File);
  } else if ($File->full_text != null && $File->download_url == null) {
    return queryInsertFileWithFullText($File);
  } else {
    return queryInsertFileEmpty($File);
  }
}

function queryInsertFileWithDownloadUrl($File)
{
  return "INSERT INTO ax.ax_file (type, visibility, file_name, download_url, status) 
                VALUES ($File->type, $File->visibility, \$antihype1\$$File->name\$antihype1\$, '$File->download_url', $File->status) 
                RETURNING id;
      ";
}

function queryInsertFileWithFullText($File)
{
  return "INSERT INTO ax.ax_file (type, visibility, file_name, full_text, status) 
  VALUES ($File->type, $File->visibility, \$antihype1\$$File->name_without_prefix\$antihype1\$, \$antihype1\$$File->full_text\$antihype1\$,
  $File->status) 
  RETURNING id;";
}

function queryInsertFileEmpty($File)
{
  return "INSERT INTO ax.ax_file (type, visibility, file_name, status) 
                VALUES ($File->type, $File->visibility, \$antihype1\$$File->name\$antihype1\$, $File->status) 
                RETURNING id;
      ";
}

function queryInsertFileWithFullTextWithDeclaredVariablePageId($File)
{
  return "INSERT INTO ax.ax_file (type, visibility, file_name, full_text, status) 
  VALUES ($File->type, $File->visibility, \$antihype1\$$File->name_without_prefix\$antihype1\$, \$antihype1\$$File->full_text\$antihype1\$,
  $File->status) 
  RETURNING id INTO current_file_id;";
}
