CREATE TABLE `conference_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` TIMESTAMP DEFAULT now(),
  `conferencesid` VARCHAR(100) NOT NULL,
  `callsid` VARCHAR(100) NOT NULL,
  `friendlyname` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
