<?php
class UploadController extends Controller
{
	const LOG_CATEGORY = 'upload.newUpload';
	public function actionFile()
	{
		if (!isset($_POST)) {
			$errMes = "No post request";
			Yii::log($errMes, 'error', self::LOG_CATEGORY);
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

private function throwError($error)
{
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

private function clearAllUploadFiles() {
	if(is_numeric(Yii::app()->session['idUpload'])) {
			if(Yii::app()->session['idUpload'] !== floatval($_POST['idUpload'])) {
				Yii::log('Begin clear upload files [id upload change from ' .
						Yii::app()->session['idUpload'] . ' to ' .
						intval($_POST['idUpload']), 'info', self::LOG_CATEGORY);
				$filesInfo = Yii::app()->session['filesInfo'];
				foreach($filesInfo as $idFile => $info) {
					list($fileSize, $fileType, $fileName) = explode("|", $info['fileData']);
					$totalPackages = ceil($fileSize / Yii::app()->params['packetSize']);
					$this->clearUploadFiles($totalPackages, $idFile);
				}
				Yii::app()->session['filesInfo'] = null;
			}
	}
	Yii::app()->session['idUpload'] = floatval($_POST['idUpload']);
}

private function prepareUploadDir(){
	if(!is_dir(Yii::app()->params['uploadPath']))
		mkdir (Yii::app()->params['uploadPath'],777, true);
}
private function mergeFiles()
{
	Yii::log('Begin merge uploaded file', 'info', self::LOG_CATEGORY);
    if (!$this->rowExists($_POST['fileid'], $_POST['token'])) {
		$errMes = "No file found in the storage for the provided ID / token";
		Yii::log($errMes, 'error', self::LOG_CATEGORY);
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
				$this->clearUploadFiles($totalPackages, $_POST['fileid']);
				$errMes = "Missing package #" . $package;
				Yii::log($errMes, 'error', self::LOG_CATEGORY);
                $this->throwError($errMes);
            }
        }

        // open file to create final file
        if (!$handle = fopen(Yii::app()->params['uploadPath'] . $_POST['fileid'], 'w')) {
			$this->clearUploadFiles($totalPackages, $_POST['fileid'], $handle);
			$errMes = "Unable to create new file for merging";
			Yii::log($errMes, 'error', self::LOG_CATEGORY);
			$this->throwError($errMes);
        }

        // write each package to the file
        for ($package = 0; $package < $totalPackages; $package++) {
			// file_get_contets can return empty string, if user disconnect hard drive
            $contents = file_get_contents(Yii::app()->params['uploadPath'] . $_POST['fileid'] . "-" . $package);
            if (!$contents) {
				$this->clearUploadFiles($totalPackages, $_POST['fileid'], $handle);
				$errMes = "Unable to read contents of package #" . $package;
				Yii::log($errMes, 'error', self::LOG_CATEGORY);
				$this->throwError($errMes);
            }

            if (fwrite($handle, $contents) === FALSE) {
				$this->clearUploadFiles($totalPackages, $_POST['fileid'], $handle);
				$errMes = "Unable to write package #" . $package . " to merge";
				Yii::log($errMes, 'error', self::LOG_CATEGORY);
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

private function clearUploadFiles($totalPackages, $fileId, $handle=null) {
	if(isset($handle) && $handle !== false){
		fclose($handle);
	}

	$pathMergeFile = Yii::app()->params['uploadPath'] . $fileId;
	if(file_exists($pathMergeFile)) {
		if(!unlink($pathMergeFile)) {
			$errMes = "Unable to remove file " . $fileId;
			Yii::log($errMes, 'error', self::LOG_CATEGORY);
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
				Yii::log($errMes, 'error', self::LOG_CATEGORY);
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
			Yii::log($errMes, 'error', self::LOG_CATEGORY);
			$this->throwError($errMes);
		}

		if (fwrite($handle, $GLOBALS['HTTP_RAW_POST_DATA']) === FALSE) {
			$errMes = "Unable to write to package #" . $_GET['packet'];
			Yii::log($errMes, 'error', self::LOG_CATEGORY);
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
		Yii::log($errMes, 'error', self::LOG_CATEGORY);
        $this->throwError($errMes);
	}
	Yii::log('End uploaded packet ' . $_GET['packet'] . ' in file ' . $_GET['fileid'], 'info', self::LOG_CATEGORY);
}
}