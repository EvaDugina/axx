<html>

<body>

  <?php
  require("auth_ssh.class.php");

  $au = new auth_ssh();
  if ($au->isAdmin($_SESSION['hash'])) {
    echo '<form action="update_action.php"><input type="submit" value="update"/></form>';
  }
  ?>

</body>

</html>