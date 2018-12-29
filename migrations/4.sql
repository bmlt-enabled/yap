CREATE TABLE `sessions` (
  id varchar(32) NOT NULL,
	access int(10) unsigned,
	data text,
  PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
