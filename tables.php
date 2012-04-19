<?PHP
//////////////////////////////////////////////////////////////////////////////

require_once("inc/setup.php");

//////////////////////////////////////////////////////////////////////////////

$cms_html_title = "Browsing Tables";
require_once("inc/header.php");

//////////////////////////////////////////////////////////////////////////////
?>

<h2>Tables</h2>

<hr class="chubby">

<table id="tables_table" class="table" cellpadding="0" cellspacing="0" border="0" style="width:100%">
	<thead>
		<tr>
			<th class="icon"></th>
			<th class="first_field">Table</th>
			<th width="10%">Active Items</th>
		</tr>
	</thead>
	<tbody class="check_no_rows">
		<?PHP
		foreach($visible_tables as $table){
			$table_info = get_rows_info($table);
			$active_items = $table_info['num'];
			if(in_array($table,$settings['table_single'])){
				$add_or_edit = ($active_items == 0)? '':'&item=1';
				$url = 'edit.php?table=' . $table . $add_or_edit;
				$icon = 'database-arrow';
			} else {
				$url = 'browse.php?table=' . $table;
				$icon = 'database';
			}
			?>
			<tr onclick="location.href='<?PHP echo $url; ?>'">
				<td class="icon"><img src="media/site/icons/<?PHP echo $icon; ?>.png" width="16" height="16" /></td>
				<td class="first_field"><div class="wrap"><?PHP echo uc_table($table); ?></div></td>
				<td class="text_right"><?PHP echo $active_items; ?></td>
			</tr>
			<?PHP
		}
		?>
		<tr class="item no_rows"><td colspan="3">No tables available</td></tr>
	</tbody>
</table>

<?PHP
require_once("inc/footer.php");
?>