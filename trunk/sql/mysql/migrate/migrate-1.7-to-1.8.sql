USE xerxes;

DROP TABLE IF EXISTS xerxes_database_alternate_publishers;
DROP TABLE IF EXISTS xerxes_database_alternate_titles;
DROP TABLE IF EXISTS xerxes_database_keywords;
DROP TABLE IF EXISTS xerxes_database_group_restrictions;
DROP TABLE IF EXISTS xerxes_database_languages;
DROP TABLE IF EXISTS xerxes_database_notes;

DROP TABLE IF EXISTS xerxes_cache;

CREATE TABLE xerxes_cache (
	source		VARCHAR(20),
	id 		VARCHAR(80),
	data		MEDIUMTEXT,
	timestamp	INTEGER,
	expiry		INTEGER,

	PRIMARY KEY (source,id)
);