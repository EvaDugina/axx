
<?php
require_once("./settings.php");

class Variant
{

  public $id;
  public $total_count, $passed_count;
  public $comment, $number;


  function __construct()
  {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1) {
      $this->id = (int)$args[0];

      $query = queryGetVariantInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $variant = pg_fetch_assoc($result);

      $this->total_count = $variant['total_count'];
      $this->passed_count = $variant['passed_count'];
      $this->comment = $variant['variant_comment'];
      $this->number = $variant['variant_num'];
    } else {
      die('Неверные аргументы в конструкторе Variant');
    }
  }
}







function queryGetVariantInfo($variant_id)
{
  return "SELECT * FROM ax.ax_student_page_info WHERE id = $variant_id;";
}

?>