var DEBUG_MODE = 1;

window.onerror = function(message, source, line) {
	logError({'message' : message, 'source' : source, 'line' : line});
	return true;
};

function logError(info){
	if(!DEBUG_MODE) {
		// save error in server log
		var message = renderLogError(info);

		var request = $.ajax({
		  type: "POST",
		  url: URL_LOG,
		  data: {'message' : message}
		});

		request.success(function(msg) {
		});

		request.fail(function(jqXHR, textStatus) {
		});
	}
}

function renderLogError(info) {
	// catchError
	var result = '';
	if(info['catchError'] != undefined) {
		var catchError = info['catchError'];
		result += 'Exception information\n';
		result += 'name: ' + catchError.name + '\n';
		result += 'message: ' + catchError.message + '\n';
		result += 'stack: ' + catchError.stack + '\n';
	} 	else if(info['message'] != undefined &&
				info['source'] != undefined &&
				info['line'] != undefined) {
		result += 'Event window.onerror information\n';
		result += 'message: ' + info['message'] + '\n';
		result += 'source: ' + info['source'] + '\n';
		result += 'line: ' + info['line'] + '\n';
	}
	return result;
}


