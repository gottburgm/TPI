
<?php

include ('./includes/header.php');

?>

<body>
  <div class="container">
    <a class="btn pageswitch" href="./index.php" >Configuration Bus</a>
    <h1 class="main-title">Parametres locaux</h1>
    <form method="POST" action="">
     <p>
       <br>
       <?php
       include_once('./includes/functions.php');
       Main();
	
       function Main()
       {
        # Affichage du formulaire de saisie de la planification
        formulaire_saisie_configuration();
	
	# On regarde si des taches existent deja
	$tasks = get_tasks();
	$tasks_html;
	
	if(!($tasks == "false"))
	{
	   for ($i = 0; $i < count($tasks); $i++)
	   {
	     $tasks_html = $tasks_html . $tasks[$i] . "<br>";
	   }
	   
	   echo "<script>$(\"#erbWarning\").hide(600);</script><br><div class='erbWraning warning' id='erbWarning'> <p class='erbWarningText' style='max-width: 95%;'>" . $tasks_html . "</p> <div class='erbHideWarning' id='erbHideWarning'> X </div>";
	}
	else
	{
	  echo "<script>$(\"#erbWarning\").hide(600);</script><br><div class='erbWraning warning' id='erbWarning'> <p class='erbWarningText' style='max-width: 95%;'>Aucune tâche planifée actuellement.</p> <div class='erbHideWarning' id='erbHideWarning'> X </div>";
	}
	
        if(isset($_POST['clear_db']))
        {
          # Suppression directe des positions
          $output = exec("perl /var/www/scripts/functions.pl --clear-positions");
        }

        # Si $_POST['save_clean'] existe, c'est que l'utilisateur a demandé la planification
        # de la tache de suppresion automatique
        if(isset($_POST['save_clean'])) 
        {
          # Test et formatage de la saisie
          $format = get_inputs();

          # Si la saisie est incorrecte on affiche une erreur
          if($format === "false")
          {
            echo "<script>$(\"#erbWarning\").hide(600);</script><br><div class='erbWraning warning2' id='erbWarning'> <p class='erbWarningText' style='max-width: 95%;'>Erreur de saisie, tâche non planifiée.</p> <div class='erbHideWarning' id='erbHideWarning'> X </div>";
          }
          else
          {
            # Sinon on cree la nouvelle tache
            $output = exec("perl /var/www/scripts/functions.pl --set-cleantime $format");
          }
        }

       # Si $_POST['save_collect'] existe, c'est que l'utilisateur a demandé la planification
       # de la tache de collecte automatique
	     if(isset($_POST['save_collect']))
       {
          # Test et formatage de la saisie
          $format = get_inputs();

          # Si la saisie est incorrecte on affiche une erreur
          if($format === "false")
          {
            echo "<script>$(\"#erbWarning\").hide(600);</script><br><div class='erbWraning warning2' id='erbWarning'> <p class='erbWarningText' style='max-width: 95%;'>Erreur de saisie, tâche non planifiée.</p> <div class='erbHideWarning' id='erbHideWarning'> X </div>";
          }
          else
          {
            # Sinon on cree la nouvelle tache
            $output = exec("perl /var/www/scripts/functions.pl --set-collecttime $format");
          }
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
