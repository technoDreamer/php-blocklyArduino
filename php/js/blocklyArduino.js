// fonctions JavaScript 
function initVersionPHP() {
	bA_setOrientation();
	Code2.initLanguage();
	$('#btn_saveProj').on("click", bA_prepareXmlFileAndSave);
	$('#btnDeconnecte').on("click", bA_deconnecte);
	$('#btnConnecte').on("click", bA_connecte);
	$('#btn_chgPwd').on("click", bA_chgPwd);
	$('#btn_delete').on("click", bA_discard);
	$('#btn_saveXML').on("click", BlocklyDuino.saveXmlFile2);
	$('#btnParamUserSave').on("click", bA_saveParamUser);
	$('#btnParamSave4all').on("click", bA_saveParamUser4all);
	$('#btnParamUserSave4U').on("click", bA_saveParamUser);
	$('#btn_saveU').on("click", bA_saveInfosUser);
	$('#btnAddUser').on("click", bA_prepareAddUser);
//	$('#btnAddUser').on("click", bA_addUser);
	//pour cacher la fenêtre affichée au début
	//$('#firstModal').modal('hide');	
	window.sessionStorage.msg_first_seen = true;
}

function bA_deconnecte() {
	window.location='./index.php?logout';
}

function bA_connecte() {
	verifPassword($('#_caseNomU').val(),$('#_casePwdU').val());
}

function bA_chgPwd() {
	if ($('#caseChgPwdU1').val() == $('#caseChgPwdU2').val()) {
		if (verifSaisie('pwd', $('#caseChgPwdU1').val())) {
			sauvePwdU($('#caseChgPwdU1').val()); 
			$('#caseChgPwdU1').val('');
			$('#caseChgPwdU2').val('');
		} else 
			alert("Le mot de passe n'est pas conforme...");
	}
	else alert('Erreur !!! Les mots de passe sont différents !');
}
function bA_setOrientation() {
	var newOrientation = BlocklyDuino.getStringParamFromUrl('ort', '');
	if (newOrientation == 'hor') {
		$("#btn_save").removeClass("btn-block");
		$("#btn_open").removeClass("btn-block");
		$("#btn_connect").removeClass("btn-block");
		$("#btn_deconnect").removeClass("btn-block");
		$("#btn_param").removeClass("btn-block");
	} else {
		$("#btn_save").addClass("btn_ver");
		$("#btn_open").addClass("btn_ver");
		$("#btn_connect").addClass("btn_ver");
		$("#btn_deconnect").addClass("btn_ver");
		$("#btn_param").addClass("btn_ver");
	}
}

var laListe='';
var finiChercherListe=false;

function bA_openGestUser() {
	afficheListeUsers();
	/*var datenow = Date.now();
	while (!finiChercherListe && Date.now()<datenow+3000) { }; //on attend 5s ou la fin de chargement
	finiChercherListe=false;
	alert(laListe);*/
	$("#paramUserModal").modal("hide");
	$("#gestUserModal").modal("show");
//	$("#listeUsers").html(afficheListeUsers());
}

var choixAddModif=false;

function bA_prepareAddUser() {
	bA_prepareAddModifUser('add','','');
}

function bA_prepareAddModifUser(choix, login, infos) {
	choixAddModif=choix;
	if (choix=='add') { //nouveau
		$("#addModifUserModalLabel").text(MSG2['newUserLabel']);
		$("#uLogin").val('');
		$("#uLogin").prop('disabled','');
		$("#uNom").val('');
		$("#uPrenom").val('');
		$("#uMail1").val('');
		$("#uMail2").val('');
		$("#uMail2").css("display","");
		$("#trComMail").css("display","");
		$("#tdComPwd").html("Saisir 2 fois pour confirmer");
		$("#uPwd1").val('');
		$("#uPwd2").val('');
		$("#sProfil option[value=\"1\"]").prop("selected", true);
		//$("#uProfil").val('');
	} else { //modif
		$("#addModifUserModalLabel").text(MSG2['modifUserLabel']);
		if (infos!="") {
			var tI=infos.split('/');
		}
		$("#uLogin").val(login);
		$("#uLogin").prop('disabled','1');
		$("#uNom").val(tI[0]);
		$("#uPrenom").val(tI[1]);
		$("#uMail1").val(tI[2]);
		$("#uMail2").css("display","none");
		$("#uPwd1").val('');
		$("#uPwd2").val('');
		$("#trComMail").css("display","none");
		$("#tdComPwd").html("Laisser vide pour ne pas modifier ou saisir 2 fois pour confirmer");
		//$("#uProfil").val(tI[3]);
		$("#sProfil option[value=\""+tI[3]+"\"]").prop("selected", true);
		//alert('option[value="'+tI[3]+'"]');
	}
	
	$("#addModifUserModal").modal("show");
}

function bA_saveInfosUser() {
	if (choixAddModif=='add') {
		if ($("#uLogin").val()=='' || $("#uNom").val()=='' ||/* $("#uMail1").val()=='' || */$("#uPwd1").val()=='') {alert('Saisie incomplète...'); return false;}
		if ($("#uMail1").val()!=$("#uMail2").val()) {alert('Les adresses mail ne correspondent pas...'); return false;}
		if ($("#uPwd1").val()!=$("#uPwd2").val()) {alert('Les mots de passe ne correspondent pas...'); return false;}
		if (!verifSaisie('login',$("#uLogin").val())) {alert('Le login n\'est pas conforme...'); return false;}
		if (!verifSaisie('txt',$("#uNom").val())) {alert('Le nom n\'est pas conforme...'); return false;}
		if ($("#uPrenom").val()!='') if (!verifSaisie('txt',$("#uPrenom").val())) {alert('Le prénom n\'est pas conforme...'); return false;}
		if ($("#uMail1").val()!='') if (!verifSaisie('mail',$("#uMail1").val())) {alert('L\' adresse mail n\'est pas conforme...'); return false;}
		if (!verifSaisie('pwd',$("#uPwd1").val())) {alert('Le mot de passe n\'est pas conforme...'); return false;}
		modifUser('add',$("#uLogin").val(),$("#uNom").val(),$("#uPrenom").val(),$("#uMail1").val(),$("#uPwd1").val(),$("#sProfil").val());
//		alert('add');
	} else if (choixAddModif=='modif') {
		if ($("#uLogin").val()=='' || $("#uNom").val()==''/* || $("#uPrenom").val()=='' || $("#uMail1").val()==''*/) {alert('Saisie incomplète...'); return false;}
		if (!verifSaisie('txt',$("#uNom").val())) {alert('Le nom n\'est pas conforme...'); return false;}
		if ($("#uPrenom").val()!='') if (!verifSaisie('txt',$("#uPrenom").val())) {alert('Le prénom n\'est pas conforme...'); return false;}
		if ($("#uPwd1").val()!='' && ($("#uPwd1").val()!=$("#uPwd2").val())) {alert('Les mots de passe ne correspondent pas...'); return false;}
		if ($("#uMail1").val()!='') if (!verifSaisie('mail',$("#uMail1").val())) {alert('L\' adresse mail n\'est pas conforme...'); return false;}
		if ($("#uPwd1").val()!='') if (!verifSaisie('pwd',$("#uPwd1").val())) {alert('Le mot de passe n\'est pas conforme...'); return false;}
		modifUser('modif',$("#uLogin").val(),$("#uNom").val(),$("#uPrenom").val(),$("#uMail1").val(),$("#uPwd1").val(),$("#sProfil").val());
//		alert('modif');
	}
	return true;
}

bA_discard = function () {
	//var count = BlocklyDuino.workspace.getAllBlocks().length;
 if (BlocklyDuino.workspace.getAllBlocks().length==0) {
 		$("#nomProjet").html("&nbsp;&nbsp;&nbsp;&nbsp;");
		$("#caseNomP").attr("value","");
		nomProjet="";
		var search = window.location.search;	
	
	// remove values from url
		var search = window.location.search;
    var newsearch = search.replace(/([?&]url=)[^&]*/, '');
    newsearch = newsearch.replace(/([?&]nom=)[^&]*/, '');

		window.history.pushState(search, "Title", newsearch);
	
	}
}

/**
 * Create an XML object containing the blocks from the Blockly workspace and
 *  save it in filesystem and database
 */
bA_prepareXmlFileAndSave = function () {
	if (!verifSaisieNomFichier($('#caseNomP').val())) {
		alert("Le nom de projet saisi est incorrect. Sont autorisées :\n - les lettres\n - les chiffres\n - les caractères \'espace\', - et _\nMerci de modifier le nom saisi.");
		return false;
	}
	
	var xml = Blockly.Xml.workspaceToDom(Blockly.mainWorkspace);
	
	var toolbox = window.localStorage.toolbox;
	if (!toolbox) {
		toolbox = $("#toolboxes").val();
	}
	
	if (toolbox) {
		var newel = document.createElement("toolbox");
		newel.appendChild(document.createTextNode(toolbox));
		xml.insertBefore(newel, xml.childNodes[0]);
	}
	
	var toolboxids = window.localStorage.toolboxids;
	if (toolboxids === undefined || toolboxids === "") {
		if ($('#defaultCategories').length) {
			toolboxids = $('#defaultCategories').html();
		}
	}
	
	if (toolboxids) {
		var newel = document.createElement("toolboxcategories");
		newel.appendChild(document.createTextNode(toolboxids));
		xml.insertBefore(newel, xml.childNodes[0]);
	}

	var dataXML = Blockly.Xml.domToPrettyText(xml);
	
	var datenow = Date.now();
	
//		alert(dataXML);
	var newNomProjet=$("#caseNomP").val();
	
	var onEcrase='N';	//changement de nom du projet
	
	if ((newNomProjet==nomProjet)/* && !nonDefini*/) onEcrase='O'; //réenregistrement donc on écrase
	//alert(onEcrase);
	//var codeRetour=
	lanceSauvegardeFichier(newNomProjet, onEcrase, dataXML, uid, datenow); //on sauve sans écraser
	//alert('codeRetour:'+codeRetour);
	//if (codeRetour==2) lanceSauvegardeFichier(newNomProjet, 'O', dataXML, uid, datenow); //on sauve en écrasant
	
};
	
	
function lanceSauvegardeFichier(newNomProjet, onEcrase, dataXML, uid, datenow) {
	var modeEnr=0; //0:enregistrement ok    1:demande confirmation ecrasement    2:ecrasement accepté
	
	$.ajax({
    url: "./php/sauveFic.php",
    data: {
    	  inputxml: dataXML,
        user: uid,
        timeS: datenow,
        //nomF: uid+'-'+datenow+".xml",
        nomP: newNomProjet,
        ecrase: onEcrase
    }, 
    type: 'POST',
    //contentType: "text/xml",
    dataType: "text",
    success : function(result) { //réponse de la fenêtre d'enregistrement
    	//alert("res:"+result);
    	var codeRetour=result.substring(0,1);
    	if (codeRetour=='0') {
    		//alert("succes");
    		modeEnr=0;
    	}
    	else {// if (codeRetour=='1') {
    		//alert("pb");
    		modeEnr=1;
    		//alert(result.substring(2,5));
    		if (result.substring(2,5)=='exi') { //le fichier existe ?
    			if (confirm("Un projet portant ce nom existe déjà !\n\nEtes vous sûr de vouloir le remplacer ?\n")) {
    				modeEnr=2;
    				lanceSauvegardeFichier(newNomProjet, 'O', dataXML, uid, datenow); //on sauve en écrasant
    			}
    		} else { //autre pb
    			alert(result);
    		}
    	}
    	if (modeEnr==0) {
	    	nomProjet=newNomProjet; //mémorise le nouveau nom
	    	$("#nomProjet").html(nomProjet); //l'affiche
				$('#saveModal .close').click(); //ferme la fenêtre sauvegarde
			}
    },
    error : function (xhr, ajaxOptions, thrownError){  
        alert(xhr.status);          
        alert(thrownError);
    } 
	});

}

/**
 * Creates an XML file containing the blocks from the Blockly workspace and
 * prompts the users to save it into their local file system.
 */
BlocklyDuino.saveXmlFile2 = function () {
	var xml = Blockly.Xml.workspaceToDom(Blockly.mainWorkspace);
	
	var toolbox = window.localStorage.toolbox;
	if (!toolbox) {
		toolbox = $("#toolboxes").val();
	}
	
	if (toolbox) {
		var newel = document.createElement("toolbox");
		newel.appendChild(document.createTextNode(toolbox));
		xml.insertBefore(newel, xml.childNodes[0]);
	}
	
	var toolboxids = window.localStorage.toolboxids;
	if (toolboxids === undefined || toolboxids === "") {
		if ($('#defaultCategories').length) {
			toolboxids = $('#defaultCategories').html();
		}
	}
	
	if (toolboxids) {
		var newel = document.createElement("toolboxcategories");
		newel.appendChild(document.createTextNode(toolboxids));
		xml.insertBefore(newel, xml.childNodes[0]);
	}
	
	var data = Blockly.Xml.domToPrettyText(xml);
	//prépare le nom du fichier d'export XML
	var datenow = Date.now();
	var theUid="";
	if (uid!="adminBA") theUid=uid+'-';
	var theNomProjet="";
	if (nomProjet!="") theNomProjet=nomProjet+"-";
	var uri = "data:text/xml;charset=utf-8," + encodeURIComponent(data);
	$(this).attr({
	            "download": "blockly_arduino"+"-"+theUid+theNomProjet+datenow+".xml",
				"href": uri,
				"target": "_blank"
	});
};

/**
 * Save param like Arduino car, lang and toolbox config
 */
bA_saveParamUser = function () {

	//get current toolboxes
	var toolboxids = window.localStorage.toolboxids;
	if (toolboxids === undefined || toolboxids === "") {
		if ($('#defaultCategories').length) {
			toolboxids = $('#defaultCategories').html();
		}
	}
	
	var dateNow = Date.now();
	var card=sessionStorage.getItem("card");
	var lang=sessionStorage.getItem("lang");
	var toolboxUrl=window.localStorage.toolbox;
	
	//alert(toolboxids+' / '+dateNow+' / '+lang+' / '+card);
	sauveParamUser(toolboxUrl,toolboxids, lang, card, uid);
	//if (codeRetour==2) lanceSauvegardeFichier(newNomProjet, 'O', dataXML, uid, datenow); //on sauve en écrasant
	$("#paramUserModal").modal('toggle');
};
	
/**
 * Save param like Arduino car, lang and toolbox config
 */
bA_saveParamUser4all = function () {

	//get current toolboxes
	var toolboxids = window.localStorage.toolboxids;
	if (toolboxids === undefined || toolboxids === "") {
		if ($('#defaultCategories').length) {
			toolboxids = $('#defaultCategories').html();
		}
	}
	alert(1);
	var dateNow = Date.now();
	var card=sessionStorage.getItem("card");
	var lang=sessionStorage.getItem("lang");
	var toolboxUrl=window.localStorage.toolbox;
	
	//alert(toolboxids+' / '+dateNow+' / '+lang+' / '+card);
	sauveParamUser(toolboxUrl,toolboxids, lang, card, '_all_');
	//if (codeRetour==2) lanceSauvegardeFichier(newNomProjet, 'O', dataXML, uid, datenow); //on sauve en écrasant
	$("#paramUserModal").modal('toggle');
};
	
function sauveParamUser(_toolboxUrl, toolbox, lng, crd, _uid) {
	if (_uid=='_all_') uid=_uid;
	$.ajax({
    url: "./php/action.php",
    data: {
    	  action:'sauveParams',
    	  toolboxName: _toolboxUrl,
    	  toolboxids: toolbox,
        user: uid,
        lang: lng,
        //nomF: uid+'-'+datenow+".xml",
        card: crd
    }, 
    type: 'POST',
    //contentType: "text/xml",
    dataType: "text",
    success : function(result) { //réponse de la fenêtre d'enregistrement
    	//alert("res:"+result);
    	var codeRetour=result.substring(0,1);
    	if (codeRetour=='0') {
    		alert("Sauvegarde des paramètres utilisateurs effectuée.");
    	}
    	else {// if (codeRetour=='1') {
    		//alert("pb");
    		modeEnr=1;
    		//alert(result.substring(2,5));
    	}
    },
    error : function (xhr, ajaxOptions, thrownError){  
        alert(xhr.status);          
        alert(thrownError);
    } 
	});

}

function sauvePwdU(pwd) {
	//alert (uid+' / '+pwd);
	$.ajax({
    url: "./php/action.php",
    data: {
    		action:'chgPwd',
    	  user: uid,
        password: pwd
    }, 
    type: 'POST',
    //contentType: "text/xml",
    dataType: "text",
    success : function(result) { //réponse de la fenêtre d'enregistrement
    	//alert("res:"+result);
    	var codeRetour=result.substring(0,1);
    	if (codeRetour=='0') {
    		alert("Changement du mot de passe utilisateur effectué.");
    	}
    	else {// if (codeRetour=='1') {
    		//alert("pb");
    		modeEnr=1;
    		//alert(result.substring(2,5));
    	}
    },
    error : function (xhr, ajaxOptions, thrownError){  
        alert(xhr.status);          
        alert(thrownError);
    } 
	});
	$("#paramUserModal").modal('toggle');
}

function afficheListeUsers() {
	//alert (uid+' / '+pwd);
	$.ajax({
    url: "./php/action.php",
    data: {
    		action:'listUsers'    }, 
    type: 'POST',
    //contentType: "text/xml",
    dataType: "text",
    success : function(result) { //réponse de la fenêtre d'enregistrement
    	//alert("res:"+result);
    	laListe=result; //.substring(0,1);
    	$("#listeUsers").html(result);
    	finiChercherListe=true;
    },
    error : function (xhr, ajaxOptions, thrownError){  
        alert(xhr.status);          
        alert(thrownError);
    	finiChercherListe=true;
    } 
	});
}

function verifPassword(_login, _pwd) {
	//alert (uid+' / '+pwd);
	$.ajax({
    url: "./php/action.php",
    data: {
    		action:'verifPwd',
    		login: _login,
    		pwd: _pwd    }, 
    type: 'POST',
    //contentType: "text/xml",
    dataType: "text",
    success : function(result) { //réponse de la fenêtre d'enregistrement
    	//alert("res:"+result);
    	if (result.substring(0,1)=='O') { //mot de passe ok
    		window.location='./index.php?action=login&login='+_login+'&pwd='+_pwd;
    	} else if (result.substring(0,1)=='N') { //mot de passe pas ok
    		alert("Erreur !!! Le login ou le mot de passe est incorrect !");
    	} else alert(result);
    },
    error : function (xhr, ajaxOptions, thrownError){  
        alert(xhr.status);          
        alert(thrownError);
    } 
	});
}

function modifUser(_action, _login, _nom, _prenom, _mail, _pwd, _profil) {
	//alert (uid+' / '+pwd);
	$.ajax({
    url: "./php/action.php",
    data: {
    		action:_action,
    		login: _login,
    		nom: _nom,
    		prenom: _prenom,
    		mail: _mail,
    		pwd: _pwd,
    		profil:_profil    }, 
    type: 'POST',
    //contentType: "text/xml",
    dataType: "text",
    success : function(result) { //réponse de la fenêtre d'enregistrement
    	//alert("res:"+result);
    	if (result.substring(0,1)==0) {
    		afficheListeUsers();
    		$("#addModifUserModal").modal("hide");
    	}
    	else alert(result);
    	/*if (choix=="modif") {
    		
    	} else if (choix=="supp") {
    		afficheListeUsers();
    	}*/
    	
    },
    error : function (xhr, ajaxOptions, thrownError){  
        alert(xhr.status);          
        alert(thrownError);
    } 
	});
}

function verifSaisie(typeTxt, texte) {
	var regex = /^[a-zA-Z0-9._-\séèàçäëïöüôîûâê]+$/;
	if (typeTxt=='login') {
		var regex = /^[a-zA-Z0-9._-]+$/;
	}
	if (typeTxt=='txt') {
		var regex = /^[a-zA-Z-\séèàçäëïöüôîûâê]+$/;
	}
	else if (typeTxt=='nomFichier') {
		var regex = /^[a-zA-Z0-9._-\séèàçäëïöüôîûâê]+$/;
	}
	else if (typeTxt=='pwd') {
		var regex = /^[a-zA-Z0-9.;:,-_!@&#\/\=\~\(\)\[\]\*\?\+\^\|\$\\\séèàçäëïöüôîûâê]+$/;
	}
	else if (typeTxt=='mail') {
		var regex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	}
	
	if(!regex.test(texte)) {
		return false;
	} else {
			//alert("Good !");
			return true;
	}
}

function verifSaisieNomFichier(texte)
{
	return verifSaisie('nomFichier', texte);
	/*var regex = /^[a-zA-Z0-9._-\séèàçäëïöüôîûâê]+$/;
	if(!regex.test(texte)) {
		alert("Le nom de projet saisi est incorrect. Sont autorisées :\n - les lettres\n - les chiffres\n - les caractères \'espace\', - et _\nMerci de modifier le nom saisi.");
		return false;
	} else {
			//alert("Good !");
			return true;
	}*/
}
