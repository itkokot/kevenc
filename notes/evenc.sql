CREATE TABLE `devoo_evenc_accounts` (
  `id` int(11) NOT NULL auto_increment,
  `login` tinytext NOT NULL,
  `email` tinytext NOT NULL,
  `name` tinytext NOT NULL,
  `password` tinytext NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM ;

CREATE TABLE `devoo_evenc_comments` (
  `id` int(11) NOT NULL auto_increment,
  `eventid` int(11) NOT NULL default '0',
  `eventdate` date NOT NULL default '0000-00-00',
  `name` tinytext NOT NULL,
  `email` tinytext NOT NULL,
  `date` date NOT NULL default '0000-00-00',
  `time` time NOT NULL default '00:00:00',
  `text` text NOT NULL,
  `log` tinytext NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM ;


CREATE TABLE `devoo_evenc_events` (
  `id` int(11) NOT NULL auto_increment,
  `account` int(11) NOT NULL COMMENT 'Benutzeraccount',
  `name` tinytext NOT NULL,
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `time` time NOT NULL default '00:00:00',
  `repeat` enum('NONE','DAY','WEEK','MONTH','YEAR','DECADE') NOT NULL,
  `text` text NOT NULL,
  `location` tinytext NOT NULL,
  `log` tinytext NOT NULL,
  PRIMARY KEY  (`id`),
  FULLTEXT KEY `text` (`text`)
) TYPE=MyISAM ;


CREATE TABLE `devoo_evenc_settings` (
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `account` int(11) NOT NULL,
  PRIMARY KEY  (`key`,`account`)
) TYPE=MyISAM;
