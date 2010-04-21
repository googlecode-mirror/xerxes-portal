# author: David Walker
# copyright: 2009 California State University
# version: $Id$
# package: Xerxes
# link: http://xerxes.calstate.edu
# license: http://www.gnu.org/licenses/

USE xerxes;

CREATE TABLE xerxes_databases(
	metalib_id     		VARCHAR(10),
	title_full     		VARCHAR(255),
	title_display		VARCHAR(100),
	institute		VARCHAR(5),
	filter			VARCHAR(50),
	searchable		INT,
	guest_access  		INT,
	subscription		INT,
	proxy 			INT,
	active			VARCHAR(10),
	new_resource_expiry	datetime,
	updated			datetime,
	number_sessions		INT,
	sfx_suppress		INT,
	creator			VARCHAR(600),
	publisher		VARCHAR(300),
	publisher_description	VARCHAR(3000),
	description		VARCHAR(3000),
	coverage		VARCHAR(1500),
	time_span		VARCHAR(200),
	copyright		VARCHAR(1000),
	note_cataloger		VARCHAR(2500),
	note_fulltext		VARCHAR(500),
	search_hints		VARCHAR(3000),
	type			VARCHAR(50),
	icon			VARCHAR(50),
	library_address		VARCHAR(200),
	library_city		VARCHAR(100),
	library_state		VARCHAR(30),
	library_zipcode		VARCHAR(20),
	library_country		VARCHAR(50),
	library_telephone	VARCHAR(50),
	library_fax		VARCHAR(50),
	library_email		VARCHAR(50),
	library_contact		VARCHAR(200),
	library_note		VARCHAR(200),
	library_hours		VARCHAR(150),
	link_native_home	VARCHAR(500),
	link_native_record		VARCHAR(500),
	link_native_home_alternative	VARCHAR(500),
	link_native_record_alternative	VARCHAR(500),
	link_native_holdings		VARCHAR(500),
	link_guide			VARCHAR(500),
	link_publisher			VARCHAR(500),
	link_search_post		VARCHAR(500),

	PRIMARY KEY (metalib_id)
);

CREATE TABLE xerxes_database_alternate_titles (
	id			INT IDENTITY,
	database_id     	VARCHAR(10),
	alt_title		VARCHAR(100),

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