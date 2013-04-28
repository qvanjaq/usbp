<?php
class UploaderFiles extends CComponent {
	private $fileId,
			$token;

	function __construct($fileId = null, $token = null) {
		$this->fileId = $fileId;
		$this->token = $token;
	}

	private function rowExists() {
		$rowExists = isset(Yii::app()->session['filesInfo'][$this->fileId]) &&
					Yii::app()->session['filesInfo'][$this->fileId]['token'] == $this->token;
		return $rowExists;
	}

	private function prepareUploadDir(){
		if(!is_dir(Yii::app()->params['uploadPath']))
			mkdir (Yii::app()->params['uploadPath'],777, true);
	}

	public function initNewUpload($totalSize, $type, $fileName) {
		$fileData = $totalSize . "|" . preg_replace('/[^A-Za-z0-9\/]/', '', $type) . "|" . preg_replace('/[^A-Za-z0-9_\.]/', '', $fileName);
		$originalFileName = $fileName;
		$token 	  = md5($fileData);
		$fileid   = time() . mt_rand(5, pow(2, 31) - 1);

		if(!is_array(Yii::app()->session['filesInfo'])) {
			$event = new CEvent(null, array('info' => 'Set session["filesInfo"] to empty'));
			$this->onLogInfo($event);
			Yii::app()->session['filesInfo'] = array();
		}

		Yii::app()->session['filesInfo'] = array_merge(Yii::app()->session['filesInfo'],
															array($fileid => array('fileData' => $fileData,
																'token' => $token,
																'filename' => $originalFileName)));
		return array('fileid' => $fileid, 'token' => $token);
	}

	public function mergeFiles() {
		if (!$this->rowExists()) {
			$event = new CEvent(null, array('error' => "No file found in the storage for the provided ID / token"));
			$this->onError($event);
		}

		// check if we the file has already been uploaded, merged and completed
		if (!file_exists(Yii::app()->params['uploadPath'] . $this->fileId)) {
			$this->prepareUploadDir();

			$fileData = Yii::app()->session['filesInfo'][$this->fileId]['fileData'];
			list($fileSize, $fileType, $fileName) = explode("|", $fileData);
			$totalPackages = ceil($fileSize / Yii::app()->params['packetSize']);

			// check that all packages exist
			for ($package = 0; $package < $totalPackages; $package++) {
				if (!file_exists(Yii::app()->params['uploadPath'] . $this->fileId . "-" . $package)) {
					$event = new CEvent(null, array('error' => "Missing package #" . $package));
					$this->onError($event);
				}
			}

			// open file to create final file
			if (!$handle = fopen(Yii::app()->params['uploadPath'] . $this->fileId, 'w')) {
				$event = new CEvent(null, array('error' => "Unable to create new file for merging"));
				$this->onError($event);
			}

			// write each package to the file
			for ($package = 0; $package < $totalPackages; $package++) {
				// file_get_contets can return empty string, if user disconnect hard drive
				$contents = file_get_contents(Yii::app()->params['uploadPath'] . $this->fileId . "-" . $package);
				if (!$contents) {
					$event = new CEvent(null, array('error' => "Unable to read contents of package #" . $package));
					$this->onError($event);
				}

				if (fwrite($handle, $contents) === FALSE) {
					$event = new CEvent(null, array('error' => "Unable to write package #" . $package . " to merge"));
					$this->onError($event);
				}
			}

			return $totalPackages;
		}
	}

	public function writePacket($packet) {
		$rowExists = $this->rowExists();

		if($rowExists) {
			$this->prepareUploadDir();

			if (!$handle = fopen(Yii::app()->params['uploadPath'] . $this->fileId . "-" . $packet, 'w')) {
				$event = new CEvent(null, array('error' => "Unable to open package handle"));
				$this->onError($event);
			}

			if (fwrite($handle, $GLOBALS['HTTP_RAW_POST_DATA']) === FALSE) {
				$event = new CEvent(null, array('error' => "Unable to write to package #" . $packet));
				$this->onError($event);
			}
			fclose($handle);
		} else {
			$event = new CEvent(null, array('error' => "No file found in the storage for the provided ID / token"));
			$this->onError($event);
		}
	}

	public function onError($event) {
		$this->raiseEvent('onError', $event);
	}

	public function onLogInfo($event) {
		$this->raiseEvent('onLogInfo', $event);
	}
}