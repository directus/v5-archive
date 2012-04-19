<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once("config.php");
require_once("functions.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get variables and make them safe

$table_field = ($_POST['tablefield'])? $_POST['tablefield'] : $table_field;
$datatype = ($_POST['datatype'])? $_POST['datatype'] : $datatype;
$table = reset(explode(',', $table_field));

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get existing values

unset($option);
$sth = $dbh->prepare("SELECT * FROM `directus_settings` WHERE `active` = '1' AND `type` = 'field_option' AND `option` = :option AND `value` = :value ORDER BY `id` ASC ");
$sth->bindParam(':option', $table_field);
$sth->bindParam(':value', $datatype);
$sth->execute();
while($datatype_option_row = $sth->fetch()){
	$option[$datatype_option_row['option_2']] = $datatype_option_row['value_2'];
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Echo out the fields for this datatype

if($datatype == 'text_field'){
	// NULL
} elseif($datatype == 'text_area') {
	?>
	<div class="block">
		<input type="text" name="field_option[<?PHP echo $table_field;?>][height]" maxlength="3" size="3" class="small force_numeric" value="<?PHP echo ($option['height'])?$option['height']:'8';?>" /> <label class="normal">Height (rows)</label>
	</div>
	<div>
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][no_nl2br]" value="true" <?PHP echo ($option['no_nl2br'])?'checked="checked"':'';?> /> <label>Don't add break tags</label>
	</div>
	<div>
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][urls_to_links]" value="true" <?PHP echo ($option['urls_to_links'])?'checked="checked"':'';?> /> <label>Auto-convert URLs to links</label>
	</div>
	<?PHP
} elseif($datatype == 'wysiwyg') {
	?>
	<div class="block">
		<input type="text" name="field_option[<?PHP echo $table_field;?>][height]" maxlength="3" size="3" class="small force_numeric" value="<?PHP echo ($option['height'])?$option['height']:'8';?>" /> <label class="normal">Height (rows)</label>
	</div>
	<?PHP	
} elseif($datatype == 'table_view') {
	?>
	<div class="block">
		<input type="text" name="field_option[<?PHP echo $table_field;?>][columns]" maxlength="3" size="3" class="small force_numeric" value="<?PHP echo ($option['columns'])?$option['columns']:'3';?>" /> <label class="normal">Columns</label>
	</div>
	<div class="block">
		<input type="text" name="field_option[<?PHP echo $table_field;?>][rows]" maxlength="3" size="3" class="small force_numeric" value="<?PHP echo ($option['rows'])?$option['rows']:'6';?>" /> <label class="normal">Rows</label>
	</div>
	<div>
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][change_columns]" value="true" <?PHP echo ($option['change_columns'])?'checked="checked"':'';?> /> <label>Can add/remove columns</label>
	</div>
	<div>
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][change_rows]" value="true" <?PHP echo ($option['change_rows'])?'checked="checked"':'';?> /> <label>Can add/remove rows</label>
	</div>
	<?PHP
} elseif($datatype == 'media') {
	?>
	<!--
	<div class="block">
		<input type="text" name="field_option[<?PHP echo $table_field;?>][min]" maxlength="3" size="3" class="small force_numeric" value="<?PHP echo ($option['min'])?$option['min']:'0';?>" /> <label>Min media</label>
	</div>
	<div class="block">
		<input type="text" name="field_option[<?PHP echo $table_field;?>][max]" maxlength="3" size="3" class="small force_numeric" value="<?PHP echo ($option['max'])?$option['max']:'0';?>" /> <label>Max media</label>
	</div>
	-->
	<div class="block">
		<label>Extensions allowed</label><br>
		<input type="text" name="field_option[<?PHP echo $table_field;?>][extensions]" maxlength="255" class="small options" value="<?PHP echo ($option['extensions'])?$option['extensions']:'';?>" /><br>
	</div>
	<div class="block">
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][new_only]" value="true" <?PHP echo ($option['new_only'])?'checked="checked"':'';?> /> <label>New media only</label><br>
	</div>
	<div class="block">
		<input type="text" name="field_option[<?PHP echo $table_field;?>][browse_media]" maxlength="2" size="3" class="small force_numeric" value="<?PHP echo ($option['browse_media'] > 0)? $option['browse_media'] : '0';?>" /> <label>thumbs on browse page</label><br>
	</div>
	<?PHP
} elseif($datatype == 'relational') {
	// MUST CHECK THE SQL STATEMENT TO ENSURE THERE IS NOTHING MALICIOUS!
	?>
	<div class="block">
		<label>Style</label><br>
		<select name="field_option[<?PHP echo $table_field;?>][style]">
			<option value="list_dropdown" <?PHP echo ($option['style'] == 'list_dropdown')?'selected="selected"':'';?> >Simple (List/Dropdown)</option>
			<option value="checkboxes_radios" <?PHP echo ($option['style'] == 'checkboxes_radios')?'selected="selected"':'';?> >Broad (Checkboxes/Radio Buttons)</option>
			<option value="fancy" <?PHP echo ($option['style'] == 'fancy')?'selected="selected"':'';?>>Fancy (Reorderable)</option>
		</select>
	</div>
	<div class="block">
		<label class="normal">SQL for options</label><br>
		<textarea name="field_option[<?PHP echo $table_field;?>][sql]" class="small options" rows="4"><?PHP echo ($option['sql'])?$option['sql']:"SELECT * FROM `related_table` WHERE `active` = '1' ORDER BY `main_field` ASC";?></textarea>
	</div>
	<div class="block">
		<label>Related table name</label><br>
		<input type="text" name="field_option[<?PHP echo $table_field;?>][add_from_table]" class="small options" maxlength="255" value="<?PHP echo ($option['add_from_table'])?$option['add_from_table']:'related_table';?>" />
	</div>
	<div class="block">
		<label class="normal">Option (Field name)</label><br>
		<input type="text" name="field_option[<?PHP echo $table_field;?>][option]" class="small options" maxlength="255" value="<?PHP echo ($option['option'])?$option['option']:'main_field';?>" />
	</div>
	<div class="block">
		<input type="text" name="field_option[<?PHP echo $table_field;?>][option_glue]" maxlength="255" size="2" class="small" value="<?PHP echo ($option['option_glue'])?$option['option_glue']:', ';?>" /> <label title="Used between combined option fields">Option Glue</label>
	</div>
	<div class="block">
		<label>Value (Field name)</label><br>
		<input type="text" name="field_option[<?PHP echo $table_field;?>][value]" maxlength="255" class="small options" value="<?PHP echo ($option['value'])?$option['value']:'id';?>" />
	</div>
	<div>
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][multiple]" value="true" <?PHP echo ($option['multiple'])?'checked="checked"':'';?> /> <label>Allow multiple</label>
	</div>
	<div>
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][only_new]" value="true" <?PHP echo ($option['only_new'])?'checked="checked"':'';?> /> <label>Only New Items (Fancy)</label>
	</div>
	<div>
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][reorderable]" value="true" <?PHP echo ($option['reorderable'])?'checked="checked"':'';?> /> <label>Reorderable (Fancy)</label>
	</div>
	<?PHP
} elseif($datatype == 'options') {
	?>
	<div class="block">
		<label>Style</label><br>
		<select name="field_option[<?PHP echo $table_field;?>][style]">
			<option value="list_dropdown" <?PHP echo ($option['style'] == 'list_dropdown')?'selected="selected"':'';?> >Simple (List/Dropdown)</option>
			<option value="checkboxes_radios" <?PHP echo ($option['style'] == 'checkboxes_radios')?'selected="selected"':'';?> >Broad (Checkboxes/Radio Buttons)</option>
		</select>
	</div>
	<div class="block">
		<label class="normal">Values (Comma separated)</label><br>
		<textarea name="field_option[<?PHP echo $table_field;?>][values]" class="small options" rows="4"><?PHP echo ($option['values'])?$option['values']:"option 1,option 2,option 3";?></textarea>
	</div>
	<div>
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][multiple]" value="true" <?PHP echo ($option['multiple'])?'checked="checked"':'';?> /> <label>Allow multiple</label>
	</div>
	<?PHP
} elseif($datatype == 'tags') {
	?>
	<div>
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][autocomplete]" value="true" <?PHP echo ($option['autocomplete'])?'checked="checked"':'';?> /> <label>Autocomplete</label>
	</div>
	<!--
	<div>
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][alphabetize]" value="true" <?PHP echo ($option['alphabetize'])?'checked="checked"':'';?> /> <label>Alphabetize</label>
	</div>
	-->
	<?PHP
} elseif($datatype == 'email') {
	// NULL
} elseif($datatype == 'short_name') {
	?>
	<div>
		<label>Field name (link)</label><br>
		<select name="field_option[<?PHP echo $table_field;?>][field]">
			<?PHP
			$table_rows_short_name = get_rows_info($table);
			foreach($table_rows_short_name['fields'] as $field_key_short_name => $field_short_name){
				?><option value="<?PHP echo $field_short_name;?>" <?PHP echo ($option['field'] == $field_short_name)?'selected="selected"':'';?>><?PHP echo uc_convert($field_short_name);?></option><?PHP
			}
			?>
		</select>
	</div>
	<?PHP
} elseif($datatype == 'password') {
	?>
	<div>
		<input type="checkbox" name="field_option[<?PHP echo $table_field;?>][unmask]" value="true" <?PHP echo ($option['unmask'])?'checked="checked"':'';?> /> <label>Unmask on focus</label>
	</div>
	<?PHP
} elseif($datatype == 'password_confirm') {
	// Might get rid of this... is it needed in the CMS?
	?>
	<div>
		<label>Password field name (link)</label><br>
		<select name="field_option[<?PHP echo $table_field;?>][field]">
			<?PHP
			$table_rows_short_name = get_rows_info($table);
			foreach($table_rows_short_name['fields'] as $field_key_short_name => $field_short_name){
				?><option value="<?PHP echo $field_short_name;?>" <?PHP echo ($option['field'] == $field_short_name)?'selected="selected"':'';?>><?PHP echo uc_convert($field_short_name);?></option><?PHP
			}
			?>
		</select>
	</div>
	<?PHP
} elseif($datatype == 'color') {
	// NULL
} elseif($datatype == 'rating') {
	?>
	<div>
		<input type="text" name="field_option[<?PHP echo $table_field;?>][max]" maxlength="3" size="3" class="small force_numeric" value="<?PHP echo ($option['max'])?$option['max']:'0';?>" /> <label class="normal">Max Rating</label><br>
	</div>
	<?PHP
} else {
	// NULL
}

?>