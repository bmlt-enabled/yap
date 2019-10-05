CREATE TABLE `records_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callsid` varchar(255) DEFAULT NULL,
  `start_time` varchar(45) DEFAULT NULL,
  `end_time` varchar(45) DEFAULT NULL,
  `event_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
