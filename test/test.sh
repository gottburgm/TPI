#/bin/bash

using_password="OUI"
echo ""
echo "Veuillez saisir l'adresse IP du serveur central :"
read remote_host
if [[ -z "$remote_host" ]]; then 
	echo "Saisie vide, utilisation de l'adresse locale '127.0.0.1'"
	remote_host="127.0.0.1"
fi
echo ""
echo "Veuillez saisir le nom d'utilisateur sur la base de donnees distante (par defaut 'pi') :"
read username
if [[ -z "$username" ]]; then 
	echo "Saisie vide, utilisation du nom d'utilisateur 'pi'"
	username="pi"
fi
echo ""
echo "Veuillez saisir le mot de passe de l'utilisateur $username :"
read -s password
if [[ -z "$password" ]]; then 
	using_password="Aucun"
fi
echo ""
echo ""
echo "*********************************************************"
echo "                   Configuration                         "
echo "                                                         "
echo " Adresse IP serveur central : $remote_host               "
echo " Username : $username                                    "
echo " Password : $using_password                              "
echo "                                                         "
echo "*********************************************************"

echo "Ecriture de la configuration dans le script Perl"
mv scripts/functions.pl scripts/temp.pl
$(sed 's/Connexion('mysql', 'localhost', 'db_buscentrale', 'root', '')/Connexion('mysql', '$remote_host', 'db_buscentrale', '$username', '$password')/' scripts/temp.pl) > scripts/functions.pl
echo "Fait."