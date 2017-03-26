<?php
/*---------------------------------------------------------------
		f_ldap_scribe.inc.php
-----------------------------------------------------------------
	Librairie de fonctions d'utilisation du LDAP pour les services 
		installés sur les serveurs Eole Scribe
	inc/f_scribe.inc.php doit être chargé avant pour récupérer le config
-----------------------------------------------------------------
	auteur : Olivier HACQUARD - Académie de Besançon   
---------------------------------------------------------------*/

//*------------------=O===================O----------------------


/*------------------------------------------
 Fonction : 
-------------------------------------
 
-------------------------------------
 - Entrée : 
 - Sortie :
---------------------------------------------*/
function mdpReader() {
	global $cheminMdpReader;
	
	if (file_exists($cheminMdpReader)) {
		$mdp=file_get_contents($cheminMdpReader);
		return trim($mdp);
	} else return false;
				
}

/*------------------------------------------
 Fonction : connecteLDAP
-------------------------------------
 - Connexion au serveur LDAP - scribe ou académique selon le choix...
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function connecteLDAP($choix='scribe') {
	global $cnxLDAP;

	if ($choix!='scribe' && $choix!='aca') return false; //choix scribe ou aca seulement
	//if (isset($cnxLDAP[$choix]['etat'])) if ($cnxLDAP[$choix]['etat']) return true; //si déjà connecté, on sort
	
	switch ($choix) {
		case 'scribe':
			if (!isset($cnxLDAP['scribe']['host'])) { //si infos pas encore générées
				//connexion en mode reader
				if (($mdp=mdpReader())!==false) {
					if ($mdp!="") $cnxLDAP['scribe']=array('host'=>"localhost", 'port'=>"389", 'etat'=>false, 'dnCnx'=>"cn=reader,o=gouv,c=fr", 'mdpCnx'=>$mdp); 
					//echo "LDAP reader";
				}
				//ou anonyme...
				else {
					$cnxLDAP['scribe']=array('host'=>"localhost", 'port'=>"389", 'etat'=>false); 
					//echo "LDAP anonyme";
				}
			}
			if ($cnxLDAP['scribe']['etat']) return true;
			break;
		case 'aca':
			if (connecteLDAP('scribe')) { //on récupère les infos du serveur local
				if (!isset($cnxLDAP['aca']['host'])) { //si infos pas encore générées
					$cnxLDAP['aca']=array('academie'=>$cnxLDAP['aca']['academie'], 'host'=>"annuaire.".$cnxLDAP['aca']['academie'].".fr", 'port'=>"389", 'etat'=>false);
				}
			}
			break;
	} //switch
	
	// Accès LDAP  
	$cnxLDAP[$choix]['ds']=ldap_connect($cnxLDAP[$choix]['host'], $cnxLDAP[$choix]['port']); //connexion serveur ldap
	
	if($cnxLDAP[$choix]['ds']) { //la connexion a marché

		if (!isset($cnxLDAP[$choix]['dnCnx'])) $r=ldap_bind($cnxLDAP[$choix]['ds']); //connexion anonyme
		else $r=ldap_bind($cnxLDAP[$choix]['ds'], $cnxLDAP[$choix]['dnCnx'], $cnxLDAP[$choix]['mdpCnx']); //connexion non anonyme

		if ($r===false) { //liaison (bind) a échoué
			$cnxLDAP['message']="Connexion LDAP $choix impossible (bind) !";
			//echo ("Connexion LDAP $choix impossible (bind) !".BRCR);
			return false;
		} else $cnxLDAP[$choix]['etat']=true;
		//echo "Connexion LDAP OK !".BRCR;
	}
	else { //la connexion n'a pas marché
		$cnxLDAP['message']="Erreur de Connexion LDAP !";
		return false;
	}

	switch ($choix) {
		case 'scribe':
      if (is_executable('/usr/bin/CreoleGet')) {
          // EOLE >= 2.4
          $RNE = exec('CreoleGet numero_etab');
      } else {
          // EOLE 2.3
          $numero_etab 	= exec("grep -3  numero_etab /etc/eole/config.eol  | gawk -F ']' '{print $1}' | gawk -F '['  '{print $2}' ");
          $RNE			= substr($numero_etab,1,-1);
      }
			$sr = ldap_list( $cnxLDAP['scribe']['ds'], 'ou=education,o=gouv,c=fr', 'ou=ac*', array('ou'));
			$info = ldap_get_entries($cnxLDAP['scribe']['ds'],$sr);
			$cnxLDAP['aca']['academie']=$info[0]['ou'][0];
			$cnxLDAP['scribe']['rne']=$RNE;
			$cnxLDAP['scribe']['baseDN']="ou=$RNE,".$info[0]['dn'];
			break;
		case 'aca':
				$cnxLDAP['aca']['baseDN']="ou=".$cnxLDAP['aca']['academie'].",ou=education,o=gouv,c=fr";
			break;
	} //switch
	return $cnxLDAP[$choix]['etat'];
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function testConnexionLDAP($choixLDAP) {
	//global $cnxLDAP;
	
	return connecteLDAP($choixLDAP);
}


/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function recupDataLDAP2($choixLDAP, $choixDN, $champs, $filtre="(objectClass=*)") {
	global $cnxLDAP;
	
	$tabData=false;
	if ($choixDN!="") $choixDN.=","; 
	if (!$cnxLDAP[$choixLDAP]['etat']) { //si pas encore connecté
		if ((connecteLDAP($choixLDAP))===false) {
			die($cnxLDAP['message']);
		} //else echo "Connexion LDAP $choixLDAP OK !".BRCR;
	}
	$sr=$count=$info=NULL;
	//if (trim($filtre)=='') $filtre='(objectClass=*)';
	//echo "<br>dn:".$choixDN.$cnxLDAP[$choixLDAP]['baseDN']."<br>\n".$filtre."<br>\n";
	$sr=ldap_search($cnxLDAP[$choixLDAP]['ds'],$choixDN.$cnxLDAP[$choixLDAP]['baseDN'], $filtre, $champs);
	
	if($sr)	$info=ldap_get_entries($cnxLDAP[$choixLDAP]['ds'], $sr);
	
	for ($i=0; $i<$info["count"]; $i++) {
		if (is_array($champs)) foreach ($champs as $valC) {
			if ($valC=="dn") {
				$tabData[$i][$valC]=$info[$i]['dn'];
			} else {
				if (count($info[$i][$valC])>2) {
					foreach ($info[$i][$valC] as $_idx => $_val) {
						if ( is_numeric($_idx)) {
							$tabData[$i][$valC][$_idx]=$_val;
						}
					}
				}	else {
					$tabData[$i][$valC]=$info[$i][$valC][0];
				} 
			}
		}		
	}
	//print_r($tabData);die("on est ici");
	
	return $tabData;
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function recupListeClasse($div="", $avecEleves=true, $avecDetailsEleves=false, $champsDetails=array('uid','sn','givenname','dn','divcod','objectclass','mail'), $champsComplementaires=array('datenaissance')) {
	if ($div!="") $filtre="(cn=$div)";
	else $filtre="(description=classe*)"; //toutes les classes
	
	$choixRecup=array("cn");
	if ($avecEleves) $choixRecup[]="memberuid"; //on ajoute les uid des eleves si on veut
	//récup des classes avec les élèves qui en font partie
	//$tabD=recupDataLDAP2('scribe',"ou=local,ou=groupes", array("cn", "memberuid"), $filtre);
	$tabD=recupDataLDAP2('scribe',"ou=local,ou=groupes", $choixRecup, $filtre);
	//si pas d'élèves, on renvoi un tableau à 1 dimension
//	print_r($tabD); exit();
//if ($div=='1st2') print_r($tabD);
//echo (is_array($tabD[0]['memberuid'])?'oui':'non');
	if ($avecDetailsEleves) {
		//if ($avecEleves && !is_array($tabD[0]['memberuid'])) continue; //return false; //s'il n'y a pas de membre de ce groupe, on sort
		$tabD['details']=array();
		if (is_array($tabD)) foreach ($tabD as $_tabD) {
			if (is_array($_tabD['memberuid'])) { //foreach ($_tabD['memberuid'] as $_uid) {
				$tabD['details']=array_merge($tabD['details'],recupInfosMultiUsersLDAP($_tabD['memberuid'], true, $champsDetails,$champsComplementaires));
				//tri par ordre alphabétique
			}
			if (is_array($tabD['details'])) {
					foreach ($tabD['details'] as $_uid=>$_elv) {
				 	 	$tTri[$_elv['nom'].$_elv['prenom'].$_uid]=$_uid;
				 	}
				 	if (count($tTri)>0) {
					 	ksort($tTri);
					 	foreach($tTri as $_uid) {
					 		$details2[$_uid]=$tabD['details'][$_uid];
					 	}
					 	$tabD['details']=$details2;
					}
			} 			
		}
	}
	if (!$avecEleves) {
		foreach ($tabD as $_tClasse) {
			$tClasses[]=$_tClasse['cn'];
		}
		return $tClasses; //tableau simple
	}
	else return($tabD); //tableau complet
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function recupListeClasseAvecRegime($div="", $avecEleves=true, $avecDetailsEleves=false, $champsDetails=array('uid','sn','givenname','dn','divcod','objectclass','mail'), $champsComplementaires=array('datenaissance')) {
	$filtreE='(objectclass=*)';
	$filtre="(description=classe*)"; //toutes les classes
	if ($div!="") {
		$filtre="(cn=$div)";
		$filtreE="(divcod=$div)";
	}

	$choixRecup=array('cn','memberuid');
	//$choixRecup[]="memberuid"; //on ajoute les uid des eleves
	
	$tabD=recupDataLDAP2('scribe',"ou=local,ou=groupes", $choixRecup, $filtre);
	//si pas d'élèves, on renvoi un tableau à 1 dimension
//	print_r($tabD); exit();
//if ($div=='1st2') print_r($tabD);
//echo (is_array($tabD[0]['memberuid'])?'oui':'non');
	$tabE=recupDataLDAP2('scribe',"ou=local,ou=eleves,ou=Utilisateurs", array('sn', 'givenname','uid',/*'divcod',*/'enteleveregime'), $filtreE);
	//print_r($tabE);
	if (is_array($tabE)) foreach($tabE as $_tElv) {
		if ($_tElv['uid']!='') {
			$_regime='ext';
			if (stristr($_tElv['enteleveregime'],'demi_pens')!==false) $_regime='dp';
			else if (stristr($_tElv['enteleveregime'],'interne')!==false) $_regime='int';
//			$tabD['details'][$_tElv['uid']]=array('nom'=>$_tElv['sn'].' '.$_tElv['givenname'],'regime'=>$_regime);
			$tabD['details'][$_tElv['uid']]=array('nom'=>$_tElv['sn'],'prenom'=>$_tElv['givenname'],'regime'=>$_regime);
		}
	}
	if (is_array($tabD)) {
			foreach ($tabD as $_n=>$_tMembers) { //chaque classe
		 	 	$tTri=array();
		 	 	if (is_array($_tMembers['memberuid'])) {
		 	 		foreach ($_tMembers['memberuid'] as $_uid) {
		 	 			$tTri[$tabD['details'][$_uid]['nom'].$tabD['details'][$_uid]['prenom'].$_uid]=$_uid;
		 			}
		 		}
			 	if (count($tTri)>0) {
				 	ksort($tTri); //on trie le tableau des memberuid par ordre alpha
				 	$tabD[$_n]['memberuid']=array();
				 	foreach($tTri as $_uid) $tabD[$_n]['memberuid'][]=$_uid;//on le remplace dans le tableau
				}
		 	}
	}	

	if (!$avecEleves) {
		foreach ($tabD as $_tClasse) {
			$tClasses[]=$_tClasse['cn'];
		}
		return $tClasses; //tableau simple
	}
	else return($tabD); //tableau complet
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function recupListeUtilisateursAvecDetails($tUtilisateurs) {
	
	$tabD[0]['memberuid']=$tUtilisateurs;
	//si pas d'élèves, on renvoi un tableau à 1 dimension
	//print_r($tabD);
	//if (is_array($tabD['memberuid'])) foreach ($tabD['memberuid'] as $_uid) {
		$tabD['details']=recupInfosMultiUsersLDAP($tabD[0]['memberuid'], true, array('uid','sn','givenname','dn','divcod','objectclass','mail'),array('datenaissance'));
		//tri par ordre alphabétique
		if (is_array($tabD['details'])) {
			foreach ($tabD['details'] as $_uid=>$_elv) {
		 	 	$tTri[$_elv['nom'].$_elv['prenom'].$_uid]=$_uid;
		 	}
		 	ksort($tTri);
		 	foreach($tTri as $_uid) {
		 		$details2[$_uid]=$tabD['details'][$_uid];
		 	}
		 	$tabD['details']=$details2;
		} 
	//}
	return($tabD); //tableau complet
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 
-------------------------------------
 - Entrée : 
 - Sortie :
---------------------------------------------*/
function recupListeClassesGroupes($avecMembres=false, $choixType=array('Classe','Option')) {
	$filtre=mkRqtORLdap($choixType, 'type');
	
	$choixRecup=array('cn', 'type');
	if ($avecMembres) $choixRecup[]='memberuid'; //ajout des membres des groupes
	$tabD=recupDataLDAP2('scribe','ou=local,ou=groupes', $choixRecup, $filtre);
	//print_r($tabD);
	if (is_array($tabD)) foreach ($tabD as $_tGrp) {
		if ($_tGrp['type']=='Classe') $leType='div'; else $leType='grp';
		if ($avecMembres) {
			if (is_array($_tGrp['memberuid'])) $tabDivGrp[$leType][$_tGrp['cn']]=$_tGrp['memberuid'];
			else $tabDivGrp[$leType][$_tGrp['cn']]=array($_tGrp['memberuid']);
		}
		else $tabDivGrp[$leType][]=$_tGrp['cn'];
	}
	if (isset($tabDivGrp['div'])) asort($tabDivGrp['div']);
	if (isset($tabDivGrp['grp'])) asort($tabDivGrp['grp']);
	//print_r($tabDivGrp);
	return $tabDivGrp;
} //fin de fonction

/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function recupEquipePeda($div="") {
	if ($div!="") $filtre="(cn=profs-$div)";
	else $filtre="(description=Equipe*)"; //toutes les classes

	//récup des classes avec les élèves qui en font partie
	$tabD=recupListeClasse();
	$chF="";
	if (is_array($tabD)) foreach($tabD as $_tVal) {
		$chF.="(description=Equipe profs-".$_tVal['cn'].")";
	}
	if (count($tabD)>1) $chF="(|".$chF.")";
	
	//récup des équipes pédagogiques pour toutes les classes
	$tabD=recupDataLDAP2('scribe',"ou=local,ou=groupes", array("cn", "memberuid"), $chF);
	
	return ($tabD);
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function recupListePersonnelsLDAP($profil="", $supplement="") {
	$tP=array();
	$champs=array("sn", "givenname","entpersonsexe","entpersonprofils","intid","uid");
	if ($supplement!="") if (is_array($supplement)) {
		foreach ($supplement as $_sup) {
			$champs[]=$_sup;
		}
	}
	//print_r($champs);
	//récup des infos
	if ($profil=="") $chF='(objectclass=*)';
	else $chF='(entpersonprofils='.$profil.')';
	$tabP=recupDataLDAP2('scribe',"ou=local,ou=personnels,ou=Utilisateurs", $champs, $chF);
	if (is_array($tabP)) foreach ($tabP as $_personnel) {
		$tP[$_personnel['uid']]=array('profil'=>$_personnel['entpersonprofils'],'nom'=>$_personnel['sn'],'prenom'=>$_personnel['givenname'],'civ'=>$_personnel['entpersonsexe'],'intid'=>$_prof['intid']);
		if (is_array($supplement)) foreach ($supplement as $_sup) {$tP[$_personnel['uid']][$_sup]=$_personnel[$_sup];};
	}
	return $tP;
}

function recupListeProfsLDAP($sup='') {
	return recupListePersonnelsLDAP('enseignant', $sup);
}
function recupListeAdministratifsLDAP($sup='') {
	return recupListePersonnelsLDAP('administratif', $sup);
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function recupListeElevesLDAP($uid="") {
	$tE=array();
	//récup des infos
	$chF="(objectclass=*)";
	$tabE=recupDataLDAP2('scribe',"ou=local,ou=eleves,ou=Utilisateurs", array("sn", "givenname","entpersonsexe","intid","uid", "divcod"), $chF);
	if (is_array($tabE)) foreach ($tabE as $_eleve) {
		$tE[$_eleve['uid']]=array('nom'=>$_eleve['sn'],'prenom'=>$_eleve['givenname'],'civ'=>$_eleve['entpersonsexe'],'intid'=>$_eleve['intid'], 'div'=>$_eleve['divcod']);
	}
	return $tE;
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function recupGroupesLDAP() {
	$filtre="(objectClass=*)";

	//récup des équipes pédagogiques pour toutes les classes
	$tabD=recupDataLDAP2('scribe',"ou=local,ou=groupes", array("cn","type", "description","memberuid","lastupdate"), $filtre);
	
	return ($tabD);
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function rechercheCorrespondUseridProfLDAP($nom,$prenom) {
	$useridTrouve="";
	if (($_pos=strpos($nom,'-'))!==false) {
		$_nom=substr($nom,0,$_pos).'*';
	} else if (($_pos=strpos($nom,' '))!==false) {
		$_nom=substr($nom,0,$_pos).'*';
	} else if (($_pos=strpos($nom,'_'))!==false) {
		$_nom=substr($nom,0,$_pos).'*';
	} else $_nom=$nom.'*';
	if (($_pos=strpos($prenom,'-'))!==false) {
		$_prenom=substr($prenom,0,$_pos).'*';
	} else if (($_pos=strpos($prenom,' '))!==false) {
		$_prenom=substr($prenom,0,$_pos).'*';
	} else if (($_pos=strpos($prenom,'_'))!==false) {
		$_prenom=substr($prenom,0,$_pos).'*';
	} else $_prenom=$prenom.'*';
	//echo "<i>$nom $prenom - $_nom $_prenom - (&(sn=$_nom)(givenname=$_prenom))".BRCR;
	$tabD=recupDataLDAP2('scribe',"ou=local,ou=personnels,ou=utilisateurs", array("uid"), "(&(sn=$_nom)(givenname=$_prenom))");	
	//print_r($tabD);
	if (is_array($tabD)) {
			$premPass=true;
			foreach($tabD as $_useridTrouve) {
				if (!$premPass) { //s'il y a plusieurs login correspondant
					$useridTrouve.=';';
				}
				$useridTrouve.=$_useridTrouve['uid'];
				$premPass=false;
			}
	}

	return $useridTrouve;
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function recupInfosUserLDAP($login, $infosComplementaires=false) {
	$champs=array('sn','givenname','dn','homedirectory','divcod','objectclass','mail');
	if (is_array($infosComplementaires)) {
		foreach ($infosComplementaires as $info) {
			$champs[]=$info;
		}
	}
	$tabD=recupDataLDAP2('scribe',"", $champs, "(|(uid=$login))");	
	//print_r($tabD); exit();
	if ($tabD[0]['sn']=='admin') {
		$_profil="administrateur";
	} else if (stristr($tabD[0]['dn'],'ou=personnels')!==false) {
		if (is_array($tabD[0]['objectclass']) && in_array("administratif",$tabD[0]['objectclass'])) {
			$_profil="administratif";
		} else {
			$_profil="professeur";
		}
	} else if (stristr($tabD[0]['dn'],'ou=eleves')!==false) {
		$_profil="eleve";
	} else if (stristr($tabD[0]['dn'],'ou=responsables')!==false) {
		$_profil="responsable";
	} else {
		$_profil="autre";
	}
	//récupération des groupes
	$tabD3=recupDataLDAP2('scribe',"", array('cn','dn','type'), "(|(memberuid=$login))");	
	$tGrp=array();
	if(is_array($tabD3)) foreach ($tabD3 as $_tGrp) {
		if ($_tGrp['type']=="") $_tGrp['type']="general";
		//if ($_tGrp['type']!="general" && $_tGrp['type']!="Base") 
		$tGrp[$_tGrp['type']][]=$_tGrp['cn'];
	}
	//echo "profil:$_profil<br/>";
	//print_r($tabD3);
	//print_r($tGrp);exit();
	return array('nom'=>strtoupper($tabD[0]['sn']),'prenom'=>ucfirst(strtolower($tabD[0]['givenname'])),'profil'=>$_profil,'div'=>$tabD[0]['divcod'],'home'=>$tabD[0]['homedirectory'], 'groupes'=>$tGrp);
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 
-------------------------------------
 - Entrée : 
 - Sortie :
---------------------------------------------*/
function recupNomUser($login, $prenomPrems=false) {
	if ($login=='') return '';
	$tUser=recupInfosUserLDAP($login);
	//print_r($tUser);exit();
	if (isset($tUser['nom'])) {
		if ($prenomPrems) return $tUser['prenom'].' '.$tUser['nom'];
		else return $tUser['nom'].' '.$tUser['prenom'];
	}
	return '';
} //fin de fonction
/*------------------------------------------
 Fonction : 
-------------------------------------
 
-------------------------------------
 - Entrée : 
 - Sortie :
---------------------------------------------*/
function mkRqtORLdap($tab, $champsRecherche="") {
	$chaine="(|";
	if (is_array($tab)) {
		foreach($tab as $key => $elem) {
			if (is_numeric($key)) { //tous les champs utilisent le même champs de recherche
				if ($champsRecherche!="") $chaine.='('.$champsRecherche.'='.$elem.')';
			} else {
				$chaine.='('.$key.'='.$elem.')';
			}
		}
	} else return false;
	return $chaine.")";
}

/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function recupInfosMultiUsersLDAP($tLogin, $recupGroupes=false, $tChamps=array('uid','sn','givenname','dn','divcod','objectclass','mail'), $tChampsComplementaires=false) {
	$critereRecherche=mkRqtORLdap($tLogin, "uid");
	//print_r($tLogin,$tChamps,$critereRecherche); //exit();
	if (is_array($tChampsComplementaires)) foreach ($tChampsComplementaires as $_champs) {
		$tChamps[]=$_champs;
	}
	$tabD=recupDataLDAP2('scribe',"", $tChamps, $critereRecherche);	
	//print_r($tabD); //exit();
	if (is_array($tabD)) foreach ($tabD as $_tResult) {
		if ($_tResult['sn']=='admin') {
			$_profil="administrateur";
		} else if (stristr($_tResult['dn'],'ou=personnels')!==false) {
			if (is_array($_tResult['objectclass']) && in_array("administratif",$_tResult['objectclass'])) {
				$_profil="administratif";
			} else {
				$_profil="professeur";
			}
		} else if (stristr($_tResult['dn'],'ou=eleves')!==false) {
			$_profil="eleve";
		} else if (stristr($_tResult['dn'],'ou=responsables')!==false) {
			$_profil="responsable";
		} else {
			$_profil="autre";
		}
		//récupération des groupes
		$tabD3=recupDataLDAP2('scribe',"", array('cn','dn','type'), '(|(memberuid='.$_tResult['uid'].'))');	
		$tGrp=array();
		if(is_array($tabD3)) foreach ($tabD3 as $_tGrp) {
			if ($_tGrp['type']=="") $_tGrp['type']="general";
			if ($_tGrp['type']!="general" && $_tGrp['type']!="Base") $tGrp[$_tGrp['type']][]=$_tGrp['cn'];
		}
		$tUsers[$_tResult['uid']]=array('nom'=>strtoupper($_tResult['sn']),'prenom'=>ucfirst(strtolower($_tResult['givenname'])),'profil'=>$_profil,'div'=>$_tResult['divcod'],'home'=>$_tResult['homedirectory'], 'groupes'=>$tGrp);
		if (is_array($tChampsComplementaires)) foreach ($tChampsComplementaires as $_champs) {
			$tUsers[$_tResult['uid']][$_champs]=$_tResult[$_champs];
		}
	} 
	//print_r($tabD);
	//print_r($tGrp);exit();
	return $tUsers; //array('nom'=>strtoupper($tabD[0]['sn']),'prenom'=>ucfirst(strtolower($tabD[0]['givenname'])),'profil'=>$_profil,'div'=>$tabD[0]['divcod'],'home'=>$tabD[0]['homedirectory'], 'groupes'=>$tGrp);
}


//----------------- A VERIFIER ------------------------------


/*------------------------------------------
 Fonction : 
-------------------------------------
 -
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/
function recupDatasLDAPacaProfs() {
	set_time_limit(0);
	global $cnxLDAP;

	connecteLDAP('scribe'); //appel bidon pour récupérer à coup sûr le rne de l'étab
	$filter="(rneextract=".$cnxLDAP['scribe']['rne'].")";
	$tabD=recupDataLDAP2('aca',"ou=personnels EN", array("cn", "codecivilite", "datenaissance", "dermaj" ,"discim", "discipline", "givenname", "mail", "nompatro", "rne", "rneextract", "sn", "title", "uid"), $filter); //);"sn", "mail", "givenname"
	//print_r($tabD);
	$nbUid=$nbUidTrouve=$nbMultiples=0;
	//exit();
	if (is_array($tabD)) foreach ($tabD as $_tProf) {
		$_userid=rechercheCorrespondUseridProfLDAP($_tProf['sn'],$_tProf['givenname']);
		//if ($_tProf['title']=="ENS") 
		$nbUid++;
		$uidMultiple=(strpos($_userid,';')!==false);
		if ($_userid!="" && !$uidMultiple) $nbUidTrouve++;
		if ($uidMultiple) {$nbMultiples++;echo "<b>".$_tProf['sn']." ".$_tProf['givenname']."</b> login multiple : $_userid".BRCR;}
		if ($_userid=="") {echo "<b>".$_tProf['sn']." ".$_tProf['givenname']."</b> login inconnu".BRCR;}
		//echo "uid=".$_tProf['uid']." userid=$_userid".BRCR;
		$tInfosLDAPaca[]=array('uid_aca'=>$_tProf['uid'],'userid'=>$_userid,'codecivilite'=>$_tProf['codecivilite'],'nom'=>$_tProf['sn'],'prenom'=>$_tProf['givenname'],'nomComplet'=>$_tProf['cn'],'date_naiss'=>$_tProf['datenaissance'],'title'=>$_tProf['title'],'discim'=>$_tProf['discim'],'discipline'=>$_tProf['discipline'],'rne'=>$_tProf['rne'],'rneextract'=>$_tProf['rneextract'],'mail'=>$_tProf['mail'],'dermaj'=>$_tProf['dermaj']);
	}
	echo "résultat : <b>$nbUidTrouve</b> userid trouvés, <b>$nbMultiples</b> userid multiples,  sur <b>$nbUid</b> profs...".BRCR;
	return $tInfosLDAPaca;
}
	
//--------------------------------------- Partie exécutée ----------------------------------------------

global $cnxLDAP;

$cnxLDAP=array('scribe'=>false,'aca'=>false);
if (file_exists('./config/config.inc.php')) require_once ('./config/config.inc.php'); 

?>