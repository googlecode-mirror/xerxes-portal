# author: David Walker
# copyright: 2009 California State University
# version: $Id$
# package: Xerxes
# link: http://xerxes.calstate.edu
# license: http://www.gnu.org/licenses/

CREATE DATABASE IF NOT EXISTS xerxes DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE xerxes;

SET storage_engine = INNODB;

DROP TABLE IF EXISTS xerxes_databases_search;
DROP TABLE IF EXISTS xerxes_subcategory_databases;
DROP TABLE IF EXISTS xerxes_subcategories;
DROP TABLE IF EXISTS xerxes_databases;
DROP TABLE IF EXISTS xerxes_categories;
DROP TABLE IF EXISTS xerxes_types;

CREATE TABLE xerxes_databases(
	metalib_id     		VARCHAR(10),
	title_display		VARCHAR(100),
	type                    VARCHAR(50),
	data			MEDIUMTEXT,
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

CREATE TABLE xerxes_categories(
	id 			MEDIUMINT NOT NULL AUTO_INCREMENT,
	name     		VARCHAR(255),
	old			VARCHAR(255),
	normalized		VARCHAR(255),
	lang			VARCHAR(5),

	PRIMARY KEY (id)
);

CREATE TABLE xerxes_subcategories(
	metalib_id	VARCHAR(20),
	name     	VARCHAR(255),
	sequence	MEDIUMINT NOT NULL,
  	category_id	MEDIUMINT NOT NULL,

	PRIMARY KEY (metalib_id),
 	FOREIGN KEY (category_id) REFERENCES xerxes_categories(id) ON DELETE CASCADE
);

CREATE TABLE xerxes_subcategory_databases(

	database_id	VARCHAR(10),
  	subcategory_id	VARCHAR(20),
    	sequence 	MEDIUMINT,

 	FOREIGN KEY (database_id) REFERENCES xerxes_databases(metalib_id) ON DELETE CASCADE,
	FOREIGN KEY (subcategory_id) REFERENCES xerxes_subcategories(metalib_id) ON DELETE CASCADE
);

CREATE TABLE xerxes_types(
	id 			MEDIUMINT NOT NULL AUTO_INCREMENT,
	name     		VARCHAR(255),
	normalized		VARCHAR(255),

	PRIMARY KEY (id)
);
