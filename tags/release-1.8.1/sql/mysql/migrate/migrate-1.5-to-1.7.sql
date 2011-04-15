USE xerxes;

# increase space for saved records and cache

ALTER TABLE xerxes_records MODIFY marc MEDIUMTEXT;
ALTER TABLE xerxes_cache MODIFY data MEDIUMTEXT;

# new refereed data

DROP TABLE xerxes_refereed;

CREATE TABLE xerxes_refereed (
	issn		VARCHAR(8),
	title		VARCHAR(1000),
	timestamp	VARCHAR(8)
);

CREATE INDEX xerxes_refereed_issn_idx ON xerxes_refereed(issn);

# for search arch

ALTER TABLE xerxes_records ADD record_type VARCHAR(100);