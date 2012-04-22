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
					<div class="dialog_section">
						<div class="pad_bottom"> 
							<label class="primary" for="site_name">Table Name</label><br> 
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
						<a id="" class="button pill cancel_dialog">Add</a>
					</div>
					<?PHP
				} elseif($_POST['type'] == 'add_field'){
					?>
					<div class="dialog_section">
						<div class="pad_bottom"> 
							<label class="primary" for="site_name">Field Name</label><br> 
							<input class="settings_general force_safe" type="text" name="table_name" maxlength="64" value=""> 
						</div>
						<div class="pad_bottom"> 
							<label class="primary" for="site_name">Type</label><br> 
							<input class="settings_general force_safe" type="text" name="table_name" maxlength="64" value="">
						</div>
						<div class="pad_bottom"> 
							<label class="primary" for="table_sort"><input type="checkbox" name="table_sort" value="true" checked="checked"> Will items be reorderable?</label> 
						</div>
					</div>
					<div class="dialog_actions">
						<a id="" class="button pill cancel_dialog">Add</a>
					</div>
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