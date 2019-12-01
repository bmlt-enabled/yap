CREATE TABLE `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callsid` varchar(255) NOT NULL,
  `start_time` timestamp NULL,
  `end_time` timestamp NULL,
  `from_number` varchar(255) NOT NULL,
  `to_number` varchar(255) NOT NULL,
  `payload` longtext,
  `duration` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
