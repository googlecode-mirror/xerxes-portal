USE xerxes;

# fix for not explicitly setting storage engine to a type
# that can support transactions and stuff

ALTER TABLE xerxes_cache ENGINE = INNODB;
ALTER TABLE xerxes_sfx ENGINE = INNODB;
ALTER TABLE xerxes_refereed ENGINE = INNODB;
ALTER TABLE xerxes_users ENGINE = INNODB;
ALTER TABLE xerxes_records ENGINE = INNODB;

SET storage_engine = INNODB;


