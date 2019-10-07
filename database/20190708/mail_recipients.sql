CREATE TABLE IF NOT EXISTS mail_recipients (
  id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(64) DEFAULT NULL,
  email VARCHAR(254) NOT NULL,
  type INT(1) NOT NULL DEFAULT 1,
  mail_id INT DEFAULT NULL,
  CreationDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ModificationDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)

CREATE TABLE IF NOT EXISTS mail_recipient_types (
  id INT(1) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(64) DEFAULT NULL,
  CreationDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ModificationDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
INSERT INTO mail_recipient_types (id, name) VALUES (1, 'recipient');
INSERT INTO mail_recipient_types (id, name) VALUES (2, 'cc');
INSERT INTO mail_recipient_types (id, name) VALUES (3, 'bcc');