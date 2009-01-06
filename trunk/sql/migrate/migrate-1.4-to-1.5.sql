# Create tables for user-created categories


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

