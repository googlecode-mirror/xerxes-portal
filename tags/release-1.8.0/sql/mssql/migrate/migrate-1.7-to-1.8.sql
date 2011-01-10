USE xerxes;

DROP TABLE xerxes_database_alternate_publishers;
DROP TABLE xerxes_database_alternate_titles;
DROP TABLE xerxes_database_keywords;
DROP TABLE xerxes_database_group_restrictions;
DROP TABLE xerxes_database_languages;
DROP TABLE xerxes_database_notes;

DROP TABLE  xerxes_cache;

CREATE TABLE xerxes_cache (
	source		VARCHAR(20),
	id 		VARCHAR(80),
	data		TEXT,
	timestamp	INTEGER,
	expiry		INTEGER,

	PRIMARY KEY (source,id)
);