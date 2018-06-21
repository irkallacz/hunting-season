<?php
$query = $db->query('SELECT mail, id FROM user');
$users = $query->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);

$locationQuery = $db->prepare('INSERT INTO location (longitude,latitude,accuracy,datetime,uid,user_id) VALUES (:lon,:lat,:acc,:datetime,:uid,:user_id)');
$pointQuery = $db->prepare('SELECT id FROM point WHERE DISTANCE(longitude,latitude,:lon,:lat) < :dist');
$query = $db->prepare('INSERT INTO user_has_point (user_id,point_id,location_id) VALUES(:user_id,:point_id,:lonation_id)');

$imap = imap_open('{imap-64733.m33.wedos.net:143}INBOX', 'geo@irkalla.cz', '*****');
print imap_errors();
//$messages = imap_search($imap, 'UNSEEN FROM "'.$user->mail.'"', SE_UID);
$messages = imap_search($imap, 'UNSEEN', SE_UID);

if ($messages) foreach ($messages as $message_id) { 
  $msg = imap_fetch_overview($imap,$message_id, FT_UID);
  $date = new Datetime($msg[0]->date);
  
  if (preg_match('~<?([\w\.-]+@[\w\.-]+\.[\w]+)>?~', $msg[0]->from, $mail)) $mail = $mail[1];  
  unset($msg);
  
  if (array_key_exists($mail,$users)) { 
    $body = strip_tags(imap_fetchbody($imap,$message_id,1,FT_UID));
        
    if (preg_match('~(15\.\d+)~', $body, $lon)) $lon = $lon[1];  
    if (preg_match('~(50\.\d+)~', $body, $lat)) $lat = $lat[1];   
    if (preg_match('~(\d+\.?\d*)\s?m~', $body, $acc))  $acc = $acc[1];
    
    unset($body);
        
    if (($lon)and($lat)and($mail)) {
      if (empty($acc)) $acc = null;
      
      $user_id = $users[$mail][0];
      
      $db->beginTransaction();
      $param = array(':lon'=>$lon,':lat'=>$lat,':acc'=>$acc,':datetime'=>$date->format('Y-m-d H:i:s'),':uid'=>$message_id,':user_id'=>$user_id);  
      $locationQuery->execute($param);
      $location_id = $db->lastInsertId(); 
          
      $pointQuery->execute(array(':lon'=>$lon,':lat'=>$lat,':dist'=>50));
      $points = $pointQuery->fetchAll(PDO::FETCH_COLUMN,0);
      
      foreach ($points as $point_id) 
        $query->execute(array(':user_id'=>$user_id,':point_id'=>$point_id,':lonation_id'=>$location_id));
         
      $db->commit();
    }
  }
}
imap_close($imap);   