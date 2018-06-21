<?php
require '../database.php';
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

$request = $_GET;

if ((!array_key_exists('hash', $request))or((!array_key_exists('mail', $request)))){
  header('HTTP/1.0 400 Bad Request');
  die('Chybný požadavek'); 
}

$query = $db->prepare('SELECT mail, user.id, password, nemesis, (SELECT MAX(datetime) FROM location WHERE location.user_id = user.id)AS datetime FROM user WHERE mail = :mail LIMIT 1');
$query->execute(array(':mail' => $request['mail']));
$user = $query->fetch(PDO::FETCH_OBJ);

if (!$user){
  header('HTTP/1.0 401 Unauthorized');
  die('Uživatel nenalezen');
  exit;
}

$hash = array_pop($request);

$data =  json_encode($request);
$hmac = hash_hmac('sha256',$data,$user->password);

if ($hash != $hmac){
  header('HTTP/1.0 401 Unauthorized');
  die('Chyba přihlášení');
  exit;
}

$date = new Datetime(); 
 
if (array_key_exists('location', $request)and(array_key_exists('lon', $request['location'])and(array_key_exists('lat', $request['location']))and(array_key_exists('acc', $request['location'])) )){  
  $db->beginTransaction();
  $locationQuery = $db->prepare('INSERT INTO location (longitude,latitude,accuracy,datetime,user_id) VALUES (:lon,:lat,:acc,:datetime,:user_id)');
  $param = array(':lon'=>$request['location']['lon'],':lat'=>$request['location']['lat'],':acc'=>$request['location']['acc'],':datetime'=>$date->format('Y-m-d H:i:s'),':user_id'=>$user->id);  
  $locationQuery->execute($param);
  $location_id = $db->lastInsertId();   
  
  $pointQuery = $db->prepare('SELECT id FROM point WHERE DISTANCE(longitude,latitude,:lon,:lat) < :dist');    
  $pointQuery->execute(array(':lon'=>$request['location']['lon'],':lat'=>$request['location']['lat'],':dist'=>50));
  $points = $pointQuery->fetchAll(PDO::FETCH_COLUMN,0);
  
  $query = $db->prepare('INSERT INTO user_has_point (user_id,point_id,location_id) VALUES(:user_id,:point_id,:location_id)');
  foreach ($points as $point_id) 
    $query->execute(array(':user_id'=>$user->id,':point_id'=>$point_id,':location_id'=>$location_id));
     
  $db->commit();
  $user->datetime = $date->format('Y-m-d H:i:s');
}

$last = new Datetime($user->datetime);      
$timeout = new Datetime($user->datetime);
$timeout->add(new DateInterval('PT10M')); 

//if ($user->id == 'X') $timeout->add(new DateInterval('PT23H'));

if (array_key_exists('secret', $request)){  
  $minus = $db->prepare('UPDATE user SET minus = minus + 1 WHERE id = :user');

  $query = $db->prepare('SELECT id FROM user WHERE secret = MD5(:secret)');
  $query->execute(array(':secret' => ucfirst(strtolower($request['secret']))));
  $target = $query->fetchColumn();

  if (!$target){
    header('HTTP/1.0 400 Bad Request');
    die('Chybné tajné slovo');
    exit;
  } 
  
  if ($target == $user->id){
    $minus->execute(array(':user' => $user->id));
    header('HTTP/1.0 400 Bad Request');
    die('Nesmůžete chytit sami sebe');    
    exit;
  } 
  
  if ($target == $user->nemesis){
    $minus->execute(array(':user' => $user->id));
    header('HTTP/1.0 400 Bad Request');
    die('Nemůžete svoji nemesis!');
    exit;
  } 

  $query = $db->prepare('INSERT into user_has_user (user_id, target_id, datetime) values (:user, :target, NOW()) ON duplicate key UPDATE datetime = NOW()');
  $query->execute(array(':user' => $user->id, ':target' => $target));

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('target'=> $target,'message' => 'Chycení týmu '.$target.' zaznamenáno'));
  exit;
}

if ((array_key_exists('location', $request)or($timeout > $date))and(!array_key_exists('secret', $request))){
  $query = $db->query('SELECT id,longitude,latitude FROM point ORDER BY id');
  $points = $query->fetchAll(PDO::FETCH_ASSOC);
  
  $query = $db->query('SELECT user_id AS id,longitude,latitude, DATE_FORMAT(datetime, "%Y-%m-%dT%T+01:00")AS datetime,accuracy FROM (SELECT user_id,longitude,latitude,datetime,accuracy FROM location ORDER BY datetime DESC) AS l GROUP BY user_id');
  $players = $query->fetchAll(PDO::FETCH_ASSOC);
  
  $query = $db->prepare('SELECT user.id, IF(target_id IS NULL,0,1)AS taken FROM user LEFT JOIN (SELECT user_id, target_id FROM user_has_user WHERE user_id = :user)AS target ON target_id = user.id');
  $query->execute(array(':user'=>$user->id));

  $playerTaken = $query->fetchAll(PDO::FETCH_KEY_PAIR);

  $query = $db->query('SELECT user.id, COUNT(target_id) FROM user LEFT JOIN user_has_user ON user.id = target_id GROUP BY user.id');
  $playerCount = $query->fetchAll(PDO::FETCH_KEY_PAIR);

  $query = $db->query('SELECT point_id AS id, GROUP_CONCAT(DISTINCT user_id)AS users FROM user_has_point GROUP BY point_id');
  $taken = $query->fetchAll(PDO::FETCH_KEY_PAIR);
  
  $taken = array_map(function($value){
    return explode(',',$value);
  },$taken);
  
  $response = array('user'=>$user->id,'datetime'=>$date->format('c'));
  
  foreach($points as $point){
    $response['points'][$point['id']] = $point;
    if (isset($taken[$point['id']])) $response['points'][$point['id']]['isTaken'] = in_array($user->id, $taken[$point['id']]);
    $response['points'][$point['id']]['count'] = count($taken[$point['id']]); 
  }
  

  foreach($players as $player){
    $response['players'][$player['id']] = $player;
    $response['players'][$player['id']]['isTaken'] = $playerTaken[$player['id']] ? 'true' : 'false';
    $response['players'][$player['id']]['count'] = $playerCount[$player['id']]; 
  } 
  
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($response);
}else{
  header('HTTP/1.0 403 Forbidden');
  die('Poslední lokace je více jak 10 minut stará ('.$last->format('H:i:s').')'); 
}
?>