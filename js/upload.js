/**
 * The core upload function. The way it works is very simple: slice the file on client side, sends the slices
 * to the server. When no more slices remain the server merges the slices (or packets). This function continuously stores
 * the current packages' number in the localStorage, so we can pause the upload anytime,
 * then continue upload from the latest package.
 * @param {Object} options
 * @param options.file The current File object
 * @parma option.size Size of each packet
 * @param options.logger Logger element, where we write the logs.
 * @param options.progressHandler Function that updates the progressbar value, at percent 100 it shows the 'success image'
 * @param option.destination Address server receiver
 */
function Upload(options, parentSelf){
    var packetSize,
	activeReconnectError = false,
	activeReconnectTimeout = false,
    self = this;

    options.logger = options.logger || function(msg){
        console.log(msg);
    };

    function init(){
		log('File uploader initialized');
        self.file = options.file;
        self.idUpload = options.idUpload;
        self.id = options.id;
		self.totalSize = self.file.size;
        self.url = options.destination;
        self.type = self.file.type;
        self.fileName = self.file.name;
        self.packetSize = options.size || 512 * 512; // bytes, defaults to 256Kb packets
        self.fileId = self.fileName+"|"+self.type+"|"+self.totalSize;
        self.totalPackages = Math.ceil(self.totalSize/self.packetSize);
        log('Total size: ' + self.totalSize/(1024*1024) + " mb, total of " + self.totalPackages + " packets");
        self.fileDetails = getFile(self.fileId);
		self.reconnectionTimeout = options.reconnectionTimeout;
    }

    /**
     * Checks whether the fileId exists in the localStorage, if yes then we continue uploading from the
     * last uploaded package number.
     * Else, if the dropped fileId does not exists in the localStorage then initialize a new upload
     * @param {String} fileId
     */
    function getFile(fileId){
        log('Checking whether to resume upload');
        var fileData = localStorage[fileId];
        if (fileData){
            var  fileParts = fileData.split("|");
            log ('Resuming upload from package '+(parseInt(fileParts[2])+1));
            setDetails({
                fileId:fileParts[0],
                token:fileParts[1],
                currentPackage:fileParts[2]
            });
        }else{
            log ('No upload to resume, informing server to initialize a new upload');
            // submit file information to server
            var formData = new FormData();
            formData.append('totalSize', self.totalSize);
            formData.append('type', self.type);
            formData.append('fileName', self.fileName);
			formData.append('idUpload', self.idUpload);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', self.url, true);
            xhr.onload = function(e) {
                var response = JSON.parse(xhr.responseText);
                if (response.action=="new_upload"){
                    log ('New upload initialized with ID '+response.fileid+' and token '+response.token);
                    setDetails(setFile(fileId,response.fileid,response.token));
                }
            };
            xhr.send(formData);
        }
    }


    /**
     * Write to localStorage
     * @param {String}  fileId  "fname" + "ftype" + "ftotalSize"
     * @param {Number}  serverFileId  a numeric value, get form server (will be the name of the file on the server)
     * @param {String}  packageId  md5() hash of the package, get from server
     * @returns {Object}  Deatils of the package being uploaded
     */
    function setFile(fileId,serverFileId,token,packageId){
        packageId = packageId || 0;
        localStorage[fileId] = serverFileId+"|"+token+"|"+packageId;
        return {
            fileId:serverFileId,
            token:token,
            currentPackage:packageId
        };
    }

    /**
     * If we haven't uploaded all the packages yet, then upload the current package, else informing
     * the server to merge the packets (or slices) on the server side
     * @param {Object} file details from setFile()
     */
    function setDetails(details){
        self.fileDetails = details;

        if (self.fileDetails.currentPackage<self.totalPackages){
            log ('Uploading packet '+(parseInt(self.fileDetails.currentPackage)+1)+' out of '+self.totalPackages);

            uploadPacket(getPacket(self.fileDetails.currentPackage));
        }else{
            // finished uploading data, let's close up the file on the server
            log('Finished uploading, informing the server.');
			parentSelf.progress.progressbar({value : false}).find('.progress-label').text('Preparation of file processing');
            var formData = new FormData();
            formData.append('fileid', self.fileDetails.fileId);
            formData.append('token', self.fileDetails.token);


            var xhr = new XMLHttpRequest();
            xhr.open('POST', self.url, true);
            xhr.onload = function(e) {
                var response = JSON.parse(xhr.responseText);
                if (response.action=="complete"){
                    log ('New upload completed, file: '+response.file);

					//last parameter is 'alldone' plus the timestamp
					var currTimeStamp = Math.round(new Date().getTime() / 1000);
					setFile(self.fileId, self.fileDetails.fileId, self.fileDetails.token, 'alldone|' + currTimeStamp);
					checkAllUploads();
                } else{
					localStorage.removeItem(self.fileId)
				}
            };
            xhr.send(formData);

        }
    }

	function checkAllUploads() {
		// set flag upload finish
		parentSelf.statusUpload[self.id] = 1;
		var allFinish = 1;
		for(var i = 0; i < parentSelf.statusUpload.length; i++) {
			if(parentSelf.statusUpload[i] == 0) {
				allFinish = 0;
				break;
			}
		}
		if(allFinish)
			parentSelf.uplFinCallback();
	}

    function log(message){
        options.logger(message);
    }

    /**
     * Log the success. Then initiate the next packet upload (as long as the pause button is not pressed)
     */
    function updateDetails(details){
        log('Finished uploading package '+(1 + parseInt(details.currentPackage)));
        details.currentPackage++;
        var fileDetails = setFile(self.fileId,details.fileId,details.token,details.currentPackage);

		setDetails(fileDetails);
    }

   	/**
   	 * Return the proper slice (packet)
   	 * @param {Number} packetId
   	 * @returns {Blob} Returns a new Blob object containing the data in the specified range of bytes
   	 */
    function getPacket(packetId){
        var startByte = packetId  * self.packetSize,
        endByte = startByte+self.packetSize,
        packet;
        packet = self.file.slice(startByte, endByte);
        return packet;
    }

    function uploadPacket(packet){
        var xhr = new XMLHttpRequest();
        var url = self.url + "&fileid="+self.fileDetails.fileId+"&token="+self.fileDetails.token+"&packet="+self.fileDetails.currentPackage;
        var fileDetails = self.fileDetails;
        xhr.open('POST', url, true);

		options.progressHandler(packet.size);
        /*xhr.onprogress = function(e){
			options.progressHandler(e.position);
        };*/

		// Multiple handle disconnect for different browsers.
		// Use variable activeReconnect to prevent state, when browser
		// generate multiple event(onerror and ontimeout).
        xhr.onerror = function(e){
			if(self.activeReconnectTimeout) return;
			self.activeReconnectError = true;
            setTimeout(function(){
				uploadPacket(packet);
			}, self.reconnectionTimeout);
        };

		/**
		 * If the server uploaded the packet successfully, then go to updateDetails() where log the success,
		 * and initiate uploading the next package (if we not paused while it uploaded)
		 */
		xhr.onreadystatechange = function (e) {
		  if (xhr.readyState == 4) {
			try
			{
				var response = JSON.parse(xhr.responseText);
				if (response.action=="new_packet" && response.result=="success"){
					updateDetails(fileDetails);
				}
			}
			catch(e)
			{
			   log('Invalid JSON in readyState 4.');
			}
		  }
		};

        xhr.send(packet);
    }

    init();
}