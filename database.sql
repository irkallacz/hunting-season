-- Adminer 3.7.1 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = '+02:00';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DELIMITER ;;

DROP FUNCTION IF EXISTS `distance`;;
CREATE FUNCTION `distance`(`lon1` double unsigned, `lat1` double unsigned, `lon2` double unsigned, `lat2` double unsigned) RETURNS int(10) unsigned
RETURN ROUND( SQRT( POW( (RADIANS(lon2)-RADIANS(lon1)) * COS( (RADIANS(lat2) + RADIANS(lat1)) /2) ,2) + POW(RADIANS(lat2)-RADIANS(lat1),2) ) *6371000 );;

DELIMITER ;


DROP TABLE IF EXISTS `location`;
CREATE TABLE `location` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(1) COLLATE utf8_bin NOT NULL,
  `datetime` datetime NOT NULL,
  `latitude` double unsigned NOT NULL,
  `longitude` double unsigned NOT NULL,
  `accuracy` float unsigned DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `point`;
CREATE TABLE `point` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `latitude` double unsigned NOT NULL,
  `longitude` double unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` char(1) COLLATE utf8_bin NOT NULL,
  `mail` varchar(50) COLLATE utf8_bin NOT NULL,
  `password` varchar(32) COLLATE utf8_bin NOT NULL,
  `secret` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `nemesis` char(1) COLLATE utf8_bin DEFAULT NULL,
  `minus` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mail` (`mail`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `user_has_point`;
CREATE TABLE `user_has_point` (
  `user_id` char(1) COLLATE utf8_bin NOT NULL,
  `point_id` tinyint(3) unsigned NOT NULL,
  `location_id` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `user_has_user`;
CREATE TABLE `user_has_user` (
  `user_id` char(1) COLLATE utf8_bin NOT NULL,
  `target_id` char(1) COLLATE utf8_bin NOT NULL,
  `datetime` datetime NOT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`user_id`,`target_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- 2018-05-21 10:31:11
