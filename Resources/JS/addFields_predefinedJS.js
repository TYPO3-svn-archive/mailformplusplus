/*
 * Adds onchange listener on the drop down menu "predefined".
 * If the event is fired and old value was ".default", then empty some fields.
 * 
 * $Id: $
 */

Event.observe(window, 'load', function() {
	var templateFileName = 'data[tt_content][' + uid + '][pi_flexform][data][sDEF][lDEF][template_file][vDEF]_list';
	var langFileName = 'data[tt_content][' + uid + '][pi_flexform][data][sDEF][lDEF][lang_file][vDEF]_list';
	var predefinedName = 'data[tt_content][' + uid + '][pi_flexform][data][sDEF][lDEF][predefined][vDEF]';
	var requiredFieldsName = 'data[tt_content][' + uid + '][pi_flexform][data][sMISC][lDEF][required_fields][vDEF]_hr';

	// Initializes variables
	var templateFile, langFile, predefined, requiredFields;

	// Searches <select> reference
	$$('#' + flexformBoxId + ' select').each(function(element){
		switch(element.readAttribute('name')) {
			case templateFileName :
				templateFile = element;
				break;
			case langFileName :
				langFile = element;
				break;
			case predefinedName :
				predefined = element;
				break;
			default:
				break;
		}
	});

	// Searches <input> reference
	$$('#' + flexformBoxId + ' input').each(function(element){
		switch(element.readAttribute('name')) {
			case requiredFieldsName :
				requiredFields = element;
				break;
			default:
				break;
		}
	});

	// Handles the even change
	Event.observe(predefined, 'change', function(){
		if (this.value != 'default.') {
			if (typeof(templateFile.options[0]) != 'undefined' && templateFile.options[0].value.search('EXT:mailformplusplus/Examples/Default/') > -1) {
				templateFile.removeChild(templateFile.options[0]);
			}
			if (typeof(langFile.options[0]) != 'undefined' && langFile.options[0].value.search('EXT:mailformplusplus/Examples/Default/') > -1) {
				langFile.removeChild(langFile.options[0]);
			}
			if (requiredFields.value == 'firstname, lastname, email') {
				requiredFields.value = '';
			}
		}
	});
});
