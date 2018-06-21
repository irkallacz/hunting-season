<?php
if ((array_key_exists('mail',$_POST))and(array_key_exists('password',$_POST))){
  if(!empty($_POST['mail'])and(!empty($_POST['password']))) {  
    session_start();

    require 'database.php';

    $query = $db->prepare('SELECT id,mail,password FROM user WHERE mail = :mail');
    $queryTime = $db->prepare('SELECT max(datetime)AS datetime FROM location WHERE user_id = :user');
    
    $query->execute(array(':mail'=>$_POST['mail']));
    $user = $query->fetchObject();
    
    if ($user){
      if ($user->password == md5($_POST['password'])){
        
        require 'imap.php';
        
        $queryTime->execute(array(':user'=>$user->id));
        $datetime = $queryTime->fetchColumn();
        
        if (($datetime)or($user->id == 'X')) {
          $last = new Datetime($datetime);      
          $timeout = new Datetime($datetime);
          $timeout->add(new DateInterval('PT10M')); 
          if ($user->id == 'X') $timeout->add(new DateInterval('PT23H'));
          
          if ($timeout > date_create()) {
            $_SESSION['timeout'] = $timeout;
            $_SESSION['user'] = $user;
            header('Location: index.php');
          } else $flashMessage = 'Poslední lokace je více jak 10 minut stará ('.$last->format('H:i:s').')';
        } else $flashMessage = 'Nemáte žádnou poslední lokaci';
      } else $flashMessage = 'Chybné heslo';  
    } else $flashMessage = 'Uživatel nenalezen';
  }else $flashMessage = 'Vyplňte e-mail a heslo';
} 
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=yes">
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="geo.css" type="text/css">
<title>SIGN</title>
</head>
<body>
<div id="main">
<h1><span>Lovecká</span> <span class="red">sezóna</span></h1>
<form action="sign.php" method="post">
<?php if((isset($_SERVER['HTTP_REFERER']))and($_SERVER['HTTP_REFERER'] == 'http://'.$_SERVER['SERVER_NAME'].'/geo.php')):?>
<p class="red">Byl jste odhlášen</p>
<?php endif?>
<?php if(isset($flashMessage)):?>
<p class="red"><strong><?php echo $flashMessage?></strong></p>
<?php endif?>
<table>
<tr><th><label for="form-mail">Email</label></th><td><input id="form-mail" name="mail" type="email" placeholder="@" autofocus></td></tr>
<tr><th><label for="form-password">Heslo</label></th><td><input id="form-password" name="password" type="password"></td></tr>
<tr><th></th><td><input id="form-submit" name="submit" value="Přihlásit" type="submit"></td></tr>
</table> 
</form>
<img src="hs_logo.gif">
</div>
</body>
</html>