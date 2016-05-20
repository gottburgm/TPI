<?php
	# Titre de la page
	$siteTitle = "Site Web Centrale Bus";
	session_start();
?>

<!DOCTYPE html>
<html>
	<head>
		<!--Importation Google Icon Font-->
		<link href="http://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
		<!--Importation materialize.css-->
		<link type="text/css" rel="stylesheet" href="css/materialize.min.css"  media="screen,projection"/>

		<meta charset="UTF-8">
		<meta name="author" content="Michael Gottburg">
	    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

		<title><?php echo($siteTitle) ?> </title>
		<link rel="stylesheet" href="css/master.css">
		<style>
			#map {
		     height: 80%;
		     width: 60%;
		     margin: auto;
		     border: 3px solid #73AD21;
		     padding: 10px;
			}
		</style>

