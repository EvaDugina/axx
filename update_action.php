<html>

<body>

	<?php
	require("auth_ssh.class.php");

	$au = new auth_ssh();
	if ($au->isAdmin($_SESSION['hash'])) {
		exec("git reset --hard", $out, $ret);
		echo "reset<br>" . implode("<br>", $out) . "<br>ret code = " . $ret . "<br>";
		unset($out);
		exec("git fetch", $out, $ret);
		echo "fetch<br>" . implode("<br>", $out) . "<br>ret code = " . $ret . "<br>";
		unset($out);
		exec("git pull", $out, $ret);
		echo "pull<br>" . implode("<br>", $out) . "<br>ret code = " . $ret . "<br>";
	}
	?>

</body>

</html>