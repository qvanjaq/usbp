<script type="text/javascript"><?php require_once 'js/main-files.php'; ?></script>
<script type="text/javascript" src="js/log.js"></script>
<script type="text/javascript" src="js/upload.js"></script>
<script type="text/javascript" src="js/uploader.js"></script>
<script type="text/javascript" src="js/service.js"></script>
<script type="text/javascript" src="js/download-files.js"></script>
<script type="text/javascript" src="js/text-files.js"></script>
<?php
  $baseUrl = Yii::app()->baseUrl;
  $cs = Yii::app()->getClientScript();
  $cs->registerCssFile($baseUrl.'/css/text-files.css');
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
		<tr id="filesEncodingWrapper">
			<td class="label">Files encoding</td>
			<td>
				<select id="filesEncoding">
					<?php $this->renderPartial("optionsEncoding"); ?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="label">Change encoding to</td>
			<td>
				<select  id="optionToEncoding">
					<option value="not" selected="selected">Not</option>
					<?php $this->renderPartial("optionsEncoding"); ?>
				</select>
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
		<tr id="findWrapper">
			<td class="label">Find</td>
			<td>
				<textarea id="findText"></textarea>
			</td>
		</tr>
		<tr id="replaceWrapper">
			<td class="label">Replace</td>
			<td>
				<textarea id="replaceText"></textarea>
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