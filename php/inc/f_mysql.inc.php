<?php
if (file_exists('./config/db.inc.php')) require ('./config/db.inc.php');
else if (file_exists('./php/config/db.inc.php')) require ('./php/config/db.inc.php');

$mysqli= new mysqli($dbhost,$user,$pass, $database);
$mysqli->query("SET NAMES UTF8");

global $gpc_on;
if (get_magic_quotes_gpc()) $gpc_on=true; else $_gpc_on=false;

/*--------------------
 fonction getVersionTableDB 
 --------------------------
 retourne la version de table . Permet de ne faire les mises à jours de table
  que si c'est nécessaire.
----------------------*/
function getVersionTableDB($table) {
	$val=litParam("versionTable_".$table);
	if ($val===false) return 65000;
	else return $val;
}

/*--------------------
 fonction ecritVersionTableDB 
 --------------------------
 Ecrit la version de la table dans les params.
----------------------*/
function ecritVersionTableDB($table, $version) {
	ecritParam("versionTable_".$table, $version);
	}

/*--------------------
 fonction existTable
----------------------*/
function existTable($table) {
	global $mysqli;
	
	$res=$mysqli->query("SHOW TABLES");
	if ($res) 
		while (list($tablePresente)=$res->fetch_row()) {
			if (strtolower($table)==strtolower($tablePresente)) return true;
		}
	return false;
}

/*--------------------
 fonction secMy
 --------------------
 Sécurise l'élément de syntaxe MySQL pour éviter les attaques de type SQL-injection 
----------------------*/
function secMy($elem) {
	global $gpc_on, $mysqli;
	
	if ($gpc_on) $elem=stripslashes($elem);
	return $mysqli->real_escape_string($elem);
}

	 

?>