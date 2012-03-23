<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once("inc/setup.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Save user info

if($_POST["submit"]){
	
	$_POST["id"] = ($_POST["id"] == 'create')? 'create' : intval($_POST["id"]);
	
	//////////////////////////////////////////////////////////////////////////////
	// Check if email exists
	
	$query   = "SELECT * FROM `directus_users` WHERE `email` = :email "; 
	if($_POST["id"] != 'create'){
		$query  .= "AND `id` != :id ";
	}
	
	$sth = $dbh->prepare($query);
	if($_POST["id"] != 'create'){
		$sth->bindParam(':id', $_POST["id"]);
	}
	$sth->bindParam(':email', $_POST["email"]);
	$sth->execute();
	if($row = $sth->fetch()){
		$save_errors[] = 'email_in_use_'.$row['id'];
	}
	
	//////////////////////////////////////////////////////////////////////////////
	// Check the first_name, last_name, password and email address
	
	if(!$_POST["first_name"] || !preg_match("/^[A-Za-z]+(?:[ -][A-Za-z]+)*$/", $_POST["first_name"])){
		$save_errors[] = 'first_name';
	}
	if(!$_POST["last_name"] || !preg_match("/^[A-Za-z]+(?:[ -][A-Za-z]+)*$/", $_POST["last_name"])){
		$save_errors[] = 'last_name';
	}
	if($_POST["password"] != $_POST["password_confirm"]){
		$save_errors[] = 'passwords_match';
	}
	if($_POST["password"] != "" && strlen($_POST["password"]) < 3){ // !preg_match("/^[A-Za-z0-9_@!#$%^&*]{3,}$/", $_POST["password"])
		$save_errors[] = 'password_format';
	}
	if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $_POST["email"])){
		$save_errors[] = 'email_format';
	}
	
	$alert = array_merge( (array) $save_errors, $alert);
	
	//////////////////////////////////////////////////////////////////////////////
	
	if(count($save_errors) == 0){
		if($_POST["id"] != 'create'){
			$query  = "UPDATE `directus_users` SET ";
		} else {
			$query  = "INSERT INTO `directus_users` SET ";
		}
		
		// Get new password hash if needed
		if($_POST["password"] != ""){
			$hasher = new PasswordHash(8, FALSE);
			$new_password = $hasher->HashPassword($_POST["password"]);
		} else {
			$new_password = false;
		}
		
		// To fix non submitting checkbox
		$_POST["active"] = ($_POST["active"] == "1")? '1' : '0';
		$_POST["admin"] = ($_POST["admin"] == "1")? '1' : '0';
		$_POST["email_messages"] = ($_POST["email_messages"] == "1")? '1' : '0';
			
		// Only save THESE values if the user saving is an admin
		if($cms_user["admin"] == '1'){
			
			// If admin then give admin defaults
			if($_POST["admin"] == "1"){
				$_POST["media"] = '1';
				$_POST["notes"] = '1';
				$_POST["editable"] = '1';
				$_POST["view"] = 'all';
				$_POST["add"] = 'all';
				$_POST["edit"] = 'all';
				$_POST["reorder"] = 'all';
				$_POST["delete"] = 'all';
			} else {
				$_POST["media"] = ($_POST["media"] == "1")? '1' : '0';
				$_POST["notes"] = ($_POST["notes"] == "1")? '1' : '0';
				$_POST["editable"] = ($_POST["editable"] == "1")? '1' : '0';
				$_POST["view"] = ($_POST["view"] != "")? ','. implode(',',$_POST["view"]) .',' : '';
				$_POST["add"] = ($_POST["add"] != "")? ','. implode(',',$_POST["add"]) .',' : '';
				$_POST["edit"] = ($_POST["edit"] != "")? ','. implode(',',$_POST["edit"]) .',' : '';
				$_POST["reorder"] = ($_POST["reorder"] != "")? ','. implode(',',$_POST["reorder"]) .',' : '';
				$_POST["delete"] = ($_POST["delete"] != "")? ','. implode(',',$_POST["delete"]) .',' : '';
			}
			
			//////////////////////////////////////////////////////////////////////////////
			
			// Admin only
			$query .= "`admin` = :admin, ";
			$query .= "`active` = :active, ";
			$query .= "`media` = :media, ";
			$query .= "`notes` = :notes, ";
			$query .= "`editable` = :editable, ";
			$query .= "`view` = :view, ";
			$query .= "`add` = :add, ";
			$query .= "`edit` = :edit, ";
			$query .= "`reorder` = :reorder, ";
			$query .= "`delete` = :delete, ";
		}
		
		// Admin OR current user
		$query .= "`first_name` = :first_name, ";
		$query .= "`last_name` = :last_name, ";
		if($new_password){ $query .= "`password` = :password, "; }
		$query .= "`email` = :email, ";
		$query .= "`email_messages` = :email_messages, ";
		$query .= "`description` = :description ";
		
		if($_POST["id"] != 'create'){
			$query .= "WHERE `id` = :id "; 
		}
		
		//////////////////////////////////////////////////////////////////////////////
		
		$sth = $dbh->prepare($query);
		
		// Admin options only
		if($cms_user["admin"] == '1'){
			$sth->bindParam(':admin', $_POST["admin"]);
			$sth->bindParam(':active', $_POST["active"]);
			$sth->bindParam(':media', $_POST["media"]);
			$sth->bindParam(':notes', $_POST["notes"]);
			$sth->bindParam(':editable', $_POST["editable"]);
			$sth->bindParam(':view', $_POST["view"]);
			$sth->bindParam(':add', $_POST["add"]);
			$sth->bindParam(':edit', $_POST["edit"]);
			$sth->bindParam(':reorder', $_POST["reorder"]);
			$sth->bindParam(':delete', $_POST["delete"]);
		}
		
		// Admin OR current user
		$sth->bindParam(':first_name', htmlspecialchars(strip_tags($_POST["first_name"])));
		$sth->bindParam(':last_name', htmlspecialchars(strip_tags($_POST["last_name"])));
		if($new_password){ $sth->bindParam(':password', $new_password); }
		$sth->bindParam(':email', $_POST["email"]);
		$sth->bindParam(':email_messages', $_POST["email_messages"]);
		$sth->bindParam(':description', htmlspecialchars(strip_tags($_POST["description"])));
		
		if($_POST["id"] != 'create'){
			$sth->bindParam(':id', $_POST["id"]);
		}
		
		if($sth->execute()){
			
			// Remove or upload the user avatar
			if($_POST["remove_avatar"] == '1'){
				unlink(BASE_PATH . "media/users/".$_POST["id"].".jpg");
			} elseif($_FILES['avatar']['tmp_name']){
				$extension = strtolower(end(explode('.', $_FILES['avatar']['name'])));
				$extension = ($extension == "jpeg")? "jpg" : $extension;
				$_POST["id"] = (!is_numeric($_POST["id"]))? $dbh->lastInsertId() : $_POST["id"];
				make_thumb("media/users/".$_POST["id"].".jpg", false, $_FILES["avatar"]['tmp_name'], $extension, 50,  50,  true);
			}
			
			// Save as a revision if new user
			if($_POST["id"] == 'create'){
				insert_activity($table = 'directus_users', $row = $dbh->lastInsertId(), $type = 'added', $sql = $_POST["first_name"].' '.$_POST["last_name"]);
			}
			
			$_SESSION['alert'] = ($_POST["id"] != 'create')? "saved" : "added";
			header("Location: ".CMS_INSTALL_PATH."users.php");
			die();
		} else {
			$alert[] = "user_save_error";
		}
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Try to get user info

if($_GET['u']){
	$sth = $dbh->prepare("SELECT * FROM `directus_users` WHERE id = :id LIMIT 1 ");
	$sth->bindParam(':id', $_GET['u']);
	$sth->execute();
	if($user_edit = $sth->fetch()){
		// Found
		if(($user_edit['id'] != $cms_user['id'] || $user_edit['editable'] != '1') && $cms_user["admin"] != '1'){
			$_SESSION['alert'] = "user_see";
			header("Location: ".CMS_INSTALL_PATH."users.php");
			die();
		}
	} else {
		$_SESSION['alert'] = "no_such_user_".addslashes(htmlspecialchars(strip_tags($_GET['u'])));
		header("Location: ".CMS_INSTALL_PATH."users.php");
		die();
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$cms_html_title = ($_GET['u'])? 'User Settings':'Create New User';
require_once("inc/header.php");

?>

<h2><a href="users.php">Users</a> <span class="divider">/</span> <?PHP echo ($user_edit['id'])? $user_edit['first_name'].' '.$user_edit['last_name']."'s User Settings":"Create New User";?></h2> 

<hr class="chubby">

<div class="clearfix" style="position:relative;">
	<form enctype="multipart/form-data" id="user_settings" name="user_settings" action="user_settings.php<?PHP echo ($_GET['u'])?'?u='.$_GET['u']:'';?>" method="post">
		
		<div id="user_info">
		
			<input type="hidden" name="id" value="<?PHP echo ($user_edit['id'])? $user_edit['id'] : 'create';?>">
		
			<div class="box">
				<div class="pad_bottom"> 
					<label class="primary" for="first_name" title="User ID: <?PHP echo $user_edit['id']; ?>">First Name*</label><br> 
					<input type="text" class="user_info" name="first_name" value="<?PHP echo ($user_edit['first_name'])? $user_edit['first_name'] : $_POST['first_name'];?>"> 
				</div> 
				<div class="pad_bottom"> 
					<label class="primary" for="last_name">Last Name*</label><br> 
					<input type="text" class="user_info" name="last_name" value="<?PHP echo ($user_edit['last_name'])? $user_edit['last_name'] : $_POST['last_name'];?>"> 
				</div> 
				<div class="pad_bottom"> 
					<label class="primary" for="email">Email*</label><br> 
					<input type="text" class="user_info" name="email" value="<?PHP echo ($user_edit['email'])? $user_edit['email'] : $_POST['email'];?>"><br>
					<input id="email_messages" title="Email me my messages" name="email_messages" type="checkbox" value="1" <?PHP echo ($user_edit["email_messages"])? 'checked="checked"' : '';?> > <label class="instruct small" for="email_messages"><em>Email me my new messages</em></label>
				</div> 
				<div class="pad_bottom"> 
					<label class="primary" for="password">Change Password</label><br> 
					<input type="password" class="user_info" name="password" value="" autocomplete="off"> 
				</div> 
				<div class="pad_bottom"> 
					<label class="primary" for="password_confirm">Confirm password</label><br> 
					<input type="password" class="user_info" name="password_confirm" value="" autocomplete="off"> 
				</div> 
				<div class="pad_bottom"> 
					<label class="primary" for="description">Description</label><br> 
					<textarea class="user_info" rows="4" name="description"><?PHP echo ($user_edit['description'])? $user_edit['description'] : $_POST['description'];?></textarea> 
				</div>
				<div id="user_avatar" class="clearfix">
					<img src="<?PHP echo get_avatar($user_edit['id']);?>" width="50" height="50" />
					<div>
						<label>Upload a different photo</label><br>
						<input id="avatar" name="avatar" type="file"><br>
						<?PHP if(file_exists(BASE_PATH . "media/users/" . $user_edit['id'] . ".jpg")){ ?><input name="remove_avatar" type="checkbox" value="1"> <label for="remove_avatar">Remove photo</label><?PHP } ?>
					</div>
				</div>
			</div>
			
			<input class="button color big now_activity" activity="<?PHP echo ($user_edit['id'])? 'saving':'adding';?>" type="submit" value="<?PHP echo ($user_edit['id'])? 'Update Settings':'Add User';?>" name="submit"> 
			<span>or <a class="cancel" href="users.php">Cancel</a></span>
		</div>
	
		
		<?PHP if($cms_user["admin"]){ ?>
		
		<div id="user_privileges">
			
			<div class="table_actions">
				<label for="user_settings_active"><input id="user_settings_active" <?PHP echo ($user_edit['id'] == $cms_user['id'])?'class="disable_self"':'';?> name="active" type="checkbox" value="1" <?PHP echo ($user_edit["active"] || !$user_edit['id'])? 'checked="checked"' : '';?> <?PHP echo (!$cms_user["admin"])? 'disabled="disabled"' : ''; ?> >Active User</label>
				<label for="user_settings_admin"><input id="user_settings_admin" name="admin" type="checkbox" value="1" <?PHP echo ($user_edit["admin"])? 'checked="checked"' : '';?> <?PHP echo (!$cms_user["admin"])? 'disabled="disabled"' : ''; ?> >CMS Administrator</label>
				<label for="user_settings_media"><input id="user_settings_media" name="media" type="checkbox" value="1" <?PHP echo ($user_edit["media"] || $user_edit["admin"] || !$user_edit['id'])? 'checked="checked"' : '';?> <?PHP echo (!$cms_user["admin"] || $user_edit["admin"])? 'disabled="disabled"' : ''; ?> >Can change media</label>
				<label for="user_settings_notes"><input id="user_settings_notes" name="notes" type="checkbox" value="1" <?PHP echo ($user_edit["notes"] || $user_edit["admin"] || !$user_edit['id'])? 'checked="checked"' : '';?> <?PHP echo (!$cms_user["admin"] || $user_edit["admin"])? 'disabled="disabled"' : ''; ?> >Can send messages</label>
				<label for="user_settings_editable"><input id="user_settings_editable" name="editable" type="checkbox" value="1" <?PHP echo ($user_edit["editable"] || $user_edit["admin"] || !$user_edit['id'])? 'checked="checked"' : '';?> <?PHP echo (!$cms_user["admin"] || $user_edit["admin"])? 'disabled="disabled"' : ''; ?> >Can change their settings/credentials</label>
			</div>
									
			<table class="table actions">
				<thead>
					<tr>
						<th class="icon"></th>
						<th class="first_field privileges_toggle" link="all">Table</th>
						<th width="14%" class="privileges_toggle" link="view[]">View</th>
						<th width="14%" class="privileges_toggle" link="add[]">Add</th>
						<th width="14%" class="privileges_toggle" link="edit[]">Edit</th>
						<th width="14%" class="privileges_toggle" link="reorder[]">Reorder</th>
						<th width="14%" class="privileges_toggle" link="delete[]">Delete</th>
					</tr>
				</thead>
				<tbody class="check_no_rows">
				
					<?PHP
					// User privileges
					// Note: Should check which tables have active/order fields since those would not have options for reordering and deleting
					foreach($tables as $key => $value){
						?>
						<tr>
							<td class="icon"><img class="icon" src="media/site/icons/database.png" width="16" height="16" /></td>
							<td class="first_field privileges_toggle_table" link="<?PHP echo $value; ?>"><div class="wrap"><?PHP echo uc_table($value); ?></div></td>
							<td><input value="<?PHP echo $value; ?>" priv="all" name="view[]" type="checkbox" <?PHP echo (!$cms_user["admin"] || $user_edit['admin'])? 'disabled="disabled"' : ''; ?> <?PHP echo ($user_edit["view"] == 'all' || !$user_edit['id'] || $user_edit['admin'] || strpos($user_edit["view"],','.$value.',') !== false)? 'checked="checked"' : '';?> /></td>
							<td><input value="<?PHP echo $value; ?>" priv="all" name="add[]" type="checkbox" <?PHP echo (!$cms_user["admin"] || $user_edit['admin'])? 'disabled="disabled"' : ''; ?> <?PHP echo ($user_edit["add"] == 'all' || !$user_edit['id'] || $user_edit['admin'] || strpos($user_edit["add"],','.$value.',') !== false)? 'checked="checked"' : '';?> /></td>
							<td><input value="<?PHP echo $value; ?>" priv="all" name="edit[]" type="checkbox" <?PHP echo (!$cms_user["admin"] || $user_edit['admin'])? 'disabled="disabled"' : ''; ?> <?PHP echo ($user_edit["edit"] == 'all' || !$user_edit['id'] || $user_edit['admin'] || strpos($user_edit["edit"],','.$value.',') !== false)? 'checked="checked"' : '';?> /></td>
							<td><input value="<?PHP echo $value; ?>" priv="all" name="reorder[]" type="checkbox" <?PHP echo (!$cms_user["admin"] || $user_edit['admin'])? 'disabled="disabled"' : ''; ?> <?PHP echo ($user_edit["reorder"] == 'all' || !$user_edit['id'] || $user_edit['admin'] || strpos($user_edit["reorder"],','.$value.',') !== false)? 'checked="checked"' : '';?> /></td>
							<td><input value="<?PHP echo $value; ?>" priv="all" name="delete[]" type="checkbox" <?PHP echo (!$cms_user["admin"] || $user_edit['admin'])? 'disabled="disabled"' : ''; ?> <?PHP echo ($user_edit["delete"] == 'all' || !$user_edit['id'] || $user_edit['admin'] || strpos($user_edit["delete"],','.$value.',') !== false)? 'checked="checked"' : '';?> /></td>
						</tr>
						<?PHP
					}
					?>
					<tr class="item no_rows"><td colspan="7">No non-directus tables in database</td></tr>
				</tbody>
			</table>
			
		</div>
		
		<?PHP } ?>
		
	</form>
</div>

<?PHP

require_once("inc/footer.php");
?>
