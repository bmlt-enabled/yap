CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_body_id` int(10) unsigned NOT NULL,
  `data` text NOT NULL,
  `data_type` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_body_id_data_type_UNIQUE` (`service_body_id`, `data_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
