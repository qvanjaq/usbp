$(function(){
	function initTextFilesUI() {
		$('#typeUpload').buttonset();
		$('#preview,#submit,#typePopup').button();
		$( "#filesProgress" ).progressbar({
			value: false
		 });
	}
	function initTextFilesBehaviour() {
		$('#typePopup').click(showUploadPopup);
		$('#preview').click(textFilesPreviw);
	}

	function progressHandler() {

	}
		//var a = null; a.toString();
	function textFilesPreviw() {
		var idUpload = getRandom(1, Number.MAX_VALUE);
		var filesForm = $('#files')[0].files;
		var options = {idUpload: idUpload,
						size : packetSize,
			progressHandler : progressHandler,
					destination : URL_UPLOAD_FILE,
					reconnectionTimeout : reconnectionTimeout};

		for(var i = 0; i < filesForm.length; i++) {
			options.file = filesForm[i];
			new jsUpload(options);
		}
	}

	initTextFilesUI();
	initTextFilesBehaviour();
});