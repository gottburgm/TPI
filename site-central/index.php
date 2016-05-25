<?php

include('includes/header.php');
include('includes/functions.php');

  # Creation des objets
$DB_locale = new Database();
$DB_locale->Database_Setup('localhost', '3306', 'db_buscentrale', 'root', '');

  # Variables globales

  # ---------- /!\ API KEY A CHANGER ICI /!\ -----------
$api_key = "AIzaSyA31yykKDjOFi0bGFITzRdljzmdihwjWbs";
  # ----------------------------------------------------
$num_bus='';
$js_marker = '';
$waypoints = '';
$date_depart = '';
$date_fin = '';
$heure_depart = '';
$heure_fin = '';
$positions = Array();

  # Centre de la map par défaut
$center_map="{
  lat: 46.999, 
  lng: 6.900
}";

  # Zoom de la map par défaut
$zoom = 10;

  # Permet de savoir si l'utilisateur a selectionne un bus grace au stockage du numero de bus choisi dans
  # une variable de session, si la variable de session n'existe pas et que le numero de bus n'est pas encore
  # selectionne par l'utilisateur, on aura un "refresh" automatique de laa page toutes les 15 secondes

if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
  session_destroy();
  session_start();
}

  # Stockage du numero de bus dans la variable de session
  # Ou attribution a la variable num_bus a partir de la variable de session

if(isset($_POST['num_bus'])) 
{
  $_SESSION['num_bus'] = $_POST['num_bus'];
  $num_bus = $_POST['num_bus'];
}
elseif (isset($_SESSION['num_bus']))
{
  $num_bus = $_SESSION['num_bus'];
}

  # Si un numero de bus est selectionne et que celui-ci n'est pas vide
if(isset($num_bus) && !empty($num_bus))
{
    # Requete de selection des informations du bus
  $query2 = "SELECT * FROM tblBus WHERE numeroBus=?";
    # Requete de selection des positions du bus
  $query3 = "SELECT * FROM tblPositions WHERE num_tblBus=?";

    # Afin d'executer les requetes en "bindant" les valeurs, on place celles-ci dans un tableau
    # ainsi que le type de donnee dans un autre tableau pour que la valeurs[x] correspondent au types[x]
    # ces deux tableaux seront ensuite passes a la fonction qui "bindera" les valeurs et executeras la requete
  $values = Array($num_bus);
  $types = Array(PDO::PARAM_STR);
  
  $result2=$DB_locale->Execute_Bind($query2, $values, $types);

    # Apres chaque requete on vide les tableaux
  $values = Array();
  $types = Array();
  $result3='';
  $result4='';

    # On recupere les donnees du bus selectionne
  while( $row2 = $result2->fetch(PDO::FETCH_OBJ) )
  {
      # On cree egalement le code html du pop up d'informations qui sera affiche
    echo "<div id='modal1' class='modal'> <div class='modal-content'>\n
    <h4>Bus <b>$row2->numeroBus</b></h4>\n
    <p>Derniere Synchronisation <b>$row2->dateDerniereSynchronisation</b></p>\n
  </div> <div class='modal-footer'>\n
  <a href='#!' class=' modal-action modal-close waves-effect waves-green btn-flat'>Fermer</a>\n
</div> </div>
";
$num_bus = $row2->numeroBus;
$values = Array($row2->numero);
$types = Array(PDO::PARAM_INT);
}

    # On recupere les positions du bus
$result3=$DB_locale->Execute_Bind($query3, $values, $types);

    # Si les 4 champs requis sont selectionnes
if((isset($_POST['date_depart']))&&(isset($_POST['date_fin']))&&(!empty($_POST['date_depart']))&&(!empty($_POST['date_fin']))&&(isset($_POST['heure_depart']))&&(isset($_POST['heure_fin']))&&(!empty($_POST['heure_depart']))&&(!empty($_POST['heure_fin'])))
{
      # On les formate
  $date_depart = format_date("Y:m:d", $_POST['date_depart']);
  $date_fin = format_date("Y:m:d", $_POST['date_fin']);
  $heure_depart = format_date("H:i:s", $_POST['heure_depart']);
  $heure_fin = format_date("H:i:s", $_POST['heure_fin']);
  
  $dateHeure_depart = format_date("Y:m:d H:i:s", "$date_depart $heure_depart");
  $dateHeure_fin = format_date("Y:m:d H:i:s", "$date_fin $heure_fin");
  $waypoints = '';
  $compteur = 0;
  
      # On stock les positions de la base de donnes se trouvant dans l'intervalle saisit par l'utilisateur
      # dans le tableau : $postions
  while($row3 = $result3->fetch(PDO::FETCH_OBJ))
  {
    if((format_date('Y:m:d H:i:s', $row3->dateHeure) >= $dateHeure_depart)&&(format_date('Y:m:d H:i:s', $row3->dateHeure) <= $dateHeure_fin))
    {
      $positions[$compteur] = "{lat: $row3->latitude, lng: $row3->longitude}";
      $compteur++;
    }
  }
  
  if(count($positions) > 0)
  {
        # Si il y a trop de positions les waypoints genererons une erreur
    if(count($positions) < 10)
    {
      for($i = 1; $i < count($positions)-1; $i++)
      {
        $waypoints = $waypoints . "
        {
          location: " . $positions[$i] . ",
          stopover:true
        },\n";

      }
    }
    
        # On calcule et affiche le trajet entre la premiere position du tableau et la derniere
    $js_marker = $js_marker . "calculateAndDisplayRoute(directionsService, directionsDisplay, " . $positions[0] . ", " . end($positions) . ");\n";
        # On attribue la premiere postion au centre de la map
    $center_map = $positions[0];
        # Comme il n'y a qu'un bus, on augmente un petit peu le zoom sur la map
    $zoom = 15;
  }
      # on termine le code javascript
  $js_marker = $js_marker . "setMapOnAll(map);";

}
else
{
      # Sinon on affiche la derniere position connue du bus selectionne
  $js_marker = show_last_position($DB_locale, FALSE, $num_bus, $center_map);
  $zoom=18;

}
}
else
{
    # Sinon on affiche la derniere position connue de chacun des bus
  $js_marker = show_last_position($DB_locale, TRUE, "",  $center_map);
  $zoom = 12;
}

  # Code javascript final
$js_map =
"
<script>
                    // In the following example, markers appear when the user clicks on the map.
                    // The markers are stored in an array.
                    // The user can then click an option to hide, show or delete the markers.
  var map;
  var markers = [];


  function initMap() {
    var haightAshbury = " . $center_map . ";
    var directionsDisplay = new google.maps.DirectionsRenderer;
    var directionsService = new google.maps.DirectionsService;
    
    map = new google.maps.Map(document.getElementById('map'), {
      zoom: " . $zoom . ",
      center: haightAshbury,
      mapTypeId: google.maps.MapTypeId.TERRAIN
    });
    
    directionsDisplay.setMap(map);
    
    " . $js_marker . "
  }

                    // Adds a marker to the map and push to the array.
  function addMarker(location, contentString) {
    var marker = new google.maps.Marker({
      position: location,
                        // icon : image,
      map: map
    });
    
    var infowindow = new google.maps.InfoWindow({
      content: contentString
    });


    marker.addListener('click', function() {
      infowindow.open(map, marker);
    });

    markers.push(marker);
  }
  
  function addDirection(location) {
    var marker = new google.maps.Marker({
      position: location,
      icon : image,
      map: map
    });
    markers.push(marker);
  }

                    // Sets the map on all markers in the array.
  function setMapOnAll(map) {
    for (var i = 0; i < markers.length; i++) {
      markers[i].setMap(map);
    }
  }

                    // Removes the markers from the map, but keeps them in the array.
  function clearMarkers() {
    setMapOnAll(null);
  }

                    // Shows any markers currently in the array.
  function showMarkers() {
    setMapOnAll(map);
  }

                    // Deletes all markers in the array by removing references to them.
  function deleteMarkers() {
    clearMarkers();
    markers = [];
  }
  
  function calculateAndDisplayRoute(directionsService, directionsDisplay, org, dest) {
    for (i = 0; i < markers.length; i++)
    {
      markers[i].setMap(null);
    }

    directionsService.route({
      origin : org,
      destination : dest,
      waypoints: [
      " . $waypoints . "
      ],

      travelMode: google.maps.TravelMode['DRIVING']
    }, function(response, status) {
      if (status == google.maps.DirectionsStatus.OK) {
        directionsDisplay.setDirections(response);
      } else {
        window.alert('Directions request failed due to ' + status);
      }
    });
  }
  
  function showSteps(i, position) {
    var marker = new google.maps.Marker({
      position: position,
      map: map
    });
    markers[i] = marker;
  }
</script>
<script async defer src=\"https://maps.googleapis.com/maps/api/js?key=" . $api_key . "&amp;callback=initMap\" ></script>
</head>\n";
# Affichage du JavaScript final
echo $js_map;


?>

<body>

  <div class="container-fluid" style="margin: 40px 0;">
    
    
    <div class="row" style="padding-top: 80px;">
      
      <div class="col s6">
        
        <div style="width: 100%; min-height: 720px !important; height: auto;" id="map"></div>      

      </div>
      <div class="col s6">
        

        <h1>Centrale - Bus</h1>
        <form method="POST" action="">
          <p>
	    <p class="range-field">
      	       <input type="range" name="refresh" id="refresh" min="1" max="50" onmouseup="document.forms[0].submit()" />
            
            <select name="num_bus" onchange="document.forms[0].submit()">
              <?php
              # On rempli la liste des bus avec les numeros de bus presents dans la base de données
              $query = "SELECT * FROM tblBus ORDER BY numeroBus" ;

              # Execution de la requete
              $result=$DB_locale->Execute($query);
              
              $num_bus = '';
              $numero = "" ;
              $position_actuelle = "" ;
              $select = "";
              
              if(isset($_POST['num_bus']))
              {
                $num_bus = $_POST['num_bus'];
              }
              
              # On parcours les donnees afin de rester sur le bus que l'utilisateur a choisi meme après le rechargement de la page
              while( $row = $result->fetch(PDO::FETCH_OBJ) ) {
                if ($row->numeroBus == $num_bus) {
                  $select = 'selected="selected"' ;
                  $numero = $row->numeroBus;        
                }
                else {
                  $select = "" ;
                }
                # Affichage de chacun des numeros de bus dans la liste
                echo "<option value=\"". $row->numeroBus . "\" " .  $select . " >". $row->numeroBus . "</option>\n" ;
              }
              ?>
            </select>
          </p>
          <p>
            <label>Date du départ du trajet: </label>
            <input type="text" name="date_depart" placeholder="2017-04-19" class="datepicker">
            <br>
            <label>Date de fin du trajet: </label>
            <input type="text" name="date_fin" placeholder="2017-05-19" class="datepicker">
            <br>
            <label>Heure départ du trajet: </label>
            <input type="text" name="heure_depart" placeholder="10:30" />
            <br>
            <label>Heure fin du trajet : </label>
            <input type="text" name="heure_fin" placeholder="11:00" />
            <br>
          </p>
          <p>
            <button type="submit" class="submitBtn" name="afficher" value="Afficher" >Afficher</button>
            <button type="reset" class="resetBtn" name="effacer" value="Effacer" >Effacer</button>
          </p>
        </form>


      </div>

    </div>
  </div> <!-- container -->

  <script src="js/jquery.js"></script>
  <script type="text/javascript" src="js/materialize.min.js"></script>

  <?php
  # Affichage du pop up avec les donnes du bus si un numero de bus est selectionne
  # ainsi que la liste des numeros de bus
  if(isset($_POST['num_bus']))
  {
    echo "
    <script>       
      $(document).ready(function() {
        $('select').material_select();
        $('#modal1').openModal();
      });
    </script>
    ";
  }
  else
  {
    # Sinon on affiche seulement la liste des numeros de bus
    echo "
    <script>       
      $(document).ready(function() {
        $('select').material_select();
      });
    </script>
    ";
  }
  ?>
  <script>
    // Traduction du calendrier
    $('.datepicker').pickadate({
      selectMonths: true, 
      selectYears: 15, 
      format: 'yyyy-mm-dd',  
      labelMonthNext: 'Mois suivants',
      labelMonthPrev: 'Mois précedent',
      labelMonthSelect: 'Selectionner un mois',
      labelYearSelect: 'Selectionner une année',
      monthsFull: [ 'Janvier', 'Fevrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre' ],
      monthsShort: [ 'Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc' ],
      weekdaysFull: [ 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi' ],
      weekdaysShort: [ 'Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam' ],
      weekdaysLetter: [ 'D', 'L', 'M', 'M', 'J', 'V', 'S' ],
      today: 'Aujourd\'hui',
      clear: 'Effacer',
      close: 'Fermer'
    });

  </script>

</body>

</html>
