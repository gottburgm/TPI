
<?php

include ('./includes/header.php');

?>

<body>

    <div class="container">
        <a class="btn pageswitch" href="./configuration.php" >Parametres locaux</a>
        <h1 class="main-title">Configuration Bus</h1>
        <div class="newHR"></div>
        <form method="POST" action="">
        	<p>
             <br>
             <?php
        	    # On inclus le script contenant les fonctions ainsi que les classes necessaires
             include_once('./includes/functions.php');

                # Variables globales
             $num_bus;
             $id_bus;
             $date_acquisition;

                # Declaration de l'objet Database
             $DB_locale = new Database();
                # Initialisation de l'objet Database
             $DB_locale->Database_Setup('localhost', '3306', 'db_buslocale', 'root', '');

                # Declaration de l'objet Bus
             $bus_existe = new Bus(); 
                # Initialisation de l'objet Bus

                # On appelle la fonction qui ira voir si il y a un bus enregistre dans la base de donnees locale
                # et qui retournera un objet Bus avec les informations du bus si celui-ci est present
                # sinon retourne "false"
             $bus_existe = $DB_locale->Bus_Existe();

                # Si une action a ete demandee (si un bouton a ete clique)
             if((isset($_POST['modifier']))||(isset($_POST['enregistrer'])))
             {
                    # Si l'objet Bus n'est pas "false", donc qu'un bus est deja enregistre dans la base de donnees locale
                if($bus_existe)
                {
                        # -- Modification --
                        # Si un des deux champs modifiables a ete saisi
                    if((isset($_POST['num_bus']))||(isset($_POST['date_acquisition'])))
                    {
                        $num_bus_valide = "true";
                        $date_acquisition_valide = "true";

                            # Si le champ de numero de bus a ete saisi
                        if(isset($_POST['num_bus']))
                        {
                            
                            $num_bus = $_POST['num_bus'];

                                # On demande au script de verifier que le numero n'existe pas deja dans la base de donnes centrale
                            exec("perl /var/www/scripts/functions.pl --check-num $num_bus", $output);

                                # Si le script retourne autre chose que "true", la valeur saisie n'est pas valide
                            if(!($output[0] == "true"))
                            {
                                $num_bus_valide = "false";
                            }
                        }
                        else
                        {
                            $num_bus = $bus_existe->numeroBus;
                        }

                            # Si le champ de date d'acquisition a ete saisi
                        if(isset($_POST['date_acquisition']))
                        {
                            $today = date("Y:m:d-H:i");
                            $date_acquisition = date("Y:m:d-H:i", strtotime($_POST['date_acquisition']));

                            if(($date_acquisition >= $today)||($date_acquisition == "1970:01:01"))
                            {
                                $date_acquisition_valide = "true";
                            }
                            
                        }
                        else
                        {
                            $date_acquisition = $bus_existe->debut_acquisition;
                        }

                            # Si les deux champs saisis sont correctes
                        if(($num_bus_valide == "true")&&($date_acquisition_valide == "true"))
                        {
                            $bus_existe->numeroBus = $num_bus;
                            $bus_existe->debut_acquisition = $date_acquisition;
                            $bus_existe->derniere_synchronisation =  date("Y:m:d-H:i:s");

                                # On met a jour le bus dans la base de donnes locale
                            $DB_locale->Bus_Update($bus_existe);

                                # Puis, on demande au script de le mettre a jour dans la base de donnees centrale
                            exec("perl /var/www/scripts/functions.pl --update");
                            header('Location: index.php');
                        }
                        else
                        {
                            affiche_infos_bus($bus_existe);
                            echo "<script>$(\"#erbWarning\").hide(600);</script><br><div class='erbWraning warning2' id='erbWarning'> <p class='erbWarningText' style='max-width: 95%;'>Erreur de saisie, le numéro de bus existe déjà ou la date saisie n'est pas valide.</p> <div class='erbHideWarning' id='erbHideWarning'> X </div>";
                        }
                    }
                    else
                    {
                            # On affiche le formulaire de modification avec les valeurs actuelles du bus
                        affiche_infos_bus($bus_existe);
                    }
                }
                else
                {
                        # -- Enregistrement --
                        # Si les deux champs obligatoires de numero de bus et de numero d'identification de bus on ete saisi
                    if((isset($_POST['num_bus']))&&(isset($_POST['id_bus'])))
                    {
                            # Si ces deux champs sont des nombres
                        if((is_numeric($_POST['num_bus']))&&(is_numeric($_POST['id_bus'])))
                        {
                            $num_bus = $_POST['num_bus'];
                            $id_bus = $_POST['id_bus'];
                            $num_bus = $num_bus . "-" . $id_bus;

                                # On demande au scripr de verifier que le numero n'existe pas deja dans la base de donnees distante
                            exec("perl /var/www/scripts/functions.pl --check-num $num_bus", $output);

                                # Si le script nous a retourne "true" (donc que le numero est disponible)      
                            if($output[0] == "true")
                            {
                                    # Si la date d'acquisition a ete saisie
                                if(isset($_POST['date_acquisition']))
                                {
                                    $today = date("Y:m:d");

                                        # Si la date d'acquisition saisie est superieure a celle d'aujourd'hui
                                    if(date("Y:m:d-H:i", strtotime($_POST['date_acquisition'])) > $today)
                                    {
                                            # On attribue la valeur saisie par l'utilisateur
                                        $date_acquisition = date("Y:m:d-H:i", strtotime($_POST['date_acquisition']));
                                    }
                                    else
                                    {
                                            # On attribue la date par defaut, qui sera toujours plus petite que celle du jours et 
                                            # donc la collecte se fera des que celle-ci sera configuree                                           
                                        $date_acquisition = date("Y:m:d-H:i", strtotime('0000-00-00'));
                                    }
                                    
                                }

                                    # Creation d'un nouvel objet de type bus
                                $BUS = new Bus();
                                    # Attribution des nouvelles valeurs
                                $BUS->Bus_Setup($num_bus, $date_acquisition, date("Y:m:d-H:i:s"));
                                
                                    # Enregistrement local de celui-ci
                                $DB_locale->Bus_Register($BUS);

                                    # Enregistrement sur la centrale qui doit obligatoirement etre accessible pour l'enregistrement
                                    # du bus
                                exec("perl /var/www/scripts/functions.pl --register");
                                header('Location: index.php');
                            }
                            else
                            {
                                formulaire_saisie_bus();
                                    # Affichage de l'erreur
                                echo "<script>$(\"#erbWarning\").hide(600);</script><br><div class='erbWraning warning2' id='erbWarning'> <p class='erbWarningText' style='max-width: 95%;'>Erreur de saisie, le numéro de bus existe déjà dans la base de données centrale.</p> <div class='erbHideWarning' id='erbHideWarning'> X </div>";
                            }
                        }
                    }
                    else
                    {
                            # Affichage du formulaire d'enregistrement du bus
                        formulaire_saisie_bus();
                    }
                }

            }
            else
            {
                    # Si un bus existe dans la base de donnes locale on affiche le formulaire de  modification
                    # Sinon on affiche celui d'enregistrement
                
                if($bus_existe)
                {
                    affiche_infos_bus($bus_existe);
                }
                else
                {
                    formulaire_saisie_bus();
                }
            }
            
            ?>	 
        </p>
    </form>
</div> <!-- Container -->

<script>
    $(document).ready(function(){
     $("#erbHideWarning").click(function(){
         $("#erbWarning").hide(600);
     });
 });

</script>

</body>
</html>
