CREATE TABLE `flags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `flag_name` varchar(50) NOT NULL,
  `flag_setting` int(11) NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flag_name_UNIQUE` (`flag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
