<form id="filesForm">
	<table>
		<tr id="selectFiles">
			<td class="label" id="labelChooseUpload">Choose source of images</td>
			<td>
				<div id="typeUpload">
					<input type="radio" id="typePopup" name="radio" /><label for="typePopup">Popup</label>
					<input type="radio" id="typeDrag" name="radio" checked="checked" /><label for="typeDrag">Drag'n'Drop</label>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="2"><div id="dropPanel"></div></td>
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
				<select  id="optionToEncoding">
					<option value="7z">7z</option>
					<option value="zip" selected="selected">zip</option>
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