<?php
/*--------------------------------------
 Classe : Parametres
----------------------------------------
 Gère les paramètres dans une application
 On peut récupérer les paramètres un par un ou utiliser la variable globale $_params
---------------------------------------*/
define ('BDD',0);
define ('FICHIER',1);
define ('ERR_PARAM_0', "<i>Classe Parametres</i> : nombre d'arguments insuffisants");
define ('ERR_PARAM_1', "<i>Classe Parametres</i> : arguments incorrects");
define ('ERR_PARAM_2', "<i>Classe Parametres</i> : impossible de créer le fichier de paramètres");

define ('DEBUT_FIC_PARAM',
"<?php
//fichier de paramètres

global \$_params;
	
");

include_once("./inc/f_mysql.inc.php");

class Parametres {
	
	private $type; //BDD ou FICHIER
	
	//utilisation avec les fichiers
	private $nomFic; //nom du fichier si besoin
	private $fichierAMettreAJour; //indique si le fichier doit être mis à jour
	private $fichierDejaInclu;
	
	//utilisation avec la bdd
	private $nomBdd; //nom de la bdd
	private $tableBdd; //table  où sont stockées les valeurs

/*-------------------
	__construct() //Parametres() - Constructeur
	--------------------------------
	Créer l'objet permettant l'accès aux paramètres qui peuvent être stockés soit dans une bdd soit dans un fichier
	--------------------------------
	Les paramètres sont variables selon que c'est un fichier ou une bdd. 
	- Le premier paramètre précise le type (FICHIER ou BDD)
	* si c'est un fichier
			- nomFic : le nom (avec le chemin) du fichier de paramètres
	* si c'est une bdd
			- nomBdd et tableBdd		
	--------------------*/
	/*  */
	
	function __construct() { //Parametres() { 
		global $_params;
		$tArgs=func_get_args();
		$nbArgs=func_num_args();
		if ($nbArgs==0) die(ERR_PARAM_0); //pas assez d'arguments
		
		$_type=$this->type=$tArgs[0];
		//------- paramètres dans un fichier -------
		if ($_type==FICHIER) {
			if ($nbArgs!=2) die(ERR_PARAM_1); //nb d'arguments incorrects
			
			//initialisation des flag
			$this->fichierAMettreAJour=false;
			$this->fichierDejaInclu=false;
			
			$this->nomFic=$tArgs[1];
			//tentative d'accès au fichier
			if (!file_exists($this->nomFic)) { //si le fichier n'existe pas
				echo "Le fichier de paramètres n'existe pas".BR;
				$contenu=DEBUT_FIC_PARAM.'?>';

				if (($fp=fopen($this->nomFic, "w+"))===false) { //si impossible de créer le fichier de param
					die(ERR_PARAM_2);
				} else {
					chmod($this->nomFic, 0774);
					fwrite($fp, $contenu);
					fclose($fp);
				}
			} else { //le fichier existe déjà
				
			}
			
		} else { //bdd
			if ($nbArgs>2) die(ERR_PARAM_1); //nb d'arguments incorrects
			if ($nbArgs==1) $this->tableBdd="params";
			else $this->tableBdd=$tArgs[1];
			$this->verifTableParams();
			
		}
	}
	
	public function __destruct () {
		if ($this->type==FICHIER) {
			//si il y a eu des maj des params et qu'on ne les a pas enregistrées
			if ($this->fichierAMettreAJour) {
				$this->ecritTousLesParams();
			}
		}		
	}
	
	function litParam($_nomParam) {
		global $mysqli;
		global $_params;

		if (isset($_params[$_nomParam])) return $_params[$_nomParam];
		//echo 'demande param['.$_nomParam.']'.BRCR;
		if ($this->type==FICHIER) {
			if (!$this->fichierDejaInclu) $this->litTousLesParams();
			if (isset($_params[$_nomParam])) return $_params[$_nomParam];
		} else { //bdd
			if (isset($_params[$_nomParam])) return $_params[$_nomParam];
			$rqt="SELECT nomParam,valeur FROM ".$this->tableBdd." WHERE (nomParam='$_nomParam' OR nomParam LIKE '".$_nomParam."[%]')";
			$res=$mysqli->query($rqt);
			if ($res) {
				$valeurNonDefinie=true;
				while (list($nomParam, $valeur)=$res->fetch_row()) {
					$valeurNonDefinie=false;
					if (strpos($nomParam, '[')===false) { //n'est pas un tableau
						$_params[$_nomParam]=$valeur;
						return $valeur;
					}
					else { //c'est un tableau
						list($nomV,$keyT)=explode('[', substr($nomParam,0,-1));
						${$nomV}[$keyT]=$valeur;
					}
				}
				if ($valeurNonDefinie) return false;
				$_params[$_nomParam]=${$nomV};
				return ${$nomV};
			}
			return false;
				
		}
	}
	
	function litTousLesParams() {
		global $mysqli;
		global $_params;
		if ($this->type==FICHIER) {
			include ($this->nomFic); //on inclue le fichier
			//echo "inclue fichier".BRCR;
			$this->fichierDejaInclu=true;
		} else { //bdd
			$rqt="SELECT nomParam, valeur FROM ".$this->tableBdd." WHERE 1 ORDER BY nomParam";
			$res=$mysqli->query($rqt);
			if ($res) {
				while($ligne=$res->fetch_array()) {
					$_params[$ligne['nomParam']]=$ligne['valeur'];
				}
			}
			$varTab=array();
			if (is_array($_params)) {
				foreach ($_params as $nomParam => $valeur) {
					if (strpos($nomParam, '[')===false) { //n'est pas un tableau
						$_params[$nomParam]=$valeur;
					}
					else { //c'est un tableau
						list($nomV,$keyT)=explode('[', substr($nomParam,0,-1)); //on extrait le nom du tableau de l'index
						${'___'.$nomV}[$keyT]=$valeur; //on mémorise les différentes valeurs du tableau
						if (!in_array($nomV,$varTab)) $varTab[]=$nomV; //on se rappelle des tableaux déclarés
					}
				}
				if (is_array($varTab)) foreach ($varTab as $tVar) { //chaque variable tableau
					$_params[$tVar]=${'___'.$tVar}; //on y remet la valeur
				}
				return true;
			}
			else return false;
		}
	}

	function ecritParam($_nomParam, $_val) {
		global $mysqli;
		global $_params;
		//echo "ecritParam($_nomParam, $_val)";
		$_params[$_nomParam]=$_val;
		if ($this->type==FICHIER) {
			$this->fichierAMettreAJour=true;
		} else { //bdd
			if (is_array($_val)) foreach($_val as $_key=>$_valeur) {
				$this->ecritParam($_nomParam."[$_key]", $_valeur);	
			} else {
				$rqt="DELETE FROM ".$this->tableBdd."  WHERE nomParam='$_nomParam'";
				$res=$mysqli->query($rqt);
				//écriture du nouveau
				$rqt="INSERT INTO ".$this->tableBdd."  ( `nomParam` , `valeur` ) VALUES ('".secMy($_nomParam)."', '".secMy($_val)."')";
				$res=$mysqli->query($rqt);
				if ($res) return true; else return false;
			}
		}
	}
	
	function ecritTousLesParams() {
		global $_params;
		//		echo "<script>alert('debut ecritTousLesParams');</script>";
		if ($this->type==FICHIER) {
			$contenu=DEBUT_FIC_PARAM;
			if (is_array($_params)) foreach ($_params as $_nom => $_val) {
				$contenu.="\$_params['".$_nom."'] = \"".$_val."\";".CR;
				//echo "$_nom:$_val".BRCR;
			}
			$contenu.="?>";
			//echo(htmlentities($contenu));
			echo $this->nomFic.BRCR;
			if (($fp=fopen($this->nomFic, "w+"))===false) { //si impossible de créer le fichier de param
				//echo "<script>alert('impossible créer fichier param');</script>";
				die(ERR_PARAM_2);
			} else {
				fwrite($fp, $contenu);
				fclose($fp);
				//echo "<script>alert('fin ecritTousLesParams');</script>";
			}
			$this->fichierAMettreAJour=false;
		} else { //bdd
		
		}
	}
	
	/*--------------------
	 fonction supprimeParam 
	 --------------------------
	 supprime un paramètre dans la base
	----------------------*/
	function supprimeParam($_nomParam) {
			global $mysqli;
			global $_params;
			//		echo "<script>alert('debut ecritTousLesParams');</script>";
			if (isset($_params[$_nomParam])) unset($_params[$_nomParam]);
			if ($this->type==FICHIER) {
				$this->fichierAMettreAJour=true;
			} else { //bdd
				//supression du paramètre
				$rqt="DELETE FROM ".$this->tableBdd."  WHERE (nomParam='$_nomParam' OR nomParam LIKE '".$_nomParam."[%]')";
				$res=$mysqli->query($rqt);
				if ($res) return true; else return false;
			}
	}


	
	/*--------------------
	 fonction verifTableParam 
	 --------------------------
	 vérifie et créé si nécessaire la table param pour stocker les paramètres des services
	----------------------*/
	function verifTableParams() {
		global $mysqli;
		global $msgCreaTables;
		
		if (!existTable( "params")) {
			// création des tables
			$sql_query="CREATE TABLE `".$this->tableBdd."` (
				`id` INT NOT NULL AUTO_INCREMENT,
			  `nomParam` varchar(50) NOT NULL default '',
			  `valeur` varchar(2000) NOT NULL default '',
			  PRIMARY KEY  (id)
				) ENGINE=MyISAM";
			if ($mysqli->query($sql_query)) $msgCreaTables.="Table <b>'param'</b> créée.\n<br>";
		}
	}

} //fin de la classe 

global $_params;

//si on a pas défini le paramètre PARAMS_PERSONNALISES on prend la config par défaut : table params en base de données
global $oParametres;
if (!defined(PARAMS_PERSONNALISES)) $oParametres=new Parametres(BDD);

function litParam($nomParam) {
	global $oParametres;
	
	return $oParametres->litParam($nomParam);
}

function ecritParam($_nomParam, $_val) {
	global $oParametres;
	
	return $oParametres->ecritParam($_nomParam, $_val);
}

function litTousLesParams() {
	global $oParametres;
	
	return $oParametres->litTousLesParams();
}

function ecritTousLesParams() {
	global $oParametres;
	
	return $oParametres->ecritTousLesParams();
}

function supprimeParam($nomParam) {
	global $oParametres;
	
	return $oParametres->supprimeParam($nomParam);
}

?>