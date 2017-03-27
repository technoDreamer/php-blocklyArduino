# php-blocklyArduino
Php plugin for blockly@rduino to allow users project saving in database.

Must be copied in blockly@rduino directory. 
Program must be launch by index.php instead of index.html...

#installation
On suppose que blockly@rduino est installé sur un serveur web, exécutant PHP, et disposant d'un serveur de base de données MySQL.

Il faut créer une base données  nommée 'blocklyArduino' avec un utilisateur 'blocklyArduino' ayant tous les privilièges et droits.
Le mot de passe de cet utilisateur devra être renseigné dans le fichier de config   php/config/db.inc.php .
Dans phpMyAdmin (par exemple), il faut, dans la base 'blocklyArduino',  importer le fichier     install/php-blocklyArduino.sql     pour créer les tables nécessaires.

Il faut donner les droits d'écriture (770) sur le dossier   php/files .

A la racine de blocklyArduino, il faut créer un lien symbolique  'datas'  qui pointe vers   php/files .

Remarques :
Les projets blocklyArduino sont :
 - enregistrés sous forme de fichier xml dans le dossier datas (qui pointe vers php/files) sous forme   login-nom_du_projet-10000000.xml    (login remplacé par le login de l'utilisateur, nom_du_projet par le nom du projet :) et 10000000 par le timestamp de la date et l'heure d'enregistrement)
 - stockés dans la base de données sous la forme d'une association entre l'utilisateur (user) le nom du projet (nom) et le timestamp de la date et heure d'enregistrement. Le nom du fichier physique étant recréé d'après ces infos pour être ouvert.