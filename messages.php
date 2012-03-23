<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once("inc/setup.php");

$_GET["m"] = ($_GET["m"] == "sent" || $_GET["m"] == "archived" || $_GET["m"] == "compose")? $_GET["m"] : 'inbox';

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Function to create an item message subject line

function item_message_subject($table, $row){
	return uc_table($table)." Item: ".get_primary_field_value($table, $row);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Archive message

if($_POST['archive']){
	$sth = $dbh->prepare("SELECT * FROM `directus_messages` WHERE `active` = '1' AND `id` = :id AND (`to` LIKE :to OR `to` = 'all' OR `from` = :from)  LIMIT 1");
	$sth->bindParam(':id', $_POST['archive']);
	$sth->bindValue(':to', '%,'.$cms_user['id'].',%');
	$sth->bindParam(':from', $cms_user['id']);
	$sth->execute();
	if($message_to_archive = $sth->fetch()){
		if(strpos($message_to_archive['archived'],','.$cms_user['id'].',') !== 0){
			$sth = $dbh->prepare("UPDATE `directus_messages` SET `archived` = CONCAT(archived, :archived) WHERE `id` = :id ");
			$sth->bindValue(':archived', $cms_user['id'].',');
			$sth->bindParam(':id', $message_to_archive['id']);
			$alert = ( $sth->execute() )? "message_archived" : "error_archive_message";
		} else {
			$sth = $dbh->prepare("UPDATE `directus_messages` SET `archived` = REPLACE(archived, :archived, ',') WHERE `id` = :id ");
			$sth->bindValue(':archived', ','.$cms_user['id'].',');
			$sth->bindParam(':id', $message_to_archive['id']);
			$alert = ( $sth->execute() )? "message_restored" : "error_restore_message";
		}
	} else {
		$alert = "allow_archive";
	}
	// Only one, no need for array
	die($alert);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Mark all messages as read

if($_POST['mark_all_as_read']){
	$sth = $dbh->prepare("UPDATE `directus_messages` SET `viewed` = CONCAT(viewed,:viewed) WHERE `active` = '1' AND (`to` LIKE :to OR `to` = 'all') AND `viewed` NOT LIKE :viewed2 ");
	$sth->bindValue(':viewed', $cms_user['id'].',');
	$sth->bindValue(':to', '%,'.$cms_user['id'].',%');
	$sth->bindValue(':viewed2', '%,'.$cms_user['id'].',%');
	if( $sth->execute() ){
		$marked_messages = $sth->rowCount();
		if($marked_messages>0){
			$_SESSION['alert'] = "marked_messages_$marked_messages";
			$alert = 'success';
		} else {
			$alert = "messages_read_already";
		}
	} else {
		$alert = "messages_read_error";
	}
	// Only one, no need for array
	die($alert);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Send message

if($_POST['submit']){
	
	// Stop XSS by cleaning subject field
	$_POST["subject"] = htmlspecialchars(strip_tags($_POST["subject"]));
	
	// If this is an item message, get the ID if there is one
	if($_POST['item_message'] && $_POST["table"] && $_POST["row"]){
		$sth = $dbh->prepare("SELECT id FROM `directus_messages` WHERE `active` = '1' AND `table` = :table AND `row` = :row  LIMIT 1");
		$sth->bindValue(':table', $_POST["table"]);
		$sth->bindParam(':row', $_POST["row"]);
		$sth->execute();
		if($item_message_id = $sth->fetch()){
			$_GET['id'] = $item_message_id['id'];
		}
	}

	if($_GET['id']){
		//////////////////////////////////////////////////////////////////////////////
		// Reply to message
	
		$sth = $dbh->prepare("SELECT * FROM `directus_messages` WHERE `active` = '1' AND `id` = :id AND (`to` LIKE :to OR `to` = 'all' OR `from` = :from)  LIMIT 1");
		$sth->bindParam(':id', $_GET['id']);
		$sth->bindValue(':to', '%,'.$cms_user['id'].',%');
		$sth->bindParam(':from', $cms_user['id']);
		$sth->execute();
		if($message_reply_to = $sth->fetch()){
			
			$to = ($message_reply_to['to'] == 'all' || $_POST['item_message'])? 'all' : str_replace(','.$cms_user['id'].',', ','.$message_reply_to['from'].',', $message_reply_to['to']);
			
			$sth = $dbh->prepare("INSERT INTO `directus_messages` SET `from` = :from, `reply` = :reply, `to` = :to, `message` = :message, `datetime` = :datetime, `viewed` = :viewed, `table` = :table, `row` = :row ");
			$sth->bindParam(':from', $cms_user['id']);
			$sth->bindParam(':reply', $_GET['id']);
			$sth->bindParam(':to', $to);
			$sth->bindParam(':message', nl2br(escape_and_link_urls($_POST["message"])));
			$sth->bindValue(':datetime', CMS_TIME);
			$sth->bindValue(':viewed', ','.$cms_user['id'].',');
			$sth->bindValue(':table', $message_reply_to['table']);
			$sth->bindValue(':row', $message_reply_to['row']);
			
			if( $sth->execute() ){
				$_SESSION['alert'] = "reply_sent";
				
				// For use in emails
				$message_id = $message_reply_to['id'];
				$message_type = 'reply';
				$subject = ($message_reply_to['table'] && $message_reply_to['row'])? item_message_subject($message_reply_to['table'], $message_reply_to['row']) : $message_reply_to['subject'];
				$_POST["subject"] = 'Re: ' . $subject;
				
				// Removes this messages from the archives since it now has a new reply
				$sth = $dbh->prepare("UPDATE `directus_messages` SET `archived` = ',' WHERE `id` = :id ");
				$sth->bindParam(':id', $_GET['id']);
				$sth->execute();
			}
		} else {
			$_SESSION['alert'] = "allow_reply";
			header("Location: ".CMS_INSTALL_PATH."messages.php?m=inbox");
			die();
		}
	} else {
		//////////////////////////////////////////////////////////////////////////////
		// New message
		
		// Defaults
		$to = ($_POST["everyone"][0] == 'all' || $_POST['item_message'])? 'all' : ','.implode(',', (array) $_POST["everyone"]).',';
		$subject = ($_POST["subject"])? $_POST["subject"] : '';
		$table = ($_POST["table"])? $_POST["table"] : '';
		$row = ($_POST["row"])? $_POST["row"] : '';
		
		// Save message
		$sth = $dbh->prepare("INSERT INTO `directus_messages` SET `from` = :from, `to` = :to, `subject` = :subject, `message` = :message, `datetime` = :datetime, `viewed` = :viewed, `table` = :table, `row` = :row ");
		$sth->bindParam(':from', $cms_user['id']);
		$sth->bindParam(':to', $to);
		$sth->bindParam(':subject', $subject);
		$sth->bindParam(':message', nl2br(escape_and_link_urls($_POST["message"])));
		$sth->bindValue(':datetime', CMS_TIME);
		$sth->bindValue(':viewed', ','.$cms_user['id'].',');
		$sth->bindValue(':table', $table);
		$sth->bindValue(':row', $row);
		if( $sth->execute() ){
			$_SESSION['alert'] = "message_sent";
			
			// For use in emails
			$message_id = $dbh->lastInsertId();
			$message_type = 'new message';
		}
		
		$_POST["subject"] = ($_POST["table"] && $_POST["row"])? item_message_subject($_POST["table"], $_POST["row"]) : $_POST["subject"];
	}
	
	//////////////////////////////////////////////////////////////////////////////
	// Send email notifications to those who mave email alerts turned on
	
	$sth = $dbh->prepare("SELECT * FROM `directus_users` WHERE `active` = '1' AND `id` != :id AND `email_messages` = '1' ");
	$sth->bindParam(':id', $cms_user['id']);
	$sth->execute();
	$users = $sth->fetchAll();
	
	if($to == 'all'){	
		foreach($users as $user){
			$to_emails[] = $user['first_name'].' '.$user['last_name'].' <'.$user['email'].'>';
		}
	} else {
		foreach($users as $user){
			if(strpos($to, ','.$user['id'].',') !== false){
				$to_emails[] = $user['first_name'].' '.$user['last_name'] .' <'.$user['email'].'>';
			}
		}
	}
	
	// If there are recipients then send out emails
	if(count($to_emails) > 0){
		$body = "<b>".$settings['cms']['site_name']."</b> - ".$cms_user['username']." posted a $message_type:\n<br>\n<br><div style='max-width:400px; padding-bottom:40px; border-bottom:1px #cccccc solid; border-top:1px #cccccc solid; font-size:14px;'><h3><a href='".CMS_PAGE_PATH."messages.php?id=$message_id'>".$_POST["subject"]."</a></h3>\n".nl2br($_POST["message"])."</div>";
		$sent = send_email($subject = $_POST["subject"], $body, $to = 'You <messages@example.com>', $from = $cms_user['username'].' <messages@getdirectus.com>', $bcc = $to_emails);
	}
	
	//////////////////////////////////////////////////////////////////////////////
	
	if($_POST['item_message']){
		// Cancel alert since we're ajaxing it in
		$_SESSION['alert'] = "";
		
		// Print the message to ajax to the page
		?>
		<li>
			<span class="item_message_user"><?PHP echo $cms_all_users[$cms_user['id']]['username'];?> wrote:</span>
			<?PHP echo nl2br(escape_and_link_urls($_POST["message"])); ?><!-- <a href="#">more</a> -->
			<span class="item_message_date"><?PHP echo contextual_time(strtotime(CMS_TIME));?></span>
		</li>
		<?PHP
	} else{
		// Removes repost on refresh
		header("HTTP/1.1 303 See Other");
		header("Location: ".CMS_INSTALL_PATH."messages.php?id=$message_id");
	}
	
	die();
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$cms_html_title = ($unread_messages_total > 0)? "Messages ($unread_messages_total)":"Messages";
require_once("inc/header.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>


<h2>Messages</h2>

<hr class="chubby">

<div class="clearfix" style="position:relative;">
	
	<div id="messages_actions">
		<div class="box">
			<a id="compose_message_button" class="button pill" href="?m=compose">Compose Message</a>
			<ul>
				<li><a href="?m=inbox"><?PHP echo ($unread_messages_total > 0)? "<b>Inbox ($unread_messages_total)</b>":'Inbox';?></a></li>
				<!-- <li><a href="?m=unread">Unread</a></li> -->
				<li><a href="?m=sent">Sent</a></li>
				<li><a href="?m=archived" class="quiet">Archived</a></li>
			</ul>
		</div>
	</div>
		
	<?PHP 
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Compose a new message
	
	if ($_GET["m"] == "compose") { 
		?>
		<div id="messages_compose">
			<h3>Compose Message</h3>
			<form name="compose_message" method="post" action="messages.php?m=inbox">
				<div class="fieldset">
					<div class="field">
						<input type="checkbox" id="message_everyone" name="everyone[]" value="all"> <label for="everyone" class="primary pad_right_small">Everyone</label> 
						<?PHP 
						// Get all users except sender
						$sth = $dbh->prepare("SELECT * FROM `directus_users` WHERE `active` = '1' AND `id` != :id ORDER BY `first_name` ASC, `last_name` ASC");
						$sth->bindParam(':id', $cms_user['id']);
						$sth->execute();
						while($user = $sth->fetch()){ 
							?><input type="checkbox" class="message_user" name="everyone[]" value="<?PHP echo $user['id'];?>"> <label for="user1" class="pad_right_small"><?PHP echo $cms_all_users[$user['id']]['username'];?></label><?PHP 
						} 
						?>
					</div>
					<div class="field">
						<label class="primary">Subject</label><br>
						<input type="text" class="track_max_length" id="message_subject" maxlength="250" name="subject">
						<span class="char_count">250</span>
					</div>
					<div class="field">
						<label class="primary">Message</label><br>
						<textarea rows="16" id="message_message" name="message"></textarea>
					</div>
				</div>
				<input class="button color big now_activity" activity="sending" type="submit" value="Send Message" id="message_submit" name="submit"> 
				<span>or <a class="cancel" href="messages.php">Cancel</a></span>
			</form>
		</div>
		<?PHP 
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// View message
	
	} elseif ($_GET["id"]) { 
		// Get the primary message
		$sth = $dbh->prepare("SELECT * FROM `directus_messages` WHERE `active` = '1' AND `id` = :id AND (`from` = :from OR `to` = 'all' OR `to` LIKE :to) LIMIT 1");
		$sth->bindParam(':id', $_GET["id"]);
		$sth->bindParam(':from', $cms_user['id']);
		$sth->bindValue(':to', '%,'.$cms_user['id'].',%');
		$sth->execute();
		if($message = $sth->fetch()){
		
			// Mark as read
			if(strpos($message['viewed'],','.$cms_user['id'].',') === false){
				$sth = $dbh->prepare("UPDATE `directus_messages` SET `viewed` = CONCAT(viewed,:viewed) WHERE `id` = :id ");
				$sth->bindValue(':viewed', $cms_user['id'].',');
				$sth->bindParam(':id', $message['id']);
				$sth->execute();
			}
			
			$to = ($message['to'] != ','.$cms_user['id'].',')? '<br> To: '.implode(', ',get_usernames($message['to'])) : '';
			$subject = ($message['table'] && $message['row'])? item_message_subject($message['table'], $message['row']) : $message['subject'];
			?>
			<div id="messages_message">
				<h3><?PHP echo $subject;?></h3>
				<table id="message" cellspacing="0" cellpadding="0">
					<tr class="message">
						<td width="180">
							<div>				
								<img class="user_avatar" src="<?PHP echo get_avatar($message['from']);?>" width="50" height="50">
								<div>
									<span class="user"><?PHP echo get_username($message['from']);?></span>
									<span class="date">sent <?PHP echo contextual_time(strtotime($message['datetime']));?> <?PHP echo $to;?></span>
								</div>
							</div>
						</td>
						<td>
							<div class="pad_right">
								<span class="body">
									<?PHP echo $message['message'];?>							
								</span>
								<?PHP 
								if($message['table'] && $message['row']){ 
									?><span class="actions"><a href="edit.php?table=<?PHP echo $message['table'] . '&item=' . $message['row'];?>">Go To Item</a></span><?PHP 
								} 
								?>
							</div>
						</td>
						<td width="8.253%">
							<a class="ui-icon ui-icon-close right archive_message" archive_id="<?PHP echo $message['id'];?>" href="messages.php?m=inbox"></a>
						</td>
					</tr>
					
					<?PHP
					//////////////////////////////////////////////////////////////////////////////
					// Get replies to this message
					
					$sth = $dbh->prepare("SELECT * FROM `directus_messages` WHERE `active` = '1' AND `reply` = :reply AND `archived` NOT LIKE :archived ORDER BY `datetime` ASC");
					$sth->bindParam(':reply', $_GET["id"]);
					$sth->bindValue(':archived', '%,'.$cms_user['id'].',%');
					$sth->execute();
					while($reply = $sth->fetch()){
					
						// Mark as read
						if(strpos($reply['viewed'],','.$cms_user['id'].',') === false){
							$sth_inner = $dbh->prepare("UPDATE `directus_messages` SET `viewed` = CONCAT(viewed,:viewed) WHERE `id` = :id ");
							$sth_inner->bindValue(':viewed', $cms_user['id'].',');
							$sth_inner->bindParam(':id', $reply['id']);
							$sth_inner->execute();
						}
						?>
						<tr class="message">
							<td width="180">
								<div>				
									<img class="user_avatar" src="<?PHP echo get_avatar($reply['from']);?>" width="50" height="50">
									<div>
										<span class="user"><?PHP echo get_username($reply['from']);?></span>
										<span class="date">sent <?PHP echo contextual_time(strtotime($reply['datetime']));?></span>
									</div>
								</div>
							</td>
							<td>
								<div class="pad_right">
									<span class="body">
										<?PHP echo $reply['message'];?>							
									</span>
								</div>
							</td>
							<td width="8.253%">
								<!-- Can't archive replies -->
							</td>
						</tr>
						<?PHP
					}
					//////////////////////////////////////////////////////////////////////////////
					?>
					<tr id="reply" class="reply">
						<td width="180">
							<div>				
								<h4>Reply</h4>
							</div>
						</td>
						<td>
							<div class="pad_right">
								<form name="reply_message" method="post" action="messages.php?id=<?PHP echo $message['id'];?>">
									<a name="reply"></a>
									<textarea class="reply_message" rows="8" id="message_reply" name="message"></textarea>
									<div class="pad_top">
										<input class="button color big now_activity" activity="replying" type="submit" value="Send" name="submit"> 
										<span>or <a class="cancel" href="messages.php">Cancel</a></span>
									</div>
								</form>
							</div>
						</td>
						<td width="8.253%">
							&nbsp;
						</td>
					</tr>
				</table>
			</div>
			<?PHP 
		} else {
			echo '<div id="messages_message">No such message</div>';
		}
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// View listing of messages
	
	} else {
		?>
		<div id="messages_<?PHP echo $_GET["m"];?>">
			<h3>
				<span>
				<?PHP echo (!$_GET["m"] || $_GET["m"] == "inbox")? 'Inbox' : uc_convert($_GET["m"]) . ' Messages';?>
				</span>
				<?PHP if(!$_GET["m"] || $_GET["m"] == "inbox"){ ?><a id="mark_all_as_read" class="button right" href="#" style="line-height:1.5;">Mark all as read</a><?PHP } ?>
			</h3>
			<table id="inbox" cellspacing="0" cellpadding="0">
				<?PHP
				$total_visible = 0;
				foreach($messages as $message){
					
					$unread = ((strpos($message['viewed'], ','.$cms_user['id'].',') === false && $message['unread_replies'] == NULL) || ($message['unread_replies']>0 && $message['unread_replies'] != NULL) )? true : false;
					
					if($_GET['m'] == 'unread'){
						$view = ($unread)? true : false;
					} elseif($_GET['m'] == 'sent'){
					
						// Only show messages from this user in the sent folder
						$view = ($message['from'] == $cms_user['id'])? true : false;
						
						// Do not show item messages that have replies in the sent folder
						$view = ($message['table'] != '' && $message['row'] != '' && $message['num_replies'] > 0)? false : $view;
					} else {
						if(!$message['message_archive'] && $_GET['m'] == 'archived'){
							$view = false;
						} elseif($message['message_archive'] && $_GET['m'] != 'archived') {
							$view = false;
						} else {
							// Dont show messages you sent (that dont have replies) in the inbox
							$view = ($message['from'] == $cms_user['id'] && $message['num_replies'] == 0)? false : true;
						}
					}
					$total_visible += ($view)?1:0;
					
					// Get all users included (except last poster) unless this message is for everyone or admins
					$users_included = str_replace(','.$message['last_from'].',', ',', $message['to'].$message['from'].',');
					$to = '<br> To: '.implode(', ',get_usernames($users_included));
					$to = ($message['to'] == 'all')? '<br> To: Everyone': $to;
					$subject = ($message['table'] && $message['row'])? item_message_subject($message['table'], $message['row']) : $message['subject'];
					?>
					<tr class="inbox_item <?PHP echo ($unread)? 'unread' : '';?> <?PHP echo ($view)?'':'hide';?>">
						<td width="180">
							<div>				
								<img class="user_avatar" src="<?PHP echo get_avatar($message['last_from']);?>" width="50" height="50">
								<div>
									<span class="user"><?PHP echo ($_GET["m"] == "sent")? 'To: ' . implode(', ',get_usernames($message['to'])) : get_username($message['last_from']);?></span>
									<span class="date"><?PHP echo contextual_time(strtotime($message['last_datetime']));?> <?PHP echo ($_GET["m"] != "sent")? $to:'';?></span>
								</div>
							</div>
						</td>
						<td>
							<div class="pad_right">
								<span class="subject"><a href="?id=<?PHP echo $message['id'];?>"><?PHP echo $subject;?></a></span>
								<span class="body"><?PHP echo ellipses($message['last_message'],90);?></span>
								<span class="actions"><a href="?id=<?PHP echo $message['id'];?>">View message</a> | 
								<?PHP if($message['table'] && $message['row']){ ?><a href="edit.php?table=<?PHP echo $message['table'] . '&item=' . $message['row'];?>">Go To Item</a> | <?PHP } ?>
								<a href="?id=<?PHP echo $message['id'];?>#reply">Reply <?PHP echo ($message['num_replies'] > 0)? '('.$message['num_replies'].')':'';?></a></span>
							</div>
						</td>
						<td width="8.253%">
							<a class="ui-icon <?PHP echo ($_GET['m'] == 'archived')?'ui-icon-arrowreturnthick-1-w':'ui-icon-close';?> right archive_message" archive_id="<?PHP echo $message['id'];?>" href="#"></a>
						</td>
					</tr>
					<?PHP
				}
				if($total_visible == 0){
					echo "<div class=\"no_messages\">No messages</div>";
				}
				?>
			</table>
		</div>
		<?PHP 
	}
	?>
	
</div>

<?PHP
require_once("inc/footer.php");
?>