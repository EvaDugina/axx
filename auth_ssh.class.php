<?php
//include_once('../disc/variables_disc.php');
session_start();

class auth_ssh
{
    //ROLES
    // 1 - admin
    // 2 - teacher
    // 3 - student
    // 4 - kaf administration
    function login($login, $pwd, $source)
    {
        if ($pwd == "")
            return false;

        require('dbparams.php');

        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");

        $lg = pg_escape_string($dbConn, $login);
        $source = pg_escape_string($source);

        $is_local = pg_query("SELECT * FROM users WHERE login='$lg' AND local_user='TRUE'");
        $is_local = pg_fetch_assoc($is_local);
        if ($is_local) {
            $pwd = $pwd . 'poniesRuleTheWorld';
            $pwd = md5($pwd);
            $logged = pg_query("SELECT * FROM users WHERE login='$lg' AND password = '$pwd' AND local_user='TRUE'");
            $logged = pg_fetch_assoc($logged);
            if (!$logged) {
                pg_query("INSERT INTO access_log (user_action, result, action_time, source_page, login_used) VALUES ('login', 'failed', " . time() . ", '$source', '$lg')");
                return false;
            }

            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHI JKLMNOPRQSTUVWXYZ0123456789";
            $code = "";
            $clen = strlen($chars) - 1;
            while (strlen($code) < 9) {
                $code .= $chars[mt_rand(0, $clen)];
            }
            $hash = md5($code);

            $_SESSION["hash"] = $hash;
            $time = time();
            pg_query("UPDATE users SET login_time=$time, session_hash='$hash', last_action=$time WHERE login='$login' AND local_user='TRUE'");
            pg_query("INSERT INTO access_log (user_action, result, action_time, source_page, login_used) VALUES ('login', 'success', " . time() . ", '$source', '$lg')");
            return true;
        }

        //$c = ssh2_connect('193.41.142.106', '22'); - old


        //$domain = "win.mirea.ru";
        //$port = 389;
        //$conn = ldap_connect($domain, $port);
        //$logged = ldap_bind($conn, $login."@".$domain, $pwd);
        $c = ssh2_connect('10.0.66.38', '22');
        /*		if ($login == 'dmitrij1699')
			$logged = true;
		else
*/
        $logged = ssh2_auth_password($c, $login, $pwd);
        //$logged = true;

        if ($logged == false) {
            pg_query("INSERT INTO access_log (user_action, result, action_time, source_page, login_used) VALUES ('login', 'failed', " . time() . ", '$source', '$lg')");
            return false;
        }

        $user_exist = pg_query("SELECT * FROM users WHERE login='$login' AND local_user='FALSE'");
        $user_exist = pg_fetch_assoc($user_exist);
        $time = time();

        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHI JKLMNOPRQSTUVWXYZ0123456789";
        $code = "";
        $clen = strlen($chars) - 1;
        while (strlen($code) < 9) {
            $code .= $chars[mt_rand(0, $clen)];
        }
        $hash = md5($code);

        $_SESSION["hash"] = $hash;

        if (!$user_exist) {
            pg_query("INSERT INTO users (login, role, login_time, session_hash, last_action) VALUES ('$login', 3, $time, '$hash', $time)");
            $role = 3; //1 - admin, 2 - teacher, 3 - student
        } else {
            $role = $user_exist['role'];
            pg_query("UPDATE users SET login_time=$time, session_hash='$hash', last_action=$time WHERE login='$login' AND local_user='FALSE'");
        }

        pg_query("INSERT INTO access_log (user_action, result, action_time, source_page, login_used) VALUES ('login', 'success', " . time() . ", '$source', '$lg')");

        if (!$role)
            $role = 3;
        return $role;
    }

    //----------------------------------------------------------------------------------------------

    function logout()
    {
        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");
        pg_query("UPDATE users SET session_hash='' WHERE session_hash='" . $_SESSION['hash'] . "'");
        pg_query("UPDATE local_users SET session_hash='' WHERE session_hash='" . $_SESSION['hash'] . "'");

        $_SESSION['hash'] = '';
    }

    //----------------------------------------------------------------------------------------------

    function loggedIn($hash = "")
    {
        if ($hash == '')
            $hash = $_SESSION['hash'];

        if (!$hash)
            return false;
        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");
        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash' AND local_user='TRUE'");
        $user = pg_fetch_assoc($user);
        if ($user) {
            $now = time();

            if ($now - $user['last_action'] > 10000) {

                $this->logout();
                return false;
            }
            pg_query("UPDATE users SET last_action=$now WHERE id=" . $user['id']);

            return $user['login'];
        }
        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash' AND local_user='FALSE'");
        $user = pg_fetch_assoc($user);
        if (!$user) {

            return false;
        }

        $now = time();

        if ($now - $user['last_action'] > 10000) {

            $this->logout();
            return false;
        }
        pg_query("UPDATE users SET last_action=$now WHERE id=" . $user['id']);

        return $user['login'];
    }

    //----------------------------------------------------------------------------------------------

    function checkDiscAccess($hash = '', $pageId = 0)
    {
        if ($hash == '')
            $hash = $_SESSION['hash'];

        if (!$hash || !$pageId)
            return false;

        $loggegIn = $this->loggedIn($hash);
        if (!$loggegIn)
            return false;

        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");

        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash'");
        $user = pg_fetch_assoc($user);
        if (!$user) {

            return false;
        }


        if ($user['role'] == 1) {

            return true;
        }

        $access = pg_query("SELECT * FROM disc_access WHERE user_id=" . $user['id'] . " AND page_id=$pageId");
        $access = pg_fetch_assoc($access);


        if (!$access)
            return false;

        return true;
    }

    //----------------------------------------------------------------------------------------------

    function isAdmin($hash = '')
    {
        if ($hash == '')
            $hash = $_SESSION['hash'];

        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");

        $t = debug_backtrace();
        $source = $t[0]['file'];
        $source = pg_escape_string($dbConn, $source);

        $loggegIn = $this->loggedIn($hash);
        if (!$loggegIn)
            return false;

        if (!$hash) {
            pg_query("INSERT INTO access_log (user_action, result, action_time, source_page) VALUES ('admin_access', 'failed', " . time() . ", '$source')");

            return false;
        }

        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash'");
        $user = pg_fetch_assoc($user);

        if (!$user) {
            pg_query("INSERT INTO access_log (user_action, result, action_time, source_page) VALUES ('admin_access', 'failed', " . time() . ", '$source')");

            return false;
        }
        $lg = $user['login'];

        if ($user['role'] == 1) {
            pg_query("INSERT INTO access_log (user_action, result, action_time, source_page, login_used) VALUES ('admin_access', 'success', " . time() . ", '$source', '$lg')");

            return true;
        }
        pg_query("INSERT INTO access_log (user_action, result, action_time, source_page, login_used) VALUES ('admin_access', 'failed', " . time() . ", '$source', '$lg')");

        return false;
    }

    //----------------------------------------------------------------------------------------------

    function isStudent($hash = '')
    {
        if ($hash == '')
            $hash = $_SESSION['hash'];

        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");

        $t = debug_backtrace();
        $source = $t[0]['file'];
        $source = pg_escape_string($dbConn, $source);

        $loggegIn = $this->loggedIn($hash);
        if (!$loggegIn)
            return false;

        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash'");
        $user = pg_fetch_assoc($user);

        $lg = $user['login'];

        if (($user['role'] == 3) && ($lg != 'admin')) {
            return true;
        }

        return false;
    }

    //----------------------------------------------------------------------------------------------


    function isAdminOrPrep($hash = '')
    {
        if ($hash == '')
            $hash = $_SESSION['hash'];

        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");

        $t = debug_backtrace();
        $source = $t[0]['file'];
        $source = pg_escape_string($dbConn, $source);

        $loggegIn = $this->loggedIn($hash);
        if (!$loggegIn)
            return false;

        if (!$hash) {
            pg_query("INSERT INTO access_log (user_action, result, action_time, source_page) VALUES ('admin_or_prep_access', 'failed', " . time() . ", '$source')");

            return false;
        }

        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash'");
        $user = pg_fetch_assoc($user);

        if (!$user) {
            pg_query("INSERT INTO access_log (user_action, result, action_time, source_page) VALUES ('admin_or_prep_access', 'failed', " . time() . ", '$source')");

            return false;
        }
        $lg = $user['login'];

        $prep = $this->isInPrepGroup($user['id']);

        if ($user['role'] == 1 || $user['role'] == 2 || $user['role'] == 4 || $prep || $lg == 'admin') {
            pg_query("INSERT INTO access_log (user_action, result, action_time, source_page, login_used) VALUES ('admin_or_prep_access', 'success', " . time() . ", '$source', '$lg')");

            return true;
        }
        pg_query("INSERT INTO access_log (user_action, result, action_time, source_page, login_used) VALUES ('admin_or_prep_access', 'failed', " . time() . ", '$source', '$lg')");

        return false;
    }

    //----------------------------------------------------------------------------------------------

    function isInPrepGroup($userId)
    {
        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");

        $user = pg_query("SELECT * FROM users WHERE id=$userId");
        $user = pg_fetch_assoc($user);
        if ($user['role'] == 4 || $user['login'] == 'admin') {
            return true;
        }

        $student = pg_query("SELECT * FROM students WHERE user_id=$userId");
        $student = pg_fetch_all($student);
        //$student = $studentId['id'];
        foreach ($student as $s) {
            $groupId = pg_query("SELECT * FROM students_to_groups WHERE student_id=" . $s['id']);
            $groupId = pg_fetch_assoc($groupId);
            $groupId = $groupId['group_id'];

            $group = pg_query("SELECT * FROM groups WHERE id=$groupId");
            $group = pg_fetch_assoc($group);

            if ($group['name'] == 'Преподаватели')
                return true;
        }
        return false;
    }

    //----------------------------------------------------------------------------------------------

    function isKafAdmin($hash = '')
    {
        if ($hash == '')
            $hash = $_SESSION['hash'];

        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");

        $t = debug_backtrace();
        $source = $t[0]['file'];
        $source = pg_escape_string($dbConn, $source);

        $loggegIn = $this->loggedIn($hash);
        if (!$loggegIn)
            return false;

        if (!$hash) {
            pg_query("INSERT INTO access_log (user_action, result, action_time, source_page) VALUES ('kaf_admin_access', 'failed', " . time() . ", '$source')");

            return false;
        }

        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash'");
        $user = pg_fetch_assoc($user);

        if (!$user) {
            pg_query("INSERT INTO access_log (user_action, result, action_time, source_page) VALUES ('kaf_admin_access', 'failed', " . time() . ", '$source')");

            return false;
        }
        $lg = $user['login'];

        if ($user['role'] == 4) {
            pg_query("INSERT INTO access_log (user_action, result, action_time, source_page, login_used) VALUES ('kaf_admin_access', 'success', " . time() . ", '$source', '$lg')");

            return true;
        }
        pg_query("INSERT INTO access_log (user_action, result, action_time, source_page, login_used) VALUES ('kaf_admin_access', 'failed', " . time() . ", '$source', '$lg')");

        return false;
    }

    //----------------------------------------------------------------------------------------------

    function getUserId($hash = '')
    {

        if ($hash == '')
            $hash = $_SESSION['hash'];

        if (!$hash)
            return false;
        $loggegIn = $this->loggedIn($hash);

        if (!$loggegIn)
            return false;
        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");
        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash'");
        $user = pg_fetch_assoc($user);
        if (!$user)
            return false;

        $student = pg_query("SELECT * FROM students WHERE user_id=" . $user['id']);
        $student = pg_fetch_assoc($student);

        if (!$student)
            return false;

        return $student['id'];
    }

    //----------------------------------------------------------------------------------------------

    function getUserLogin($hash = '')
    {
        if ($hash == '')
            $hash = $_SESSION['hash'];

        if (!$hash)
            return false;
        $loggegIn = $this->loggedIn($hash);
        if (!$loggegIn)
            return false;
        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");
        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash'");
        $user = pg_fetch_assoc($user);


        if (!$user)
            return false;

        return $user['login'];
    }

    //----------------------------------------------------------------------------------------------

    function getUserRole($hash = '')
    {
        if ($hash == '')
            $hash = $_SESSION['hash'];

        if (!$hash)
            return false;
        $loggegIn = $this->loggedIn($hash);
        if (!$loggegIn)
            return false;
        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");
        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash'");
        $user = pg_fetch_assoc($user);


        if (!$user)
            return false;

        if ($user['login'] == 'admin')
            return 2;
        else
            return $user['role'];
    }

    //----------------------------------------------------------------------------------------------

    function getUserData($hash = '')
    {
        if ($hash == '')
            $hash = $_SESSION['hash'];

        if (!$hash)
            return false;

        $loggegIn = $this->loggedIn($hash);
        if (!$loggegIn)
            return false;

        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");
        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash'");
        $user = pg_fetch_assoc($user);

        if (!$user) {

            return false;
        }

        $userId = $user['id'];
        $user = pg_query("SELECT * FROM students WHERE user_id=$userId");
        $user = pg_fetch_assoc($user);

        if (!$user) {

            return false;
        }

        $studentId = $user['id'];
        $groupId = pg_query("SELECT * FROM students_to_groups WHERE student_id=$studentId");
        $groupId = pg_fetch_assoc($groupId);
        $groupId = $groupId['group_id'];

        $groupName = pg_query("SELECT * FROM groups WHERE id=$groupId");
        $groupName = pg_fetch_assoc($groupName);
        $groupName = $groupName['name'];

        $user['group_id'] = $groupId;
        $user['group_name'] = $groupName;



        return $user;
    }

    //----------------------------------------------------------------------------------------------

    function setNameFromReception($studentId, $hash)
    {
        if ($hash == '')
            $hash = $_SESSION['hash'];

        if (!$studentId)
            return;
        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");
        $student = pg_query("SELECT * FROM students WHERE id=$studentId");
        $student = pg_fetch_assoc($student);
        $user = pg_query("SELECT * FROM users WHERE session_hash='$hash' AND local_user='FALSE'");
        $user = pg_fetch_assoc($user);
        pg_query("UPDATE users SET first_name='" . $student['first_name'] . "', middle_name='" . $student['middle_name'] . "', last_name='" . $student['last_name'] . "' WHERE id=" . $user['id']);
    }

    //----------------------------------------------------------------------------------------------

    function addUserByLogin($login = '')
    {
        if (!$login)
            return 0;
        require('dbparams.php');
        $dbConn = pg_pconnect("host='$host' port = $port dbname ='$dbname' user = '$user' password='$password'");
        $exist = pg_query("SELECT * FROM users WHERE login = '$login'");
        $exist = pg_fetch_assoc($exist);
        if ($exist)
            return $exist['id'];

        $id = pg_query("INSERT INTO users (login) VALUES ('$login') RETURNING id");
        $id = pg_fetch_assoc($id);
        return $id['id'];
    }

    //----------------------------------------------------------------------------------------------

    function addUserFromConference() {}

    //----------------------------------------------------------------------------------------------

}
