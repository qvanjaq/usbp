<?php
class TextFilesProcessor extends CComponent {
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

	public function encodeFiles($fromEncode, $toEncode) {
		// validate encoding
		$encodList = mb_list_encodings();
		if(!in_array($fromEncode, $encodList)) {
			$event = new CEvent(null, array('error' => "Not support encode pass for 'fromEncode' " . $fromEncode));
			$this->onError($event);
		}

		if(!in_array($toEncode, $encodList)) {
			$event = new CEvent(null, array('error' => "Not support encode pass for 'toEncode' " . $toEncode));
			$this->onError($event);
		}

		// change encoding
		foreach(Yii::app()->session['filesInfo'] as $fileid => $infoFile) {
			$contents = file_get_contents(Yii::app()->params['uploadPath'] . $fileid);
			if (!$contents) {
				$event = new CEvent(null, array('error' => "Unable to read contents of file " . $fileid));
				$this->onError($event);
			}
			$event = new CEvent(null, array('info' => 'Convert file ' . $fileid . ' from ' .
				$fromEncode . ' to ' . $toEncode));
			$this->onLogInfo($event);

			$contents = mb_convert_encoding($contents, $toEncode, $fromEncode);
			$resultWrite = file_put_contents(Yii::app()->params['uploadPath'] . $fileid, $contents);
			if($resultWrite === false) {
				$event = new CEvent(null, array('error' => "Unable to write contents to file " .
					$fileid . ' after change encoding'));
				$this->onError($event);
			}
		}
	}

	function findAndReplace($fromEncode, $findText, $replaceText) {
		mb_regex_encoding('UTF-8');

		$findText = preg_quote($findText);
		$regularFind = mb_ereg_replace('(\\r\\n)|(\\r)|(\\n)', '[\\s]+', $findText);
		$event = new CEvent(null, array('info' => 'Regular fo find text ' . $regularFind));
		$this->onLogInfo($event);

		foreach(Yii::app()->session['filesInfo'] as $fileid => $infoFile) {
			$contents = file_get_contents(Yii::app()->params['uploadPath'] . $fileid);
			if (!$contents) {
				$event = new CEvent(null, array('error' => "Unable to read contents of file " . $fileid));
				$this->onError($event);
			}

			$typeNewLineReplace = $this->newlineType($contents);
			if($typeNewLineReplace !== false)
				$replaceText = mb_ereg_replace('(\\r\\n)|(\\r)|(\\n)',
												$typeNewLineReplace,
														$replaceText);

			$contentsEncoded = mb_convert_encoding($contents, mb_regex_encoding(), $fromEncode);
			$contentsChanged = mb_ereg_replace ($regularFind, $replaceText, $contentsEncoded, 'm');
			$contentsResult = mb_convert_encoding($contentsChanged, $fromEncode, mb_regex_encoding());

			if($contentsEncoded == $contentsChanged) {
				// if not changes, may be posted
				// incorrect encode, we reverse result
				$contentsResult	= $contents;
			}

			$resultWrite = file_put_contents(Yii::app()->params['uploadPath'] . $fileid, $contentsResult);
			if($resultWrite === false) {
				$event = new CEvent(null, array('error' => "Unable to write contents to file " .
					$fileid . ' after change encoding'));
				$this->onError($event);
			}
		}
	}

	public function onError($event) {
		$this->raiseEvent('onError', $event);
	}

	public function onLogInfo($event) {
		$this->raiseEvent('onLogInfo', $event);
	}
}