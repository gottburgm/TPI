-- database db_buslocale
CREATE DATABASE IF NOT EXISTS db_buslocale
	CHARSET utf8 COLLATE utf8_unicode_ci;
	
USE db_buslocale;

DROP TABLE IF EXISTS tblPositions;
DROP TABLE IF EXISTS tblBus;

CREATE TABLE IF NOT EXISTS tblBus(
	numero INTEGER AUTO_INCREMENT PRIMARY KEY,
	idBus INTEGER,
        numeroBus VARCHAR(30) NOT NULL,
	adresseIP VARCHAR(30) NOT NULL,
	dateDerniereSynchronisation DATETIME NOT NULL, 
	dateDebutAcquisition DATETIME
);



CREATE TABLE IF NOT EXISTS tblPositions(
	numero INTEGER AUTO_INCREMENT PRIMARY KEY,
	dateHeure DATETIME NOT NULL,
	latitude VARCHAR(30) NOT NULL,
	longitude VARCHAR(30) NOT NULL,
	synchronise TINYINT(1) NOT NULL
);
