/* author: David Walker
   copyright: 2009 California State University
   version: $Id$
   package: Xerxes
   link: http://xerxes.calstate.edu
   license: http://www.gnu.org/licenses/
*/

USE xerxes;

CREATE TABLE xerxes_users (
	username 	VARCHAR(50),
	last_login	DATETIME,
	suspended	INTEGER,
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
	startdate	INTEGER,
	enddate		INTEGER,
	embargo		INTEGER,
	updated		VARCHAR(20),
	live		INTEGER
);

CREATE INDEX xerxes_sfx_issn_idx ON xerxes_sfx(issn);

CREATE TABLE xerxes_refereed (
	issn		VARCHAR(8),
	title		VARCHAR(1000),
	timestamp	VARCHAR(8)
);

CREATE INDEX xerxes_refereed_issn_idx ON xerxes_refereed(issn);

CREATE TABLE xerxes_records (
	id 		INT IDENTITY,
	source 		VARCHAR(10),
	original_id 	VARCHAR(100),
	timestamp 	DATETIME,
	username 	VARCHAR(50),
	nonsort 	VARCHAR(5),
	title 		VARCHAR(255),
	author 		VARCHAR (150),
	year		SMALLINT,
	format 		VARCHAR(50),
	refereed 	SMALLINT,
	record_type	VARCHAR(100),
	marc		TEXT,

	PRIMARY KEY (id)
);

CREATE INDEX xerxes_records_username_idx ON xerxes_records(username);
CREATE INDEX xerxes_records_original_id_idx ON xerxes_records(original_id);

CREATE TABLE xerxes_tags (
	username	VARCHAR(50),
	record_id	INTEGER,
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
	id 		INT IDENTITY,
	name		VARCHAR(255),
	username	VARCHAR(50),
	published	INTEGER NOT NULL DEFAULT 0, 
	normalized	VARCHAR(255),
	
	PRIMARY KEY (id)
);

CREATE INDEX xerxes_user_categories_normalized_idx ON xerxes_user_categories(username, normalized);

CREATE TABLE xerxes_user_subcategories(
	id		INT IDENTITY PRIMARY KEY,
	name		VARCHAR(255),
	sequence	INTEGER NOT NULL,
	category_id	INTEGER NOT NULL,

	FOREIGN KEY (category_id) REFERENCES xerxes_user_categories(id) ON DELETE CASCADE
);

CREATE TABLE xerxes_user_subcategory_databases(

	database_id	VARCHAR(10),
	subcategory_id	INTEGER,
	sequence 	INTEGER,

	PRIMARY KEY(database_id, subcategory_id),
	FOREIGN KEY (subcategory_id) REFERENCES xerxes_user_subcategories (id) ON DELETE CASCADE
);
