<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once("inc/setup.php");

$cms_html_title = 'Users';
require_once("inc/header.php");
?>

<div id="page_header" class="clearfix"> 
	<h2 class="col_8">Users</h2> 
	<?PHP if($cms_user['admin']){ ?><a id="add_item_button" class="button color big right add_header" href="user_settings.php">Add New User</a><?PHP } ?>
</div> 

<hr class="chubby">
		
<table id="users_table" class="table" cellpadding="0" cellspacing="0" border="0" style="width:100%"> 
	<thead> 
		<tr> 
			<th class="icon"></th> 
			<th class="first_field">Name</th> 
			<th>Logged in</th> 
			<th>Activity</th> 
			<th>Email</th> 
			<th>Description</th> 
		</tr> 
	</thead> 
	<tbody> 
		<?PHP
		$query 	 = "SELECT * FROM `directus_users` ";
		
		// Hides inactive users from non-administrators
		if(!$cms_user['admin']){
			$query 	.= "WHERE `active` = '1' ";
		}
		$query 	.= "ORDER BY `active` DESC, `last_login` DESC ";
		
		foreach($dbh->query($query) as $user){
			$last_page = explode('?',$user["last_page"]);
			$page_user_on = uc_convert(basename($last_page[0], ".php"));
			
			parse_str($last_page[1], $user_query_string);
			$table = ($user_query_string['table'])? uc_table($user_query_string['table']) : '';
			
			// Find out the page that users are on based on URL
			if($plugin_pos = strpos($user["last_page"], '/plugins/')){
				$plugin_name = explode('/', substr($last_page[0], $plugin_pos+9));
				$describe_page_on = uc_convert($plugin_name[0]) . ' Plugin';
			}elseif($page_user_on == 'Edit'){
				$describe_page_on = ($user_query_string['item'])? 'Editing within <b>'.$table.'</b>' : 'Creating new item in <b>'.$table.'</b>';
			} elseif($page_user_on == 'Index') {
				$describe_page_on = 'Dashboard';
			} elseif($page_user_on == 'Browse') {
				$describe_page_on = 'Browsing <b>'.$table.'</b>';
			}elseif(!$user["last_page"]){
				$describe_page_on = 'Hasn\'t logged in yet';
			} else {
				$describe_page_on = $page_user_on . ' page';
			}
			?>
			<tr<?PHP echo (!$user['active'])? ' class="inactive_user"':''; ?>> 
				<td class="icon"><img src="<?PHP echo get_avatar($user['id']);?>" width="25" height="25"></td> 
				<td class="first_field"><div class="wrap"><?PHP echo (in_array($user['id'],$users_online))?'<strong>'.$user['first_name'].' '.$user['last_name'].'</strong>':$user['first_name'].' '.$user['last_name'];?><?PHP echo (($user['id'] == $cms_user['id'] && $cms_user['editable'] == '1') || $cms_user['admin'])? '<a class="badge edit_user" href="user_settings.php?u='.$user['id'].'" style="display:none;">Edit</a>':'';?></div></td> 
				<td><div class="wrap"><?PHP echo (in_array($user['id'],$users_online))? 'Now' : ucwords(contextual_time(strtotime($user['last_login'])));?></div></td> 
				<td><div class="wrap"><a href="<?PHP echo $user['last_page'];?>"><?PHP echo $describe_page_on;?></a></div></td> 
				<td><div class="wrap"><a href="mailto:<?PHP echo $user['email'];?>"><?PHP echo $user['email'];?></a></div></td> 
				<td><div class="wrap"><?PHP echo $user['description'];?></div></td> 
			</tr> 
			<?PHP
		}
		?>
	</tbody> 
</table>

<?PHP
require_once("inc/footer.php");
?>