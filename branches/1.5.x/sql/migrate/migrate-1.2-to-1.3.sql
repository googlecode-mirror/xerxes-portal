USE xerxes;

# new tagging feature

DROP TABLE IF EXISTS xerxes_tags;

CREATE TABLE xerxes_tags (
	username	VARCHAR(50),
  	record_id	MEDIUMINT,
   	tag 		VARCHAR(100),

 	FOREIGN KEY (username) REFERENCES xerxes_users(username) ON DELETE CASCADE,
	FOREIGN KEY (record_id) REFERENCES xerxes_records(id) ON DELETE CASCADE
);

# support usergroup/'secondary affiliation' access control.

ALTER TABLE xerxes_users ADD first_name VARCHAR(50);
ALTER TABLE xerxes_users ADD last_name VARCHAR(50);
ALTER TABLE xerxes_users ADD email_addr VARCHAR(120);

ALTER TABLE xerxes_databases ADD guest_access INTEGER(1);

CREATE TABLE xerxes_user_usergroups (
	username    		VARCHAR(50),
	usergroup		VARCHAR(50),

	PRIMARY KEY (username, usergroup),
	FOREIGN KEY (username) REFERENCES xerxes_users(username) ON DELETE CASCADE
);

# bug fix to make sure temporary usernames fit in length of fields

ALTER TABLE xerxes_records MODIFY username VARCHAR(50)