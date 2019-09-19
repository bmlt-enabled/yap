CREATE TABLE `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callsid` varchar(255) DEFAULT NULL,
  `start_time` varchar(45) DEFAULT NULL,
  `end_time` varchar(45) DEFAULT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) DEFAULT NULL,
  `search_type` int(11) DEFAULT NULL,
  `search_method` int(11) DEFAULT NULL,
  `payload` longtext,
  `duration` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`,`from`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
