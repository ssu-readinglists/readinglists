var refshareDialog = {
	init : function() {
		var f = document.forms[0];

	},
	insert : function() {
		if(!document.forms[0].f_refshare.value) {
			alert("You must enter a RefWorks RSS feed URL");
			document.forms[0].f_refshare.focus();
			return false;
		}
		var strURL = document.forms[0].f_refshare.value;
		if (strURL.substring(strURL.length-3).toLowerCase() != 'rss') {
			alert('RefWorks RSS feeds always end with rss. Please check your URL');
			return false;
		}
		// Insert the contents from the input into the document
		var strURL = document.forms[0].f_refshare.value;
		var strStyle = document.forms[0].f_style.value;
		var strHead = "<h3 class=\"rl-section-heading\">" + document.forms[0].f_rlsecheading.value + "</h3>";
		var strNotes = "<div class=\"rl-section-notes\">" + document.forms[0].f_rlsecnotes.value + "</div><br>";
		var refshareString = strHead + strNotes + strURL + "#" + strStyle;
	  
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, refshareString);
		tinyMCEPopup.close();
	},
	check : function() {
		
	}
};

tinyMCEPopup.onInit.add(refshareDialog.init, refshareDialog);
