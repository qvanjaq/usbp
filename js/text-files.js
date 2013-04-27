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
		$('#submit').click(textFilesProcess);
	}

	function progressHandler() {

	}

	function textFilesPreviw() {

	}

	function textFilesProcess() {
		var idUpload = getRandom(1, Number.MAX_VALUE);
		var filesForm = $('#files')[0].files;
		var options = {idUpload: idUpload,
						size : packetSize,
			progressHandler : progressHandler,
					destination : URL_UPLOAD_FILE,
					reconnectionTimeout : reconnectionTimeout};

		var uploader = new Uploader(filesForm, finUploadCallback, options);
		uploader.upload();
	}

	function finUploadCallback() {
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
				$('#loader').remove();
				var link = response.link;
				var iframe = document.getElementById("iframe");
				iframe.style.display = "none";
				iframe.src = link;
				document.getElementById("main").appendChild(iframe);
			}
		});

		request.fail(function(jqXHR, textStatus) {
		  alert( "Request failed: ");
		});
	}

	function reportServerDelArchive(){
		alert('finisd download');
		var request = $.ajax({
		  url: URL_DEL_ARCHIVE,
		  type: "POST"
		});
	}

	initTextFilesUI();
	initTextFilesBehaviour();
});

function test() {
	alert('Yiii');
}