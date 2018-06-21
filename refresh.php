<?php
session_start();
if (!array_key_exists('timeout',$_SESSION)or($_SESSION['timeout'] < date_create())or($_SESSION['user']->id == 'X')) header('Location: sign.php');

$user = $_SESSION['user']; 

require 'database.php';
require 'imap.php';    

$queryTime = $db->prepare('SELECT max(datetime)AS datetime FROM location WHERE user_id = :user');
$queryTime->execute(array(':user'=>$user->id));
$datetime = $queryTime->fetchColumn();

if ($datetime) {      
  $timeout = new Datetime($datetime);
  $timeout->add(new DateInterval('PT10M')); 
  
  if ($timeout > date_create()) $_SESSION['timeout'] = $timeout;
}   

header('Location: index.php');
?>