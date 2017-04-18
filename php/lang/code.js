/**
 * Based on 
 * 
 * Blockly Demos: Code
 *
 * Copyright 2012 Google Inc.
 * https://developers.google.com/blockly/
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * @fileoverview JavaScript for Blockly's Code demo.
 * @author fraser@google.com (Neil Fraser)
 */
'use strict';

/**
 * Create a namespace for the application.
 */
var Code2 = {};

/**
 * Lookup for names of supported languages.  Keys should be in ISO 639 format.
 */
Code2.LANGUAGE_NAME = {
		  'ar': 'العربية',
		  'be-tarask': 'Taraškievica',
		  'br': 'Brezhoneg',
		  'ca': 'Català',
		  'cs': 'Česky',
		  'da': 'Dansk',
		  'de': 'Deutsch',
		  'el': 'Ελληνικά',
		  'en': 'English',
		  'es': 'Español',
		  'fa': 'فارسی',
		  'fr': 'Français',
		  'he': 'עברית',
		  'hrx': 'Hunsrik',
		  'hu': 'Magyar',
		  'ia': 'Interlingua',
		  'is': 'Íslenska',
		  'it': 'Italiano',
		  'ja': '日本語',
		  'ko': '한국어',
		  'mk': 'Македонски',
		  'ms': 'Bahasa Melayu',
		  'nb': 'Norsk Bokmål',
		  'nl': 'Nederlands, Vlaams',
		  'oc': 'Lenga d\'òc',
		  'pl': 'Polski',
		  'pms': 'Piemontèis',
		  'pt-br': 'Português Brasileiro',
		  'ro': 'Română',
		  'ru': 'Русский',
		  'sc': 'Sardu',
		  'sk': 'Slovenčina',
		  'sr': 'Српски',
		  'sv': 'Svenska',
		  'ta': 'தமிழ்',
		  'th': 'ภาษาไทย',
		  'tlh': 'tlhIngan Hol',
		  'tr': 'Türkçe',
		  'uk': 'Українська',
		  'vi': 'Tiếng Việt',
		  'zh-hans': '簡體中文',
		  'zh-hant': '正體中文'
		};

/**
 * List of RTL languages.
 */
Code2.LANGUAGE_RTL = ['ar', 'fa', 'he'];

/**
 * Get the language of this user from the URL.
 * @return {string} User's language.
 */
Code2.getLang = function() {
  var lang = BlocklyDuino.getStringParamFromUrl('lang', '');
  if (Code2.LANGUAGE_NAME[lang] === undefined) {
    // Default to English.
    lang = 'en';
  }
  return lang;
};

/**
 * Is the current language (Code2.LANG) an RTL language?
 * @return {boolean} True if RTL, false if LTR.
 */
Code2.isRtl = function() {
  return Code2.LANGUAGE_RTL.indexOf(Code2.LANG) != -1;
};

/**
 * User's language (e.g. "en").
 * @type string
 */
Code2.LANG = Code2.getLang();

/**
 * Initialize the page language.
 */
Code2.initLanguage = function() {
  // Set the HTML's language and direction.
  var rtl = Code2.isRtl();
  $("html").attr('dir', rtl ? 'rtl' : 'ltr');
  $("html").attr('lang', Code2.LANG);

  // Sort languages alphabetically.
  var languages = [];
  for (var lang in Code2.LANGUAGE_NAME) {
    languages.push([Code2.LANGUAGE_NAME[lang], lang]);
  }
  var comp = function(a, b) {
    // Sort based on first argument ('English', 'Русский', '简体字', etc).
    if (a[0] > b[0]) return 1;
    if (a[0] < b[0]) return -1;
    return 0;
  };
  languages.sort(comp);

// Populate the language selection menu.
  var languageMenu = $('#languageMenu');
  languageMenu.empty();
  for (var i = 0; i < languages.length; i++) {
    var tuple = languages[i];
    var lang = tuple[tuple.length - 1];
    var option = new Option(tuple[0], lang);
    if (lang == Code2.LANG) {
      option.selected = true;
    }
    languageMenu.append(option);
  }

  // Inject language strings.
//modifOH
  $('#span_open').text(MSG2['span_open']);
  $('#span_save').text(MSG2['span_save']);
  $('#span_connect').text(MSG2['span_connect']);
  $('#openModalLabel').text(MSG2['openModalLabel']);
 	$('#saveModalLabel').text(MSG2['saveModalLabel']);
 	$('#save_comment').text(MSG2['save_comment']);
 	$('#saveIdName').text(MSG2['saveIdName']);
 	$('#deconnecteModalLabel').text(MSG2['deconnecteModalLabel']);
 	$('#connecteModalLabel').text(MSG2['connecteModalLabel']);
 	$('#errorModalLabel').text(MSG2['errorModalLabel']);
 	$('#txtLogout').text(MSG2['txtLogout']);
 	$('#txtNomU').text(MSG2['txtNomU']);
 	$('#txtPwdU').text(MSG2['txtPwdU']);
 	$('#btnSaveProj').text(MSG2['btnSaveProj']);
 	$('#span_saveXML').text(MSG2['span_saveXML']);
 	$('#span_fakeload').text(MSG2['span_fakeload']);
///modifOH
  
  $("xml").find("category").each(function() {
	// add attribute ID to keep categorie code
		if (!$(this).attr('id')) {
	$(this).attr('id', $(this).attr('name'));
	$(this).attr('name', Blockly.Msg[$(this).attr('name')]);
		}
  });

};

//Load FRENCH by default... This allow to have a definition for additionnal blocks messages in case another langage is chosen
//Load the Code demo's language strings.
//document.write('<script src="lang/msg/fr.js"></script>\n');
// Load Blockly's language strings.
//document.write('<script src="lang/Blockly/fr.js"></script>\n');
// Load Blockly@rduino specific block's language strings.
//document.write('<script src="lang/BlocklyArduino/fr.js"></script>\n');
// Load Supervision's language strings.
//document.write('<script src="lang/supervision/fr.js"></script>\n');

// And then load the choose langage
//Load the Code demo's language strings.
document.write('<script src="php/lang/msg/' + Code2.LANG + '.js"></script>\n');
