drop database if exists webadmin;
create database webadmin;
use webadmin;

CREATE TABLE IF NOT EXISTS `users` (
	`uid` int(11) NOT NULL AUTO_INCREMENT,
	`email` varchar(300) NOT NULL UNIQUE,
	`password` varchar(300) NOT NULL,
	`activation` varchar(300) NOT NULL UNIQUE,
	`status` enum('0','1') NOT NULL DEFAULT '0',
	`owner_authorized` enum('0','1') NOT NULL DEFAULT '0',
	`justification` varchar(300),
	PRIMARY KEY (`uid`)
);

CREATE TABLE IF NOT EXISTS `file_access` (
	`uid` int(11) NOT NULL ,
	`path` varchar(500) NOT NULL,
	`access_type` enum('0','1') NOT NULL DEFAULT '0',
	`owner_authorized` enum('0','1') NOT NULL DEFAULT '0',
	`updated_path` varchar(500) NOT NULL
);

