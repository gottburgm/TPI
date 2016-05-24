-- database db_buscentrale
CREATE DATABASE IF NOT EXISTS db_buscentrale
	CHARSET utf8 COLLATE utf8_unicode_ci;
	
USE db_buscentrale;

DROP TABLE IF EXISTS tblPositions;
DROP TABLE IF EXISTS tblBus;

CREATE TABLE IF NOT EXISTS tblBus(
	numero INTEGER AUTO_INCREMENT PRIMARY KEY,
    numeroBus VARCHAR(30) NOT NULL,
	adresseIP VARCHAR(30) NOT NULL,
	dateDerniereSynchronisation DATETIME NOT NULL, 
	dateDebutAcquisition DATETIME
);

CREATE TABLE IF NOT EXISTS tblPositions(
	numero INTEGER AUTO_INCREMENT PRIMARY KEY,
	dateHeure DATETIME NOT NULL,
	num_tblBus INTEGER,
	latitude VARCHAR(30) NOT NULL,
	longitude VARCHAR(30) NOT NULL,
	FOREIGN KEY (num_tblBus)
		REFERENCES tblBus(numero)
);
















