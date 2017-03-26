// fonctions JavaScript 
function initVersionPHP() {
	bA_setOrientation();
	Code2.initLanguage();
	$('#btn_saveProj').on("click", bA_prepareXmlFileAndSave);
	$('#btn_delete').on("click", bA_discard);
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
	if (newNomProjet==nomProjet) onEcrase='O'; //réenregistrement donc on écrase
	
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
 // * Load blocks from db file.
 */
/*BlocklyDuino.loadFic = function () {
  var files = event.target.files;
  // Only allow uploading one file.
  if (files.length != 1) {
    return;
  }

  // FileReader
  var reader = new FileReader();
  reader.onloadend = function(event) {
    var target = event.target;
    // 2 == FileReader.DONE
    if (target.readyState == 2) {
      try {
        var xml = Blockly.Xml.textToDom(target.result);
      } catch (e) {
        alert(MSG['xmlError']+'\n' + e);
        return;
      }
      var count = BlocklyDuino.workspace.getAllBlocks().length;
      if (count && confirm(MSG['xmlLoad'])) {
    	  BlocklyDuino.workspace.clear();
      }
      $('#tab_blocks a').tab('show');
      Blockly.Xml.domToWorkspace(BlocklyDuino.workspace, xml);
      BlocklyDuino.selectedTab = 'blocks';
      BlocklyDuino.renderContent();
      
	  	// load toolbox
      var elem = xml.getElementsByTagName("toolbox")[0];
      if (elem != undefined) {
				var node = elem.childNodes[0];
				window.localStorage.toolbox = node.nodeValue;
				$("#toolboxes").val(node.nodeValue);
				
				// load toolbox categories
				elem = xml.getElementsByTagName("toolboxcategories")[0];
				if (elem != undefined) {
					node = elem.childNodes[0];
					window.localStorage.toolboxids = node.nodeValue;
				}
		
				var search = BlocklyDuino.addReplaceParamToUrl(window.location.search, 'toolbox', $("#toolboxes").val());
				window.location = window.location.protocol + '//'
						+ window.location.host + window.location.pathname
						+ search;
			}

    }
    // Reset value of input after loading because Chrome will not fire
    // a 'change' event if the same file is loaded again.
    $('#load').val('');
  };
  reader.readAsText(files[0]);
};*/
