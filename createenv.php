 <?php
 
   if (array_key_exists('str', $_POST))
   {
        $myfile = fopen("env.php", "w") or die("Unable to open file!");
        fwrite($myfile, '<?php'.PHP_EOL.'    $DB_CONNECTION_STRING = "'.$_POST['str'].'"; '.PHP_EOL.'    echo "ok";'.PHP_EOL.'?>');
        fclose($myfile);
   }
   else
   {
?>
	<form action="createenv.php" method="POST">
	    <input type="text" name="str" value="">
		<input type="submit" value="Submit">
	</form>
<?php
   }
?> 