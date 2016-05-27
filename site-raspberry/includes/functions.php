<?php
# Fonctions

function get_inputs() {
  $months = '*';
  $days = '*';
  $hour = '*';
  $minute = '*';
  $dom = '*';
  $format = '';

  if((isset($_POST['minute']))&&(!empty($_POST['minute'])))
  {
    $eachx_string = explode("/", $_POST['minute']);

    if(($_POST['minute'] >= 0)&&($_POST['minute'] <= 59)&&(is_numeric($_POST['minute'])))
    {
      $minute = $_POST['minute'];
    }
    elseif (isset($eachx_string[1] )&&(($eachx_string[1] >= 0)&&($eachx_string[1] <= 59)&&(is_numeric($eachx_string[1]))))
    {
      $minute = "*/" . $eachx_string[1];
    }
  }

  if((isset($_POST['dom']))&&(!empty($_POST['dom']))&&(is_numeric($_POST['dom'])))
  {
    if(($_POST['dom']>= 1)&&($_POST['dom'] <= 31))
    {
      $dom = $_POST['dom'];
    }
  }

  if((isset($_POST['hour']))&&(!empty($_POST['hour'])))
  {
    $eachx_string = explode("/", $_POST['hour']);
    if(($_POST['hour'] >= 0)&&($_POST['hour'] <= 23)&&(is_numeric($_POST['hour'])))
    {
      $hour = $_POST['hour'];
    }
    elseif (isset($eachx_string[1] )&&(($eachx_string[1] >= 0)&&($eachx_string[1] <= 60)&&(is_numeric($eachx_string[1]))))
    {
      $hour = "*/" . $eachx_string[1];
    }
  }

  if(!empty($_POST['month']))
  {
    $temp_months = '';
   foreach($_POST['month'] as $month)
   {
    if(!empty($temp_months))
    {
     $temp_months = "$temp_months,$month";
   }
   else
   {
     $temp_months = $month;
   }
 }
 $months = $temp_months;
}

if(!empty($_POST['day']))
{
  $temp_days = '';
	foreach($_POST['day'] as $day)
	{
    if(!empty($temp_days))
    {
     $temp_days = "$temp_days,$day";
   }
   else
   {
     $temp_days = $day;
   }
 }
 $days = $temp_days;
}

$format = "$minute:$hour:$dom:$months:$days";

if($format === "*:*:*:*:*")
{
  $format = "false";
}

return $format;
}

function formulaire_saisie_bus()
{
  echo "<label class='erb1'>Numero du bus : </label><input type=\"text\" name=\"num_bus\" placeholder=\"/\" /><br>\n
  <label>Numero d'identification : </label><input type=\"text\" name=\"id_bus\" placeholder=\"/\" /><br>\n
  <label>Date debut d'acquisition : </label><input type=\"text\" name=\"date_acquisition\" placeholder=\"/\" /><br>\n
  <button class='submitBtn' type=\"submit\" name=\"enregistrer\" value=\"Enregistrer\" >Enregistrer</button>\n
  <button class='resetBtn' type=\"reset\" name=\"effacer\" value=\"Effacer\" >Effacer la saisie</button>
  <div class='erbWraning warning' id='erbWarning'> <p class='erbWarningText' style='max-width: 95%;'>  Veuillez saisir la date dans le format suivant: YYYY-MM-DD HH:MM <br>Exemple: 2016-05-01 12:00</p> <div class='erbHideWarning' id='erbHideWarning'> X </div></div>
  ";
}

function affiche_infos_bus($bus)
{
  echo "<div class'xcontainer'><label>Numero du bus : </label>
  <input type=\"text\" name=\"num_bus\" value=\"" . $bus->numeroBus . "\" /><br>\n
  <label>Adresse IP : </label>
  <input type=\"text\" name=\"adresse_ip\" value=\"" . $bus->adresse_IP . "\" disabled/><br>\n
  <label>Date debut d'acquisition : </label>
  <input type=\"text\" name=\"date_acquisition\" value=\"" . $bus->debut_acquisition . "\" /><br><br><br>\n
  <button class='btn submitBtn' type=\"submit\" name=\"modifier\" value=\"Modifier\" >Modifier</button>
  <button class='btn resetBtn' type=\"reset\" name=\"effacer\" value=\"Effacer\" >Effacer la saisie</button>
  <button class='btn deleteBtn' type=\"submit\" name=\"supprimer\" value=\"Supprimer\" >Supprimer le bus</button></div></br>  
  <div class='erbWraning warning' id='erbWarning'> <p class='erbWarningText' style='max-width: 95%;'>  Veuillez saisir la date dans le format suivant: YYYY-MM-DD HH:MM <br>Exemple: 2016-05-01 12:00</p> <div class='erbHideWarning' id='erbHideWarning'> X </div></div>\n
  ";
}

function formulaire_saisie_configuration()
{
  echo "
  <div class='newHR' style='margin: 40px 0 40px 0 !important;'></div>\n
  <div class='resetNormal'>
    <p class='subtitle' >Configuration tâches automatiques : </p><br><br>
    <label>Mois :</label><br><br>
    <div class='checkboxes'>
      <div class='col'>
        <label><input type=\"checkbox\" name=\"month[]\" value=\"1\">Janvier</label>
        <label><input type=\"checkbox\" name=\"month[]\" value=\"2\">Fevrier</label>
        <label><input type=\"checkbox\" name=\"month[]\" value=\"3\">Mars</label>
        <label><input type=\"checkbox\" name=\"month[]\" value=\"4\">Avril</label>
        <label><input type=\"checkbox\" name=\"month[]\" value=\"5\">Mai</label>
        <label><input type=\"checkbox\" name=\"month[]\" value=\"6\">Juin<br></label>
      </div>
      <div class='col'>
       <label><input type=\"checkbox\" name=\"month[]\" value=\"7\">Juillet</label>
       <label><input type=\"checkbox\" name=\"month[]\" value=\"8\">Août</label>
       <label><input type=\"checkbox\" name=\"month[]\" value=\"9\">Septembre</label>
       <label><input type=\"checkbox\" name=\"month[]\" value=\"10\">Octobre</label>
       <label><input type=\"checkbox\" name=\"month[]\" value=\"11\">Novembre</label>
       <label><input type=\"checkbox\" name=\"month[]\" value=\"12\">Décembre</label>
     </div></div><br>
     <label>Jours de la semaine :</label><br><br>
     <div class='checkboxes'>
      <div class='col'>
        <label><input type=\"checkbox\" name=\"day[]\" value=\"1\">Lundi</label>
        <label><input type=\"checkbox\" name=\"day[]\" value=\"2\">Mardi</label>
        <label><input type=\"checkbox\" name=\"day[]\" value=\"3\">Mercredi</label>
        <label><input type=\"checkbox\" name=\"day[]\" value=\"4\">Jeudi</label>
        <label><input type=\"checkbox\" name=\"day[]\" value=\"5\">Vendredi</label>
        <label><input type=\"checkbox\" name=\"day[]\" value=\"6\">Samedi</label>
        <label><input type=\"checkbox\" name=\"day[]\" value=\"0\">Dimanche</label></div>
      </div></div>
      <!-- resetnormal --><br>
      <input placeholder='Jours du mois' type=\"text\" name=\"dom\" ><br>\n
      <input placeholder='Heures' type=\"text\" name=\"hour\" ><br>\n
      <input type=\"text\" placeholder='Minutes' name=\"minute\" ><br><br>\n
      <button type=\"submit\" class='btn delBtns' name=\"save_clean\" value=\"Configurer suppression automatique\" style='border: 5px solid #242424;margin-bottom: 10px;'> Configurer suppression automatique </button><br>\n
      <button type=\"submit\" class='btn delBtns' style='background: #666; margin-bottom: 20px; border: 5px solid #777;' name=\"save_collect\" value=\"Configurer collecte automatique\" > Configurer collecte automatique </button>
      <div class='erbWraning warning' id='erbWarning'> <p class='erbWarningText' style='max-width: 95%;'><ul>
        <li>Pour la collecte automatique des données, il est conseillé de mettre 2 minutes soit : */2 dans 
          la case prévue à cet effet et de ne rien saisir d'autre.</li>
          <li>Une saisie entièrement nulle correspond à une exécution toutes les minutes</li>
          <li>Pour les saisies plus spécifiques, veuillez vous référer au manuel</li>
        </ul></p> <div class='erbHideWarning' id='erbHideWarning'> X </div></div>
        <br><div class='newHR' style='margin: 40px 0 40px 0 !important;'></div>\n
        <p class='subtitle' >Effacer les positions du bus des bases de données :</p><br>
        <input type='submit' name='clear_db' class='delete' value='Effacer' /><br>\n
        ";
      }

# Classes
      class Bus {
        var  $idBus;
        var  $numeroBus;
        var  $adresse_IP;
        var  $derniere_synchronisation;
        var  $debut_acquisition;

        function Bus()
        {
          # On recupere l'adresse IP et on l'attribue a la propriete correspondante
          $this->adresse_IP = $_SERVER['SERVER_ADDR'];
          
	# On lui met la valeur 0, c'est le script de fonctions PERL qui se chargera de lui attribuer la bonne valeur
          $this->idBus = 0;
        }
        
        function Bus_Setup($idBus, $numeroBus, $debut_acquisition, $derniere_synchronisation)
        {
          $this->idBus = $idBus;
          $this->numeroBus = $numeroBus;
          $this->derniere_synchronisation = $derniere_synchronisation;
          $this->debut_acquisition = $debut_acquisition;
        }

      }

      class Database {
        var  $hostname;
        var  $port;
        var  $db_name;
        var  $username;
        var  $password;
        var  $accessible;
        var  $BUS;
        var  $connexion;
        
        function Database()
        {
        }
        
        function Database_Setup($hostname, $port, $db_name, $username, $password)
        {
          $this->hostname = $hostname;
          $this->port = $port;
          $this->db_name = $db_name;
          $this->username = $username;
          $this->password = $password;
          $this->Connexion();
        }
        
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
        
        function Bus_Existe()
        {
          $bus;
          $result;
          $row;
          $query = 'SELECT * FROM tblBus';
          $result = $this->Execute($query);
          
          if($result)
          {
            while ($row = $result->fetch(PDO::FETCH_OBJ))
            {
              $this->BUS = new Bus();
              $this->BUS->Bus_Setup($row->numeroBus, $row->dateDebutAcquisition, $row->dateDerniereSynchronisation);
            }
            return $this->BUS;
          }
          else
          {
            return "false";
          }
        }
        
        function Bus_Register($bus)
        {
          $result;
          $query = 'INSERT INTO tblBus(idBus,numeroBus,adresseIP,dateDerniereSynchronisation,dateDebutAcquisition) VALUES (:idBus, :numeroBus, :adresseIP, :dateDerniereSynchronisation, :dateDebutAcquisition);';
          
          $result = $this->connexion->prepare($query);
          $result->bindValue(':idBus', $bus->idBus);
          $result->bindValue(':numeroBus', $bus->numeroBus, PDO::PARAM_STR);
          $result->bindValue(':adresseIP', $bus->adresse_IP, PDO::PARAM_STR);
          $result->bindValue(':dateDerniereSynchronisation', $bus->derniere_synchronisation);
          $result->bindValue(':dateDebutAcquisition', $bus->debut_acquisition);
          $result->execute();
          
        }
        
        function Bus_Update($bus)
        {
          $result;
          $query = 'UPDATE tblBus SET numeroBus=:numeroBus, dateDebutAcquisition=:dateDebutAcquisition where idBus=:idBus;';

          $result = $this->connexion->prepare($query);
          $result->bindValue(':idBus', $bus->idBus);
          $result->bindValue(':numeroBus', $bus->numeroBus, PDO::PARAM_STR);
          $result->bindValue(':dateDebutAcquisition', $bus->debut_acquisition);
          $result->execute();
        }
	
      }

      ?>
