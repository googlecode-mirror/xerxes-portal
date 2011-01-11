USE xerxes;

DROP TABLE  xerxes_cache;

CREATE TABLE xerxes_cache (
	source		VARCHAR(20),
	id 		VARCHAR(80),
	data		TEXT,
	timestamp	INTEGER,
	expiry		INTEGER,

	PRIMARY KEY (source,id)
);