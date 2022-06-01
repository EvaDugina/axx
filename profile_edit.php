<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

if (isset($_POST['email'])){
        $query = update_user_email($_SESSION['hash'], $_POST['email']);
        $result = pg_query($dbconnect, $query);
        var_dump($_POST['email']);
        var_dump($result);
}


if (isset($_POST['checkbox_notify'])){
        $query = update_user_notify_type($_SESSION['hash'], $_POST['checkbox_notify']);
        $result = pg_query($dbconnect, $query);
        var_dump($_POST['checkbox_notify']);
        var_dump($result);
} else { // тк. если чекбокс не нажимается он не передаётся методом POST
        $query = update_user_notify_type($_SESSION['hash'], "off");
        $result = pg_query($dbconnect, $query);
        var_dump($result);
}

//header('Location: profile.php');
?>

