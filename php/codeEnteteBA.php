<?php
/*---------------------------------------------------------------
		codeEnteteBA.php
-----------------------------------------------------------------
	code PHP permettant le fonctionnement de blocklyArduino.php
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

	if ($uid=='admin') $nomUser='Admin';
	else $nomUser=$_SESSION['_user_']['prenom']." ".$_SESSION['_user_']['nom'];
	//gère la langue et la carte par défaut
	$ajoutURL=array();
	$codeJSlangCardDefaut='';
	$manque=false;
	if (empty($_GET['lang'])) {$manque=true; $ajoutURL[]='lang=fr';} else $ajoutURL[]='lang='.$_GET['lang']; //fr
if (empty($_GET['card'])) {$manque=true; $ajoutURL[]='card=arduino_uno';} else $ajoutURL[]='card='.$_GET['card']; //arduino_uno;
	if ($manque) {
		$prems=true;
		foreach ($ajoutURL as $_ajout) {
			if (!$prems) $chAjout.='&';
			$prems=false;
			$chAjout.=$_ajout;
		}
		$codeJSlangCardDefaut='window.location="blocklyArduino.php?'.$chAjout.'";';
		/*
		'
		var chUrl = window.location.search;
		var newchUrl = chUrl;
		if (newchUrl.search("?")==-1) newchUrl+="?";
		else newchUrl+="&";
		newchUrl+= "'.$chAjout.'";
    window.history.pushState(chUrl, "Title", newchUrl);
    alert(newchUrl);
';*/
	}
	
	//on regarde si on doit récupérer le nom du projet dans l'URL
	$nomProjet='';
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
	
	//complément JS exécuté au chargement de la page
	$codeJSdebut='
	var nomProjet="'.$nomProjet.'";
	var aEnregistrer=false;
	'.$codeJSsupNom.$codeJSlangCardDefaut.'
	var uid="'.$uid.'";

	function verifSaisieNomFichier(texte)
	{
		var regex = /^[a-zA-Z0-9._-\séèàçäëïöüôîûâê]+$/;
		if(!regex.test(texte)) {
			alert("Le nom de projet saisi est incorrect. Sont autorisées :\n - les lettres\n - les chiffres\n - les caractères \'espace\', - et _\nMerci de modifier le nom saisi.");
			return false;
		} else {
				//alert("Good !");
				return true;
		}
	}
	';
	
	//complément JS exécuté à la fin du chargement de la page - en fin de code HTML
	$codeJSfin= '
<script type="text/javascript">
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
	$caseNomProjet='
	          <span style="margin:0 0px 0 150px;font-weight:bold;font-style:italic">projet :</span>
            <span id="nomProjet" style="font-weight:bold;background-color:#ddd;padding:2px 10px 2px 10px;font-size:1.2em">'.$nomProjet.'</span>
';
	
	$isConnecte=true;
	$btnOpenSaveConnect='<div id="cont_btn_openSave">'.CR;
	
	//code HTML des boutons 	 charger, sauver, connecter/déconnecter 
	if ($isConnecte) { //connecté
		$btnOpenSaveConnect.='		<button id="btn_open" type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#openModal">
			<b><span id="span_open"> </span> </b>
			<span class="glyphicon glyphicon-open"></span>		
		</button>
		<button id="btn_save" type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#saveModal">
			<b><span id="span_save"> </span> </b>
			<span class="glyphicon glyphicon-save"></span>		
		</button>
		<button id="btn_deconnect" type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#deconnecteModal" alt="Se déconnecter">
			<b>'.$nomUser.' </span> </b>
			<span class="glyphicon glyphicon-off"></span>		
		</button>'.CR;
	} else { //non connecté
		$btnOpenSaveConnect.='		<button id="btn_connect" type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#saveModal">
			<b><span id="span_connect"> </span></b>
			<span class="glyphicon glyphicon-off"></span>		
		</button>'.CR;
	}
	
	$btnOpenSaveConnect.='</div>'.CR;
	
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
              <b id="saveIdName">nom du projet : </b><input type="text" id="caseNomP" value="'.$nomProjet.'" style="width:60%"> 
              <button id="btn_saveProj" type="button" class="btn btn-success btn-sm" data-toggle="modal" onMouseDown="if (verifSaisieNomFichier($(\'#caseNomP\').val())) $(this).click()">Enregistrer</button>
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
              <span id="txtLogout" style="font-weight:bold;"></span>
              <button type="button" class="btn btn-success btn-sm" data-toggle="modal" onClick="window.location=\'?logout\'">Ok</button>
      </div>
    </div>
  </div>
</div>
';
?>