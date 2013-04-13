$(function() {
	function initTextFilesUI() {
		$('#typeUpload').buttonset();
		$('#preview,#submit').button();
		$( "#filesProgress" ).progressbar({
			value: false
		 });
	}

	initTextFilesUI();
 });
