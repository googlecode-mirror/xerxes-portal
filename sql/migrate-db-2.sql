# Add stuff to Xerxes tables to support usergroup/'secondary affiliation'
# access control. 

# New columns in xerxes_databases

#ALTER TABLE xerxes_databases ADD guest_access  INTEGER(1);

# New table for database usergroups

CREATE TABLE xerxes_database_keywords (
	id			MEDIUMINT NOT NULL AUTO_INCREMENT,
	database_id    		VARCHAR(10),
	keyword			VARCHAR(255),

	PRIMARY KEY (id),
	FOREIGN KEY (database_id) REFERENCES xerxes_databases(metalib_id) ON DELETE CASCADE
);

# And a table for user usergroup assignments too
CREATE TABLE xerxes_user_usergroups (
	username    		VARCHAR(50),
	usergroup			  VARCHAR(50),

	PRIMARY KEY (username, usergroup),
	FOREIGN KEY (username) REFERENCES xerxes_users(username) ON DELETE CASCADE
);
