<?php
require 'database.php';
 
class Point {
    public $id;
    public $longitude;
    public $latitude;
    
}

class Location extends Point {
    public $user_id;
    public $accuracy;
    public $datetime;
}

$query = $db->query('SELECT id,longitude,latitude FROM point ORDER BY id');
$points = $query->fetchAll(PDO::FETCH_CLASS, 'Point');

$query = $db->query('SELECT user_id,longitude,latitude,datetime,accuracy FROM location ORDER BY user_id, datetime');
$locations = $query->fetchAll(PDO::FETCH_CLASS, 'Location');
?>
<?php 
header('Content-type: application/gpx+xml; charset=utf-8');
header('Content-Disposition: attachment; filename="lovecka_sezona_2014.gpx"');
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'?>
<gpx version="1.0">
	<name>Hunting season gpx</name>
	<?php foreach($points as $point):?>
  <wpt lat="<?php echo $point->latitude ?>" lon="<?php echo $point->longitude ?>"><name><?php echo $point->id ?></name></wpt>
  <?php endforeach?>
  
  <?php $last = 0; $id = 0; ?>
  <?php foreach($locations as $location):?>
  <?php if($last !== $location->user_id):?>
  <?php if($id !== 0):?>
  </trkseg></trk>
  <?php endif?>
  <?php $last = $location->user_id; $id++; ?>
  <trk><name>Team <?php echo $location->user_id ?></name><number><?php echo $id ?></number><trkseg>
  <?php endif?>
    <trkpt lat="<?php echo $location->latitude ?>" lon="<?php echo $location->longitude ?>">
    <time><?php echo date_create($location->datetime)->format('c') ?></time></trkpt>
  <?php endforeach?>
  </trkseg></trk>
</gpx>