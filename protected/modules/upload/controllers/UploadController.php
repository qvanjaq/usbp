<?php

class UploadController extends Controller
{
	/*const FILE_PATH = '../uploaded_files/';
define('PACKET_SIZE', 512 * 512); // bytes, need to be same as in JavaScript
define('STORE_FILES', true); //whether store files or not	*/
	public function actionFile()
	{
		if (!isset($_POST)) {
			throwError("No post request");
		}

		if (isset($_POST['totalSize']) && isset($_POST['type']) && isset($_POST['fileName']) && is_numeric($_POST['totalSize'])) {
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
	exit;
}




private function newUpload()
{
    $fileData = $_POST['totalSize'] . "|" . preg_replace('/[^A-Za-z0-9\/]/', '', $_POST['type']) . "|" . preg_replace('/[^A-Za-z0-9_\.]/', '', $_POST['fileName']);
    $originalFileName = $_POST['fileName'];
    $token 	  = md5($fileData);
	$fileid   = time() . mt_rand(5, pow(2, 31) - 1);

	if(!is_array(Yii::app()->session['filesInfo']))
	Yii::app()->session['filesInfo'] = array();
	Yii::app()->session['filesInfo'] = array_merge(Yii::app()->session['filesInfo'],
														array($fileid => array('fileData' => $fileData,
															'token' => $token,
															'filename' => $originalFileName)));
    $this->sendAsJSON(array(
        "action" => "new_upload",
        "fileid" => $fileid,
        "token"  => $token
    ));
}


private function mergeFiles()
{
		//echo Yii::app()->session['filesInfo'][$_POST['fileid']]['token'] . '@' . $_POST['token'];

    if (!$this->rowExists($_POST['fileid'], $_POST['token'])) {
        $this->throwError("No file found in the storage for the provided ID / token");
    }

    // check if we the file has already been uploaded, merged and completed
    if (!file_exists(Yii::app()->params['uploadPath'] . $_POST['fileid'])) {
		$fileData = Yii::app()->session['filesInfo'][$_GET['fileid']]['fileData'];
        list($fileSize, $fileType, $fileName) = explode("|", $fileData);

        $totalPackages = ceil($fileSize / Yii::app()->params['packetSize']);

        // check that all packages exist
        for ($package = 0; $package < $totalPackages; $package++) {
            if (!file_exists(Yii::app()->params['uploadPath'] . $_POST['fileid'] . "-" . $package)) {
                $this->throwError("Missing package #" . $package);
            }
        }

        // open file to create final file
        if (!$handle = fopen(Yii::app()->params['uploadPath'] . $_POST['fileid'], 'w')) {
            $this->throwError("Unable to create new file for merging");
        }

        // write each package to the file
        for ($package = 0; $package < $totalPackages; $package++) {
            $contents = @file_get_contents(Yii::app()->params['uploadPath'] . $_POST['fileid'] . "-" . $package);
            if (!$contents) {
                unlink(FILE_PATH . $_POST['fileid']);
                $this->throwError("Unable to read contents of package #" . $package);
            }

            if (fwrite($handle, $contents) === FALSE) {
                unlink(FILE_PATH . $_POST['fileid']);
                $this->throwError("Unable to write package #" . $package . " to merge");
            }
        }

        // remove the packages
        for ($package = 0; $package < $totalPackages; $package++) {
            if (!unlink(Yii::app()->params['uploadPath'] . $_POST['fileid'] . "-" . $package)) {
                $this->throwError("Unable to remove package #" . $package);
            }
        }
    }

    $this->sendAsJSON(array(
        "action" => "complete",
        "file" => $_POST['fileid']
    ));
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
    if ($this->rowExists($_GET['fileid'], $_GET['token'])) {
		if (!$handle = fopen(Yii::app()->params['uploadPath'] . $_GET['fileid'] . "-" . $_GET['packet'], 'w')) {
			throwError("Unable to open package handle");
		}

		if (fwrite($handle, $GLOBALS['HTTP_RAW_POST_DATA']) === FALSE) {
			throwError("Unable to write to package #" . $_GET['packet']);
		}
		fclose($handle);

        $this->sendAsJSON(array(
            "action" => "new_packet",
            "result" => "success",
            "packet" => $_GET['packet'],
        ));
    }
}
}