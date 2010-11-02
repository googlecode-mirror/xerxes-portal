USE xerxes;

DROP TABLE IF EXISTS xerxes_cache_alternate_id;
DROP TABLE IF EXISTS xerxes_cache;

CREATE TABLE xerxes_cache (
	source		VARCHAR(20),
	id 		VARCHAR(80),
	data		MEDIUMTEXT,
	timestamp	INTEGER,
	expiry		INTEGER,

	PRIMARY KEY (source,id)
);

CREATE TABLE xerxes_cache_alternate_id (
	alt_id 		VARCHAR(80),
	source		VARCHAR(20),
	cache_id	VARCHAR(80),
	
	FOREIGN KEY (source,cache_id) REFERENCES xerxes_cache(source,id) ON DELETE CASCADE
);