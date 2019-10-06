CREATE TABLE `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callsid` varchar(255) NOT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NOT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `payload` longtext,
  `duration` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
