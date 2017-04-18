// fonctions JavaScript 
function initVersionPHP() {
	bA_setOrientation();
	Code2.initLanguage();
	$('#btn_saveProj').on("click", bA_prepareXmlFileAndSave);
	$('#btn_delete').on("click", bA_discard);
	$('#btn_saveXML').on("click", BlocklyDuino.saveXmlFile2);
	//pour cacher la fenêtre affichée au début
	//$('#firstModal').modal('hide');	
	window.sessionStorage.msg_first_seen = true;
}

function bA_setOrientation() {
	var newOrientation = BlocklyDuino.getStringParamFromUrl('ort', '');
	if (newOrientation == 'hor') {
		$("#btn_save").removeClass("btn-block");
		$("#btn_open").removeClass("btn-block");
		$("#btn_connect").removeClass("btn-block");
		$("#btn_deconnect").removeClass("btn-block");
	} else {
		$("#btn_save").addClass("btn_ver");
		$("#btn_open").addClass("btn_ver");
		$("#btn_connect").addClass("btn_ver");
		$("#btn_deconnect").addClass("btn_ver");
	}
}

bA_discard = function () {
	//var count = BlocklyDuino.workspace.getAllBlocks().length;
 if (BlocklyDuino.workspace.getAllBlocks().length==0) {
		$("#nomProjet").html("");
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
	if ((newNomProjet==nomProjet) && !nonDefini) onEcrase='O'; //réenregistrement donc on écrase
	
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
