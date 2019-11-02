CREATE TABLE `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callsid` varchar(255) NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `from_number` varchar(255) NOT NULL,
  `to_number` varchar(255) NOT NULL,
  `payload` longtext,
  `duration` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
