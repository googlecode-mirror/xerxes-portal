
USE xerxes;
CREATE LOGIN xerxes WITH PASSWORD = 'password';
CREATE user xerxes FOR LOGIN xerxes;
GRANT SELECT, INSERT, DELETE, UPDATE TO xerxes;