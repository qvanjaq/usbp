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

	const NL_NIX = "\n";
	const NL_WIN = "\r\n";
	const NL_MAC = "\r";

	private function newlineType($string){
	  if(mb_strpos($string, self::NL_WIN ) !== false){
		return self::NL_WIN;
	  }elseif(mb_strpos($string, self::NL_MAC ) !== false){
		return self::NL_MAC;
	  }elseif(mb_strpos($string, self::NL_NIX ) !== false){
		return self::NL_NIX;
	  } return false;
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

		if($_POST['toEncode'] !== 'not') {
			// validate encoding
			$encodList = mb_list_encodings();
			if(!in_array($_POST['fromEncode'], $encodList)) {
				$errMes = "Not support encode pass for 'fromEncode' " . $_POST['fromEncode'];
				$this->throwError($errMes);
			}

			if(!in_array($_POST['toEncode'], $encodList)) {
				$errMes = "Not support encode pass for 'toEncode' " . $_POST['toEncode'];
				$this->throwError($errMes);
			}

			// change encoding
			foreach(Yii::app()->session['filesInfo'] as $fileid => $infoFile) {
				$contents = file_get_contents(Yii::app()->params['uploadPath'] . $fileid);
				if (!$contents) {
					$this->clearUploadFiles($fileid);
					$errMes = "Unable to read contents of file " . $fileid;
					$this->throwError($errMes);
				}
				Yii::log('Convert file ' . $fileid . ' from ' . $_POST['fromEncode'] . ' to ' .
						$_POST['toEncode'], 'info', self::LOG_CATEGORY);
				$contents = mb_convert_encoding($contents, $_POST['toEncode'], $_POST['fromEncode']);
				$resultWrite = file_put_contents(Yii::app()->params['uploadPath'] . $fileid, $contents);
				if($resultWrite === false) {
					$this->clearUploadFiles($fileid);
					$errMes = "Unable to write contents to file " . $fileid . ' after change encoding';
					$this->throwError($errMes);
				}
			}
		}

		if(!empty($_POST['findText']) && isset($_POST['replaceText'])) {
			// find and replace text in files
			mb_regex_encoding('UTF-8');

			$_POST['findText'] = preg_quote($_POST['findText']);
			$regularFind = mb_ereg_replace('(\\r\\n)|(\\r)|(\\n)', '[\\s]+', $_POST['findText']);
			Yii::log('Regular fo find text ' . $regularFind, 'info', self::LOG_CATEGORY);

			foreach(Yii::app()->session['filesInfo'] as $fileid => $infoFile) {
				$contents = file_get_contents(Yii::app()->params['uploadPath'] . $fileid);

				$typeNewLinteReplace = $this->newlineType($contents);
				if($typeNewLinteReplace !== false)
					$_POST['replaceText'] = mb_ereg_replace('(\\r\\n)|(\\r)|(\\n)',
																$typeNewLinteReplace,
															$_POST['replaceText']);

				if (!$contents) {
					$this->clearUploadFiles($fileid);
					$errMes = "Unable to read contents of file " . $fileid;
					$this->throwError($errMes);
				}


				$contentsEncoded = mb_convert_encoding($contents, mb_regex_encoding(), $_POST['fromEncode']);
				$contentsChanged = mb_ereg_replace ($regularFind, $_POST['replaceText'], $contentsEncoded, 'm');
				$contentsResult = mb_convert_encoding($contentsChanged, $_POST['fromEncode'], mb_regex_encoding());

				if($contentsEncoded == $contentsChanged) {
					// if not changes, may be posted
					// incorrect encode, we reverse result
					Yii::log('Not one result found', 'info', self::LOG_CATEGORY);
					$contentsResult	= $contents;
				}

				$resultWrite = file_put_contents(Yii::app()->params['uploadPath'] . $fileid, $contentsResult);
				if($resultWrite === false) {
					$this->clearUploadFiles($fileid);
					$errMes = "Unable to write contents to file " . $fileid . ' after change encoding';
					$this->throwError($errMes);
				}
			}
		}

		// return archive
		$this->preparDownloadDir();
		if($_POST['resultArchive'] == 'zip') {
			$timeBegin = microtime(true);
			Yii::log('Begin zip files', 'info', self::LOG_CATEGORY);
			$zip = new ZipArchive();
			$uniqueId = time() . mt_rand(5, pow(2, 31) - 1);
			Yii::app()->session['idLastArchive'] = $uniqueId;
			$zipName = $uniqueId . '.zip';
			$filename = Yii::app()->params['downloadPath'] . $zipName;
			if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
				$errMes = 'Can not open new zip archive ' . $filename ;
				$this->clearUploadFiles($idFile);
				$this->throwError($errMes);
			}
			$filesInfo =  Yii::app()->session['filesInfo'];
			foreach($filesInfo as $idFile => $infoFile) {
				if(!file_exists(Yii::app()->params['uploadPath'] . $idFile)) {
					$this->clearUploadFiles($idFile);
					//unlink($filename);
					$errMes = 'File ' . $idFile . ' not exists';
					$this->throwError($errMes);
				}

				if($zip->addFile(Yii::app()->params['uploadPath'] . $idFile,
				 $infoFile['filename'])  === false) {
					$this->clearUploadFiles($idFile);
					//unlink($filename);
					$errMes = 'Can not add file to archive {' . $zip->getStatusString() . '}';
					$this->throwError($errMes);
				}
			}
			$zip->close();
			Yii::log('End zip files (time: ' . (microtime(true) - $timeBegin) . ' s)',
					'info', self::LOG_CATEGORY);

			$this->sendAsJSON(array(
				"action" => "new_download",
				"link" => //ltrim(Yii::app()->request->baseUrl, '/\\') . '/' .
					basename(Yii::app()->params['downloadPath']) . '/' . $zipName
			));

			$this->clearAllUploadFiles(true);
		}

        /*$this->sendAsJSON(array(
            "action" => "processed",
            "result" => "success",
        ));*/
		Yii::log('Finish process text file ', 'info', self::LOG_CATEGORY);
	}

	private function actionDelArchive() {
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
	}

private function throwError($error)
{
	Yii::log($error, 'error', self::LOG_CATEGORY);
    echo json_encode(array(
        "error" => $error
    ));
    exit;
}

private function sendAsJSON($array)
{
	echo json_encode($array);
}

private function newUpload()
{
	Yii::log('Begin new upload file ', 'info', self::LOG_CATEGORY);

	$this->clearAllUploadFiles();

    $fileData = $_POST['totalSize'] . "|" . preg_replace('/[^A-Za-z0-9\/]/', '', $_POST['type']) . "|" . preg_replace('/[^A-Za-z0-9_\.]/', '', $_POST['fileName']);
    $originalFileName = $_POST['fileName'];
    $token 	  = md5($fileData);
	$fileid   = time() . mt_rand(5, pow(2, 31) - 1);

	if(!is_array(Yii::app()->session['filesInfo'])) {
		$dump = print_r (Yii::app()->session, true);
		Yii::log('Set session["filesInfo"] to empty ' . $dump, 'info', self::LOG_CATEGORY);
		Yii::app()->session['filesInfo'] = array();
	}
	Yii::app()->session['filesInfo'] = array_merge(Yii::app()->session['filesInfo'],
														array($fileid => array('fileData' => $fileData,
															'token' => $token,
															'filename' => $originalFileName)));
    $this->sendAsJSON(array(
        "action" => "new_upload",
        "fileid" => $fileid,
        "token"  => $token
    ));
	Yii::log('End of begin upload files ' . $fileid, 'info', self::LOG_CATEGORY);
}

private function clearAllUploadFiles($forceClear = false) {
	if(is_numeric(Yii::app()->session['idUpload'])) {
			if($forceClear || (Yii::app()->session['idUpload'] !== floatval($_POST['idUpload']))) {
				Yii::log('Begin clear upload files', 'info', self::LOG_CATEGORY);
				$filesInfo = Yii::app()->session['filesInfo'];
				foreach($filesInfo as $idFile => $info) {
					$this->clearUploadFiles($idFile);
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

private function prepareUploadDir(){
	if(!is_dir(Yii::app()->params['uploadPath']))
		mkdir (Yii::app()->params['uploadPath'],777, true);
}

private function preparDownloadDir(){
	if(!is_dir(Yii::app()->params['downloadPath']))
		mkdir (Yii::app()->params['downloadPath'],777, true);
}

private function mergeFiles()
{
	Yii::log('Begin merge uploaded file', 'info', self::LOG_CATEGORY);
    if (!$this->rowExists($_POST['fileid'], $_POST['token'])) {
		$errMes = "No file found in the storage for the provided ID / token";
        $this->throwError($errMes);
    }

    // check if we the file has already been uploaded, merged and completed
    if (!file_exists(Yii::app()->params['uploadPath'] . $_POST['fileid'])) {
		$this->prepareUploadDir();

		$fileData = Yii::app()->session['filesInfo'][$_POST['fileid']]['fileData'];
        list($fileSize, $fileType, $fileName) = explode("|", $fileData);
        $totalPackages = ceil($fileSize / Yii::app()->params['packetSize']);

        // check that all packages exist
        for ($package = 0; $package < $totalPackages; $package++) {
            if (!file_exists(Yii::app()->params['uploadPath'] . $_POST['fileid'] . "-" . $package)) {
				$this->clearUploadFiles($_POST['fileid']);
				$errMes = "Missing package #" . $package;
                $this->throwError($errMes);
            }
        }

        // open file to create final file
        if (!$handle = fopen(Yii::app()->params['uploadPath'] . $_POST['fileid'], 'w')) {
			$this->clearUploadFiles($_POST['fileid'], $handle);
			$errMes = "Unable to create new file for merging";
			$this->throwError($errMes);
        }

        // write each package to the file
        for ($package = 0; $package < $totalPackages; $package++) {
			// file_get_contets can return empty string, if user disconnect hard drive
            $contents = file_get_contents(Yii::app()->params['uploadPath'] . $_POST['fileid'] . "-" . $package);
            if (!$contents) {
				$this->clearUploadFiles($_POST['fileid'], $handle);
				$errMes = "Unable to read contents of package #" . $package;
				$this->throwError($errMes);
            }

            if (fwrite($handle, $contents) === FALSE) {
				$this->clearUploadFiles($_POST['fileid'], $handle);
				$errMes = "Unable to write package #" . $package . " to merge";
				$this->throwError($errMes);
            }
        }

		$this->clearUploadPackages($totalPackages, $_POST['fileid']);
    }

    $this->sendAsJSON(array(
        "action" => "complete",
        "file" => $_POST['fileid']
    ));
	Yii::log('End merge uploaded file ' . $_POST['fileid'], 'info', self::LOG_CATEGORY);
}

private function clearUploadFiles($fileId, $handle=null) {
	list($fileSize, $fileType, $fileName) = explode("|", $_SESSION['filesInfo'][$fileId]['fileData']);
	$totalPackages = ceil($fileSize / Yii::app()->params['packetSize']);

	if(isset($handle) && $handle !== false){
		fclose($handle);
	}

	$pathMergeFile = Yii::app()->params['uploadPath'] . $fileId;
	if(file_exists($pathMergeFile)) {
		if(!unlink($pathMergeFile)) {
			$errMes = "Unable to remove file " . $fileId;
			$this->throwError($errMes);
		}
	}

	$this->clearUploadPackages($totalPackages, $fileId);
}

private function clearUploadPackages($totalPackages, $fileId){
	// remove the packages
	for ($package = 0; $package < $totalPackages; $package++) {
		$pathPacket =  Yii::app()->params['uploadPath'] . $fileId . "-" . $package;
		if(file_exists($pathPacket)) {
			if (!unlink($pathPacket)) {
				$errMes = "Unable to remove package #" . $package;
				$this->throwError($errMes);
			}
		}
	}
}
private function rowExists($fileid, $token) {
	$rowExists = isset(Yii::app()->session['filesInfo'][$fileid]) &&
				Yii::app()->session['filesInfo'][$fileid]['token'] == $token;
	return $rowExists;
}
/**
 * After initialized the upload, we can start receiving the packets (or 'slices')
 */
private function getPacket()
{
	Yii::log('Begin uploaded packet', 'info', self::LOG_CATEGORY);
	$rowExists = $this->rowExists($_GET['fileid'], $_GET['token']);
    if($rowExists) {
		$this->prepareUploadDir();

		if (!$handle = fopen(Yii::app()->params['uploadPath'] . $_GET['fileid'] . "-" . $_GET['packet'], 'w')) {
			$errMes = "Unable to open package handle";
			$this->throwError($errMes);
		}

		if (fwrite($handle, $GLOBALS['HTTP_RAW_POST_DATA']) === FALSE) {
			$errMes = "Unable to write to package #" . $_GET['packet'];
			$this->throwError($errMes);
		}
		fclose($handle);

        $this->sendAsJSON(array(
            "action" => "new_packet",
            "result" => "success",
            "packet" => $_GET['packet'],
        ));
    } else {
		$errMes = "No file found in the storage for the provided ID / token";
        $this->throwError($errMes);
	}
	Yii::log('End uploaded packet ' . $_GET['packet'] . ' in file ' . $_GET['fileid'], 'info', self::LOG_CATEGORY);
}


}