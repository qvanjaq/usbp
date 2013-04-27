<?php
	$encodeList = mb_list_encodings();
	for($i = 2; $i < count($encodeList); $i++)
		echo '<option value="' . $encodeList[$i] . '">' .
			$encodeList[$i] . '</option>';
?>
