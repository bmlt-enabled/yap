CREATE TABLE `alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NULL,
  `alert_id` int(11) NOT NULL,
  `payload` longtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
