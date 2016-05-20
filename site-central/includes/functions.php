<?php

# Fonctions

# Fonction :   format_date
#
# Parametres : $format => le format de sortie
#              $string => La chaine a formater
#
# But :        Retourner une date au format souhaite

function format_date($format, $string)
{
  $date = date($format, strtotime($string));
  return $date;
}


# Fonction :   show_last_position
#
# Parametres : $DB                =>  Objet Database
#              $show_all_bus      =>  TRUE si on veut afficher la derniere position connue de tous les bus
#                                     FALSE ou NULL si on veut seulement celle d'un bus specifique
#
#              $numBus            =>  Numero du bus que l'on veut consulter, vide si tous les bus
#              ref $center_map    => Coordonnes du centre de la map qui sera affichee
#
# But : Creer le code javascript contenant la derniere position connue d'un ou plusieurs bus et le retourner
#       pour etre ajoute au reste du code javascript

function show_last_position($DB, $show_all_bus, $numBus, &$center_map)
{
  $js_marker = "";
  $query = "";
  $result = "";

  # Si la variable est definit on selectionne tous les bus
  if($show_all_bus)
  {
    $query = "SELECT * FROM tblBus" ;
    $result=$DB->Execute($query);
  }
  else
  {
    # Sinon on selectionne seulement le bus correspondant a la variable $numBus
    $query = "SELECT * FROM tblBus where numeroBus=?"; 
    $values = Array($numBus);
    $types = Array(PDO::PARAM_STR);
    $result=$DB->Execute_Bind($query, $values, $types);
  }

  while($row = $result->fetch(PDO::FETCH_OBJ))
  {
    # La requete selectionne seulement la derniere position du bus
    $query2 = "SELECT * FROM tblPositions WHERE num_tblBus=" . $row->numero . " ORDER BY numero DESC LIMIT 1;";
    $result2=$DB->Execute($query2);

    while( $row2 = $result2->fetch(PDO::FETCH_OBJ) ) 
    {
      # On attribue a center map les coordonnes d'une des posiition afin d'etre sur que la map sera affichee correctement
      $center_map = "{
       lat: " . $row2->latitude . ", 
       lng: " . $row2->longitude . "}";

       # On cree le code javascript du marker qui sera affiche sur la map
       # Ainsi qu'un petit texte avec les informations sur la position
      $js_marker = $js_marker . 
      "\n\t\t\t\t\t\t
      addMarker({
       lat: " . $row2->latitude . ", 
       lng: " . $row2->longitude . "},
       '<p>Bus : " . $row->numeroBus . "<br>Date et Heure : " . format_date('Y:m:d H:i:s', $row2->dateHeure) . " <br>latitude : " . $row2->latitude . " longitude : " . $row2->longitude . " </p>');";
     }
   }
   # On ajoute au javascript cree l'appel de la fonction qui affichera les markers
   $js_marker = $js_marker . "\nshowMarkers()";

   # On retourne le code genere
   return $js_marker;
 }

# Classes

# Classe : Database
# But : Recuperation de donnes dans la base de donnees du serveur actuel

class Database {
  var  $hostname;
  var  $port;
  var  $db_name;
  var  $username;
  var  $password;
  var  $accessible;
  var  $connexion;

  # Constructeur
  function Database()
  {
  }

  # Attribue les valeurs aux proprietes de la classe
  function Database_Setup($hostname, $port, $db_name, $username, $password)
  {
    $this->hostname = $hostname;
    $this->port = $port;
    $this->db_name = $db_name;
    $this->username = $username;
    $this->password = $password;
    $this->Connexion();
  }

  # Etablie et fournie la connexion avec la base de donnees
  function Connexion()
  {
    try 
    {
      $this->connexion = new PDO('mysql:host=' . $this->hostname . ';dbname=' . $this->db_name, $this->username, $this->password);
      $this->accessible = 'true';
    }
    catch (Exception $e)
    {
      echo 'Erreur : ' . $e->getMessage() . '<br />N° : ' . $e->getCode();
      $this->accessible = 'false';
    }
  }

  # Execute une requete SQL en "bindant" les parametres composants celle-ci
  function Execute_Bind($query, $values, $types)
  {
    $result;
    $result = $this->connexion->prepare($query);
    try
    {
      for($i = 0; $i < count($values); $i++)
      {
        $result->bindValue($i+1, $values[$i], $types[$i]);
      }
      $result->execute();
      return $result;
    }
    catch(Exception $e)
    {
      echo 'Erreur : '.$e->getMessage().'<br />';
      echo 'N° : '.$e->getCode();
      die();
    }
  }

  # Execute une requete SQL standard
  function Execute($query)
  {
    $result;
    try
    {
      $result = $this->connexion->prepare($query);
      $result->execute();
      return $result;
    }
    catch(Exception $e)
    {
      echo 'Erreur : '.$e->getMessage().'<br />';
      echo 'N° : '.$e->getCode();
      die();
    }
  }
}

?>