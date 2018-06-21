<?php
session_start();
if (!array_key_exists('timeout',$_SESSION) or ($_SESSION['timeout'] < date_create())) header('Location: sign.php');

require 'database.php';
 
$now = new Datetime;
$height = 2044; 
$width = 1560;

class Point {
    public $id;
    public $longitude;
    public $latitude;
    
    public function dist(Point $point){
      $R = 6371000;
      $x = (deg2rad($point->longitude) - deg2rad($this->longitude)) * cos((deg2rad($this->latitude)+deg2rad($point->latitude))/2);
      $y = (deg2rad($point->latitude) - deg2rad($this->latitude));
      return round(sqrt(pow($x,2) + pow($y,2))*$R);
    }
    
    public function getGps(){
      $lond = fmod($this->longitude,1)*60;
      $latd = fmod($this->latitude,1)*60;
       
      return floor($this->latitude).'°'.floor($latd)."'".number_format(fmod($latd,1)*60,3).'"N '
        .floor($this->longitude).'°'.floor($lond)."'".number_format(fmod($lond,1)*60,3).'"E';
    }
    
    public function angle(Point $point){
      $angle = round(atan2($this->longitude - $point->longitude, $this->latitude - $point->latitude) * 180 / M_PI); 
      return $angle > 0 ? $angle : 360+$angle;  
    }
}

class Location extends Point {
    public $user_id;
    public $accuracy;
    public $datetime;
}

function position($point){
  global $width, $height;
  $zero = array('lon' => 15.0628712, 'lat' => 50.8582945);
  $max = array('lon' => 15.150971, 'lat' => 50.7846468);
  $h = $zero['lat'] - $max['lat']; 
  $w = $max['lon'] - $zero['lon'];  
  return 'top: '.($height-((($point->latitude-$max['lat']) / $h)*$height)).'px; left:'.((($point->longitude-$zero['lon']) / $w)*$width).'px;'; 
}

$query = $db->query('SELECT id,longitude,latitude FROM point ORDER BY id');
$points = $query->fetchAll(PDO::FETCH_CLASS, 'Point');

//$query = $db->query('SELECT user_id,longitude,latitude,datetime,accuracy FROM location INNER JOIN (SELECT MAX(datetime) AS d FROM location GROUP BY user_id) l ON location.datetime = l.d ORDER BY user_id');
//$query = $db->query('SELECT user_id,longitude,latitude,datetime,accuracy FROM location WHERE datetime = (SELECT MAX(datetime) AS d FROM location AS l WHERE l.user_id = location.user_id LIMIT 1) ORDER BY user_id');
$query = $db->query('SELECT user_id,longitude,latitude,datetime,accuracy FROM (SELECT user_id,longitude,latitude,datetime,accuracy FROM location ORDER BY datetime DESC) AS l GROUP BY user_id');
$players = $query->fetchAll(PDO::FETCH_CLASS, 'Location');

foreach($players as $player) if ($player->user_id == $_SESSION['user']->id) $user = $player;

$query = $db->query('SELECT point_id AS id, GROUP_CONCAT(DISTINCT user_id)AS users FROM user_has_point GROUP BY point_id');
$taken = $query->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);

$taken = array_map(function($value){
  return explode(',',$value[0]);
},$taken);
?>
<!DOCTYPE html>
<!--<html manifest="cache.manifest">-->
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=yes">
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta name="format-detection" content="telephone=no">
<link rel="stylesheet" href="geo.css" type="text/css">
<title>GEO</title>
<script src="geo.js" type="text/javascript"></script> 
</head>
<body onload="setZoom()">
  <div id="map">
    <img src="map.jpg" height="<?php echo $height ?>" width="<?php echo $width ?>" alt="mapa">
    <?php foreach($points as $point):?>
    <a id="map<?php echo $point->id?>" href="#poi<?php echo $point->id?>" class="point poi <?php if ((array_key_exists($point->id,$taken))and(in_array($_SESSION['user']->id,$taken[$point->id]))) echo 'taken'?>" style="<?php echo position($point)?>"><?php echo $point->id?></a>
    <?php endforeach?>
    
    <?php foreach($players as $player):?>
    <a id="map<?php echo $player->user_id?>" href="#player<?php echo $player->user_id?>" class="point player <?php if ($player->user_id == $_SESSION['user']->id) echo 'current'?>" style="<?php echo position($player)?>"><?php echo $player->user_id?></a>
    <?php endforeach?>
  </div>

  <div id="legend">
  <h2>Hráči</h2>
  <ul>
    <?php foreach($players as $player):?>
    <?php $player->datetime = new Datetime($player->datetime)?>
    <li id="player<?php echo $player->user_id?>"><a href="#map<?php echo $player->user_id?>" class="point player <?php if ($player->user_id == $_SESSION['user']->id) echo 'current'?>"><?php echo $player->user_id?></a> 
    <span title="<?php echo $player->latitude?>N, <?php echo $player->longitude?>E"><?php echo $player->getGps()?></span>
    <?php if($player->accuracy):?>(&plusmn;<?php echo $player->accuracy?>m)<?php endif?> 
    <?php echo $player->datetime->format('H:i')?> (<?php echo $now->diff($player->datetime)->format('%R%H:%I')?>)
    <?php if(($_SESSION['user']->id != 'X')and($player->user_id != $user->user_id)):?>
      <br><i><?php echo $player->dist($user)?>m <?php echo $player->angle($user)?>°</i>
    <?php endif?>
    </li>
    <?php endforeach?>    
  </ul>
  <h2 class="red">Body</h2>
  <ul>
    <?php foreach($points as $point):?>
    <li id="poi<?php echo $point->id?>"><a href="#map<?php echo $point->id?>" class="point poi <?php if ((array_key_exists($point->id,$taken))and(in_array($_SESSION['user']->id,$taken[$point->id]))) echo 'taken'?>">
    <?php echo $point->id?></a>                                                                  
    <span title="<?php echo $point->latitude?>N, <?php echo $point->longitude?>E"><?php echo $point->getGps()?></span> 
    (<?php if (array_key_exists($point->id,$taken)) echo count($taken[$point->id]); else echo 0?>)
    <?php if($_SESSION['user']->id != 'X'):?>
    <br><i><?php echo $point->dist($user)?>m <?php echo $point->angle($user)?>°</i>
    <?php endif?>
    </li>
    <?php endforeach?>
  </ul>
  
  <p class="warning">Toto přihlášení vyprší v <b><?php echo $_SESSION['timeout']->format('H:i:s') ?> 
  (<?php echo $now->diff($_SESSION['timeout'])->format('%R%H:%I')?>)</b></p>
  
  <a href="refresh.php"><span class="round">Refresh</span></a>
  </div>
</body>
</html>