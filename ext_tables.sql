#
# Table structure for table 'tx_mailformplusplus_log'
#
CREATE TABLE tx_mailformplusplus_log (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	ip tinytext,
	params text,
	key_hash tinytext,
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_mailformplusplus_reportlog'
#
CREATE TABLE tx_mailformplusplus_reportlog (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	ip tinytext,
	PRIMARY KEY (uid),
	KEY parent (pid)
);
