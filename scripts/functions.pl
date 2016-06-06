#!/usr/bin/perl 


################################################################################################
#                                                                                              #
#  Script : functions.pl                                                                       #
#  Auteur : Michael Gottburg                                                                   #
#  Projet : TPI - CPLN                                                                         #
#  Date : Mai 2016                                                                             #
#                                                                                              #
################################################################################################

# Declaration des modules necessaires
use 5.10.0;
use POSIX qw(strftime);

no warnings 'experimental';

# Module pour la gestion Crontab
use Config::Crontab;

# Module pour les base de donnees
use DBI;

# Variables globales

# Creation de l'"Handler" de la base de donnees centrale
my $BDD_Centrale = Connexion('mysql', 'localhost', 'db_buscentrale', 'root', '');
# Creation de l'"Handler" de la base de donnees locale
my $BDD_Locale = Connexion('mysql', 'localhost', 'db_buslocale', 'root', '');


# Recuperation des parametres
Parametres();

# Fonction :    Parametres
# Parametres :  Aucun
# Retour :      Aucun
#
# Variables :   $#ARGV => Constante, contient la commande complete qui a executee cette instance
#               $argnum => Numero de l'argument actuel dans la boucle de parcours de parametres
#               $value_pos => Correspond au numero de l'emplacement d'un parametre + 1 afin d'avoir sa valeur (qui doit le suivre)
#               $value => la valeur du parametre si presente
#
# But :  Traiter le parametre et son eventuelle valeur, qui suivent le nom du script lors de son execution
#       et de rediriger le script vers la bonne fonction et si presente, lui passer la valeur
#
# Commentaire : Cette fonction est systematiquement appellee

sub Parametres {
  my $value_pos;
  my $value;

  foreach my $argnum (0 .. $#ARGV)
  {
    given ($ARGV[$argnum])
    {
      when ($ARGV[$argnum] eq '--check-num')
      {
       $value_pos = $argnum + 1;
       $value = $ARGV[$value_pos];
       Bus_Available($value);
      }
      
      when ($ARGV[$argnum] eq '--register')
      {
         Bus_Register();
      }
      
      when ($ARGV[$argnum] eq '--update')
      {
         Bus_Update();
      }

      when ($ARGV[$argnum] eq '--remove')
      {
         Bus_Remove();
      }
      
      # Jamais appelle par un tiers mais present si necessaire
      when($ARGV[$argnum] eq '--synchronisation')
      {
           Synchronisation();
      }

      when($ARGV[$argnum] eq '--set-collecttime')
      {
           $value_pos = $argnum + 1;
           $value = $ARGV[$value_pos];
           Create_Task($value, 'collect');
      }

      when($ARGV[$argnum] eq '--set-cleantime')
      {
           $value_pos = $argnum + 1;
           $value = $ARGV[$value_pos];
           Create_Task($value, 'clean');
      }

      when($ARGV[$argnum] eq '--get-lastID')
      {
           Get_LastBusID();
      }

      when($ARGV[$argnum] eq '--save-position')
      {
           $value_pos = $argnum + 1;
           $value = $ARGV[$value_pos];
           Save_Position($value);
      }

      when($ARGV[$argnum] eq '--clear-positions')
      {
           Clear_Positions();
      }
    }
  }
}

# Fonction :    Connexion
# Parametres :  $driver   => driver de la base de donnees (mysql dans notre cas)
#               $hostname => IP/nom de domaine de la base de donnees
#               $database => Nom de la base de donnees
#               $username => Nom d'utilisateur
#               $password => Mot de passe de l'utilisateur
#
# Retour :      $connexion => Objet de type DBI
#               "false"    => si la base de donnees n'est pas accessible
#
# Variables :   $source_name => prefix de connexion a la base de donnees avec les informations passees en parametres
#
# But :         Fournir un objet provenant du module DBI afin de se connecter aux bases de donnees et d'y faire les traitements necessaires
#
# Commentaire : Cette fonction est systematiquement appellee et j'ai donc my un delai de connexion de 10 secondes afin de determiner si la base
#               de donnees est accessible ou non.

sub Connexion {
  my($driver, $hostname, $database, $username, $password ) = @_;
  my $source_name = "DBI:$driver:database=$database;host=$hostname;mysql_connect_timeout=10";
  my $connexion;
  eval { $connexion = DBI->connect($source_name, $username, $password) } or return "false";
  return $connexion;
}


# Fonction :    Clear_Positions
# Parametres :  Aucun
# Retour :      Aucun
#
# Variables :   $sth       => Objet de traitement SQL qui effacera les positions synchronisees localement
#               $sth2      => Objet de traitement SQL qui effacera les positions de notre bus sur la base de donnees centrale
#               $bus_ID    => Numero unique du bus local sur la base de donnees centrale
#
# But :         Effacer localement les positions qui ont deja ete synchronisees sur la base de donnees centrale (identifiees
#               grace a la colonne "synchronise" ou 1 indique que la synchronisation a ete faite), ainsi que effacer les positions
#               de notre bus de la base de donnes centrale.
#
# Commentaire : Cette fonction est appellee par la tache de suppression automatique

sub Clear_Positions {
  # Effacement des positions deja synchronisees localement
  my $sth = $BDD_Locale->prepare("DELETE FROM tblPositions WHERE synchronise=1");
  $sth->execute();

  # Recuperation du numero de notre bus sur la base de donnees centrale
  my $bus_ID = Get_BusID();

  # Si la base de donnees distante est accessible
  if (!($BDD_Centrale eq "false"))
  {
    # Suppression de toutes les positions liees a notre bus
    my $sth2 = $BDD_Centrale->prepare("DELETE FROM tblPositions WHERE num_tblBus=$bus_ID");
    $sth2->execute();
  }
}

# Fonction :    Bus_Available
# Parametres :  $num_bus       => <Numero de ligne du bus>-<Numero d'identification sur la ligne>
# Retour :      Affichage de : true   => Si le numero est disponible
#                              false  => Si le numero n'est pas disponible
#
# Variables :   $disponible    => Contiendra "true" ou "false" selon la disponibilite du numero et sera affichéé
#               $sth           => Objet de traitement SQL qui retournera tous les numeros de bus de la base de donnes centrale
#               @row           => Tableau contenant le resultat de la requete
#
# But :         Verifier que le numero de bus unique ne soit pas deja enregistre dans la base de donnees
#
# Commentaire : L'affichage du contenu de la variable $disponible permet au script PHP de savoir si le numero saisi est disponible
#               La base de donnees centrale doit etre accessible

sub Bus_Available {
  my ($num_bus) = @_;
  my $disponible = "true";

  # Requete de recuperation de tous les numeros de bus contenus dans la base de donnees distante
  my $sth = $BDD_Centrale->prepare("SELECT numeroBus FROM tblBus");

  $sth->execute();

  # Parcours de chaque valeurs retournees par la requete
  while ( @row = $sth->fetchrow_array ) 
  {
    # Si la ligne ne contient pas la valeur de $num_bus
    if(grep( /^$num_bus$/, @row))
    {
      $disponible = "false";
    }
  }
  print "$disponible\n";
}

# Fonction :    Bus_Register
# Parametres :  Aucun
# Retour :      Aucun
#
# Variables :   $numero, $idBus, $numeroBus, $adresseIP, $dateDerniereSynchronisation, $dateDebutAcquisition
#               => Donnees du bus extraites de la base de donnees locale
#
#               $sth           => Objet de traitement SQL qui => Retournera les donnees de notre bus contenues dans la base de donnes locale
#                                                             => Inserera les donnees extraites localement dans la base de donnes centrale
#
#               @row           => Tableau contenant le resultat de la premiere requete
#
# But :         Enregistrer les informations de notre bus enregistre localement dans la base de donnes centrale
#
# Commentaire : Cette fonction est appellee seulement lors de l'enregistrement du bus par le technicien et requiert l'accessibilite
#               de la base de donnes centrale lors de cet enregistrement

sub Bus_Register {
  my ($numero, $idBus, $numeroBus, $adresseIP, $dateDerniereSynchronisation, $dateDebutAcquisition);

  # Execution de la requete qui recupere les donnees du bus enregistre localement
  # Note : Limit 1 => Permet d'eviter une erreur au cas ou plusieurs bus se trouveraient dans la base de donnes ce qui ne devrait
  #                   pas etre possible.
  my $sth = $BDD_Locale->prepare("SELECT * FROM tblBus LIMIT 1");
  $sth->execute();

  while ( @row = $sth->fetchrow_array ) {
    ($numero, $idBus, $numeroBus, $adresseIP, $dateDerniereSynchronisation, $dateDebutAcquisition) = @row;
  }

  # On va chercher le premier numero libre de la base de donnees distante
  $idBus = Get_LastBusID();
  $idBus = $idBus + 1;
  # Si la base de donnees distante est accessible (ce qui est obligatoire ici)
  if (!($BDD_Centrale eq "false"))
  {
    # Requete d'enregistrement du bus dans la base de donnees distante
    $sth = $BDD_Centrale->prepare("INSERT INTO tblBus (numero,numeroBus,adresseIP,dateDerniereSynchronisation,dateDebutAcquisition) VALUES  ('$idBus', '$numeroBus', '$adresseIP', '$dateDerniereSynchronisation', '$dateDebutAcquisition')");
    $sth->execute();
    
    # Maintenant que nous avons un numero unique dans la base de donnees centrale, on stocke celui si localement
    $sth = $BDD_Locale->prepare("UPDATE tblBus SET idBus=$idBus WHERE numero=$numero");
    $sth->execute();
  }
}

# Fonction :    Bus_Update
# Parametres :  Aucun
# Retour :      Aucun
#
# Variables :   $numero, $idBus, $numeroBus, $adresseIP, $dateDerniereSynchronisation, $dateDebutAcquisition
#                             => Donnees du bus extraites de la base de donnees locale
#
#               $sth => Objet de traitement SQL qui => Retournera les donnees de notre bus contenues dans la base de donnes locale
#                                                   => Modifiera (mettra a jour) ces informations dans la base de donnes centrale
#               @row           => Tableau contenant le resultat de la requete
#
# But :         Mettre a jour les donnes d'un bus modifie localement, sur la base de donnes centrale
#
# Commentaire : Lors de l'execution de la tache de collecte, la fonction Synchronisation() verifie egalement que les donnees de notre bus
#               soient identiques localement et sur la base de donnes centrale et si ce n'est pas le cas elle appellera cette fonction

sub Bus_Update {
  # Si la base de donnees distante est accessible
  if (!($BDD_Centrale eq "false"))
  {
    my ($numero, $idBus, $numeroBus, $adresseIP, $dateDerniereSynchronisation, $dateDebutAcquisition);

    # Execution de la requete qui recupere les donnees du bus enregistre localement
    my $sth = $BDD_Locale->prepare("SELECT * FROM tblBus LIMIT 1");
    $sth->execute();

    while ( @row = $sth->fetchrow_array ) {
      ($numero, $idBus, $numeroBus, $adresseIP, $dateDerniereSynchronisation, $dateDebutAcquisition) = @row;
    }

    # Bypass les restrictions liees a la clee etrangere
    $sth = $BDD_Centrale->prepare("SET FOREIGN_KEY_CHECKS=0");
    $sth->execute();
    $sth = $BDD_Centrale->prepare("UPDATE tblBus SET numeroBus='$numeroBus', dateDerniereSynchronisation='$dateDerniereSynchronisation', adresseIP='$adresseIP', dateDebutAcquisition='$dateDebutAcquisition' WHERE numero=$idBus");
    $sth->execute();
  }
}

sub Bus_Remove {
  my $idBus = Get_BusID();

  # Si la base de donnes distante est accessible et qu on a recupere le numero unique de notre bus local
  if ((!($BDD_Centrale eq "false"))&&($idBus))
  {
    # Suppression dans la base de donnees locale
    my $sth = $BDD_Locale->prepare("DELETE FROM tblBus");
    $sth->execute();
    $sth = $BDD_Locale->prepare("DELETE FROM tblPositions");
    $sth->execute();

    # Suppression dans la base de donnes distante
    $sth = $BDD_Centrale->prepare("DELETE FROM tblBus WHERE numero=$idBus");
    $sth->execute();
    $sth = $BDD_Centrale->prepare("DELETE FROM tblPositions WHERE num_tblBus=$idBus");
    $sth->execute();
    print "true\n";
  }
  else
  {
    print "false\n";
  }
}

# Fonction :    Get_BusID
# Parametres :  Aucun
# Retour :      $bus_ID     => Correspond au numero unique de notre bus sur la base de donnes centrale, enregistre localement
#
# Variables :   $sth        => Objet de traitement SQL qui retournera le numero unique de notre bus
#
# But :         Permettre d'obtenir le numero unique de notre bus sans aller le chercher sur la base de donnes centrale, 
#               qui n'est pas toujours accessible
#
# Commentaire : Aucun

sub Get_BusID {
    my $bus_ID;

    # Recuperation du numero unique de notre bus dans la base de donnes locale
    my $sth = $BDD_Locale->prepare("SELECT idBus FROM tblBus LIMIT 1");
    $sth->execute();

    $bus_ID = $sth->fetchrow_array;
    return $bus_ID;
}

# Fonction :    Get_LastBusID
# Parametres :  Aucun
# Retour :      $last_ID     => Correspond au dernier numero de bus unique de la base de donnes centrale
#                            => Si la base de donnees centrale n'est pas accessible, affiche "false"
#
# Variables :   $sth         => Objet de traitement SQL qui retournera le dernier numero de bus unique
#
# But :         Permettre d'obtenir le numero unique de notre bus lors de l'enregistrement de celui-ci
#
# Commentaire : Cette valeur ne changera pas, notre bus aura toujours ce numero dans la base de donnes centrale

sub Get_LastBusID {
  my $last_ID;

  # Si la base de donnees distante est accessible
  if (!($BDD_Centrale eq "false"))
  {
    # Recuperation du dernier numero unique de la base de donnees distante
    my $sth = $BDD_Centrale->prepare("SELECT numero FROM tblBus ORDER BY numero DESC LIMIT 1");
    $sth->execute();

    $last_ID = $sth->fetchrow_array;

    # Affichage du numero
    print "$last_ID\n";
  }
  else
  {
    print "false";
  }
  return $last_ID;
}

# Fonction :    Synchronisation
# Parametres :  Aucun
# Retour :      Aucun
#
# Variables :   $today              => Date précise actuelle
#               $sth                => Objet de traitement SQL qui => Retournera les donnees de notre bus contenues dans la base de donnes locale
#                                                                  => Retournera les donnees de notre bus contenues dans la base de donnes centrale
#                                                                  => Retournera l'ensemble des positions pas encore synchronisees stockees localement
#
#               $sth2               => Objet de traitement SQL qui  => Inserera les positions non synchronisees stockees localement, dans la base de donnes centrale
#                                                                   => Changera l'etat des positions synchronisees de 0 (non synchronisee) a 1 (synchronisee)
#
#               $start_collect_date => Date de debut de la collecte des positions, configuree lors de l'enregistrement du bus
#               $bus_ID             => Numero unique de notre bus dans la base de donnes centrale
#               @local_bus_infos    => Tableau contenant les donnees locale de notre bus
#               @remote_bus_infos   => Tableau contenant les donnees de notre bus sur la base de donnees centrale
#               
# But :         Synchroniser des que possible les positions sur la base de donnes centrale et changer l'etat de celles-ci afin de permettre a la tache de suppression
#               automatique d'identifier les donnees deja synchronisees a supprimer
#               Permet egalement de comparer les donnes du bus locale et dans la bases de donnes centrale et lancer une mise a jour si les donnes different
#
# Commentaire : Cette fonction est appellee par la fonction Save_Position qui elle est appellee par la tache automatique de collecte.
#               N'effectue la collecte seulement si la date de debut d'acquisition est deja passe

sub Synchronisation {
  # Date actuelle precise et formatage de celle-ci afin d'etre
  # dans le meme format que la date de debut d'acquisition des donnees
  my $today = strftime "%m-%e-%Y %H:%M:%S", localtime;

  # Recuperation des donnees de notre bus localement et stockage dans @local_bus_infos
  my $sth = $BDD_Locale->prepare("SELECT * FROM tblBus LIMIT 1");
  $sth->execute();
  my @local_bus_infos = $sth->fetchrow_array;

  # Recuperation des informations utiles avec le tableau de donnees de notre bus
  my $start_collect_date = @local_bus_infos[5];
  my $bus_ID = @local_bus_infos[1];

  my @remote_bus_infos;

  # Si la date de debut d'acquisition est passee
  if ($start_collect_date <= $today)
  {
    # Si la base de donnees distante est accessible
    if (!($BDD_Centrale eq "false"))
    {
      # Recuperation des donnees de notre bus sur la base de donnes distante et stockage dans @remote_bus_infos
      $sth = $BDD_Centrale->prepare("SELECT * FROM tblBus WHERE numero=" . @local_bus_infos[1]);
      $sth->execute();

      @remote_bus_infos = $sth->fetchrow_array;

      # Si les informations stockees localement et dans la base de donnes centrale sont differentes
      if (!(@local_bus_infos ~~ @remote_bus_infos))
      {
        # Mise a jour des donnees sur la base de donnes centrale
        Bus_Update();
      }

      # recuperation des positions non synchronisees
      $sth = $BDD_Locale->prepare("SELECT * FROM tblPositions WHERE synchronise=0");
      $sth->execute();

      # Insertion de chaque position non synchronisee, dans la base de donnes centrale et changement de l'etat de la positions
      while ( @row = $sth->fetchrow_array )
      {       
        my ($numero,$dateHeure,$latitude,$longitude,$synchronise) = @row;
        my $sth2 = $BDD_Centrale->prepare("INSERT INTO tblPositions (dateHeure,num_tblBus,latitude,longitude) VALUES  ('$dateHeure', '$bus_ID', '$latitude', '$longitude')");
        $sth2->execute();
        $sth2 = $BDD_Locale->prepare("UPDATE tblPositions SET synchronise=1 WHERE numero=$numero");
        $sth2->execute();
      }
    }
  }
}

# Fonction :    Save_Position
# Parametres :  $position_string    => Contient une chaine au format : <latitude>:<longitude>:<date du satellite>: envoyee par le programme "gps"
#                                      la longitude et la latitude doivent etre converties en decimal pour etre comprehensibles par la Google Map
#                                      du site web central
#
# Retour :      Aucun
#
# Variables :   $actualDate         => Date actuelle precise
#               $sth                => Objet de traitement SQL qui inserera la position dans la base de donnees locale
#               $latitude           => Latitude non convertie extraite de $position_string
#               $longitude          => Longitude non convertie extraite de $position_string
#               $date               => Date non convertie extraite de $position_string mais qui n'est pas utilisee
#
# But :         Recuperer, extraire, convertir et stocker les positions envoyees par le programme "gps" communiquant avec le composant SIM908 et execute
#               par la tache automatique de collecte des donnes. Permet aussi de lancer regulierement puisqu'elle est elle-meme executee regulierement,
#               les fonctions de synchronisations de donnes 
#
# Commentaire : La date des satellites n'etant pas la meme que la notre (fuseau horaire), j'utilise celle du systeme pour le stockage des positions.
#               La date du systeme est mise a jour grace au programme par defaut NTP qui a plusieurs serveurs avec lesquels il communique quand cela
#               est possible. J'ai egalement laisser la date des satellite et stocke celle-ci dans la variable $date pour une modification rapide du script
#               si necessaire.

sub Save_Position {
  my $position_string = @_[0];
  # date actuelle
  my $actualDate = strftime("%Y:%m:%d %H:%M:%S", localtime);

  # Recuperation de la latitude, longitude et date a partir de la chaine recue grace a un regex
  my ($latitude, $longitude, $date) = $position_string =~ /(.*):(.*):(.*)/sgi;
  
  # On formate la date extraite
  $date = format_satellite_date($date);

  # Si la latitude et la longitude ne contiennent pas "0.00", ce qui correspond a une position inconnue pour le gps
  if((!($latitude =~ /^0\.00/))&&(!($longitude =~ /^0\.00/)))
  {
    # Convertion des de la latitude en degres decimal
    $latitude = Convert_DecimalToDegrees($latitude);
    # Convertion des de la longitude en degres decimal
    $longitude = Convert_DecimalToDegrees($longitude);

    # Stockage de la nouvelle position dans la base de donnees locale
    my $sth = $BDD_Locale->prepare("INSERT INTO tblPositions(dateHeure,latitude,longitude,synchronise) VALUES ('$date', '$latitude', '$longitude', 0);");
    $sth->execute();
  }

  # Lancement de la synchronisation des donnees a chaque execution de la fonction
  Synchronisation();
}

sub format_satellite_date {
  my $date = @_[0];
  my $date_formated = substr($date,0,4) . ":" . substr($date,4,2) . ":" . substr($date,6,2) . " " . substr($date,8,2) . ":" . substr($date,10,2) . ":" . substr($date,12,2);
  
  return $date_formated;
}

# Fonction :    Convert_DecimalToDegrees
# Parametres :  $cord            => Coordonnee decimale retourner par le programme "gps" qui doit etre convertie en degres decimale
# Retour :      $converted       => Coordonnee convertie en degres decimale
#
# Variables :   $degrees         => Variable utilisee pour les calculs des degres
#
# But :         Convertir et retourner une coordonnee dans un format utilisable par la Google Map du serveur web central
#
# Commentaire : Code provenant de : https://jaystile.wordpress.com/2009/02/21/perl-converting-decimal-to-degrees-minutes-seconds-and-vice-versa/

sub Convert_DecimalToDegrees {
    my ($cord) = @_;

    # Quitte la fonction si il n'y a pas de valeurs
    if (!$cord) {
        return undef;
    }

    my $degrees = int( $cord/100 );

    # Conversion en degrés décimal
    my $converted = sprintf '%0.4f',
      $degrees + ( $cord - ( $degrees * 100 ) ) / 60;

    return $converted;
}

# Fonction :    Create_Task
# Parametres :  $execution_times      => Intervalle crontab envoye par la page de planification de tache, au format "X:X:X:X:X"
#               $task                 => Tache a planifiee, depend du parametre utilise lors de l'appel du script
#
# Retour :      Aucun
#
# Variables :   $command              => Contient la commande de la tache automatique
#               $ct                   => Objet Crontab
#               $block                => Objet "Block" de crontab qui contiendera l'eventuelle ancienne tache a supprimer
#               $new_block            => Objet "Block" de crontab qui contiendera la nouvelle tache automatique
#
# But :         Planifier selon la saisie du technicien la tache de collecte automatique ou de suppression automatique des données de positions
#               selon l'intervalle saisit sur la page web de configuration locale (http://localhost/configuration.php)
#
# Commentaire : Pour plus d'informations sur Crontab et les saisies possibles par le technicien, consulter le mode d'emploi du rapport

sub Create_Task {
  my ($execution_times, $task) = @_;

  # Separation des elements recus en parametres par leur delimiteur ":"
  my ($minute,$hour,$dom,$month,$dow) = split(/:/, $execution_times);
  my $command;

  # Initialisation de l'objet Crontab et lecture du crontab
  my $ct = new Config::Crontab;

  # Lecture de la Crontab
  $ct->read;

  # Initialisation de l'objet block a partir de l'objet crontab
  my $new_block = new Config::Crontab::Block;

  # Attribution de la commande en fonction de la tache a planifier
  if($task eq "collect")
  {
    # Commande qui tue les autres processus de collecte et lance un nouveau processus de collecte
    # Le fait de tuer les processus evite de traiter plusieurs fois les memes donnees sans en perdre pour autant
    # car le programme gps tourne tant qu'il ne se fait pas tuer
    $command = 'sudo pkill gps* ; sudo /var/www/scripts/gps';
  }
  else
  {
    # Commande qui lance la suppression des donnes gps dans la base de donnes locale et centrale
    $command = 'perl /var/www/html/scripts/BDD_functions.pl --clear-positions';
  }

  # Suppression de l'ancienne tache si existante and cherchant la tache avec la command etant identique
  # a la valeur de notre variable $command (On supprime donc la tache du meme type que celle que l'on ajoute)
   for my $block ( $ct->blocks ) {
        $block->remove($block->select( -type        => 'event',
                                       -command    => $command ));
  }

  # Creation de la tache
  # note : dow => Day of week soit jours de la semaine
  #        dom => Day of month soit jours du mois
  $new_block->last(new Config::Crontab::Event(
                                            -minute  => $minute,
                                            -hour    => $hour,
                                            -month   => $month,
                                            -dow     => $dow,
                                            -dom     => $dom,
                                            -command => $command
                                          ));

# Ajout de la nouvelle tache dans la crontab
$ct->last($new_block);

# Ecriture dans la crontab
$ct->write;
}
