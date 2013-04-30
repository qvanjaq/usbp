$(function(){
	function initTextFilesUI() {
		$('#preview,#submit,#typePopup,#typeUpload').button();
		$( "#filesProgress" ).progressbar({
			value: 0
		 }).hide();
	}
	function initTextFilesBehaviour() {
		$('#typePopup').click(showUploadPopup);
		$('#preview').click(textFilesPreviw);
		$('#submit').click(textFilesProcess);
	}

	function textFilesPreviw() {

	}

	function textFilesProcess() {
		var idUpload = getRandom(1, Number.MAX_VALUE);
		var filesForm = $('#files')[0].files;
		var options = {idUpload: idUpload,
						size : packetSize,
					destination : URL_UPLOAD_FILE,
					reconnectionTimeout : reconnectionTimeout};

		var uploader = new Uploader(filesForm, '#filesProgress', finUploadCallback, options);
		uploader.upload();
	}

	function finUploadCallback() {
		$('#filesProgress').find('.progress-label').text('Files processing');
		var hideProgressTime = 1500;
		// send data to server
		var data = {fromEncode : $('#filesEncoding').val(),
					toEncode : $('#optionToEncoding').val(),
					resultArchive : $('#resultArchive').val(),
					findText : $('#findText').val(),
					replaceText : $('#replaceText').val()};
		var request = $.ajax({
		  url: URL_PROCESS_TEXT_FILE,
		  type: "POST",
		  data: data
		});

		request.done(function(data) {
			var response = JSON.parse(data);
			if(response.action === 'new_download') {
				downloadFile(response.link);
			}
			$('#filesProgress').fadeOut(hideProgressTime);
		});

		request.fail(function(jqXHR, textStatus) {
		  alert( "Request failed: ");
		});
	}

	initTextFilesUI();
	initTextFilesBehaviour();
});