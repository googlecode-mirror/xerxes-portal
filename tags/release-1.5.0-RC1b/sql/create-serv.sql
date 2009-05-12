CREATE DATABASE IF NOT EXISTS xerxes;
USE xerxes;

SET storage_engine = INNODB;

DROP TABLE IF EXISTS xerxes_user_usergroups;
DROP TABLE IF EXISTS xerxes_cache;
DROP TABLE IF EXISTS xerxes_tags;
DROP TABLE IF EXISTS xerxes_sfx;
DROP TABLE IF EXISTS xerxes_refereed;
DROP TABLE IF EXISTS xerxes_users;
DROP TABLE IF EXISTS xerxes_records;
DROP TABLE IF EXISTS xerxes_user_subcategory_databases;
DROP TABLE IF EXISTS xerxes_user_subcategories;
DROP TABLE IF EXISTS xerxes_user_categories;


CREATE TABLE xerxes_users (
	username VARCHAR(50),
	last_login	DATE,
	suspended	INTEGER(1),
	first_name	VARCHAR(50),
	last_name	VARCHAR(50),
	email_addr	VARCHAR(120),

	PRIMARY KEY (username)
);

CREATE INDEX xerxes_users_username_idx ON xerxes_users(username);

CREATE TABLE xerxes_user_usergroups (
	username	VARCHAR(50),
	usergroup	VARCHAR(50),

	PRIMARY KEY (username, usergroup),
	FOREIGN KEY (username) REFERENCES xerxes_users(username) ON DELETE CASCADE
);

CREATE TABLE xerxes_sfx (
	issn 		VARCHAR(8),
	title		VARCHAR(100),
	startdate	INTEGER(4),
	enddate		INTEGER(4),
	embargo		INTEGER(5),
	updated		DATE,
	live		INTEGER(1)
);

CREATE INDEX xerxes_sfx_issn_idx ON xerxes_sfx(issn);

CREATE TABLE xerxes_refereed (
	issn		VARCHAR(8),
	title		VARCHAR(150),
	subtitle	VARCHAR(200),
	title_normal	VARCHAR(150)
);

CREATE INDEX xerxes_refereed_issn_idx ON xerxes_refereed(issn);

CREATE TABLE xerxes_records (
	id 		MEDIUMINT NOT NULL AUTO_INCREMENT,
	source 		VARCHAR(10),
	original_id 	VARCHAR(100),
	timestamp 	DATE,
	username 	VARCHAR(50),
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
CREATE INDEX xerxes_records_original_id_idx ON xerxes_records(original_id);

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

CREATE INDEX xerxes_cache_grouping_idx ON xerxes_cache(grouping);

CREATE TABLE xerxes_user_categories(
	id 			      MEDIUMINT NOT NULL AUTO_INCREMENT,
	name     		  VARCHAR(255),
  username      VARCHAR(50),
  published        INTEGER(1) NOT NULL DEFAULT 0, 
	normalized		VARCHAR(255),

	PRIMARY KEY (id)
);
CREATE INDEX xerxes_user_categories_normalized_idx ON xerxes_user_categories(username, normalized);

CREATE TABLE xerxes_user_subcategories(
	id        MEDIUMINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name     	VARCHAR(255),
	sequence	MEDIUMINT NOT NULL,
  category_id	MEDIUMINT NOT NULL,

 	FOREIGN KEY (category_id) REFERENCES xerxes_user_categories(id) ON DELETE CASCADE
);

CREATE TABLE xerxes_user_subcategory_databases(

	database_id	VARCHAR(10),
  subcategory_id	MEDIUMINT,
  sequence MEDIUMINT,

  PRIMARY KEY(database_id, subcategory_id),
 	FOREIGN KEY (database_id) REFERENCES xerxes_databases(metalib_id) ON DELETE CASCADE,  
	FOREIGN KEY (subcategory_id) REFERENCES xerxes_user_subcategories (id) ON DELETE CASCADE
);
