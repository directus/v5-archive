<?PHP
//////////////////////////////////////////////////////////////////////////////
// Setup without saving location

$setup_ajax = true;
require_once("setup.php");

//////////////////////////////////////////////////////////////////////////////
// Set CMS user preferences

$value = ($_POST["value"])? $_POST["value"] : implode(",", str_replace('%', '_', $_POST['field']));	// Serialize doesnt allow underscores

if($_POST['session']){
	// For saving a session preference
	if($_POST['session'] == "settings_open_table"){
		$_SESSION["settings_open_table"] = $_POST['value'];
	}
} else {
	// For saving a database preference
	$sth = $dbh->prepare("SELECT * FROM `directus_preferences` WHERE `user` = :user AND `type` = :type AND `name` = :name ");
	$sth->bindParam(':user', $cms_user['id']);
	$sth->bindParam(':type', $_POST["type"]);
	$sth->bindParam(':name', $_POST["name"]);
	$sth->execute();
	if( $row = $sth->fetch() ){
		$sth = $dbh->prepare("UPDATE `directus_preferences` SET `name` = :name, `value` = :value WHERE `id` = :id ");
		$sth->bindParam(':name', $_POST["name"]);
		$sth->bindParam(':value', $value);
		$sth->bindParam(':id', $row['id']);
		if( $sth->execute() ){
			echo 'preference_saved';
		} else {
			echo 'preference_not_saved';
		}
	} else {
		$sth = $dbh->prepare("INSERT INTO `directus_preferences` SET `user` = :user, `type` = :type, `name` = :name, `value` = :value ");
		$sth->bindParam(':user', $cms_user['id']);
		$sth->bindParam(':type', $_POST["type"]);
		$sth->bindParam(':name', $_POST["name"]);
		$sth->bindParam(':value', $value);
		if( $sth->execute() ){
			echo 'preference_added';
		} else {
			echo 'preference_not_added';
		}
	}
}

//////////////////////////////////////////////////////////////////////////////
?>