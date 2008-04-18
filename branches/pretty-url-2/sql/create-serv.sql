CREATE DATABASE IF NOT EXISTS xerxes;
USE xerxes;

DROP TABLE IF EXISTS xerxes_cache;
DROP TABLE IF EXISTS xerxes_tags;
DROP TABLE IF EXISTS xerxes_sfx;
DROP TABLE IF EXISTS xerxes_refereed;
DROP TABLE IF EXISTS xerxes_users;
DROP TABLE IF EXISTS xerxes_records;

CREATE TABLE xerxes_users (
	username       	VARCHAR(50),
	last_login	DATE,
	suspended	INTEGER(1),

	PRIMARY KEY (username)
);

CREATE INDEX xerxes_users_username_idx ON xerxes_users(username);

CREATE TABLE xerxes_sfx (
	issn       	VARCHAR(8),
	title       	VARCHAR(100),
	startdate	INTEGER(4),
	enddate		INTEGER(4),
	embargo		INTEGER(5),
	updated		DATE,
	live		INTEGER(1)
);

CREATE INDEX xerxes_sfx_issn_idx ON xerxes_sfx(issn);

CREATE TABLE xerxes_refereed (
	issn       	VARCHAR(8),
	title       	VARCHAR(150),
	subtitle	VARCHAR(200),
	title_normal	VARCHAR(150)
);

CREATE INDEX xerxes_refereed_issn_idx ON xerxes_refereed(issn);

CREATE TABLE xerxes_records (
	id 		MEDIUMINT NOT NULL AUTO_INCREMENT,
	source 		VARCHAR(10),
	original_id 	VARCHAR(100),
	timestamp 	DATE,
	username 	VARCHAR(35),
	nonsort 	VARCHAR(5),
	title 		VARCHAR(255),
	author 		VARCHAR (150),
	year		SMALLINT(4),
	format 		VARCHAR(50),
	refereed 	SMALLINT(1),
	marc		TEXT,

	PRIMARY KEY (id)
);

CREATE INDEX xerxes_records_username_idx ON xerxes_records(username);

CREATE TABLE xerxes_tags (
	username	VARCHAR(50),
  	record_id	MEDIUMINT,
   	tag 		VARCHAR(100),

 	FOREIGN KEY (username) REFERENCES xerxes_users(username) ON DELETE CASCADE,
	FOREIGN KEY (record_id) REFERENCES xerxes_records(id) ON DELETE CASCADE
);

CREATE TABLE xerxes_cache (
	source		VARCHAR(20),
	grouping	VARCHAR(20),
	id 		VARCHAR(20),
	data		TEXT,
	timestamp	INTEGER,
	expiry		INTEGER
);