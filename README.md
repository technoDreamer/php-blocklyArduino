# php-blocklyArduino
Php plugin for blockly@rduino to allow users project saving in database.

Must be copied in blockly@rduino directory. 
Program must be launch by index.php instead of index.html...

#installation
On suppose que blockly@rduino est installé sur un serveur web, exécutant PHP, et disposant d'un serveur de base de données MySQL.

Il faut créer une base données  nommée 'blocklyArduino' avec un utilisateur 'blocklyArduino' ayant tous les privilièges et droits.
Le mot de passe de cet utilisateur devra être renseigné dans le fichier de config   php/config/db.inc.php .

Il faut copier ces fichiers dans le dossier où est installé blockly@rduino (qui est probablement /var/www/html/blockly@rduino)
Il faut donner les droits d'écriture (750 par chmod) sur le dossier   php/ ainsi que sur les fichiers index.php et blocklyArduino.php aini que donner comme propriétaire root:www-data (par chown).
Il faut donner les droits d'écriture (770) sur le dossier   php/files .

A la racine de blocklyArduino, il faut créer un lien symbolique  'datas'  qui pointe vers   php/files .

Remarques :
Les projets blocklyArduino sont :
 - enregistrés sous forme de fichier xml dans le dossier datas (qui pointe vers php/files) sous forme   login-nom_du_projet-10000000.xml    (login remplacé par le login de l'utilisateur, nom_du_projet par le nom du projet :) et 10000000 par le timestamp de la date et l'heure d'enregistrement)
 - stockés dans la base de données sous la forme d'une association entre l'utilisateur (user) le nom du projet (nom) et le timestamp de la date et heure d'enregistrement. Le nom du fichier physique étant recréé d'après ces infos pour être ouvert. 

#Modifications apportées au logiciel original
 - au chargement, si aucune carte ni langue n'est choisi, on bascule automatiquement sur lang=fr et carte=arduino_uno
 - de même, une toolbox basique est sélectionnée, avec la catégorie Arduino préselectionnée
 - si on est connecté
   - les bouton Charger, Sauver, Déconnnecter et Paramètres apparaissent
   - le nom de projet est affiché
   - le chargement d'un exemple est considéré comme un nom de projet et peut être sauvegardé par l'utilisateur
   - chaque utilisateur a accès à ses propres projets
   - chaque utilisateur peut sauvegarder ses paramètres de base (carte utilisée, langue, toolbox)
   - l'admin peut gérer les paramètres par défaut de tout nouvel utilisateur qui n'aurait pas sauvegardé ses paramètres de base
 - les boutons XML sont renommés en export/import XML qui produisent des exports dont le nom de fichier intègre le nom de projet
 
 #utilisation avec Scribe
 - en cas d'installation sur un serveur Scribe/envole, on profite de l'authentification des utilisateurs par le CAS de Scribe. Pour l'activer, il faut, dans le fichier php/config/config.inc.php, mettre la variable $USE_SCRIBE=1;
 
 #utilisation sans Scribe
 - il faut, dans le fichier php/config/config.inc.php, mettre la variable $USE_SCRIBE=0;   (valeur par défaut)
 - dans ce cas, c'est l'application qui permet la gestion des utilisateurs. Et notamment grace au compte admin (mot de passe "mlkmlk" par défaut !)
 - chaque utilisateur peut changer son mot de passe par le bouton paramètres
 - l'admin peut accéder à la gestion des utilisateurs, pour en créer ou modifier leurs infos
 
 #fonctionnalités en attente...
 - les utilisateurs ont une adresse mail et un profil (prof, élève, admin). Mais hormis le profil admin qui permet de gérer les utilisateurs, ces fonctionnalités n'ont pas d'incidence sur le fonctionnement.