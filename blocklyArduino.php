<?php
/*---------------------------------------------------------------
		blocklyArduino.php
-----------------------------------------------------------------
	page du service blockly@rduino
-----------------------------------------------------------------
	auteur : Olivier HACQUARD - Académie de Besançon   
---------------------------------------------------------------*/
chdir('./php');

include('./config/config.inc.php');

if ($USE_SCRIBE) { //on utilise l'authentification CAS par le scribe

	include ('./inc/f_session_scribe.inc.php');
	//doit être appelé avant initSession qui utilise le ldap pour trouver les infos sur l'utilisateur courant
	include ('./inc/f_ldap_scribe.inc.php');

} else { //pas scribe

	include ('./inc/f_session_sans_scribe.inc.php');

}

include_once('./inc/c_parametres.inc.php');
include_once('./inc/f_mysql.inc.php');

include_once('./inc/f_blocklyArduino.inc.php');

recupUid();

chdir('./..');

$isAdmin=userIsAdmin();

//print_r($_SESSION);

$isConnecte=true;
	$ajoutURL=array();
//gère la langue et la carte par défaut
	$codeJSlangCardDefaut='';
	$manque=false;
	
if (empty($uid)) { //non connecté
	//echo "non connecté...";
	//exit();
	$isConnecte=false;
	
	if (empty($_GET['connecte'])) {$manque=true; $ajoutURL[]='connecte=no';} else $ajoutURL[]='connecte='.$_GET['connecte']; //fr
	
}
else { //utilisateur connecté
	if ($uid=='admin') $nomUser='Admin';
	else $nomUser=($_SESSION['_user_']['prenom']!=''?$_SESSION['_user_']['prenom']." ":"").$_SESSION['_user_']['nom'];
}

	$dejaLance=false;
	if (isset($_SESSION['premLancement'])) if($_SESSION['premLancement']==0) {
		$dejaLance=true;
	}

//paramètres par défaut - génériques, perso ou commun
$paramAll=litParam('param__all_');
$paramU=litParam('param_'.$uid);

$langDef='fr'; //valeurs générique
$cardDef='arduino_uno';
$toolboxDef='CAT_LOGIC,CAT_LOOPS,CAT_MATH,CAT_ARRAY,CAT_TEXT,CAT_VARIABLES,CAT_FUNCTIONS,CAT_ARDUINO';
$toolboxUrlDef='toolbox_arduino_all';

if ($paramU!=false) { //les params de l'utilisateur par défaut
	list($toolboxUrlDef,$toolboxDef,$langDef,$cardDef)=explode('§', $paramU);
} else if ($paramAll!=false) { //sinon ceux pour tout le monde
	list($toolboxUrlDef,$toolboxDef,$langDef,$cardDef)=explode('§', $paramAll);
}

//echo "$toolboxDef . $langDef , $cardDef";exit();
	$errorLogin=false;
	$ajoutCat='';
			
	if (empty($_GET['lang'])) {$manque=true; $ajoutURL[]='lang='.$langDef;} else $ajoutURL[]='lang='.$_GET['lang']; //fr
	if (empty($_GET['card'])) {$manque=true; $ajoutURL[]='card='.$cardDef;} else $ajoutURL[]='card='.$_GET['card']; //arduino_uno;
	$_SESSION['premLancement']=0;
	if (!$dejaLance) {
		if (empty($_GET['toolbox'])) { //premier lancement ou juste après login et pas de toolbox définie
			$manque=true; 
			$ajoutURL[]='toolbox='.$toolboxUrlDef;
			$ajoutCat='window.localStorage.toolboxids=="'.$toolboxDef.'"'.CR.'window.localStorage.toolbox="'.$toolboxUrlDef.'";'.CR; //if (window.localStorage.toolboxids.indexOf("CAT_ARDUINO")==-1) window.localStorage.toolboxids=window.localStorage.toolboxids+",CAT_ARDUINO";';
		} else 
			$ajoutURL[]='toolbox='.$_GET['toolbox']; //arduino_uno;
			$ajoutCat='window.localStorage.toolbox="'.$_GET['toolbox'].'"; //'.$toolboxDef.CR;
		}
	
	if ($manque) {
		$prems=true;
		foreach ($ajoutURL as $_ajout) {
			if (!$prems) $chAjout.='&';
			$prems=false;
			$chAjout.=$_ajout;
		}
		$codeJSlangCardDefaut='window.location="blocklyArduino.php?'.$chAjout.'";';

	} else { //on ne manque pas, donc on ne recharge pas la page
		if (!empty($_SESSION['error'])) if ($_SESSION['error']=='login') {
			$_SESSION['error']='';
			$errorLogin=true;
		}
	}
	
	//on regarde si on doit récupérer le nom du projet dans l'URL
	$nomProjet='&nbsp;&nbsp;&nbsp;&nbsp;';
	if (!empty($_GET['nom'])) {
		$nomProjet=$_GET['nom'];
	}
	
	//on prépare le code JS pour supprimer le nom si c'est un exemple
	$codeJSsupNom='';
	if (!empty($_GET['url'])) {
		if (stristr($_GET['url'],'/examples/') !== false) { //c'est un exemple
			$nomFic=substr($_GET['url'],strrpos($_GET['url'],'/')+1,-4);
			$nomProjet=$nomFic;
			if (!empty($_GET['nom'])) { //il y a un nom dans l'url... on le retire
				$codeJSsupNom="	var search = window.location.search;
    var newsearch = search.replace(/([?&]nom=)[^&]*/, '');

	window.history.pushState(search, 'Title', newsearch);
";
			}
		}
	}
	$codeJSerror='';
	if ($errorLogin) {
		$codeJSerror='
		$(document).ready(function(){
			$("#txtError").html("<b>Erreur de connexion !</b><br/><br/>&nbsp;&nbsp;&nbsp;&nbsp;Problème de login ou de mot de passe...");
			$("#errorModal").modal("show");
		});
		
		';
	}

	//complément JS exécuté au chargement de la page
	$codeJSdebut='
	var nomProjet="'.$nomProjet.'";
	var aEnregistrer=false;
	'.$codeJSsupNom.$codeJSlangCardDefaut.'
	var uid="'.$uid.'";
	sessionStorage.setItem("lang", "'.$_GET['lang'].'");
	sessionStorage.setItem("card", "'.$_GET['card'].'");
	'.$codeJSerror.$ajoutCat.'
		$(document).ready(function() {
			$("#_casePwdU,#_caseNomU").keydown(function( event ) { 
				if ( event.which == 13 ) {
	   		//event.preventDefault();
	   			bA_connecte();
	  		}
  		});
			$("#caseNomP").keydown(function( event ) { 
				if ( event.which == 13 ) {
	   			bA_prepareXmlFileAndSave();
	  		}
  		});
			$(".inputU").keydown(function( event ) { 
				if ( event.which == 13 ) {
	   			bA_saveInfosUser();
	  		}
  		});
			$("#caseChgPwdU1, #caseChgPwdU2").keydown(function( event ) { 
				if ( event.which == 13 ) {
					$("#btn_chgPwd").click();  		
				}
  		});
/*			$("#caseChgPwdU2").keydown(function( event ) { 
				if ( event.which == 13 ) {
					$("#btn_chgPwd").click();  	  		
				}
  		});*/
  	});
	';
	
	//complément JS exécuté à la fin du chargement de la page - en fin de code HTML
	$codeJSfin= '
<script type="text/javascript">
		//window.localStorage.toolboxids="CAT_LOGIC,CAT_LOOPS";

		$("#btn_open").click(function() {
		 $.ajax({url: "./php/listeFic.php?action=liste", success: function(result){
        $("#listeFicOpen").html(result);
    }});
    
		$(window).unload(function() { //si on quitte la page
			alert("on sort...");
		});
	});
</script>
	';
	
	//case ou s'inscrit le nom du projet en cours
	$caseNomProjet='';
	
	
//	$isConnecte=true;
	$btnOpenSaveConnect='<div id="cont_btn_openSave">'.CR;
	
	//code HTML des boutons 	 charger, sauver, connecter/déconnecter 
	if ($isConnecte) { //connecté
		$btnOpenSaveConnect.='
		<div id="dNomProjet">
	          	<span id="sTxtNomProjet">projet :</span>
            	<span id="nomProjet" style="">'.$nomProjet.'</span>
    </div>
    <button id="btn_open" type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#openModal">
			<b><span id="span_open"> </span> &nbsp;</b>
			<span class="glyphicon glyphicon-open"></span>		
		</button>
		<button id="btn_save" type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#saveModal">
			<b><span id="span_save"> </span> &nbsp;</b>
			<span class="glyphicon glyphicon-save"></span>		
		</button>
		<button id="btn_deconnect" type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#deconnecteModal" alt="Se déconnecter">
			<b><span>'.$nomUser.' </span> &nbsp;</b>
			<span class="glyphicon glyphicon-off"></span>		
		</button>
		<button id="btn_param" type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#paramUserModal" alt="Paramètres">
			&nbsp;<span class="glyphicon glyphicon-cog"></span>&nbsp;		
		</button>'.CR;
	} else { //non connecté
		$btnOpenSaveConnect.='		<button id="btn_connect" type="button" class="btn btn-success btn-sm btn_ver" data-toggle="modal" data-target="#connecteModal">
			<b><span id="span_connect"> </span></b>
			<span class="glyphicon glyphicon-off"></span>		
		</button>'.CR;
	}
	
	$btnOpenSaveConnect.='</div>'.CR;
	
	$dispNiScribeNiAdmin='display:none;';
	if ($isAdmin) {
		$dispPasAdmin='display:none;';
		$disp4Admin=''; //block';
	} else {
		$dispPasAdmin='';
		$disp4Admin='display:none;'; //block';
	}
	
	if ($USE_SCRIBE) {
		$dispPasScribe='display:none;';
	} else {
		$dispPasScribe='';
		if ($isAdmin) $dispNiScribeNiAdmin='';
	}
	
	
	$codeHTMLfenetresModal='
	<!-- open modal -->
<div class="modal fade" id="openModal" tabindex="-1" role="dialog" aria-labelledby="openModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="openModalLabel"></h4>
      </div>
      <div class="modal-body">
              <b>Ouvrir...</b>
              <div id="listeFicOpen">aucun projet...</div>
              
      </div>
    </div>
  </div>
</div>

<!-- save modal -->
<div class="modal fade" id="saveModal" tabindex="-1" role="dialog" aria-labelledby="saveModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="saveModalLabel"></h4>
      </div>
      <div class="modal-body" style="text-align:center">
              <b id="saveIdName">nom du projet : </b><input type="text" id="caseNomP" value="'.($nomProjet=='&nbsp;&nbsp;&nbsp;&nbsp;'?'':$nomProjet).'" style="width:60%"> 
              <button id="btn_saveProj" type="button" class="btn btn-success btn-sm" data-toggle="modal">Enregistrer</button>
              <div id="save_comment">xxx</div>
      </div>
    </div>
  </div>
</div>

<!-- deconnecte modal -->
<div class="modal fade" id="deconnecteModal" tabindex="-1" role="dialog" aria-labelledby="deconnecteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="deconnecteModalLabel"></h4>
      </div>
      <div class="modal-body">
              <div id="txtLogout" style="font-weight:bold;"></div>
              <div style="text-align:right"><button type="button" id="btnDeconnecte" class="btn btn-success btn-sm" data-toggle="modal">Ok</button></div>
      </div>
    </div>
  </div>
</div>

<!-- connecte modal -->
<div class="modal fade" id="connecteModal" tabindex="-1" role="dialog" aria-labelledby="connecteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="connecteModalLabel"></h4>
      </div>
      <div class="modal-body">
              <span id="txtLogin" style="font-weight:bold;"></span>
              <form method="POST" action="./index.php" id="fLogin">
              <div style="margin:0 0 5px 0"><div id="txtNomU" style="float:left;width:300px;text-align:right;font-weight:bold">nom d\'utilisateur</div>&nbsp;: <input type="text" id="_caseNomU" name="caseNomU" value="" style="width:100px"></div> 
              <div><div id="txtPwdU" style="float:left;width:300px;text-align:right;font-weight:bold">mot de passe</div>&nbsp;: <input type="password" id="_casePwdU" name="casePwdU" value="" style="width:100px"></div>
              <input type="hidden" name="action" value="login">
              <div style="text-align:right"><button type="button" id="btnConnecte" class="btn btn-success btn-sm" data-toggle="modal">Ok</button></div>
              </form>
      </div>
    </div>
  </div>
</div>

<!-- param modal -->
<div class="modal fade" id="paramUserModal" tabindex="-1" role="dialog" aria-labelledby="paramUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="paramUserModalLabel"></h4>
      </div>
      <div class="modal-body">
              <!-- sauve paramètres -->
              <div id="txtSaveParam" style="font-weight:bold;"></div>
              <div style="text-align:right">
              <button type="button" id="btnParamSave4all" class="btn btn-success btn-sm" data-toggle="modal" style="'.$disp4Admin.'margin:0 10px 0 0"><span id="txtBtnSaveParam4all">Pour tout le monde</span></button>
              <button type="button" id="btnParamUserSave4U" class="btn btn-success btn-sm" data-toggle="modal" style="'.$disp4Admin.'"><span id="txtBtnSaveParam4U">Pour vous</span></button>
              <button type="button" id="btnParamUserSave" class="btn btn-success btn-sm" data-toggle="modal" style="'.$dispPasAdmin.'"><span id="txtBtnSaveParam">Ok</span></button></div>
              
              <!-- changement mot de passe -->
              <div id="txtChgPwdU" style="'.$dispPasScribe.'border-top:1px SOLID lightgrey;margin-top:20px;padding:10px 0 0 0;font-weight:bold">changer le mot de passe : </div>
              <div style="'.$dispPasScribe.'position:relative;width:100%;height:30px;"><div id="txtChgPwdU2" style="width:300px;text-align:right;float:left;margin:0 5px 0 0">Nouveau mot de passe : </div><div style="float:left"><input type="password" id="caseChgPwdU1" value="" style="width:100px"></div></div>
              <div style="'.$dispPasScribe.'position:relative;width:100%;height:30px;"><div id="txtChgPwdU3" style="width:300px;text-align:right;float:left;margin:0 5px 0 0">Confirmer le nouveau mot de passe : </div><div style="float:left"><input type="password" id="caseChgPwdU2" value="" style="width:100px"></div></div>
              
              <div style="'.$dispPasScribe.'text-align:right"><button id="btn_chgPwd" type="button" class="btn btn-success btn-sm" data-toggle="modal">Enregistrer</button></div>
              
              <!-- gestion des comptes utilisateurs -->
              <div style="'.$dispNiScribeNiAdmin.'text-align:center;padding:10px;border-top:1px SOLID lightgrey;margin-top:20px"><button id="btn_gestUser" style="width:200px" type="button" class="btn btn-success btn-sm" data-toggle="modal" onMouseDown="bA_openGestUser()"><span id="btnGestUser">Gérer les utilisateurs</span></button></div>
      </div>
    </div>
  </div>
</div>

<!-- error modal -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="errorModalLabel"></h4>
      </div>
      <div class="modal-body">
              <div id="txtError" style="font-weight:normal;margin:10px 0 10 0"></div>
              <div style="text-align:right">
              	<button type="button" class="btn btn-success btn-sm" data-dismiss="modal" onClick="">Ok</button>
              </div>
      </div>
    </div>
  </div>
</div>

	<!-- gestUser modal -->
<div class="modal fade" id="gestUserModal" tabindex="-1" role="dialog" aria-labelledby="gestUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="gestUserModalLabel"></h4>
      </div>
      <div class="modal-body">
              <!-- gestion des comptes utilisateurs -->
              <div style="'.$dispNiScribeNiAdmin.'text-align:center"><button id="btnAddUser" style="width:200px" type="button" class="btn btn-success btn-sm" data-toggle="modal"><span id="btnAddUser">Gérer les utilisateurs</span></button></div>
              
              <!-- liste des utilisateurs -->
              <div id="listeUsers" style="border-top:1px SOLID lightgrey;margin-top:20px;padding:10px 0 0 0;">aucun utilisateur...</div>
              
      </div>
    </div>
  </div>
</div>

	<!-- addModifUser modal -->
<div class="modal fade" id="addModifUserModal" tabindex="-1" role="dialog" aria-labelledby="addModifUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="addModifUserModalLabel"></h4>
      </div>
      <div class="modal-body">
      				<style>
      					#dTabUsers {text-align:center}
      					#tabUsers {text-align:right}
      					.tLabelU {padding:10px;font-weight:bold;font-size:0.9em}
      					.tCaseU {text-align:left}
      					.commentU {font-weight:bold;font-size:0.7em;font-style:italic;text-align:right}
      					.oblig::after {content:"*";color:red;font-weight:bold}
      				</style>
      				<!-- tableau nouveau/modif des comptes utilisateurs -->
              <div id="dTabUsers">
              <table align="center" id="tabUsers">
              	<tr><td class="tLabelU oblig">login</td><td class="tCaseU"><input id="uLogin" type="text" class="inputU" name="" value=""/></td></tr>
              	<tr><td class="tLabelU oblig">nom</td><td class="tCaseU"><input id="uNom" type="text" class="inputU" name="" value=""/></td></tr>
              	<tr><td class="tLabelU">prénom</td><td class="tCaseU"><input id="uPrenom" type="text" class="inputU" name="" value=""/></td></tr>
              	<tr id="trComMail"><td colspan="2" class="commentU"  id="tdComMail">Saisir 2 fois pour confirmer</td></tr>
              	<tr><td class="tLabelU">@ mail</td><td class="tCaseU"><input id="uMail1" type="text" class="inputU" name="" value=""/>&nbsp;&nbsp;<input id="uMail2" class="inputU" type="text" name="" value=""/></td></tr>
              	<tr id="trComPwd"><td colspan="2" class="commentU" id="tdComPwd">Saisir 2 fois pour confirmer</td></tr>
              	<tr><td class="tLabelU oblig">mot de passe</td><td class="tCaseU"><input id="uPwd1" type="password" class="inputU" name="" value=""/>&nbsp;&nbsp;<input id="uPwd2" type="password" class="inputU" name="" value=""/></td></tr>
              	<tr><td class="tLabelU">profil</td><td class="tCaseU">'.selectProfil(' class="inputU"').'</td></tr>
      				</table>
      				<div style="font-style:italic"><span class="oblig"></span> champs obligatoire</div>
              </div>
              <div style="text-align:right"><button id="btn_saveU" type="button" class="btn btn-success btn-sm" data-toggle="modal">Enregistrer</button></div>
              
      </div>
    </div>
  </div>
</div>

';
	
//;function(e){ if (e.keyCode == 13) alert(\'coucou\');}
	
?><html>
<head>
<link rel="icon" type="image/png" href="favicon.png" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Blockly@rduino</title>
<!--script type="text/javascript" src="core_Blockly/blockly_uncompressed.js"></script-->
<script type="text/javascript" src="core_Blockly/blockly_compressed.js"></script>

<!-- needed for type of variable -->
<script type="text/javascript" src="core_Ardublockly/type.js"></script>
<script type="text/javascript" src="core_Ardublockly/types.js"></script>
<script type="text/javascript" src="core_Ardublockly/static_typing.js"></script>
<script type="text/javascript" src="core_Ardublockly/instances.js"></script>
<script type="text/javascript" src="core_Ardublockly/field_instance.js"></script>
<script type="text/javascript" src="core_Ardublockly/block.js"></script>
<!--Arduino generator, must be defined NOW-->
<script type="text/javascript" src="generators/generator_arduino.js"></script>

<script type="text/javascript" src="core_BlocklyArduino/blockly@rduino.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/blockly@rduino_boards.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/blockly@rduino_tools.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/blockly@rduino_visual.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/jscolor.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/spin.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/jquery-2.1.3.min.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/bootstrap.min.3.3.6.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/bootstrap-toggle.min.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/prettify.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/head.load.min.js"></script>

<!-- define all additional blocks from one resumer-->
<script type="text/javascript" src="blocks/arduino_resume.js"></script>
<script type="text/javascript" src="generators/arduino_resume.js"></script>

<!--TechnoZone51-->
<script type="text/javascript" src="core_BlocklyArduino/TZ51/html2canvas.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/TZ51/canvas2image.js"></script>
<!--Fin TZ51-->

<script type="text/javascript" src="lang/code.js"></script>

<!--modifOH - scripts JS de complément pour afficher le texte des boutons suivant la langue ainsi que l'action -->
<script type="text/javascript" src="php/lang/code.js"></script>
<script type="text/javascript" src="php/js/blocklyArduino.js"></script>

<!--updated plugin>
<script type="text/javascript" src="http://codebender.cc/embed/compilerflasher.js"></script-->
<!--offline plugin-->
<script type="text/javascript" src="core_BlocklyArduino/compilerflasher.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/smoothie.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/clipboard.min.js"></script> 


<link rel="stylesheet" type="text/css" href="css/blockly@rduino.css"/>
<!--link rel="stylesheet" type="text/css" href="css/bootstrap.min.css"-->
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.SEB.css"/>
<link rel="stylesheet" type="text/css" href="css/bootstrap-toggle.min.css" />
<link rel="stylesheet" type="text/css" href="css/prettify.css"/>

<!--modifOH - feuille de style CSS propre à ce qui est rajouté sur la page -->
<link rel="stylesheet" type="text/css" href="php/css/php.css"/>

<script type="text/javascript">

<?php /* modifOH - complément JS exécuté au chargement de la page */ echo $codeJSdebut; ?>

	$(window).load(function() {
		$(".loading").fadeOut("slow");
	});
</script>
</head>

<body onload="BlocklyDuino.init(); /*modifOH - ajoute les fonctionnalités spécifiques à la version PHP */ initVersionPHP()">
<div class="loading"></div>
    <div id="divTitre">
		<a href="./index.php"><img id="clearLink" src="media/logo-mini.png" border="0" height="36px" onclick="" />
		</a> 
		<b>Blockly@rduino</b> : 
		<span id="title"></span>

<?php /* modifOH - code HTML de la case nom de projet */ echo $caseNomProjet; ?>
<?php /* modifOH - code HTML des boutons */ echo $btnOpenSaveConnect; ?>

		<button id="btn_about" type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aboutModal">
			<b><span id="span_about"> </span></b>
			<span class="glyphicon glyphicon-user"></span>		
		</button>
    </div>
    <div id="divBody">
    <div id="menuPanel">
	    <div id="menuPanelConfig">
	<!-- 		<button id="btn_switch" class="btn btn-default"> -->
	<!-- 			<span class="glyphicon glyphicon-retweet"> </span> -->
	<!-- 		</button> -->
			<!--outdated
			<button id="btn_picture" class="btn btn-danger btn-block text-left">
				<span class="glyphicon glyphicon-alert"> </span>
				<span id="span_picture"> </span>
			</button> -->
			<button id="btn_configGlobal" class="btn btn-danger btn-block text-left" data-toggle="modal" data-target="#configModalGlobal">
				<span class="glyphicon glyphicon-alert"> </span>
				<span id="configGlobalLabel"> </span>
			</button>
				<div id="div_miniPicture">
					<a id="miniCard">
						<img id="arduino_card_miniPicture" />
					</a>
				</div>
			<button id="btn_config" class="text-left btn btn-warning btn-block " data-toggle="modal" data-target="#configModal">
				<span class="glyphicon glyphicon-cog"> </span>
				<span id="span_config"> </span>
			</button>
			<a href="#" id="btn_config_kit" target="_blank" class="text-left btn btn-warning btn-block hidden" role='button'>
				<span class="glyphicon glyphicon-th-list"> </span>
				<span id="span_config_kit"> </span>
			</a>
	    </div>
	    <div id="menuPanelBlockly" class="margin-top-5">
	            <ul id="ul_nav" role="tablist">
	            <li role="presentation" id="tab_wiring">
	                 <a href="#content_wiring" aria-controls="content_wiring" role="tab" data-toggle="tab">
	                     <span class="glyphicon glyphicon-blackboard"></span>
	                     <span id="a_wiring"> </span>
	                 </a>
	            </li>
	            <li role="presentation" id="tab_supervision">
	                 <a href="#content_supervision" aria-controls="content_supervision" role="tab" data-toggle="tab">
	                     <span class="glyphicon glyphicon-transfer"></span>
	                     <span id="a_supervision"> </span>
	                 </a>
	            </li>
	            <li role="presentation" id="tab_blocks" class="active">
	                 <a href="#content_blocks" aria-controls="content_blocks" role="tab" data-toggle="tab">
	                     <span class="glyphicon glyphicon-home"></span>
	                     <span id="a_blocks"> </span>
	                 </a>
	            </li>
	            <li role="presentation" id="tab_arduino">
	                 <a href="#content_arduino" aria-controls="content_arduino" role="tab" data-toggle="tab">
	                     <span class="glyphicon glyphicon-check"></span>
	                    <span id="a_arduino"></span>
	                 </a>
	            </li>
	            <li role="presentation" id="tab_term">
	                 <a href="#content_term" aria-controls="content_term" role="tab" data-toggle="tab">
	                     <span  class="glyphicon glyphicon-log-out"></span>
	                     <span id="a_term"> </span>
	                 </a>
	            </li>
	            <!--only to see XML translation online -->
				<!--li role="presentation" id="tab_xml">
	                 <a href="#content_xml" aria-controls="content_xml" role="tab" data-toggle="tab">
	                     <span  class="glyphicon glyphicon-file"></span>
	                     <span id="a_xml"></span>
	                 </a>
	             </li-->
	        </ul>
		</div>
		<div id="menuPanelFiles">
			<a id="btn_saveXML" class='btn btn-success btn-block text-left' href="#" role='button'>
			  <span class="glyphicon glyphicon-cloud-download"></span>
			  <span id="span_saveXML"> </span>
			 </a>
			<button id="btn_fakeload" class="btn btn-success btn-block">
				<span class="glyphicon glyphicon-cloud-upload"></span>
				<span id="span_fakeload"> </span>
			</button>
			<input type="file" id="load" style="display: none;" />
			<button id="btn_example" class="btn btn-info btn-block" data-toggle="modal" data-target="#exampleModal">
				<span class="glyphicon glyphicon-share"> </span>
				<span id="span_example"> </span>
			</button>
			<a id="btn_create_example" class="btn btn-info btn-block" href="" target="_blank">
				<span class="glyphicon glyphicon-wrench"> </span>
				<span id="span_create_example"> </span>
			</a>
		</div> 
		<div id="div_accessibility_button">
			<button id="btn_font" class="btn btn-warning text-left">
		       <!--span class="glyphicon glyphicon-briefcase"> </span-->
		       <span class="glyphicon glyphicon-text-height"> </span>
		    </button>
			<button id="btn_colors" class="btn btn-warning text-left jscolor {valueElement:null,value:'F0AD4E'}" onchange="update(this.jscolor)">
		       <span class="glyphicon glyphicon-tint"> </span>
		    </button>
			<script>
					function update(jscolor) {
						// 'jscolor' instance can be used as a string
						document.getElementById('menuPanel').style.backgroundColor = jscolor;
					}
			</script>
		</div>
		<div id="div_tools_button">
			<a href="./index.php" id="btn_reset" class="btn btn-danger text-left">
				   <span class="glyphicon glyphicon-off"> </span>
			</a>
			<button id="btn_RGB" class="btn btn-primary text-left">
		       <span class="glyphicon glyphicon-th"> </span>
		    </button>
			<button id="btn_convert" class="btn btn-primary text-left">
		       <span class="glyphicon glyphicon-superscript"> </span>
		   </button>
		</div>
		<div id="div_help_button">
		   <button id="btn_videos" class="btn btn-danger text-left">
		       <span class="glyphicon glyphicon-expand"> </span>
		   </button>
		   <a id="btn_doc" class='btn btn-success btn-info text-left' href="http://info.technologiescollege.fr/wiki/doku.php/fr/arduino/blockly_rduino" target="_blank" role='button'>
		       <span class="glyphicon glyphicon-info-sign"></span>
		   </a>
		   <a id="btn_tuto" class='btn btn-success btn-info text-left' href="http://blockly.technologiescollege.fr/forum/" target="_blank" role='button'>
		       <span class="glyphicon glyphicon-question-sign"></span>
		   </a>	
		</div>       
    </div>

    <!-- Nav tabs -->
    <div id="divTabpanel" role="tabpanel">
        <!-- Tab panes -->
        <div id="content_area" class="tab-content">        
			<div id="content_wiring" class="tab-pane">
				<iframe width="100%" height="100%" frameborder="0" src="https://fr.robom.ru"></iframe>
            </div>
			<div id="content_blocks" class="tab-pane active" style="position: relative;">
				<button id="btn_delete" class="btn btn-danger">
					<span class="glyphicon glyphicon-erase"> </span>
					<span id="span_delete"> </span>
				</button>
				<button id="btn_undo" class="btn btn-default">
					<span class="glyphicon glyphicon-menu-left"> </span>
				</button>
				<button id="btn_redo" class="btn btn-default">
					<span class="glyphicon glyphicon-menu-right"> </span>
				</button>
				<button id="btn_blocs_picture_mini" class="btn btn-default">
					<span id="icon_btn_blocs_picture_mini" class="glyphicon glyphicon-zoom-out"> </span>
				</button>
				<button id="btn_blocs_picture_maxi" class="btn btn-default">
					<span id="icon_btn_blocs_picture_maxi" class="glyphicon glyphicon-zoom-in"> </span>
				</button>
				<button id="btn_blocs_picture" class="btn btn-default">
					<span id="icon_btn_blocs_picture" class="glyphicon glyphicon-eye-open"> </span>
				</button>
				<button id="btn_size" class="btn btn-default">
					<span id="icon_btn_size" class="glyphicon glyphicon-fullscreen"> </span>
				</button>
				<button id="btn_preview" class="btn btn-default">
					<span id="icon_btn_preview" class="glyphicon glyphicon-sunglasses"> </span>
				</button>
                <button id="btn_inline" class="btn btn-default">
                    <span id="icon_btn_inline" class="glyphicon glyphicon-option-vertical"> </span>
                </button>
				<div id="toggle" class="modal-content" style="display: none;">
					<pre id="pre_previewArduino"></pre>			
					<button id="btn_CopyCode" class="btn btn-warning" data-clipboard-action="copy" data-clipboard-target="#pre_previewArduino">
						<span class="glyphicon glyphicon-duplicate"> </span>
					</button>
				</div>
			</div>
			<div id="content_arduino" class="tab-pane">
				<select id="cb_cf_boards"></select>
				<select id="cb_cf_ports"></select>
				<a id="btn_plugin_codebender" class='btn btn-danger' href="https://codebender.cc/static/plugin" target="_blank" role='button'>
					<span class="glyphicon glyphicon-new-window"></span>
					<span id="span_plugin_codebender"> </span>
				</a>		
				<button id="btn_edit_code" class="btn btn-primary" data-toggle="modal" data-target="#editModal">
                    <span id="icon_edit_btn" class="glyphicon glyphicon-edit"> </span>
                    <span id="span_edit_code"> </span>
                </button>						
				<a id="btn_saveArduino" class='btn btn-primary' href="#" role='button'>
					<span class="glyphicon glyphicon-save"> </span>
					<span id="span_saveIno"> </span>
				</a>		
				<button id="cb_cf_verify_btn" class="btn btn-codebender">
					<span class="glyphicon glyphicon-ok"> </span>
					<span id="span_verify_codebender"> </span>
				</button>			
                <button id="cb_cf_flash_btn" class="btn btn-codebender">
					<span class="glyphicon glyphicon-log-in"> </span>
					<span id="span_flash_codebender"> </span>
				</button>			
				<button id="btn_verify_local" class="btn btn-arduino">
					<span class="glyphicon glyphicon-ok"> </span>
					<span id="span_verify_local"> </span>
				</button>							
				<a id="btn_flash_local" class='btn btn-arduino' href="#" role='button'>
					<span class="glyphicon glyphicon-log-in"> </span>
					<span id="span_flash_local"> </span>
				</a>		
				<!--a id="btn_getResult" class='btn btn-arduino' href="http://127.0.0.1:5005" role='button' target='_blank'>
					<span class="glyphicon glyphicon-list-alt"> </span>
					<span id="span_flash_local_result"> </span>
				</a-->
                <button id="btn_pasteIDEArduino" class="btn btn-arduino">
					<span class="glyphicon glyphicon-random"> </span>
					<span id="span_pasteIDEArduino"> </span>
				</button>
				<img id="LocalCodebenderLogo" height="35"/>
                <pre id="pre_arduino"></pre>
                <div id="debug_arduino"></div>
				<div id="local_debug">
					<iframe id="rDuino_uploader" width="100%" height="100%" frameborder="0"></iframe>
				</div>
            </div>
            <div id="content_term" class="tab-pane">
                <select id="cb_cf_baud_rates"></select>
                <button id="cb_cf_serial_monitor_connect" class="btn btn-codebender">
					<span class="glyphicon glyphicon-transfer"> </span>
					<span id="span_connect_serial"> </span>				
				</button>
                <div id="cb_cf_serial_monitor"></div>
            </div>
            <div id="content_supervision" class="tab-pane">
            </div>
            <!--div id="content_xml" class="tab-pane">
                <pre id="pre_xml"></pre>
            </div-->
        </div>
    </div>
    </div>
<!-- Modals -->
<!-- about modal -->
<div class="modal fade" id="aboutModal" tabindex="-1" role="dialog" aria-labelledby="aboutModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="aboutModalLabel"></h4>
      </div>
      <div class="modal-body">
              <b>Forum</b> : <a href='http://blockly.technologiescollege.fr/forum' target='blank'>http://blockly.technologiescollege.fr/forum</a>
			  <br />
			  <br /><b>Documentation :</b> <a href="http://info.technologiescollege.fr/wiki/doku.php" target='blank'>Wiki</a>
			  <br />
			  <br />
			  <span><b>Blockly@rduino</b></span> (<a href="https://github.com/technologiescollege/Blockly-at-rduino" target='blank'>Github</a>)
              <span id="aboutBody" style="font-style: italic;"> </span>
              <br /> - Fred LIN (@gasolin) - BlocklyDuino : (<a href='https://github.com/BlocklyDuino/BlocklyDuino' target='blank'>'https://github.com/BlocklyDuino/BlocklyDuino</a>)
              <br /> - Alan YORINKS - PyMata-aio : (<a href='http://mryslab.blogspot.fr' target='blank'>http://mryslab.blogspot.fr</a>)
			  <br /> - Carlos PEREIRA ATENCIO - Ardublockly : (<a href='https://github.com/carlosperate/ardublockly' target='blank'>https://github.com/carlosperate/ardublockly</a>)
              <br /> - Bernard REMOND - rDuino-Compiler-Uploader-Server : (<a href='https://github.com/nbremond77' target='blank'>https://github.com/nbremond77</a>)
              <br /> - Blockly : (<a href='https://developers.google.com/blockly' target='blank'>https://developers.google.com/blockly</a>)
              <br /> - Bootstrap (<a href='http://getbootstrap.com' target='blank'>http://getbootstrap.com</a>)
              <br /> - Bootstrap Toggle (<a href='http://www.bootstraptoggle.com/' target='blank'>http://www.bootstraptoggle.com/</a>)
			  <br /> - Codebender - CompilerFlasher : (<a href='https://codebender.cc' target='blank'>https://codebender.cc</a>)
              <br /> - JQuerry (<a href='https://jquery.com' target='blank'>https://jquery.com)</a>
              <br /> - HeadJS (<a href='http://headjs.com/' target='blank'>http://headjs.com/)</a>
              <br /> - SmoothieCharts (<a href='http://smoothiecharts.org/' target='blank'>http://smoothiecharts.org/)</a>
              <br /> - ROBOM.RU (<a href='https://robom.ru/' target='blank'>https://robom.ru/)</a>		
			  <br />	  
			  <b><a href="http://framaforms.org/blocklyrduino-utilisateurs-1490560876" target='blank'><font size="12"><span id="span_forms_about" style="font-style: bold;"> </span></font></a></b>
			  <br />
			  <br /><i>version 13-04-2017 - v2.4 "Wiring"</i>
			  <br />
			  <br />
			  <div align="center" id="paypal_about">
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" >
				<input type="hidden" name="cmd" value="_s-xclick"/>
				<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
				"/>
				<input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_donateCC_LG.gif" name="submit" alt="PayPal, le réflexe sécurité pour payer en ligne"/>
				<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1"/>
				</form>
			  </div>
      </div>
      <!-- modifOH -->
      <div style="margin:0 0 0 20px"><b>PHP-BlocklyArduino</b> - <i>V1.0 du 22/04/2017</i> - (<a href="https://github.com/technoDreamer/php-blocklyArduino.git" target="_blank">github</a>)</div>
      <div style="margin:0 0 20px 60px;font-style:italic">- développé par Olivier HACQUARD (<a href="mailto:Olivier%20HACQUARD%20<olivier.hacquard@ac-besancon.fr>?subject=PHP-BlocklyArduino">olivier.hacquard@ac-besancon.fr</a>)</div>
      	
    </div>
  </div>
</div>

<?php /* modifOH - ajout du code des fenêtres modales pour l'ouverture, l'enregistrement et la connexion */ echo $codeHTMLfenetresModal; ?>

<!-- info first connect modal -->
<div class="modal fade" id="firstModal" tabindex="-1" role="dialog" aria-labelledby="firstModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="firstModalLabel"></h4>
      </div>
      <div class="modal-body">
			<b>Documentation wiki : </b><a href="http://info.technologiescollege.fr/wiki/doku.php" target='blank'>http://info.technologiescollege.fr/wiki/doku.php</a>
			<br />
			<b>Forum : </b><a href='http://blockly.technologiescollege.fr/forum' target='blank'>http://blockly.technologiescollege.fr/forum</a>
			<br />
			<b>Github : </b><a href="https://github.com/technologiescollege/Blockly-at-rduino" target='blank'>https://github.com/technologiescollege/Blockly-at-rduino</a>
			<br />
	        <div>
	            <embed id="videoFirstModal"  width="570" height="322" wmode="transparent" allowfullscreen="true" title="Adobe Flash Player"/>
	        </div>
      
			  <div align="center" id="paypal_first_connect">
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" >
				<input type="hidden" name="cmd" value="_s-xclick"/>
				<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
				"/>
				<input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_donateCC_LG.gif" name="submit" alt="PayPal, le réflexe sécurité pour payer en ligne"/>
				<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1"/>
				</form>
				<b><a href="http://framaforms.org/blocklyrduino-utilisateurs-1490560876" target='blank'><font size="18"><span id="span_forms_videomodal" style="font-style: bold;"> </span></font></a></b>
			  </div>
	  </div>      
      <div class="modal-footer">
		<span id="span_first_msg"></span>  <input type="checkbox" name="first_msg" id="first_msg"/>
        <button id="btn_valid_first_msg" type="button" class="btn btn-primary"></button>
      </div>
    </div>
  </div>
</div>
<!-- ajax modal -->
<div class="modal fade" id="ajaxModal" tabindex="-1" role="dialog" aria-labelledby="ajaxModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="ajaxModalLabel"></h4>
      </div>
      <div class="modal-body">
          <pre id="msg_ajax_ko"></pre>
          <input type="checkbox" name="ajax_msg" id="ajax_msg"/> <span id="span_ajax_msg"> </span>
      </div>      
      <div class="modal-footer">
        <button id="btn_close_msg" type="button" class="btn btn-default" data-dismiss="modal" ></button>
        <button id="btn_valid_msg" type="button" class="btn btn-primary"></button>
      </div>
    </div>
  </div>
</div>
<!-- picture modal -->
<div id="showcardModal" class="modal-dialog modal-sm" style="display:none">
	<div class="modal-content">
	  <div class="modal-header">
		<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
		<button id="btn_card_picture_mini" class="btn btn-default">
			<span id="icon_btn_blocs_picture_mini" class="glyphicon glyphicon-minus"> </span>
		</button>
		<button id="btn_card_picture_maxi" class="btn btn-default">
			<span id="icon_btn_blocs_picture_maxi" class="glyphicon glyphicon-plus"> </span>
		</button>
		<button id="btn_card_picture_change" class="btn btn-primary btn-sm">
			<span id="span_card_picture_change"> </span>
		</button>
		<div id="pinout_AIO_on"></div>
		<h4 class="modal-title" id="pictureModalLabel"></h4>
	  </div>
	  <div class="modal-body text-center">
			<img id="arduino_card_picture" width="200"/>
	  </div>
	</div>
</div>
<!-- example modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="exampleModalLabel"></h4>
      </div>
      <div class="modal-body">
		<div class="table-responsive">
			<table class="table table-hover">
				<tbody id="includedContent">
				</tbody>
			</table>
		</div>
      </div>
    </div>
  </div>
</div>
<!-- convert bin <-> text "modal" -->
<div id="convertModal" class="modal-dialog" style="display:none">
	<div class="modal-content">
	  <div class="modal-header">
        <button type="button" class="close" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="convertModalLabel"></h4>
      </div>
	  <div class="modal-body text-center" id="convert_content">
		<input id="ti1" value="TEST" />
		<button onclick="BlocklyDuino.text2bin();"><span id="span_txt2bin"> </span></button>
		<input id="ti2" />
		<br/>
		<input id="ti3" value="01010101" />
		<button onclick="BlocklyDuino.bin2text();"><span id="span_bin2txt"> </span></button>
		<input id="ti4" />
	  </div>
	</div>
</div>
<!-- convert RGB color code "modal" -->
<div id="RGB_modal" class="modal-dialog" style="display:none">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="RGBModalLabel"></h4>
      </div>
	  <div class="modal-body text-center" id="frame_RGB">
		<iframe width="550" height="700" src="./core_BlocklyArduino/RGB/RGB.html" frameborder="0"></iframe>
	  </div>
    </div>
</div>
<!-- videos modal -->
<div id="videoModal" class="modal-dialog" style="display:none;width: 700px;">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="videoModalLabelTitle"></h4>
      </div>
	  <div class="modal-body text-center" style=" overflow:auto;">
		  <div style="width: 45%;float: left;">
			<a href="https://mediacad.ac-nantes.fr/m/2018" target="_blank">
				<span id="videoModalLabel1"> </span>
			</a>
			<br/>
	        <div style="float: none; clear: both;">
	            <embed id="videoModal1"  wmode="transparent" allowfullscreen="true" title="Adobe Flash Player"/>
	        </div>
			<a href="https://mediacad.ac-nantes.fr/m/2017" target="_blank">
				<span id="videoModalLabel2"> </span>
			</a>
			<br/>
			<div style="float: none; clear: both;">
	            <embed id="videoModal2"  wmode="transparent" allowfullscreen="true" title="Adobe Flash Player"/>
	        </div>
	
			<a href="https://mediacad.ac-nantes.fr/m/2016" target="_blank">
				<span id="videoModalLabel3"> </span>
			</a>
			<br/>
	        <div style="float: none; clear: both;">
	            <embed id="videoModal3"  wmode="transparent" allowfullscreen="true" title="Adobe Flash Player"/>
	        </div>
	       </div>
		  <div style="width: 45%;float: left; margin-left: 10%;">
	
			<a href="https://mediacad.ac-nantes.fr/m/2020" target="_blank">
				<span id="videoModalLabel4"> </span>
			</a>
			<br/>
	        <div style="float: none; clear: both;">
	            <embed id="videoModal4"  wmode="transparent" allowfullscreen="true" title="Adobe Flash Player"/>
	        </div>
	        
			<a href="https://www.youtube.com/playlist?list=PLwy0yw3Oq4-uFJl0j-efUAAlfCbqtcTMr" target="_blank">
				<span id="videoModalLabel5"> </span>
			</a>
	        <div style="float: none; clear: both;">
	            <embed id="videoModal5"  wmode="transparent" allowfullscreen="true" title="Adobe Flash Player"/>
	        </div>
		  </div>
    	</div>
    </div>
</div>
<!-- toolbox config modal -->
<div class="modal fade" id="configModal" tabindex="-1" role="dialog" aria-labelledby="configModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="configModalLabel"></h4>
      </div>
		<div id="divToolbox">
			<label id="labelToolboxDefinition"></label> 
			<select id="toolboxes">
				<option value="toolbox_none" selected="selected">...</option>
				<option value="toolbox_algo"></option>
				<option value="toolbox_arduino_1"></option>
				<option value="toolbox_arduino_2"></option>
				<option value="toolbox_arduino_3"></option>
				<option value="toolbox_arduino_4"></option>
				<option value="toolbox_user"></option> <!-- NBR added -->
				<option value="toolbox_arduino_all"></option>
			</select>
		</div>
      <input type="checkbox" name="select_all" id="select_all"/> <span id="span_select_all"> </span>
      <div style="float : right; margin-right : 5px;">
            <input type="checkbox" name="put_in_url" id="put_in_url"/> <span id="span_put_in_url"> </span>
      </div>
      
      <div class="modal-body" id="modal-body-config"></div>
      <div class="modal-footer">
        <button id="btn_close_config" type="button" class="btn btn-default" data-dismiss="modal" ></button>
        <button id="btn_valid_config" type="button" class="btn btn-primary"></button>
      </div>
    </div>
  </div>
</div>
<!-- edit code modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="editModalLabel"></h4>
      </div>
      <div class="modal-body" id="modal-body-code">
            <textarea id="edit_code" rows="20" cols="100"></textarea>
      </div>
      <div class="modal-footer">
        <button id="btn_closeCode" type="button" class="btn btn-default" data-dismiss="modal" ></button>
        <button id="btn_validCode" type="button" class="btn btn-primary"  data-dismiss="modal"></button>
      </div>
    </div>
  </div>
</div>
<!-- global config modal -->
<div class="modal fade" id="configModalGlobal" tabindex="-1" role="dialog" aria-labelledby="configModalGlobalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
		    <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
			<h4 class="modal-title" id="configModalGlobalLabel"></h4>
				<input type="checkbox" name="put_config_in_url" id="put_config_in_url"/><i> <span id="span_put_config_in_url"> </span></i>
		    </div>    
			<div class="modal-body" id="modal-body-config-global">						
				<div class="arduino_card_mini_picture_div">
				    <img id="arduino_card_mini_picture" width="150px"/>
				</div>
				<div class="modal-body-config-global_left">
					<label id="span_languageMenu"> </label>  <select id="languageMenu"></select><br/>
					<!--label id="textSize" class="mod">tototo</label><input name="textSizeSelect" class="form-control" style="" min="0" max="30" step="1" value="12" type="number"></input-->
					<div id="divCard">
							<label id="labelArduinoCard"></label>
							<div id="pinout_AIO_off"></div>
							<select id="pinout" onmousemove="BlocklyDuino.cardPicture_change_AIO();" onchange="BlocklyDuino.cardPicture_change_AIO();">
								<option value="none">...</option>
								<option value="arduino_leonardo">Arduino/Genuino LEONARDO</option>
								<option value="arduino_mega">Arduino/Genuino MEGA</option>
								<option value="arduino_micro">Arduino/Genuino MICRO</option>
								<option value="arduino_nano">Arduino/Genuino NANO</option>
								<option value="arduino_uno">Arduino/Genuino UNO</option>
								<option value="arduino_yun">Arduino/Genuino YUN</option>
								<option value="lilypad">LilyPad</option>
								<option value="dfrobot_romeo">DFRobot RoMeo v2</option>
								<option value="kit_microfeux">Micro-feux Jeulin</option>
							</select>
					</div>
					<br />	        
					<div style="clear : both;">
						<a href="http://info.technologiescollege.fr/wiki/doku.php/fr/arduino/blockly_rduino/configglobale"  target='blank'><img src="media/codebenderOUarduino.jpg" height="35px"/></a><br/>
						<label id="span_OnOffLine"> </label><br/>
						<input id="toggle-WebAccess" data-toggle="toggle" data-on="<span id='toggle-WebAccess-on'> </span>" data-off="<span id='toggle-WebAccess-off'> </span>" data-onstyle="codebender" data-offstyle="arduino" data-width="120" type="checkbox"/><br />
						<br/>
						<label id="span_Upload"> </label>
						<br/>
						<span id="span_Upload_local"> </span>
						<input id="toggle-LocalCodebender" data-toggle="toggle" data-on="<span id='toggle-LocalCodebender-on'> </span>" data-off="<span id='toggle-LocalCodebender-off'> </span>" data-onstyle="arduino" data-offstyle="codebender" data-width="120" type="checkbox"/>
						<span id="span_Upload_codebender"> </span><br/>
						<br/>
						<label id="span_Download"> </label>
						<br/>
						<a id="btn_MyArduino" class='btn btn-primary text-left' href="https://github.com/technologiescollege/arduino" target="_blank" role='button'>
						  <span class="glyphicon glyphicon-download"></span>
						  <span id="span_Download_Arduino"> </span>
						</a>
						<a id="btn_Help_Offline" class='btn btn-arduino text-left' href="https://github.com/technologiescollege/Blockly-rduino-communication" target="_blank" role='button'>
						  <span class="glyphicon glyphicon-download"></span>
						  <span id="span_Download_local"> </span>
						</a>
						<a id="btn_Help_Online" class='btn btn-codebender text-left' href="https://codebender.cc/static/plugin" target="_blank" role='button'>
						  <span class="glyphicon glyphicon-download"></span>
						  <span id="span_Download_codebender"> </span>
						</a>
					</div>
				</div>
			</div>  
			<div class="modal-footer">
				<button id="btn_validConfigGlobale" type="button" class="btn btn-primary"  data-dismiss="modal"></button>
			</div>	
		</div>
	</div>
</div>

<?php /* modifOH - code JS après le chargement de la page */ echo  $codeJSfin; ?>

</body>
</html>
