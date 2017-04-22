# php-blocklyArduino
Php plugin for blockly@rduino to allow users project saving in database.

Must be copied in blockly@rduino directory. 
Program must be launch by index.php instead of index.html...

#installation
On suppose que blockly@rduino est install� sur un serveur web, ex�cutant PHP, et disposant d'un serveur de base de donn�es MySQL.

Il faut cr�er une base donn�es  nomm�e 'blocklyArduino' avec un utilisateur 'blocklyArduino' ayant tous les privili�ges et droits.
Le mot de passe de cet utilisateur devra �tre renseign� dans le fichier de config   php/config/db.inc.php .

Il faut copier ces fichiers dans le dossier o� est install� blockly@rduino (qui est probablement /var/www/html/blockly@rduino)
Il faut donner les droits d'�criture (750 par chmod) sur le dossier   php/ ainsi que sur les fichiers index.php et blocklyArduino.php aini que donner comme propri�taire root:www-data (par chown).
Il faut donner les droits d'�criture (770) sur le dossier   php/files .

A la racine de blocklyArduino, il faut cr�er un lien symbolique  'datas'  qui pointe vers   php/files .

Remarques :
Les projets blocklyArduino sont :
 - enregistr�s sous forme de fichier xml dans le dossier datas (qui pointe vers php/files) sous forme   login-nom_du_projet-10000000.xml    (login remplac� par le login de l'utilisateur, nom_du_projet par le nom du projet :) et 10000000 par le timestamp de la date et l'heure d'enregistrement)
 - stock�s dans la base de donn�es sous la forme d'une association entre l'utilisateur (user) le nom du projet (nom) et le timestamp de la date et heure d'enregistrement. Le nom du fichier physique �tant recr�� d'apr�s ces infos pour �tre ouvert. 

#Modifications apport�es au logiciel original
 - au chargement, si aucune carte ni langue n'est choisi, on bascule automatiquement sur lang=fr et carte=arduino_uno
 - de m�me, une toolbox basique est s�lectionn�e, avec la cat�gorie Arduino pr�selectionn�e
 - si on est connect�
   - les bouton Charger, Sauver, D�connnecter et Param�tres apparaissent
   - le nom de projet est affich�
   - le chargement d'un exemple est consid�r� comme un nom de projet et peut �tre sauvegard� par l'utilisateur
   - chaque utilisateur a acc�s � ses propres projets
   - chaque utilisateur peut sauvegarder ses param�tres de base (carte utilis�e, langue, toolbox)
   - l'admin peut g�rer les param�tres par d�faut de tout nouvel utilisateur qui n'aurait pas sauvegard� ses param�tres de base
 - les boutons XML sont renomm�s en export/import XML qui produisent des exports dont le nom de fichier int�gre le nom de projet
 
 #utilisation avec Scribe
 - en cas d'installation sur un serveur Scribe/envole, on profite de l'authentification des utilisateurs par le CAS de Scribe. Pour l'activer, il faut, dans le fichier php/config/config.inc.php, mettre la variable $USE_SCRIBE=1;
 
 #utilisation sans Scribe
 - il faut, dans le fichier php/config/config.inc.php, mettre la variable $USE_SCRIBE=0;   (valeur par d�faut)
 - dans ce cas, c'est l'application qui permet la gestion des utilisateurs. Et notamment grace au compte admin (mot de passe "mlkmlk" par d�faut !)
 - chaque utilisateur peut changer son mot de passe par le bouton param�tres
 - l'admin peut acc�der � la gestion des utilisateurs, pour en cr�er ou modifier leurs infos
 
 #fonctionnalit�s en attente...
 - les utilisateurs ont une adresse mail et un profil (prof, �l�ve, admin). Mais hormis le profil admin qui permet de g�rer les utilisateurs, ces fonctionnalit�s n'ont pas d'incidence sur le fonctionnement.