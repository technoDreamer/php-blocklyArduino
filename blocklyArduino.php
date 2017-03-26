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
chdir('./..');

recupUid();

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
	
	$nomProjet='';
	if (!empty($_GET['nom'])) {
		$nomProjet=$_GET['nom'];
	}
	
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
	
	$isConnecte=true;
	$btnOpenSaveConnect='<div id="cont_btn_openSave">'.CR;
		
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
	
	
	
	
?>
<html>
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
<script type="text/javascript" src="core_BlocklyArduino/html2canvas.js"></script>
<script type="text/javascript" src="core_BlocklyArduino/canvas2image.js"></script>
<!--Fin TZ51-->

<script type="text/javascript" src="lang/code.js"></script>

<!--modifOH-->
<script type="text/javascript" src="php/lang/code.js"></script>
<script type="text/javascript" src="php/js/blocklyArduino.js"></script>

<!--updated plugin>
<script type="text/javascript" src="http://codebender.cc/embed/compilerflasher.js"></script-->
<!--offline plugin-->
<script type="text/javascript" src="core_BlocklyArduino/compilerflasher.js"></script>

<link rel="stylesheet" type="text/css" href="css/blockly@rduino.css"/>
<!--link rel="stylesheet" type="text/css" href="css/bootstrap.min.css"-->
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.SEB.css"/>
<link rel="stylesheet" type="text/css" href="css/bootstrap-toggle.min.css" />
<link rel="stylesheet" type="text/css" href="css/prettify.css"/>
<link rel="stylesheet" type="text/css" href="php/css/php.css"/>

<script type="text/javascript">
	//modifOH
	var nomProjet="<?php echo $nomProjet; ?>";
	var aEnregistrer=false;
	<?php echo $codeJSsupNom; echo $codeJSlangCardDefaut; ?>
	var uid="<?php echo $uid; ?>";

	function verifSaisieNomFichier(texte)
	{
		var regex = /^[a-zA-Z0-9._-\séèàçäëïöüôîûâê]+$/;
		if(!regex.test(texte)) {
			alert("Le nom de projet saisi est incorrect. Sont autorisées :\n - les lettres\n - les chiffres\n - les caractères 'espace', - et _\nMerci de modifier le nom saisi.");
			return false;
		} else {
				//alert("Good !");
				return true;
		}
	}
	//modifOH

		$(window).load(function() {
			$(".loading").fadeOut("slow");
		});
	</script>
</head>

<body onload="BlocklyDuino.init(); initVersionPHP()">
<div class="loading"></div>
    <div id="divTitre">
            <a href="./index.php"><img id="clearLink" src="media/logo-mini.png" border="0" height="36px" onclick="" />
            </a> 
            <b>Blockly@rduino</b> : 
            <span id="title"></span>
            
            <span style="margin:0 0px 0 150px;font-weight:bold;font-style:italic">projet :</span>
            <span id="nomProjet" style="font-weight:bold;background-color:#ddd;padding:2px 10px 2px 10px;font-size:1.2em"><?php echo $nomProjet; ?></span>

<?php echo $btnOpenSaveConnect;?>

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
			<button id="btn_picture" class="btn btn-danger btn-block text-left">
				<span class="glyphicon glyphicon-alert"> </span>
				<span id="span_picture"> </span>
			</button>
			<button id="btn_config" class="text-left btn btn-warning btn-block " data-toggle="modal" data-target="#configModal">
				<span class="glyphicon glyphicon-th-list"> </span>
				<span id="span_config"> </span>
			</button>
			<a href="#" id="btn_config_kit" target="_blank" class="text-left btn btn-warning btn-block hidden" role='button'>
				<span class="glyphicon glyphicon-th-list"> </span>
				<span id="span_config_kit"> </span>
			</a>
	    </div>
	    <div id="menuPanelBlockly" class="margin-top-5">
	            <ul id="ul_nav" role="tablist">
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
		<div id="div_miniPicture">
		    <a id="miniCard">
		        <img id="arduino_card_miniPicture" />
		    </a>
		</div>
		<div id="div_tools_button">
			<button id="btn_configGlobal" class="btn btn-warning text-left" data-toggle="modal" data-target="#configModalGlobal">
		       <span class="glyphicon glyphicon-cog"> </span>
		   </button>
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
			<!--input class="jscolor {hash:true}" onchange="update(this.jscolor)">
			<script>
					function update(jscolor) {
						// 'jscolor' instance can be used as a string
						document.getElementById('menuPanel').style.backgroundColor = jscolor;
						Blockly.Blocks.APDS9960.HUE = "#" + jscolor;
					}
			</script-->
		</div>       
    </div>

    <!-- Nav tabs -->
    <div id="divTabpanel" role="tabpanel">
        <!-- Tab panes -->
        <div id="content_area" class="tab-content">
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
				<a id="btn_getResult" class='btn btn-arduino' href="http://127.0.0.1:5005" role='button' target='_blank'>
					<span class="glyphicon glyphicon-list-alt"> </span>
					<span id="span_flash_local_result"> </span>
				</a>
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
            <div id="content_xml" class="tab-pane">
                <pre id="pre_xml"></pre>
            </div>
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
              <br /> - Alan YORINKS - PyMata-aio : (<a href='http://mryslab.blogspot.fr' target='blank'>http://mryslab.blogspot.fr</a>)
			  <br /> - Carlos PEREIRA ATENCIO - Ardublockly : (<a href='https://github.com/carlosperate/ardublockly' target='blank'>https://github.com/carlosperate/ardublockly</a>)
              <br /> - Bernard REMOND - rDuino-Compiler-Uploader-Server : (<a href='https://github.com/nbremond77' target='blank'>https://github.com/nbremond77</a>)
              <br /> - Fred LIN (@gasolin) - BlocklyDuino : (<a href='https://github.com/BlocklyDuino/BlocklyDuino' target='blank'>'https://github.com/BlocklyDuino/BlocklyDuino</a>)
              <br /> - Blockly : (<a href='https://developers.google.com/blockly' target='blank'>https://developers.google.com/blockly</a>)
              <br /> - Bootstrap (<a href='http://getbootstrap.com' target='blank'>http://getbootstrap.com</a>)
			  <br /> - Codebender - CompilerFlasher : (<a href='https://codebender.cc' target='blank'>https://codebender.cc</a>)
              <br /> - JQuerry (<a href='https://jquery.com' target='blank'>https://jquery.com)</a>
              <br /> - HeadJS (<a href='http://headjs.com/' target='blank'>http://headjs.com/)</a>
			  <br />
			  <br /><i>version 21-03-2017 - v2.3 "Kit Cat"</i>
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
    </div>
  </div>
</div>


<!-- open modal //modifOH -->
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

<!-- save modal //modifOH -->
<div class="modal fade" id="saveModal" tabindex="-1" role="dialog" aria-labelledby="saveModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="saveModalLabel"></h4>
      </div>
      <div class="modal-body" style="text-align:center">
              <b id="saveIdName">nom du projet : </b><input type="text" id="caseNomP" value="<?php echo $nomProjet; ?>" style="width:60%"> 
              <button id="btn_saveProj" type="button" class="btn btn-success btn-sm" data-toggle="modal" onMouseDown="if (verifSaisieNomFichier($('#caseNomP').val())) $(this).click()">Enregistrer</button>
              <div id="save_comment">xxx</div>
      </div>
    </div>
  </div>
</div>

<!-- deconnecte modal //modifOH -->
<div class="modal fade" id="deconnecteModal" tabindex="-1" role="dialog" aria-labelledby="deconnecteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="deconnecteModalLabel"></h4>
      </div>
      <div class="modal-body">
              <span id="txtLogout" style="font-weight:bold;"></span>
              <button type="button" class="btn btn-success btn-sm" data-toggle="modal" onClick="window.location='?logout'">Ok</button>
      </div>
    </div>
  </div>
</div>


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
<div id="videoModal" class="modal-dialog" style="display:none">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" aria-label="Close"><span aria-hidden="true">&#215;</span></button>
        <h4 class="modal-title" id="videoModalLabelTitle"></h4>
      </div>
	  <div class="modal-body text-center">
		<span id="videoModalLabel1"> </span><br/>
        <div style="float: none; clear: both;">
            <embed id="videoModal1"  wmode="transparent" allowfullscreen="true" title="Adobe Flash Player"/>
        </div>
	  </div>
	  <div class="modal-body text-center">
		<span id="videoModalLabel2"> </span><br/>
		<div style="float: none; clear: both;">
            <embed id="videoModal2"  wmode="transparent" allowfullscreen="true" title="Adobe Flash Player"/>
        </div>
	  </div>
	  <div class="modal-body text-center">
		<span id="videoModalLabel3"> </span><br/>
        <div style="float: none; clear: both;">
            <embed id="videoModal3"  wmode="transparent" allowfullscreen="true" title="Adobe Flash Player"/>
        </div>
	  </div>
	  <div class="modal-body text-center">
		<span id="videoModalLabel4"> </span><br/>
        <div style="float: none; clear: both;">
            <embed id="videoModal4"  wmode="transparent" allowfullscreen="true" title="Adobe Flash Player"/>
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
						<input id="toggle-WebAccess" data-toggle="toggle" data-on="" data-off="" data-onstyle="codebender" data-offstyle="arduino" data-width="120" type="checkbox"/><br />
						<br/>
						<label id="span_Upload"> </label>
						<br/>
						<span id="span_Upload_local"> </span>
						<input id="toggle-LocalCodebender" data-toggle="toggle" data-on="" data-off="" data-onstyle="arduino" data-offstyle="codebender" data-width="120" type="checkbox"/>
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

<script type="text/javascript">
//modifOH
	$("#btn_open").click(function() {
		 $.ajax({url: "./php/listeFic.php?action=liste", success: function(result){
        $("#listeFicOpen").html(result);
    }});
		/*//$("#listeFicOpen").html("New:<?php echo listeFicOpen();?>");*/
		$(window).unload(function() { //si on quitte la page
			alert("on sort...");
		});
	});
//modifOH
</script>

</body>
</html>
