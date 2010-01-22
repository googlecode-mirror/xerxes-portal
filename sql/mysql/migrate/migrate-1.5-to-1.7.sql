USE xerxes;

# increase space for saved records

ALTER TABLE xerxes_records MODIFY marc MEDIUMTEXT;

DROP TABLE xerxes_refereed;

CREATE TABLE xerxes_refereed (
	issn		VARCHAR(8),
	title		VARCHAR(1000),
	timestamp	VARCHAR(8)
);

CREATE INDEX xerxes_refereed_issn_idx ON xerxes_refereed(issn);