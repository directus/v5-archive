<?PHP

// Remove this if we add setup...
if(get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

if($_POST['title'] && $_POST['message']){
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	?>
	<div id="dialog_window_frame">
		<div id="dialog_window_content">
			<div id="dialog_header" class="clearfix">
				<?PHP echo $_POST['title'];?><a class="close_dialog" href="#"></a>
			</div>
			<div id="dialog_content">
				<?PHP
				if($_POST['type'] == 'email_list'){
					$emails = array_unique(array_filter(explode(',', $_POST['message'])));
					sort($emails);
					?>
					<div class="dialog_section"><textarea rows="10" style="width:388px;" onclick="this.focus();this.select()" readonly="readonly"><?PHP echo implode(', ', $emails);?></textarea></div>
					<div class="dialog_actions">
						<a class="button pill cancel_dialog">Ok</a>
					</div>
					<?PHP
				} elseif($_POST['type'] == 'add_table'){
					?>
					<form id="add_table_form">
						<div class="dialog_section">
							<div class="pad_bottom"> 
								<label class="primary" for="table_name">Table Name</label><br> 
								<input class="settings_general force_safe" type="text" name="table_name" maxlength="64" value=""> 
							</div>
							<div class="pad_bottom"> 
								<label class="primary" for="table_active"><input type="checkbox" name="table_active" value="true" checked="checked"> Will items have active, draft and deleted states?</label> 
							</div>
							<div class="pad_bottom"> 
								<label class="primary" for="table_sort"><input type="checkbox" name="table_sort" value="true" checked="checked"> Will items be reorderable?</label> 
							</div>
						</div>
						<div class="dialog_actions">
							<a id="add_table_ajax" class="button pill">Add</a>
						</div>
					</form>
					<?PHP
				} elseif($_POST['type'] == 'add_field'){
					?>
					<form id="add_field_form">
						<div class="dialog_section">
							<div class="pad_bottom"> 
								<label class="primary" for="field_name">Field Name</label><br> 
								<input class="settings_general force_safe" type="text" name="field_name" maxlength="64" value="">
								<input type="hidden" name="table_name" value="<?PHP echo $_POST['message']; ?>">
							</div>
							<div class="pad_bottom"> 
								<label class="primary" for="field_type">Type</label><br> 
								<select name="field_type" id="add_field_type">
									<option value="VARCHAR" title="max chars: 1 to 255">VARCHAR</option>
									<option value="CHAR" title="max chars: 1 to 255">CHAR</option>
									
									<option value="TINYTEXT" title="max chars: 255">TINYTEXT</option>
									<option value="TEXT" title="max chars: 65535">TEXT</option>
									<option value="MEDIUMTEXT" title="max chars: 16777215">MEDIUMTEXT</option>
									<option value="LONGTEXT" title="max chars: 4294967295">LONGTEXT</option>
									
									
									<option value="TINYBLOB" title="max chars: 255">TINYBLOB</option>
									<option value="BLOB" title="max chars: 65535">BLOB</option>
									<option value="MEDIUMBLOB" title="max chars: 16777215">MEDIUMBLOB</option>
									<option value="LONGBLOB" title="max chars: 4294967295">LONGBLOB</option>
									
									<option value="DATE">DATE</option>
									<option value="DATETIME">DATETIME</option>
									<option value="TIME">TIME</option>
									<option value="YEAR">YEAR</option>
									<option value="TIMESTAMP">TIMESTAMP</option>
									
									<option value="TINYINT" title="0 to 255">TINYINT</option>
									<option value="SMALLINT" title="0 to 65535">SMALLINT</option>
									<option value="MEDIUMINT" title="0 to 16777215">MEDIUMINT</option>
									<option value="INT" title="0 to 4294967295">INT</option>
									<option value="BIGINT" title="0 to 18446744073709551615">BIGINT</option>
									
									<option value="FLOAT">FLOAT</option>
									<option value="DOUBLE">DOUBLE</option>
									<option value="DECIMAL">DECIMAL</option>
									
									<option value="ENUM">ENUM</option>
									<option value="SET">SET</option>
									<option value="BOOL">BOOL</option>
									<option value="BINARY">BINARY</option>
									<option value="VARBINARY">VARBINARY</option>
								</select>
							</div>
							<div class="pad_bottom" id="add_field_length">
								<label class="primary" for="field_length">Length</label><br> 
								<input class="settings_general force_numeric" type="text" name="field_length" maxlength="3" value="255"> 
							</div>
							<div class="pad_bottom" id="add_field_default">
								<label class="primary" for="field_default">Default</label><br> 
								<input class="settings_general" type="text" name="field_default" maxlength="64" value=""> 
							</div>
						</div>
						<div class="dialog_actions">
							<a id="add_field_ajax" class="button pill">Add</a>
						</div>
					</form>
					<?PHP
				} else {
					?>
					<div class="dialog_section"><?PHP echo stripslashes(nl2br($_POST['message']));?></div>
					<!-- 
					<div class="dialog_actions">
						<a class="button color pill" href="#">Button</a> <a class="button pill cancel_dialog">Cancel</a>
					</div>
					-->
					<?PHP
				}
				?>
			</div>
		</div>
	</div>
	<?PHP
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>