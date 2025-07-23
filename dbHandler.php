<?php
function getAllBackups()
{
    $files = glob('/backups/*');

    $backups = [];
    foreach ($files as $file) {
        if (is_file($file)) {
            array_push($backups, array("file_name" => basename($file)));
        }
    }

    return $backups;
}

function makeBackup()
{
    $output = [];
    $return_value = 0;
    exec('/make_backup.sh', $output, $return_value);
    return array("output" => $output, "return_value" => $return_value, "error" => $return_value != 0);
}
