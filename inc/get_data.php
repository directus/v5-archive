<?PHP

$setup_ajax = true;
require_once("setup.php");

//////////////////////////////////////////////////////////////////////////////

if($_GET['type'] == 'autocomplete'){

	$term = trim(strip_tags($_GET['term']));
	$table = clean_db_item($_GET['table']);
	$field = clean_db_item($_GET['field']);
	$tag_string = '';
	
	// Add all fields tags to one string
	foreach($dbh->query("SELECT $field FROM `$table` ORDER BY `$field` ASC ") as $row){
		$tag_string .= $row[$field];
	}
	
	// Get all tags from string
	$tags = array_unique(array_filter(explode(',', $tag_string)));
	sort($tags);
	
	// Tags that match the term
	$final_tags = array();
	$tag_count = 0;
	foreach($tags as $value){
		if(strpos($value, $term) !== false && $tag_count++ < 10){
			$final_tags[] = $value;
		}
	}
	
	echo json_encode($final_tags);
}

?>