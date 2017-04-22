<?php
/*-------------------------------------
 fichier de config de l'interface PHP de BlocklyArduino
--------------------------------------*/
//chemin d'enregistrement des fichiers xml contenant les projets
$cheminFichiersXML="./datas";

//indiquer si vous utiliser l'authentification par le CAS d'un serveur Scribe, ou pas...
$USE_SCRIBE=0; //0 si pas sur un scribe

//indiquer si on utilise une BDD ou pas. Si oui, on peut se connecter, sinon, non...
$USE_BDD=1; //false si pas de BDD - Si USE_SCRIBE=1, USE_BDD est ignoré car on utilise forcément une BDD...
?>