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
			<?PHP
			if($_POST['type'] == 'email_list'){
				$emails = array_unique(array_filter(explode(',', $_POST['message'])));
				sort($emails);
				?>
				<div id="dialog_header" class="clearfix"><?PHP echo $_POST['title'];?><a class="close_dialog" href="#"></a></div>
				<div id="dialog_content">
					<div class="dialog_section"><textarea rows="10" style="width:388px;" onclick="this.focus();this.select()" readonly="readonly"><?PHP echo implode(', ', $emails);?></textarea></div>
					<div class="dialog_actions">
						<a class="button pill cancel_dialog">Ok</a>
					</div>
				</div>
				<?PHP
			} else {
				?>
				<div id="dialog_header" class="clearfix"><?PHP echo $_POST['title'];?><a class="close_dialog" href="#"></a></div>
				<div id="dialog_content">
					<div class="dialog_section"><?PHP echo stripslashes(nl2br($_POST['message']));?></div>
					<!-- 
					<div class="dialog_actions">
						<a class="button color pill" href="#">Button</a> <a class="button pill cancel_dialog">Cancel</a>
					</div>
					-->
				</div>
				<?PHP
			}
			?>
		</div>
	</div>
	<?PHP
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>