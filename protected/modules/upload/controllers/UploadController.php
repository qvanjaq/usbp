<?php
class UploadController extends Controller
{
	const LOG_CATEGORY = 'upload.newUpload';

	public function actionFile()
	{
		if (!isset($_POST)) {
			$errMes = "No post request";
			$this->throwError($errMes);
		}

		if (isset($_POST['totalSize']) && isset($_POST['type']) && isset($_POST['fileName']) &&
				is_numeric($_POST['totalSize']) && is_numeric($_POST['idUpload'])) {
			$this->newUpload();
		} else if (isset($_POST['fileid']) && isset($_POST['token']) && is_numeric($_POST['fileid']) && preg_match('/[A-Za-z0-9]/', $_POST['token'])) {
			$this->mergeFiles();
		} else {
			if (isset($_GET['fileid']) && isset($_GET['token']) && isset($_GET['packet']) &&
					is_numeric($_GET['packet']) && is_numeric($_GET['fileid']) &&
					preg_match('/[A-Za-z0-9]/', $_GET['token'])) {
				$this->getPacket();
			}
		}
	}

	public function actionProcessTextFiles() {
		Yii::log('Begin process text files ', 'info', self::LOG_CATEGORY);
		if(!isset($_POST['fromEncode']) || !isset($_POST['toEncode'])) {
			$errMes = "Not set ecode data in post request";
			$this->throwError($errMes);
		}

		if(!isset($_POST['resultArchive'])) {
			$errMes = "Not set data in post 'resultArchive'";
			$this->throwError($errMes);
		}

		if(!in_array($_POST['resultArchive'], Yii::app()->params['resultArchive'])) {
			$errMes = "Not suppot  'resultArchive' " . $_POST['resultArchive'] . ' passed in post';
			$this->throwError($errMes);
		}

		$textProcessor = new TextFilesProcessor();
		$textProcessor->onError = array($this,'throwError');
		$textProcessor->onLogInfo = array($this,'logInfo');

		if($_POST['toEncode'] !== 'not') {
			$textProcessor->encodeFiles($_POST['fromEncode'], $_POST['toEncode']);
			$_POST['fromEncode'] = $_POST['toEncode'];
		}

		if(!empty($_POST['findText']) && isset($_POST['replaceText'])) {
			$textProcessor->findAndReplace($_POST['fromEncode'],
											$_POST['findText'],
										$_POST['replaceText']);
		}

		$loader = new LoaderFiles();
		$loader->onError = array($this,'throwError');

		if($_POST['resultArchive'] == 'zip') {
			$timeBegin = microtime(true);
			Yii::log('Begin zip files', 'info', self::LOG_CATEGORY);

			$zipName = $loader->zipFiles();
			$this->sendAsJSON(array(
				"action" => "new_download",
				"link" => basename(Yii::app()->params['downloadPath']) . '/' . $zipName
			));

			Yii::log('End zip files (time: ' . (microtime(true) - $timeBegin) . ' s)',
				'info', self::LOG_CATEGORY);
			CleanerFiles::clearAllUploadFiles(true);
		}

		Yii::log('Finish process text file ', 'info', self::LOG_CATEGORY);
	}

/*private function actionDelArchive() {
	$idArchive = Yii::app()->session['idLastArchive'];
	Yii::log('Begin delete archive ' . $idArchive, 'info', self::LOG_CATEGORY);
	$pathArchive = Yii::app()->params['downloadPath'] . $idArchivee;
	if(file_exists($pathArchive)) {
		if(!unlink($pathArchive)) {
			$errMes = "Unable to remove archive " . $idArchive;
			$this->throwError($errMes);
		}
	}
	Yii::log('End delete archive ' . $idArchive, 'info', self::LOG_CATEGORY);
}*/

public function throwError($e)
{
	$error = null;
	if($e instanceof CEvent)
		$error = $e->params['error'];
	else
		$error = $e;

	CleanerFiles::clearAllUploadFiles(true);
	Yii::log($error, 'error', self::LOG_CATEGORY);
    echo json_encode(array(
        "error" => $error
    ));
    exit;
}

public function logInfo($e) {
	Yii::log($e->params['info'], 'info', self::LOG_CATEGORY);
}

private function sendAsJSON($array)
{
	echo json_encode($array);
}

private function newUpload()
{
	Yii::log('Begin new upload file ', 'info', self::LOG_CATEGORY);
	CleanerFiles::clearAllUploadFiles();
	$uploader = new UploaderFiles();
	$uploader->onError = array($this,'throwError');
	$uploader->onLogInfo = array($this,'logInfo');
	$result = $uploader->initNewUpload($_POST['totalSize'], $_POST['type'], $_POST['fileName']);
    $this->sendAsJSON(array(
        "action" => "new_upload",
        "fileid" => $result['fileid'],
        "token"  => $result['token']
    ));
	Yii::log('End of begin upload files ' . $result['fileid'], 'info', self::LOG_CATEGORY);
}

/**
 * After initialized the upload, we can start receiving the packets (or 'slices')
 */
private function getPacket()
{
	Yii::log('Begin uploaded packet', 'info', self::LOG_CATEGORY);
	$uploader = new UploaderFiles($_GET['fileid'], $_GET['token']);
	$uploader->onError = array($this,'throwError');
	$uploader->writePacket($_GET['packet']);
	$this->sendAsJSON(array(
		"action" => "new_packet",
		"result" => "success",
		"packet" => $_GET['packet'],
	));
	Yii::log('End uploaded packet ' . $_GET['packet'] . ' in file ' . $_GET['fileid'], 'info', self::LOG_CATEGORY);
}

private function mergeFiles()
{
	Yii::log('Begin merge uploaded file', 'info', self::LOG_CATEGORY);
	$uploader = new UploaderFiles($_POST['fileid'], $_POST['token']);
	$uploader->onError = array($this,'throwError');
	$uploader->onLogInfo = array($this,'logInfo');
	$totalPackages = $uploader->mergeFiles();
	CleanerFiles::clearUploadPackages($totalPackages, $_POST['fileid']);
    $this->sendAsJSON(array(
        "action" => "complete",
        "file" => $_POST['fileid']
    ));
	Yii::log('End merge uploaded file ' . $_POST['fileid'], 'info', self::LOG_CATEGORY);
}
}