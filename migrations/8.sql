CREATE TABLE `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callsid` varchar(255) DEFAULT NULL,
  `start_time` varchar(45) DEFAULT NULL,
  `end_time` varchar(45) DEFAULT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) DEFAULT NULL,
  `payload` longtext,
  `duration` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
