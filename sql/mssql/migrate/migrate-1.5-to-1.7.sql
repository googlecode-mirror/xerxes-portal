USE xerxes;

/* mysql scripts included changes to MEDIUMTEXT to increase space 
   for saved records and cache, that is unnecessary for sql server, 
   since the TEXT type is already equivalent to mysql MEDIUMTEXT
*/

/* new refereed data */

DROP TABLE xerxes_refereed;

CREATE TABLE xerxes_refereed (
	issn		VARCHAR(8),
	title		VARCHAR(1000),
	timestamp	VARCHAR(8)
);

CREATE INDEX xerxes_refereed_issn_idx ON xerxes_refereed(issn);

/* for search arch */

ALTER TABLE xerxes_records ADD record_type VARCHAR(100);