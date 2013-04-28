<?php
class LoaderFiles extends CComponent {
	private function preparDownloadDir(){
		if(!is_dir(Yii::app()->params['downloadPath']))
			mkdir(Yii::app()->params['downloadPath'],777, true);
	}

	public function zipFiles() {
		$this->preparDownloadDir();

		$zip = new ZipArchive();
		$uniqueId = time() . mt_rand(5, pow(2, 31) - 1);
		Yii::app()->session['idLastArchive'] = $uniqueId;
		$zipName = $uniqueId . '.zip';
		$filename = Yii::app()->params['downloadPath'] . $zipName;

		if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
			$event = new CEvent(null, array('error' => 'Can not open new zip archive ' . $filename ));
			$this->onError($event);
		}

		$filesInfo =  Yii::app()->session['filesInfo'];
		foreach($filesInfo as $idFile => $infoFile) {
			if(!file_exists(Yii::app()->params['uploadPath'] . $idFile)) {
				$event = new CEvent(null, array('error' => 'File ' . $idFile . ' not exists'));
				$this->onError($event);
			}

			if($zip->addFile(Yii::app()->params['uploadPath'] . $idFile,
			 $infoFile['filename'])  === false) {
				$event = new CEvent(null, array('error' => 'Can not add file to archive {' .
					$zip->getStatusString() . '}'));
				$this->onError($event);
			}
		}
		$zip->close();
		return $zipName;
	}

	public function onError($event) {
		$this->raiseEvent('onError', $event);
	}
}