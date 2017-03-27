# php-blocklyArduino
Php plugin for blockly@rduino to allow users project saving in database.

Must be copied in blockly@rduino directory. 
Program must be launch by index.php instead of index.html...

#installation
On suppose que blockly@rduino est install� sur un serveur web, ex�cutant PHP, et disposant d'un serveur de base de donn�es MySQL.

Il faut cr�er une base donn�es  nomm�e 'blocklyArduino' avec un utilisateur 'blocklyArduino' ayant tous les privili�ges et droits.
Le mot de passe de cet utilisateur devra �tre renseign� dans le fichier de config   php/config/db.inc.php .
Dans phpMyAdmin (par exemple), il faut, dans la base 'blocklyArduino',  importer le fichier     install/php-blocklyArduino.sql     pour cr�er les tables n�cessaires.

Il faut donner les droits d'�criture (770) sur le dossier   php/files .

A la racine de blocklyArduino, il faut cr�er un lien symbolique  'datas'  qui pointe vers   php/files .

Remarques :
Les projets blocklyArduino sont :
 - enregistr�s sous forme de fichier xml dans le dossier datas (qui pointe vers php/files) sous forme   login-nom_du_projet-10000000.xml    (login remplac� par le login de l'utilisateur, nom_du_projet par le nom du projet :) et 10000000 par le timestamp de la date et l'heure d'enregistrement)
 - stock�s dans la base de donn�es sous la forme d'une association entre l'utilisateur (user) le nom du projet (nom) et le timestamp de la date et heure d'enregistrement. Le nom du fichier physique �tant recr�� d'apr�s ces infos pour �tre ouvert.