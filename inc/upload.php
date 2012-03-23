<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Connect to database, check security and setup variables

$setup_ajax = true;
require_once("setup.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Upload, swap, edit a file

$error = array();
$error_each = array();
$files_added = array();


if(!$_POST['media_ids_checked']){
	
	// Check user media permissions
	if(!$cms_user['media']){

		$error[] = "Media management is disabled for your account";

	} else {
	
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Push the limits...
		
		ini_set("memory_limit","104857600");
		ini_set("max_execution_time","3600");
		ini_set("max_input_time","3600");
		ini_set("post_max_size","105857600");
		ini_set("upload_max_filesize","104857600");
		ini_set("output_buffering","on");
		
		
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Batch upload from folder
		
		if(isset($_GET['batch'])){
			if(file_exists('../media/batch/')){
				$batch_media = dir_list('../media/batch/', false, true);
				if(count($batch_media) > 0){
					// No need to use this GET-ID since we are deleting them as we go
					$_POST['url_media'] = $batch_media[0];	
				}
			}
		}
		
		
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Allow for multiple files (Chrome, FireFox and Safari)
		
		$files_uploaded = count($_FILES['upload_media']['name']);
		$files_to_add = ($files_uploaded)? $files_uploaded : 1;
		
		for($file_indice = 0; $file_indice < $files_to_add; $file_indice++){
			
			//////////////////////////////////////////////////////////////////////////////
			// If there are uploaded files let's set the info
			
			if($files_uploaded > 0){
				$file_upload = array();
				$file_upload['name'] = $_FILES['upload_media']['name'][$file_indice];
				$file_upload['type'] = $_FILES['upload_media']['type'][$file_indice];
				$file_upload['tmp_name'] = $_FILES['upload_media']['tmp_name'][$file_indice];
				$file_upload['error'] = $_FILES['upload_media']['error'][$file_indice];
				$file_upload['size'] = $_FILES['upload_media']['size'][$file_indice];
			}
			
			
			
			
			
			
			
			
			
			
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// Check file extension (Only using path, not MIME)
			
			if($_POST['extensions']){
				// Get allowed file extensions
				$extensions_array = array_filter(explode(',', str_replace(' ', '', strtolower($_POST['extensions']))));
				
				// Get file extension
				if(substr($_POST['url_media'], 0, 23) == 'http://www.youtube.com/'){
					$extensions_test = "youtube";
				} elseif(substr($_POST['url_media'], 0, 17) == 'http://vimeo.com/'){
					$extensions_test = "vimeo";
				} else {
					$extensions_test = strtolower(end(explode('.', $file_upload['name'])));
					$extensions_test = ($extensions_test == "jpeg")? "jpg" : $extensions_test;
				}
				
				// Is this file extension one that is allowed?
				if(!in_array($extensions_test, $extensions_array)){
					$error_each[] = "File extension not allowed. Only: " . $_POST['extensions'];
				}
			}
			
			
			
			
			
			
			
			// If this file extension is allowed let's upload it
			if(count($error_each) == 0){
				
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Media variables to set
				
				$file_info = array();
				
				$file_info['id'] = 1;
				
				$file_info['title'] = '';
				$file_info['caption'] = '';
				$file_info['location'] = '';
				$file_info['tags'] = '';
				
				$file_info['type'] = ''; 		// image/embed/video/audio/document
				$file_info['file_name'] = ''; 	// name
				$file_info['extension'] = ''; 	// jpg
				$file_info['source'] = ''; 		// name.jpg (except videos)
				
				$file_info['width'] = 0;
				$file_info['height'] = 0;
				$file_info['file_size'] = 0;
				$file_info['date_created'] = false;
				
				
				
				
				
				
				
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Get next available ID
				
				if($_POST['replace_id']){
					$file_info['id'] = $_POST['replace_id'];
				} else {
					$sth = $dbh->query("SELECT max(id) FROM `directus_media` ");
					$file_info['id'] = ($row = $sth->fetch())? ($row["max(id)"]+1) : 1;
				}
				
				
				
				
				
				
				
				
				
				
				
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// YouTube - Check if video - http://www.youtube.com/watch?v=SOKaQ_vVLzw
				
				if(substr($_POST['url_media'], 0, 23) == 'http://www.youtube.com/'){
					
					// Get ID from URL
					parse_str(parse_url($_POST['url_media'], PHP_URL_QUERY), $array_of_vars);
					$video_id = $array_of_vars['v'];
					
					// Can't find the video ID
					if($video_id === FALSE){
						die("YouTube video ID not detected. Please paste the whole URL.");
					}
					
					$file_info['source'] = $file_info['file_name'] = $video_id;
					$file_info['extension'] = 'youtube';
					$file_info['height'] = 340;
					$file_info['width'] = 560;
					$file_info['type'] = 'embed';
					
					// Get Data
					$url = "http://gdata.youtube.com/feeds/api/videos/". $video_id;
					$ch = curl_init($url);
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
					$content = curl_exec($ch);
					curl_close($ch);
					
					// Get thumbnail
					$ch = curl_init('http://img.youtube.com/vi/' . $video_id . '/0.jpg');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$data = curl_exec($ch);
					curl_close($ch);
					$move = file_put_contents("../media/cms_thumbs/youtube_" . $file_info['source'] . ".jpg", $data);
					$move = file_put_contents("../../" . $settings['cms']['media_path'] . "youtube_" . $video_id . ".jpg", $data);
					
					if ($content !== false) {
						$file_info['title'] = get_string_between($content,"<title type='text'>","</title>");
						//$file_info['file_size'] = get_string_between($content,"yt:duration seconds=","yt:duration");
						
						// Not pretty hack to get duration
						$pos_1 = strpos($content, "yt:duration seconds=") + 21;	
						$file_info['file_size'] = substr($content,$pos_1,10);
						$file_info['file_size'] = preg_replace("/[^0-9]/", "", $file_info['file_size'] );
						
					} else {
					   // an error happened
					   $file_info['title'] = "Unable to Retrieve YouTube Title";
					}
					
					
				
					
					
				
				
				
				
				
				
				
				
				
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Vimeo - http://vimeo.com/11338063
				
				} elseif(substr($_POST['url_media'], 0, 17) == 'http://vimeo.com/' || substr($_POST['url_media'], 0, 18) == 'https://vimeo.com/'){
					
					// Get ID from URL
					preg_match('/vimeo\.com\/([0-9]{1,10})/', $_POST['url_media'], $matches);
					$video_id = $matches[1];

					// Can't find the video ID
					if($video_id === FALSE){
						die("Vimeo video ID not detected. Please paste the whole URL.");
					}
					
					$file_info['source'] = $file_info['file_name'] = $video_id;
					$file_info['extension'] = 'vimeo';
					
					// Get Data
					$url = 'http://vimeo.com/api/v2/video/' . $video_id . '.php';
					$ch = curl_init($url);
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
					$content = curl_exec($ch);
					curl_close($ch);
					$array = unserialize(trim($content));
					
					if($content !== false) {
						$file_info['title'] = $array[0]['title'];
						$file_info['caption'] = strip_tags($array[0]['description']);
						$file_info['file_size'] = $array[0]['duration'];
						$file_info['height'] = $array[0]['height'];
						$file_info['width'] = $array[0]['width'];
						$file_info['tags'] = $array[0]['tags'];
						$file_info['date_created'] = $array[0]['upload_date'];
						$vimeo_thumb = $array[0]['thumbnail_large'];
						$file_info['type'] = 'embed';
						
						// Get thumbnail
						$ch = curl_init($vimeo_thumb);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						$data = curl_exec($ch);
						curl_close($ch);
						$move = file_put_contents("../media/cms_thumbs/vimeo_" . $video_id . ".jpg", $data);	
						$move = file_put_contents("../../" . $settings['cms']['media_path'] . "vimeo_" . $video_id . ".jpg", $data);	
					} else {
						// Unable to get Vimeo details
						$file_info['title'] = "Unable to Retrieve Vimeo Title";
						$file_info['height'] = 340;
						$file_info['width'] = 560;
					}
					
					
				
				
				
				
				
				
				
				
				
				
				
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Add media from URL or computer
				
				} elseif( (strlen($_POST['url_media']) > 3 && $_POST['url_media'] != 'http://') || $file_upload['name']) {
	
					//////////////////////////////////////////////////////////////////////////////
					// Get file info
					
					$original_name = (strlen($_POST['url_media']) > 3 && $_POST['url_media'] != 'http://')? basename($_POST['url_media']) : $file_upload['name'];
					$original_path = (strlen($_POST['url_media']) > 3 && $_POST['url_media'] != 'http://')? $_POST['url_media'] : $file_upload['tmp_name'];
					
					$file_info['title'] = str_replace("." . end(explode('.', $original_name)), "", $original_name);
					$file_info['extension'] = strtolower(end(explode('.', $original_name)));
					$file_info['extension'] = ($file_info['extension'] == "jpeg")? "jpg" : $file_info['extension'];
					$file_info['extension'] = ($file_info['extension'] == "tiff")? "tif" : $file_info['extension'];
					
					// Get the mime-type (just the first part): Application/Audio/Image/Message/Text/Video/X
					$file_info['type'] = reset(explode('/', $file_upload['type']));
					
					//////////////////////////////////////////////////////////////////////////////
					// Create new name and path
					
					if($settings['cms']['media_naming'] == 'original'){
						
						// Loop until an available filename is found
						$safe_title = $file_info['file_name'] = str_replace(' ', '-', preg_replace('!\s+!', ' ', preg_replace('`[^a-z0-9-\s_]`i', '', strtolower($file_info['title']))));  
						$file_counter = 1; 
						while(file_exists( "../../" . $settings['cms']['media_path'] . $file_info['file_name'] . "." . $file_info['extension'] )){
							$file_info['file_name'] = $safe_title . '-' . $file_counter++;
						}
						
					} elseif($settings['cms']['media_naming'] == 'sequential'){
						$file_info['file_name'] = str_pad($file_info['id'], 6, "0", str_pad_left);
					} else {
						$file_info['file_name'] = md5("Directus Image " . $file_info['id']);
					}
					
					//////////////////////////////////////////////////////////////////////////////
					// Create Path
					
					$file_info['source'] = $file_info['file_name'] . "." . $file_info['extension'];
					$file_info['path'] = "../../" . $settings['cms']['media_path'] . $file_info['file_name'] . "." . $file_info['extension'];
					
					//////////////////////////////////////////////////////////////////////////////
					// Move file (Back up two dirs since we're in the inc folder)
					
					if(strlen($_POST['url_media']) > 3 && $_POST['url_media'] != 'http://'){
						if(isset($_GET['batch'])){
							// Move and rename from the batch folder into the media folder
							$move = rename("../media/batch/" . $_POST['url_media'], $file_info['path']);
						} else {
							$ch = curl_init($_POST['url_media']);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							$data = curl_exec($ch);
							curl_close($ch);
							$move = file_put_contents($file_info['path'], $data);
						}
					} else {
						$move = move_uploaded_file($original_path, $file_info['path']);
					}
					
					//////////////////////////////////////////////////////////////////////////////
					// Get media size
					
					$file_info['file_size']  = filesize($file_info['path']);
					
					//////////////////////////////////////////////////////////////////////////////
					// Get media (image) dimensions and meta info
					
					if($file_info['extension'] == 'jpg' || $file_info['extension'] == 'gif' || $file_info['extension'] == 'png'){
						
						list($file_info['width'], $file_info['height']) = @getimagesize($file_info['path']);
						
						if($file_info['extension'] == 'jpg' || $file_info['extension'] == 'tif'){
							if($exif_date = @exif_read_data($file_info['path'], 'IFD0', 0)){
			        			$file_info['date_created'] = $exif_date['DateTime'];
			        		}
						}
						
						$meta_info = array();                      
						getimagesize($file_info['path'], $meta_info);
						if(isset($meta_info['APP13'])){
							
							$iptc = iptcparse($meta_info['APP13']);
							
							$location_array = array($iptc['2#092'][0], $iptc['2#090'][0], $iptc['2#095'][0], $iptc['2#101'][0]);
							$location_array = array_filter($location_array);
							$file_info['location'] = implode(', ', $location_array);
							
							if(is_array($iptc['2#025'])){
								$file_info['tags'] = ','.implode(',', $iptc['2#025']) . ',';
							}
							
							$file_info['caption'] = $iptc['2#120'][0];
							//$file_info['date_created'] = date("Y-m-d H:i:s", strtotime($iptc['2#055'][0]));
						}
						
						//$error_each[] = print_r($iptc,true);
						//$error_each[] = print_r($exif_date,true);
								
					}
					
					
					//////////////////////////////////////////////////////////////////////////////
					// Check upload directory exists
					
					if(!file_exists("../../" . $settings['cms']['media_path'])){
						$error_each[] = "Upload directory doesn't exist - ../../" . $settings['cms']['media_path'];
					}
					
					
					//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					// Check if file was moved
						
						
					// File too large for php.ini - upload_max_filesize
					if($file_upload["error"] == 1){
						$error_each[] = "File too large: increase 'upload_max_filesize' setting";
					} elseif($file_upload["error"] != 0) {
						$error_each[] = "There was an upload error: ".$file_upload["error"];
					} else {
						if(!$move) {
							$error_each[] = "Error moving file - <b>" . $original_path . "</b> to <b>" . $file_info['path'] . "</b>";
						}
					}
					
					
					
					
					
					
					
					
					
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Update existing media info (not media itself)
					
				} elseif(is_numeric($_POST['replace_id'])) {
					$sth = $dbh->prepare("UPDATE `directus_media` SET title = :title, caption = :caption, location = :location, tags = :tags WHERE id = :id ");
					$sth->bindParam(':title', $_POST['title']);
					$sth->bindParam(':caption', $_POST['caption']);
					$sth->bindParam(':location', $_POST['location']);
					$sth->bindParam(':tags', $_POST['tags']);
					$sth->bindParam(':id', $_POST['replace_id']);
					if( $sth->execute() ){
						$_SESSION['alert'] = 'saved';
						insert_activity($table = 'directus_media', $file_info['id'], 'edited', uc_media_title($file_info['title']));
					} else {
						$_SESSION['alert'] = 'error_media_update';
					}
					
					$sth = $dbh->prepare("SELECT * FROM `directus_media` WHERE `active` = '1' AND `id` = :id ");
					$sth->bindParam(':id', $_POST['replace_id']);
					$sth->execute();
					if( $file_info = $sth->fetch() ){
						// We have now saved: $file_info
					}
				
					$move = true;
					$media_edit = true;
					
				} else {
					$move = true;
					$error_each[] = "No file selected";
				}	
					
					
					
					
					
				
					
					
					
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Save the media into the database
				
				if(count($error_each) == 0 && !$media_edit){
					
					// User entered values take precedence
					$file_info['title'] = ($_POST['title'])? $_POST['title'] : uc_media_title($file_info['title']);
					$file_info['caption'] = ($_POST['caption'])? nl2br($_POST['caption']) : nl2br($file_info['caption']);
					$file_info['location'] = ($_POST['location'])? $_POST['location'] : $file_info['location'];
					$file_info['tags'] = ($_POST['tags'])? $_POST['tags'] : $file_info['tags'];
					$file_info['date_created'] = ($file_info['date_created'])? $file_info['date_created'] : "0000-00-00 00:00:00";
					
					// Replace/Swap existing media
					if($_POST['replace_id']){
						$sth = $dbh->prepare("UPDATE `directus_media` SET title = :title, caption = :caption, source = :source, file_name = :file_name, extension = :extension, type = :type, width = :width, height = :height, file_size = :file_size, location = :location, tags = :tags, date_created = :date_created WHERE id = :id ");
						$sth->bindParam(':title', $file_info['title']);
						$sth->bindParam(':caption', $file_info['caption']);
						$sth->bindParam(':source', $file_info['source']);
						$sth->bindParam(':file_name', $file_info['file_name']);
						$sth->bindParam(':extension', $file_info['extension']);
						$sth->bindParam(':type', $file_info['type']);
						$sth->bindParam(':width', $file_info['width']);
						$sth->bindParam(':height', $file_info['height']);
						$sth->bindParam(':file_size', $file_info['file_size']);
						$sth->bindParam(':location', $file_info['location']);
						$sth->bindParam(':tags', $file_info['tags']);
						$sth->bindParam(':date_created', $file_info['date_created']);
						$sth->bindParam(':id', $_POST['replace_id']);
					} else {
						$sth = $dbh->prepare("INSERT INTO `directus_media` SET uploaded = :uploaded, user = :user, title = :title, caption = :caption, source = :source, file_name = :file_name, extension = :extension, type = :type, width = :width, height = :height, file_size = :file_size, location = :location, tags = :tags, date_created = :date_created ");
						$sth->bindValue(':uploaded', CMS_TIME);
						$sth->bindParam(':user', $cms_user['id']);
						$sth->bindParam(':title', $file_info['title']);
						$sth->bindParam(':caption', $file_info['caption']);
						$sth->bindParam(':source', $file_info['source']);
						$sth->bindParam(':file_name', $file_info['file_name']);
						$sth->bindParam(':extension', $file_info['extension']);
						$sth->bindParam(':type', $file_info['type']);
						$sth->bindParam(':width', $file_info['width']);
						$sth->bindParam(':height', $file_info['height']);
						$sth->bindParam(':file_size', $file_info['file_size']);
						$sth->bindParam(':location', $file_info['location']);
						$sth->bindParam(':tags', $file_info['tags']);
						$sth->bindParam(':date_created', $file_info['date_created']);
					}
					
					if( $sth->execute() ){
						
						//////////////////////////////////////////////////////////////////////////////
						// Alert user of success
						
						$_SESSION['alert'] = ($_POST['replace_id'])? 'media_swapped' : 'media_added';
						
						//////////////////////////////////////////////////////////////////////////////
						// Thumbnails
									
						if($file_info['extension'] == 'jpg' || $file_info['extension'] == 'gif' || $file_info['extension'] == 'png'){
							
							// Create CMS thumb (Don't grab an empty error... check for error before adding to error[]
							$cms_thumb_error = make_thumb("../media/cms_thumbs/" . $file_info['file_name'] . "." . $file_info['extension'], false, $file_info['path'], $file_info['extension'], 100,  100,  false);
							if($cms_thumb_error){
								$error_each[] = $cms_thumb_error;
							}
							
							// Re-Create all thumbs
							foreach($settings['image_autothumb'] as $autothumb){
								$thumb_dimensions = explode(",", $autothumb);
								$temp_path = CMS_INSTALL_PATH . 'media/thumbnails/thumbnail.php?file_name='.$file_info['file_name'].'&extension='.$file_info['extension'].'&w='.$thumb_dimensions[0].'&h='.$thumb_dimensions[1].'&c='.$thumb_dimensions[2].'&refresh=code';
								$thumb_status = file_get_contents($temp_path);
								if($thumb_status != 'Success') {
									// Leave error alert off since this is not a horrible condition
									//$thumb_status = ($thumb_status == false)? 'Thumb update failed: '.$temp_path : $thumb_status;
									//$error[] = $thumb_status;
								} 
							}
							
						} elseif($file_info['extension'] == 'youtube') {
							/*
							foreach($settings['image_autothumb'] as $autothumb){
								$thumb_dimensions = explode(",", $autothumb);
								$error[] = make_thumb($settings['cms']['media_path'], $file_info['id'], "../media/cms_thumbs/youtube_" . $file_info['source'] . ".jpg", 'jpg', $thumb_dimensions[0],  $thumb_dimensions[1],  $thumb_dimensions[2]);
							}
							*/
						} elseif($file_info['extension'] == 'vimeo'){
							/*
							foreach($settings['image_autothumb'] as $autothumb){
								$thumb_dimensions = explode(",", $autothumb);
								$error[] = make_thumb($settings['cms']['media_path'], $file_info['id'], "../media/cms_thumbs/vimeo_" . $file_info['source'] . ".jpg", 'jpg', $thumb_dimensions[0],  $thumb_dimensions[1],  $thumb_dimensions[2]);
							}
							*/
						}
						
						//////////////////////////////////////////////////////////////////////////////
						// Add to revisions
						
						$revision_method = (isset($_GET['batch']))? 'batch' : ''; // Types batch, URL, computer
						$revision_type = ($_POST['replace_id'])? 'swapped' : 'uploaded';
						insert_activity($table = 'directus_media', $file_info['id'], $revision_type, $revision_method);
					} else {
						$_SESSION['alert'] = ($_POST['replace_id'])? 'error_swapping_media' : 'error_adding_media';
						$error_each[] = "Media not added to database!";
					}
				}
				
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				
			} // End extension check
			
			// Pass on and clear this items errors and continue to next
			$error = array_merge($error, $error_each);
			unset($error_each);
			
			// Create an array of all files uploaded
			$files_added[] = $file_info;
			
		} // End uploaded file(s) loop
		
	} // End of permissions check
	
} else {
	// For checked media items (Choose), get data from IDs
	foreach($_POST['media_ids_checked'] as $media_id_checked){
		$sth = $dbh->prepare("SELECT * FROM `directus_media` WHERE `active` = '1' AND `id` = :id ");
		$sth->bindParam(':id', $media_id_checked);
		$sth->execute();
		if( $file_info = $sth->fetch() ){
			$files_added[] = $file_info;
		}
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Create the media item for insertion/appending in javascript

			
$error = array_filter($error);

if(isset($_GET['batch'])){
	if(count($error) > 0){
		echo ''.implode("<br>", $error);
	} else {
		echo 'success';
		$_SESSION['alert'] = "batch_upload_complete";
	}
	die();
	
} elseif(count($error) > 0){
	// Show alerts for any errors
	$on_load = "top.close_alert();top.directus_alert('".addslashes(implode(" <br><br> ", $error))."');";
	
} elseif($_POST['type'] == 'inline' && count($files_added) > 0){
	// Edit page - Add inline media to TEXT
	$on_load = "top.format_text('" . $_POST['parent_item'] . "', 'image', '');";
	
} elseif($_POST['type'] == 'relational' && count($files_added) > 0){
	
	// Adapt js to replace item if needed
	$replace_item = ($_POST['replace'] == 'true')? "'media_".$_POST['replace_id']."'" : 'false';
	
	// Edit page - Add media
	$on_load = "top.modal_html_transfer('media_" . $_POST['parent_item'] . "', $replace_item);";
	
} else {
	// Default (and for swap media) - top.add_from_iframe();
	$on_load = "top.location = top.location;";
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
<html>
	<head>
		<title>Upload Media</title>
		<script src="<?PHP echo CMS_INSTALL_PATH;?>inc/js/jquery.js" type="text/javascript"></script>
		<script src="<?PHP echo CMS_INSTALL_PATH;?>inc/js/jquery-ui.js" type="text/javascript"></script>
		<script src="<?PHP echo CMS_INSTALL_PATH;?>inc/js/directus.js" type="text/javascript"></script>
	</head>
	<body onLoad="<?PHP echo $on_load; ?>">
		<span>Errors: <?PHP print_r($error); ?></span>
		<div id="modal_html"><?PHP
		
		//////////////////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////////////////
		
		if($_POST['type'] == 'inline'){
			
			$page_url_array = explode('/inc/',CMS_PAGE_URL);	// Requires directus directory name
			$site_path = $page_url_array[0];
			$temp_path = get_absolute_path($site_path . '/../' . $settings['cms']['media_path']);

			foreach($files_added as $media){		
				?><img src="<?PHP echo $temp_path . '/' . $media['source'];?>" alt="<?PHP echo str_replace('"', "'", $media['title']);?>" width="<?PHP echo $media['width'];?>" height="<?PHP echo $media['height'];?>" /><?PHP
			}
			
		} elseif($_POST['type'] == 'relational'){
			
			echo '<table>';
			foreach($files_added as $media){
				generate_media_relational($media['id'], $media['title'], $_POST['parent_item'], $media['extension'], $media['source'], $media['height'], $media['width'], $media['file_size']);
			}
			echo '</table>';
	
		} else {
			echo generate_media_item($file_info['id'], $file_info['title'], $file_info['extension'], $file_info['source'], $file_info['height'], $file_info['width'], $file_info['file_size'], CMS_TIME, CMS_USER_ID, $file_info['caption'], $search = false);
		}
		
		//////////////////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////////////////
		?></div>
	</body>
</html>