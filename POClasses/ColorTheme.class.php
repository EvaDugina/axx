
<?php
require_once("./settings.php");

require_once("File.class.php");

class ColorTheme
{

    public $id = null;
    public $disc_id = null;
    public $page_id = null;
    public $name = null;
    public $bg_color = null;
    public $src_url = null;
    public $status = null;


    function __construct()
    {
        global $dbconnect;

        $count_args = func_num_args();
        $args = func_get_args();

        // Перегружаем конструктор по количеству подданых параметров

        if ($count_args == 1) {
            $this->id = (int)$args[0];

            $query = queryGetColorTheme($this->id);
            $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
            $color_theme = pg_fetch_assoc($result);

            $this->disc_id = $color_theme['disc_id'];
            $this->page_id = $color_theme['page_id'];
            $this->name = $color_theme['name'];
            $this->bg_color = $color_theme['bg_color'];
            $this->src_url = $color_theme['src_url'];
            $this->status = $color_theme['status'];
        } else if ($count_args == 2) {
            $this->page_id = (int)$args[0];
            $this->src_url = $args[1];
            $this->status = 0;

            $this->pushNewToDB();
        } else {
            die('Неверные аргументы в конструкторе ColorTheme');
        }
    }


    public function pushNewToDB()
    {
        global $dbconnect;

        $query = queryInsertColorTheme($this->page_id, $this->src_url, $this->status);
        $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        $result = pg_fetch_assoc($pg_query);

        $this->id = $result['id'];
    }

    function deleteFromDB()
    {
        global $dbconnect;

        if (!$this->isBasic()) {
            deleteFile($this->src_url);
            $query = "DELETE FROM ax.ax_color_theme WHERE id = $this->id;";
            pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        }
    }

    //   
    // 
    // 



    function getSrcUrl()
    {
        global $dbconnect;

        if ($this->src_url == null) {
            $pg_query = pg_query($dbconnect, queryGetColorThemeSrcUrl(-1)) or die('Ошибка запроса: ' . pg_last_error());
            return pg_fetch_assoc($pg_query)['src_url'];
        }

        return $this->src_url;
    }


    function isBasic()
    {
        return $this->page_id == null;
    }
}

// 
// 
// 
// 

function queryInsertColorTheme($page_id, $src_url, $status)
{
    return "INSERT INTO ax.ax_color_theme (page_id, src_url, status)
          VALUES ($page_id, '$src_url', $status) RETURNING id;
  ";
}

function querySetColorThemeId($page_id, $color_theme_id)
{
    return "UPDATE ax.ax_page SET color_theme_id = $color_theme_id WHERE id = $page_id;
          SELECT src_url FROM ax.ax_color_theme WHERE id = $color_theme_id;";
}


function queryGetColorThemeSrcUrl($color_theme_id)
{
    return "SELECT src_url FROM ax.ax_color_theme 
          WHERE id = $color_theme_id;
  ";
}

function queryGetColorTheme($color_theme_id)
{
    return "SELECT * FROM ax.ax_color_theme WHERE id = $color_theme_id;";
}

?>