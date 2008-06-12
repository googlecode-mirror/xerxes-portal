USE xerxes;

# fix for not explicitly setting storage engine to a type
# that can support transactions and stuff

ALTER TABLE xerxes_cache ENGINE = INNODB;
ALTER TABLE xerxes_sfx ENGINE = INNODB;
ALTER TABLE xerxes_refereed ENGINE = INNODB;
ALTER TABLE xerxes_users ENGINE = INNODB;
ALTER TABLE xerxes_records ENGINE = INNODB;

SET storage_engine = INNODB;

# prepping the xerxes_tags table for new features in 1.3

DROP TABLE IF EXISTS xerxes_tags;

CREATE TABLE xerxes_tags (
	username	VARCHAR(50),
  	record_id	MEDIUMINT,
   	tag 		VARCHAR(100),

 	FOREIGN KEY (username) REFERENCES xerxes_users(username) ON DELETE CASCADE,
	FOREIGN KEY (record_id) REFERENCES xerxes_records(id) ON DELETE CASCADE
);