# Add some stuff to xerxes tables for new version

# New columns in xerxes_users

ALTER TABLE xerxes_users ADD first_name VARCHAR(50);
ALTER TABLE xerxes_users ADD last_name VARCHAR(50);
ALTER TABLE xerxes_users ADD email_addr VARCHAR(120);
