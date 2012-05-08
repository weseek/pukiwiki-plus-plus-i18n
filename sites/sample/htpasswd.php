<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>htpasswd</title>
</head>
<body>
<form method="post" action="htpasswd.php">
User:<br>
<input type="text" name="user" value=""><br>
Password:<br>
<input type="text" name="password"><br>
<input type="submit" name="submit">
</form>
<?php
$filename = '.htpasswd';

$user = $_POST['user'];
$password = $_POST['password'];
if (isset($_POST['password'])) {
    $change = false;
    $salt = substr($password, 0, 2);
    $file = file($filename);
    $fp = fopen($filename, 'w');
    flock($fp, LOCK_EX);
    foreach($file as $line) {
        list($u, $p) = explode(':', rtrim($line), 2);
        if($u == $user) {
            $p = crypt($password, $salt);
            $change = true;
        }
        fwrite($fp, "{$u}:{$p}\n");
    }
    if(!$change) {
        fwrite($fp, "{$user}:" .  crypt($password, $salt) . "\n");
        echo htmlspecialchars($user) . "が追加されました";
    } else {
        echo htmlspecialchars($user) . "のパスワードを更新しました";
    }
    flock($fp, LOCK_UN);
    fclose($fp);
}
?>
</body>
</html>
