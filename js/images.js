$(function() {
	function initImagesUI() {
		$('#preview,#submit,#typePopup,#typeUpload').button();
		$('#filesProgress').progressbar({
			value: 0
		 }).hide();
		 $('#quality').slider({'min' : 1, 'max' : 100, 'value' : 100});
	}
	initImagesUI();
});