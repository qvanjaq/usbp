function Uploader(files, selectorProgress, uplFinCallback, options) {
	var parentSelf = this;

	function init() {
		parentSelf.files = files;
		parentSelf.progress = $(selectorProgress);
		parentSelf.options = options;
		parentSelf.uplFinCallback = uplFinCallback;
		parentSelf.statusUpload = [];
		parentSelf.sumFullSize = 0;
		parentSelf.sumLoadedSize = 0;
		for(var i = 0; i < parentSelf.files.length; i++) {
			parentSelf.statusUpload[i] = 0;
			parentSelf.sumFullSize += parentSelf.files[i].size;
		}
		options.progressHandler = progressHandler;
		parentSelf.progress.show();
	}

	function progressHandler(loadedBytes) {
		parentSelf.sumLoadedSize += loadedBytes;
		var percent = parentSelf.sumLoadedSize / parentSelf.sumFullSize * 100;
		parentSelf.progress.progressbar('value', percent);
	}

	this.upload = function() {
		localStorage.clear();
		var options = parentSelf.options;
		for(var i = 0; i < parentSelf.files.length; i++) {
			options.file = parentSelf.files[i];
			options.id = i;
			new Upload(options, parentSelf);
		}
	};

	init();
}