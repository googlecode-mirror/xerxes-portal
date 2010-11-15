/* author: David Walker
   copyright: 2009 California State University
   version: $Id$
   package: Xerxes
   link: http://xerxes.calstate.edu
   license: http://www.gnu.org/licenses/
*/

USE xerxes;

/* uncomment these statements if you are doing an upgrade */
/*
DROP TABLE xerxes_database_alternate_publishers;
DROP TABLE xerxes_database_alternate_titles;
DROP TABLE xerxes_database_keywords;
DROP TABLE xerxes_database_group_restrictions;
DROP TABLE xerxes_database_languages;
DROP TABLE xerxes_database_notes;
DROP TABLE xerxes_subcategory_databases;
DROP TABLE xerxes_subcategories;
DROP TABLE xerxes_databases;
DROP TABLE xerxes_categories;
DROP TABLE xerxes_types;
*/

CREATE TABLE xerxes_databases(
	metalib_id     		VARCHAR(10),
	title_display		VARCHAR(100),
	type                    VARCHAR(50),
	data			TEXT,
	PRIMARY KEY (metalib_id)
);

CREATE TABLE xerxes_databases_search (
	database_id     	VARCHAR(10),
	field			VARCHAR(50),
	term			VARCHAR(50),

	FOREIGN KEY (database_id) REFERENCES xerxes_databases(metalib_id) ON DELETE CASCADE
);

CREATE INDEX xerxes_databases_search_field_idx ON xerxes_databases_search(field);
CREATE INDEX xerxes_databases_search_term_idx ON xerxes_databases_search(term);

CREATE TABLE xerxes_database_alternate_titles (
	id			INT IDENTITY,
	database_id     	VARCHAR(10),
	alt_title		VARCHAR(255),

	PRIMARY KEY (id),
	FOREIGN KEY (database_id) REFERENCES xerxes_databases(metalib_id) ON DELETE CASCADE
);

CREATE TABLE xerxes_database_alternate_publishers (
	id			INT IDENTITY,
	database_id     	VARCHAR(10),
	alt_publisher		VARCHAR(100),

	PRIMARY KEY (id),
	FOREIGN KEY (database_id) REFERENCES xerxes_databases(metalib_id) ON DELETE CASCADE
);

CREATE TABLE xerxes_database_languages (
	id			INT IDENTITY,
	database_id     	VARCHAR(10),
	language		VARCHAR(100),

	PRIMARY KEY (id),
	FOREIGN KEY (database_id) REFERENCES xerxes_databases(metalib_id) ON DELETE CASCADE
);

CREATE TABLE xerxes_database_notes (
	id			INT IDENTITY,
	database_id     	VARCHAR(10),
	note			VARCHAR(255),

	PRIMARY KEY (id),
	FOREIGN KEY (database_id) REFERENCES xerxes_databases(metalib_id) ON DELETE CASCADE
);

CREATE TABLE xerxes_database_keywords (
	id			INT IDENTITY,
	database_id    		VARCHAR(10),
	keyword			VARCHAR(255),

	PRIMARY KEY (id),
	FOREIGN KEY (database_id) REFERENCES xerxes_databases(metalib_id) ON DELETE CASCADE
);

CREATE TABLE xerxes_database_group_restrictions (
	id			INT IDENTITY,
	database_id    		VARCHAR(10),
	usergroup			VARCHAR(50),

	PRIMARY KEY (id),
	FOREIGN KEY (database_id) REFERENCES xerxes_databases(metalib_id) ON DELETE CASCADE
);

CREATE TABLE xerxes_categories(
	id 			INT,
	name     		VARCHAR(255),
	old			VARCHAR(255),
	normalized		VARCHAR(255),

	PRIMARY KEY (id)
);

CREATE TABLE xerxes_subcategories(
	metalib_id	VARCHAR(20),
	name     	VARCHAR(255),
	sequence	INT NOT NULL,
  	category_id	INT NOT NULL,

	PRIMARY KEY (metalib_id),
 	FOREIGN KEY (category_id) REFERENCES xerxes_categories(id) ON DELETE CASCADE
);

CREATE TABLE xerxes_subcategory_databases(

	database_id	VARCHAR(10),
  	subcategory_id	VARCHAR(20),
    	sequence INT,

 	FOREIGN KEY (database_id) REFERENCES xerxes_databases(metalib_id) ON DELETE CASCADE,
	FOREIGN KEY (subcategory_id) REFERENCES xerxes_subcategories(metalib_id) ON DELETE CASCADE
);

CREATE TABLE xerxes_types(
	id 			INT,
	name     		VARCHAR(255),
	normalized		VARCHAR(255),

	PRIMARY KEY (id)
);