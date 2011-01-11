USE xerxes;

DROP TABLE IF EXISTS xerxes_cache;

CREATE TABLE xerxes_cache (
	source		VARCHAR(20),
	id 		VARCHAR(80),
	data		MEDIUMTEXT,
	timestamp	INTEGER,
	expiry		INTEGER,

	PRIMARY KEY (source,id)
);