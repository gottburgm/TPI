#/bin/bash
echo "**********************************************************"
echo Installeur automatique - Raspberry PI
echo Michael Gottburg - 4MPI4I1
echo Projet TPI - CPLN 2016
echo "**********************************************************"
echo Mise a jour ... 
sudo apt-get update
sudo apt-get dist-upgrade
echo Fait.
echo Installation : Nano 
sudo apt-get install nano -y
echo Fait.
echo Installation : Apache
sudo apt-get install apache2 -y
sudo apt-get install libapache2-mod-php5 -y
echo Fait.
echo Installation : PHP5
sudo apt-get install php5 -y
sudo apt-get install php5-mysql -y
echo Fait.
echo Installation : MySQL Server
echo ATTENTION : Veuillez ne pas saisir de mot de passe
sudo apt-get install mysql-server -y
echo Fait.
echo Demarrage des services
sudo service apache2 start
sudo service mysql start
echo Fait.
echo Ajout des autorisations necessaires
echo www-data ALL=(ALL) NOPASSWD: ALL | sudo tee --append /etc/sudoers
echo Fait.
echo Configuration de la timezone
sudo cp /usr/share/zoneinfo/Europe/Zurich /etc/localtime
echo Fait.
echo Installation des fichiers du site web local et des scripts
unzip files.zip
sudo cp -R scripts/ /var/www/
sudo chmod 777 -R /var/www/scripts/
sudo cp -R public_html/* /var/www/html/
echo Fait.
echo Importation de la base de donnes locale
mysql -u root < sql/db_BusLocale.sql
echo Fait.
echo Installation de la librairie ArduPi
mkdir /home/pi/ArduPi
cd /home/pi/ArduPi
sudo cp sources/gps.cpp /home/pi/ArduPi/cooking/arduPi
echo Fait.
wget http://www.cooking-hacks.com/media/cooking/images/documentation/raspberry_arduino_shield/raspberrypi.zip && unzip raspberrypi.zip && cd cooking/arduPi && chmod +x install_arduPi && ./install_arduPi && rm install_arduPi && cd ../..
echo Installation des modules Perl requis
echo INFORMATION : Peut prendre plusieurs minutes
sudo cpan -i Config::Crontab
sudo cpan -i DBI
sudo cpan -i POSIX
echo Fait.
echo "**********************************************************"
echo Installation : Terminee.
echo Acces au site web : http://localhost/index.php
echo "**********************************************************"