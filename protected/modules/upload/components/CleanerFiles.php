<?php
class CleanerFiles extends CComponent {
	const LOG_CATEGORY = 'cleaner';
	static public function clearAllUploadFiles($forceClear = false) {
		if(is_numeric(Yii::app()->session['idUpload'])) {
			if($forceClear || (Yii::app()->session['idUpload'] !== floatval($_POST['idUpload']))) {
				Yii::log('Begin clear upload files', 'info', self::LOG_CATEGORY);
				$filesInfo = Yii::app()->session['filesInfo'];
				if(!empty($filesInfo)){
					foreach($filesInfo as $idFile => $info) {
						self::clearUploadFiles($idFile);
					}
				}
				Yii::app()->session['filesInfo'] = null;
				Yii::log('End clear upload files', 'info', self::LOG_CATEGORY);
			}
		}

		if(isset($_POST['idUpload']))
			Yii::app()->session['idUpload'] = floatval($_POST['idUpload']);
		else
			Yii::app()->session['idUpload'] = null;
	}

	static public function clearUploadFiles($fileId, $handle=null) {
		list($fileSize, $fileType, $fileName) = explode("|", $_SESSION['filesInfo'][$fileId]['fileData']);
		$totalPackages = ceil($fileSize / Yii::app()->params['packetSize']);

		if(isset($handle) && $handle !== false){
			fclose($handle);
		}

		$pathMergeFile = Yii::app()->params['uploadPath'] . $fileId;
		if(file_exists($pathMergeFile)) {
			if(!unlink($pathMergeFile)) {
				$error = "Unable to remove file " . $fileId;
				Yii::log($error, 'error', self::LOG_CATEGORY);
			}
		}

		self::clearUploadPackages($totalPackages, $fileId);
	}

	static public function clearUploadPackages($totalPackages, $fileId){
		for ($package = 0; $package < $totalPackages; $package++) {
			$pathPacket =  Yii::app()->params['uploadPath'] . $fileId . "-" . $package;
			if(file_exists($pathPacket)) {
				if (!unlink($pathPacket)) {
					$error = "Unable to remove package #" . $package;
					Yii::log($error, 'error', self::LOG_CATEGORY);
				}
			}
		}
	}
}