<?PHP
//////////////////////////////////////////////////////////////////////////////
// Setup without saving location

$setup_ajax = true;
require_once("setup.php");

//////////////////////////////////////////////////////////////////////////////

if($_POST['table'] && $_POST['id'] !== false && $_POST['status'] !== false){
	
	//////////////////////////////////////////////////////////////////////////////
	// Get array of IDs to change and other variables
	
	$status_ids = explode(',',$_POST['id']);
	$status_ids = array_filter($status_ids);
	
	$total_count = count($status_ids);
	$success_count = 0;
	$error_count = 0;
	
	$table = $_POST['table'];
	$status = $_POST['status'];
	
	if($status == 1){
		$action = 'activated';
	} else if($status == 0){
		$action = 'deleted';
	} else {
		$action = 'deactivated';
	}
	
	//////////////////////////////////////////////////////////////////////////////
	// Update status for each item
	
	// Reminder: Compare against existing tables
	foreach($status_ids as $id){
		$sth = $dbh->prepare("UPDATE `$table` SET `active` = :active WHERE `id` = :id AND `active` != :active ");
		$sth->bindParam(':active', $status);
		$sth->bindParam(':id', $id);
		if( $sth->execute() ){
			
			// If the item was actually changed
			if($sth->rowCount() > 0){
			
				$success_count++;
		
				$id_safe = intval($id);
			
				// Save status change to revisions
				insert_activity($table = $table, $row = $id, $type = $action, $sql = "UPDATE `$table` SET `active` = '$status' WHERE `id` = '$id_safe' ");
			}
		} else {
			$error_count++;
		}
	}
	
	//////////////////////////////////////////////////////////////////////////////
	
	// Alert user of how many items were changed
	if($success_count > 0){
		$_SESSION['alert'] = "item_$action"."_$success_count";
	}
	
	// Alert user of how many items had errors
	if($error_count > 0){
		$plural = ($error_count == 1)?'':'s';
		echo "Error: $error_count/$total_count item$plural not $action";
	}
	
	//////////////////////////////////////////////////////////////////////////////
}
?>