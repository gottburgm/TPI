<?php
	# Titre de la page
	$siteTitle = "Site Web Centrale Bus";
	session_start();

	$refresh = '';

	if((isset($_POST['refresh']))&&(is_numeric($_POST['refresh']))&&($_POST['refresh'] > 0)&&($_POST['refresh'] <= 50))
	{
	    $refresh = $_POST['refresh'];
	}
	else
	{
	    $refresh = 15;
	}
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
                <!--Rafraichissement automatique toutes les 2 secondes -->
                <meta http-equiv="refresh" content="<?php echo($refresh) ?>" >
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

