<?PHP

require_once("functions.php");

// Get array from CSV
$str = stripslashes($_POST["csv"]);
$data = csv_to_array($str);

// Find row with the most columns
$col_count = 0;
foreach($data as $row){
	$col_count = (count($row) > $col_count)? count($row) : $col_count;
}
?>
<div class="fog"></div>

<div class="edit_table" style="display:none;">

	<div class="modal_window_header" class="clearfix"> 
		Edit Table
		<a class="close_modal" href=""></a> 
	</div>
	
	<div class="edit_table_content">
		<table>
			
			<tr>
				<td class="row_selector blank"></td>
				<?PHP
				for($col=0;$col<$col_count;$col++) {
					?><td class="column_selector"><?PHP echo num_to_chars($col+1); ?></td><?PHP
				}
				?>
			</tr>
			
			<?PHP
			foreach($data as $key => $row){
				echo "<tr class=\"save\"><td class=\"row_selector\">".($key+1)."</td>";
				for($col=0;$col<$col_count;$col++) {
					$value = ($row[$col])? nl2br($row[$col]) : "";
					echo "<td><textarea>$value</textarea></td>";
				}
				echo "</tr>";
			}
			?>
			
		</table>
	</div>
	
	<div class="modal_window_actions">
		<div class="pad_full_small">
			<input id="done_table_view" class="button big color" activity="converting" csv-parent="<?PHP echo $_POST["csv-id"]; ?>" type="submit" value="Done"> 
			<span>or <a class="cancel cancel_modal" href="">Cancel</a></span>
		</div>
	</div>
	
</div>