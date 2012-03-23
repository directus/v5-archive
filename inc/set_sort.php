<?PHP
//////////////////////////////////////////////////////////////////////////////
// Setup without saving location

$setup_ajax = true;
require_once("setup.php");

//////////////////////////////////////////////////////////////////////////////

$i = 1;
$table = $_POST['table'];

// Loop through all items and set a new sort value
// Reminder: Check table against table names
foreach($_POST['item'] as $id) {
	$sth = $dbh->prepare("UPDATE `$table` SET `sort` = '$i' WHERE `id` = :id ");
	$sth->bindParam(':id', $id);
	if( $sth->execute() ){
		$i++;
	} else {
		$error = true;
		break;
	}
}

//////////////////////////////////////////////////////////////////////////////
// Alert user as to results

if($error){
	echo 'error_sorting';
} else {
	echo 'items_reordered_'.count($_POST['item']);
}

//////////////////////////////////////////////////////////////////////////////
?>