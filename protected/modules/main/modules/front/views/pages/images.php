<script type="text/javascript"><?php require_once 'js/main-files.php'; ?></script>
<script type="text/javascript" src="js/log.js"></script>
<script type="text/javascript" src="js/upload.js"></script>
<script type="text/javascript" src="js/uploader.js"></script>
<script type="text/javascript" src="js/service.js"></script>
<script type="text/javascript" src="js/download-files.js"></script>
<script type="text/javascript" src="js/FileSaver.min.js"></script>
<script type="text/javascript" src="js/jszip.js"></script>
<script type="text/javascript" src="js/jpeg_encoder_basic.js"></script>
<script type="text/javascript" src="js/images.js"></script>


<?php
  $baseUrl = Yii::app()->baseUrl;
  $cs = Yii::app()->getClientScript();
  $cs->registerCssFile($baseUrl.'/css/images.css');
?>
<form name="onlyFiles" method="post" enctype="multipart/form-data">
	<input type="file" id="files" multiple="multiple">
</form>

<form id="filesForm" method="post">
	<table>
		<tr id="selectFiles">
			<td class="label" id="labelChooseUpload">Choose source of images</td>
			<td>
				<input type="button" id="typePopup" value="Open">
			</td>
		</tr>
		<tr>
			<td class="label">Convert to</td>
			<td>
				<select  id="convertTo">
					<option value="jpg">jpg</option>
					<option value="png">png</option>
					<!-- <option value="gif">gif</option> -->
				</select>
			</td>
		</tr>
		<tr>
			<td class="label">Width</td>
			<td>
				<input class="imageSize" type="text" id="width" />
			</td>
		</tr>
		<tr>
			<td class="label">Height</td>
			<td>
				<input class="imageSize" type="text" id="height" />
			</td>
		</tr>
		<tr>
			<td class="label">Quality</td>
			<td>
				<div id="quality"></div>
			</td>
		</tr>
		<tr>
			<td class="label">Get the result in</td>
			<td>
				<select  id="resultArchive">
					<?php
						foreach(Yii::app()->params['resultArchive'] as $type)
							echo '<option value="' . $type . '">' . $type . '</option>';
					?>
				</select>
			</td>
		</tr>
		<tr id="filesListWrapper">
			<td></td>
		</tr>
		<tr id="progressWrapper">
			<td colspan="2">
				<div id="filesProgress"><div class="progress-label">Upload...</div></div>
			</td>
		</tr>
		<tr id="sendWrapper">
			<td colspan="2">
				<input type="button" id="preview" value="Preview">
				<input type="button" id="submit" value="Submit">
			</td>
		</tr>
	</table>
</form>