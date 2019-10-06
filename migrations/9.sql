CREATE TABLE `records_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callsid` varchar(255) NOT NULL,
  `event_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `event_id` int(11) NOT NULL,
  `service_body_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
