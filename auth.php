<?php
if(!isset($_SERVER ["HTTP_AUTH_USER"] ) || ! isset($_SERVER ["HTTP_AUTH_PASS"] )) {
    fail(0);
}

$protocol = $_SERVER ["HTTP_AUTH_PROTOCOL"];

$backend_port = 8110;

if($protocol == "imap") {
    $backend_port = 8143;
} elseif ($protocol == "smtp") {
    $backend_port = 8025;
}

$username = $_SERVER['HTTP_AUTH_USER'];
$userpass = $_SERVER['HTTP_AUTH_PASS'];

$auth = authuser($username, $userpass);

if(!$auth){
    fail (-2);
}

$domain = 'mail.yhv5.com';
pass($domain, $backend_port);

//自定义认证，sql查询或者api
function authuser($user, $pass) {
    return true;
    $pass_db = getDbPass($user);
    $crypt_pass = pacrypt($pass, $pass_db);
    if($pass_db == $crypt_pass){
        return true;
    }
    return false;
}

function getDbPass($user){
    $res = array();
    $conn = mysqli_connect('121.41.224.202', 'work', 'yanghangtianlong@123');

    $user = escape_string($user, $conn);
    $sql = 'SELECT password FROM postfix.mailbox WHERE username ="' . $user .  '" AND active = 1 LIMIT 1';
    $tmp = mysqli_query($conn, $sql);
    while($rs = mysqli_fetch_assoc($tmp)){
        $res[] = $rs['password'];
    }
    return $res[0];
}

function fail($code) {
    switch($code){
        case 0: header("Auth-Status: Parameter lost"); break;
        case -1: header("Auth-Status: No Back-end Server"); break;
        case -2: header("Auth-Status: Invalid login or password" ); break;
    }
    exit();
}

function pass($domain, $port) {
    header("Auth-Status: OK" );
    $server = '121.41.224.202';//gethostbyname($domain);
    header("Auth-Server: " . $server);
    header("Auth-Port: " . $port);
    exit();
}

function pacrypt($pass, $pw_db){
    $split_salt = preg_split ('/\$/', $pw_db);
    if (isset ($split_salt[2])) {
        $salt = $split_salt[2];
    }
    $password = md5crypt ($pass, $salt);
    return $password;
}

function md5crypt($pw, $salt = '', $magic = ''){
    $MAGIC = "$1$";

    if ($magic == "") $magic = $MAGIC;
    if ($salt == "") $salt = create_salt ();
    $slist = explode ("$", $salt);
    if ($slist[0] == "1") $salt = $slist[1];

    $salt = substr ($salt, 0, 8);
    $ctx = $pw . $magic . $salt;
    $final = hex2bin (md5 ($pw . $salt . $pw));

    for ($i=strlen ($pw); $i>0; $i-=16) {
        if ($i > 16) {
            $ctx .= substr ($final,0,16);
        } else {
            $ctx .= substr ($final,0,$i);
        }
    }
    $i = strlen ($pw);

    while ($i > 0) {
        if ($i & 1) $ctx .= chr (0);
        else $ctx .= $pw[0];
        $i = $i >> 1;
    }
    $final = hex2bin (md5 ($ctx));

    for ($i=0;$i<1000;$i++) {
        $ctx1 = "";
        if ($i & 1) {
            $ctx1 .= $pw;
        } else {
            $ctx1 .= substr ($final,0,16);
        }
        if ($i % 3) $ctx1 .= $salt;
        if ($i % 7) $ctx1 .= $pw;
        if ($i & 1) {
            $ctx1 .= substr ($final,0,16);
        } else {
            $ctx1 .= $pw;
        }
        $final = hex2bin (md5 ($ctx1));
    }
    $passwd = "";
    $passwd .= to64 (((ord ($final[0]) << 16) | (ord ($final[6]) << 8) | (ord ($final[12]))), 4);
    $passwd .= to64 (((ord ($final[1]) << 16) | (ord ($final[7]) << 8) | (ord ($final[13]))), 4);
    $passwd .= to64 (((ord ($final[2]) << 16) | (ord ($final[8]) << 8) | (ord ($final[14]))), 4);
    $passwd .= to64 (((ord ($final[3]) << 16) | (ord ($final[9]) << 8) | (ord ($final[15]))), 4);
    $passwd .= to64 (((ord ($final[4]) << 16) | (ord ($final[10]) << 8) | (ord ($final[5]))), 4);
    $passwd .= to64 (ord ($final[11]), 2);
    return "$magic$salt\$$passwd";
}

function create_salt () {
    srand ((double) microtime ()*1000000);
    $salt = substr (md5 (rand (0,9999999)), 0, 8);
    return $salt;
}

function to64 ($v, $n) {
    $ITOA64 = "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    $ret = "";
    while (($n - 1) >= 0) {
        $n--;
        $ret .= $ITOA64[$v & 0x3f];
        $v = $v >> 6;
    }
    return $ret;
}

function escape_string ($string, $link) {
    if(is_array($string)) {
        $clean = array();
        foreach(array_keys($string) as $row) {
            $clean[$row] = escape_string($string[$row]);
        }
        return $clean;
    }
    if (get_magic_quotes_gpc ()) {
        $string = stripslashes($string);
    }
    if (!is_numeric($string)) {
        $escaped_string = mysqli_real_escape_string($link, $string);
    } else {
        $escaped_string = $string;
    }
    return $escaped_string;
}
