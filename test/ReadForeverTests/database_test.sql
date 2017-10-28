DROP TABLE IF EXISTS `RF_ReadForeverUser`;
CREATE TABLE RF_ReadForeverUser (
	id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(60) NOT NULL,
	apikey VARCHAR(60) NOT NULL
);

DROP TABLE IF EXISTS `RF_ReadForeverListData`;
CREATE TABLE RF_ReadForeverListData (
	id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	userid INT(11) NOT NULL,
	topicname VARCHAR(255),
	FOREIGN KEY (userid) REFERENCES RF_ReadForeverUser(id)
);

INSERT INTO RF_ReadForeverUser (username, apikey) VALUES ('Dada', 'A1Z2e3r4t5');
INSERT INTO RF_ReadForeverUser (username, apikey) VALUES ('Bob', '123456');
INSERT INTO RF_ReadForeverUser (username, apikey) VALUES ('john', '123');
INSERT INTO RF_ReadForeverListData (userid, topicname) VALUES (1, 'Mon premier topic');
INSERT INTO RF_ReadForeverListData (userid, topicname) VALUES (1, 'Mon deuxième topic');
INSERT INTO RF_ReadForeverListData (userid, topicname) VALUES (1, 'Mon troisième topic');
INSERT INTO RF_ReadForeverListData (userid, topicname) VALUES (2, 'Mon troisième topic');
INSERT INTO RF_ReadForeverListData (userid, topicname) VALUES (2, 'Mon topic');
INSERT INTO RF_ReadForeverListData (userid, topicname) VALUES (2, 'Mon topic 2');
INSERT INTO RF_ReadForeverListData (userid, topicname) VALUES (2, 'Mon topic 3');
INSERT INTO RF_ReadForeverListData (userid, topicname) VALUES (3, 'Already present 1');