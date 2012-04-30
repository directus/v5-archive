<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Connect to database, check security and setup variables

$setup_ajax = true;
require_once("setup.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Show media modal window

$error = array();

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get selected media item if there is one

if(is_numeric($_GET['id'])){
	$sth = $dbh->prepare("SELECT * FROM `directus_media` WHERE `active` = '1' AND `id` = :id LIMIT 1 ");
	$sth->bindParam(':id', $_GET['id']);
	$sth->execute();
	if($media_detail = $sth->fetch()){
		$extension = $media_detail['extension'];
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get allowed file types if need be

$extensions_allowed = array();
$extensions_string = '';
$extension_sql = '';

if($_GET['extensions']){
	$extensions_allowed = array_filter(explode(',', str_replace(' ', '', strtolower($_GET['extensions']))));
	$_GET['extensions'] = implode(',', $extensions_allowed);
	$extensions_string = '(Only: ' . implode(', ', $extensions_allowed) . ')';
	$extension_sql = "AND FIND_IN_SET(`extension`, :extension)";
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>

<div class="fog"></div> 

<div class="modal_window" style="display:none;"> 
	<div class="modal_window_header" class="clearfix"> 
		<?PHP echo (is_numeric($_GET['id']))? 'Edit Media' : 'Add New Media'; ?>
		<?PHP echo ($_GET['type'] == 'relational')? ' within ' . uc_convert($_GET['parent_item']) : ''; ?>
		<?PHP echo $extensions_string; ?>
		<a class="close_modal" href=""></a> 
	</div> 
	
	<form enctype="multipart/form-data" id="upload_form" target="iframe" action="inc/upload.php" method="post" name="upload_media_form">
		
		<div class="modal_window_content media_modal_window_content"> 
			
			<?PHP if(is_numeric($_GET['id'])){ ?><input type="hidden" value="<?PHP echo $_GET['id'];?>" name="replace_id"><?PHP } ?>
			<?PHP if(isset($_GET['type'])){ ?><input type="hidden" value="<?PHP echo $_GET['type'];?>" name="type"><?PHP } ?>
			<?PHP if(isset($_GET['replace'])){ ?><input type="hidden" value="<?PHP echo $_GET['replace'];?>" name="replace"><?PHP } ?>
			<?PHP if(isset($_GET['parent_item'])){ ?><input type="hidden" value="<?PHP echo $_GET['parent_item'];?>" name="parent_item"><?PHP } ?>
			<?PHP if(isset($_GET['extensions'])){ ?><input type="hidden" value="<?PHP echo $_GET['extensions'];?>" name="extensions"><?PHP } ?>
			
			<?PHP 
			if(!is_numeric($_GET['id'])){ 
				?>
				<div class="media_modal_options clearfix">
					<ul>
						<li><a class="media_modal_type_change current" area="upload" href="#">Upload from Computer</a></li>
						<li><a class="media_modal_type_change" area="url" href="#">Add from URL</a></li>
						<li><a class="media_modal_type_change" area="video" href="#">Link to Video</a></li>
					<?PHP 
					if($_GET['type'] == 'relational' || $_GET['type'] == 'inline'){ 
						?><li><a id="media_choose" class="media_modal_type_change" area="choose" href="#">Choose from Existing</a></li><?PHP 
					} 
					?>
					</ul>
				</div>
				<?PHP 
			} 
			?>
			
			<div class="fieldset" id="media_modal_area_add"> 	
				<?PHP 
				if(is_numeric($_GET['id'])){
					?>
					<div class="field" > 
						<?PHP
						//////////////////////////////////////////////////////////////////////////////
						// Get media details
						
						if($media_detail["type"] == 'embed') {
							$length_size = seconds_convert($media_detail['file_size']);
							if($extension == 'youtube'){
								$link = 'http://www.youtube.com/watch?v='.$media_detail["source"];
								$link_text = 'http://www.youtube.com/watch?v=<b>'.$media_detail["source"].'</b>';
							} elseif($extension == 'vimeo'){
								$link = 'http://vimeo.com/'.$media_detail["source"];
								$link_text = 'http://vimeo.com/<b>'.$media_detail["source"].'</b>';
							} elseif($extension == '5min'){
								$link = 'http://www.5min.com/Video/'.$media_detail["source"];
								$link_text = 'http://www.5min.com/Video/<b>'.$media_detail["source"].'</b>';
							}
							$dimensions = $media_detail['width'] . ' x ' . $media_detail['height'] . ' - ';
						} else {	
							if($extension == 'jpg' || $extension == 'gif' || $extension == 'png'){
								$length_size = byte_convert($media_detail['file_size']);
								$link = '../'.$settings['cms']['media_path'].$media_detail["source"]; 
								$dimensions = $media_detail['width'] . ' x ' . $media_detail['height'] . ' - ';
							} else {
								$length_size = byte_convert($media_detail['file_size']);
								$link = '../'.$settings['cms']['media_path'].$media_detail["source"]; 
								$dimensions = '';
							}
							
							// Get all non-video link texts
							$page_url_array = explode('/inc/',CMS_PAGE_URL);	// Requires directus directory name
							$site_path = $page_url_array[0];
							$temp_path = get_absolute_path($site_path . '/../' . $settings['cms']['media_path'] . $media_detail["source"]);
							$link_text = highlight_custom($media_detail['source'], $temp_path, '<b>', '</b>');
						}
						
						$extension = ucwords($extension);
						
						//////////////////////////////////////////////////////////////////////////////
						?>
						<table class="media_modal_details" cellpadding="0" cellspacing="0" border="0" style="width:100%">
							<tr>
								<?PHP if($extension == 'MP3' || $extension == 'OGG' || $extension == 'WAV'){ ?>
								<td class="media_modal_thumb audio">
								<?PHP echo generate_media_image($extension, $media_detail["source"], $media_detail["height"], $media_detail["width"], $media_detail["file_size"], 100); ?>
								</td>
								<?PHP } else { ?>
								<td class="media_modal_thumb">
									<a href="<?PHP echo $link;?>" target="_blank">
									<?PHP echo generate_media_image($extension, $media_detail["source"], $media_detail["height"], $media_detail["width"], $media_detail["file_size"], 100); ?>
									</a>
								</td>
								<?PHP } ?>
								<td>
									<?PHP 
									echo 'Uploaded by '.$cms_all_users[$media_detail['user']]['username'] . ' ' . contextual_time(strtotime($media_detail['uploaded'])) . '<br>';
									echo $dimensions . $extension . ' - ' . $length_size . '<br>';
									echo ($link_text)?$link_text.'<br>':'';
	
									// If user is allowed to manage media then enable swapping and deleting
									if($cms_user['media']){ 
										?><a id="media_modal_swap" href="#">Swap</a> or <a id="media_modal_delete" class="delete" media_id="<?PHP echo $media_detail["id"];?>" href="#">Delete</a><?PHP 
									} 
									?>
								</td>
							</tr>
						</table>
					</div>
					
					<?PHP
					//////////////////////////////////////////////////////////////////////////////
					
					if($media_detail['extension'] == 'youtube'){ 
						?>
						<div class="field">
							<object width="650" height="350">
							<param name="movie" value="http://www.youtube.com/v/<?PHP echo $media_detail["source"]; ?>"></param>
							<param name="allowFullScreen" value="true"></param>
							<param name="allowscriptaccess" value="always"></param>
							<embed src="http://www.youtube.com/v/<?PHP echo $media_detail["source"]; ?>" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="650" height="350"></embed>
							</object>
						</div>
						<?PHP 
					} 
					
					//////////////////////////////////////////////////////////////////////////////
					
					if($media_detail['extension'] == 'vimeo'){ 
						?>
						<div class="field">
							<object width="650" height="350">
							<param name="allowfullscreen" value="true" />
							<param name="allowscriptaccess" value="always" />
							<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=<?PHP echo $media_detail["source"]; ?>&amp;server=vimeo.com&amp;show_title=0&amp;show_byline=0&amp;show_portrait=0&amp;color=ffffff&amp;fullscreen=1" />
							<embed src="http://vimeo.com/moogaloop.swf?clip_id=<?PHP echo $media_detail["source"]; ?>&amp;server=vimeo.com&amp;show_title=0&amp;show_byline=0&amp;show_portrait=0&amp;color=ffffff&amp;fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="650" height="350">
							</embed>
							</object>
						</div>
						<?PHP 
					} 
					
					//////////////////////////////////////////////////////////////////////////////
					
					if($media_detail['extension'] == '5min'){ 
						?>
						<div class="field">
							<object width="650" height="350" id="FiveminPlayer">
							<param name="allowfullscreen" value="true"/>
							<param name="allowScriptAccess" value="always"/>
							<param name="movie" value="http://embed.5min.com/<?PHP echo $media_detail["source"]; ?>/"/>
							<param name="wmode" value="opaque" />
							<embed name="FiveminPlayer" src="http://embed.5min.com/<?PHP echo $media_detail["source"]; ?>/" type="application/x-shockwave-flash" width="650" height="350" allowfullscreen="true" allowScriptAccess="always" wmode="opaque">
							</embed>
							</object>
						</div>
						<?PHP 
					} 
					
					//////////////////////////////////////////////////////////////////////////////
					
					if(in_array($media_detail['extension'], array('mp4','m4v','ogv','webm'))){ 
						?>
						<div class="field">
							<video src="../<?PHP echo $settings['cms']['media_path'] . $media_detail["source"];?>" width="650" height="350" controls>
								<p>This browser doesn't support HTML5 video (<a href="http://www.google.com/chrome/" target="_blank">Try Chrome</a>)</p>
							</video>
						</div>
						<?PHP 
					} 
					
				}
				
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				
				?>
				<div class="field" id="upload_media_pane" <?PHP echo (is_numeric($_GET['id']))?'style="display:none;"':'';?>> 
					<div id="media_modal_area_upload" style="position:relative;"> 
						<label for="" title="<?PHP echo ini_get('upload_max_filesize')."/".ini_get('post_max_size'); ?>">Upload file from computer</label>
						<div style="height:30px;">&nbsp;</div>
						<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
						<input class="short" name="upload_media[]" type="file" multiple="multiple" style="position:absolute; bottom:0px; padding-bottom:6px; left:0px; width:100%; height:14px; z-index:2;" id="upload_media_input"> 
						<div id="upload_media_dropzone" style="position:absolute; bottom:0px; right:0px; width:700px; height:16px; padding:6px 6px 6px 6px; background-color:#dcdcdc; color:#484848; z-index:1; text-align:right; display:none;">
							Try Dropping files here
						</div>
					</div> 
					<div id="media_modal_area_url" style="display:none;"> 
						<label for="">Add from URL</label><br> 
						<input class="short" name="url_media" type="text" value="http://"> 
						<div class="instruct">Path to image on another site or a URL from video sites like YouTube or Vimeo.<br> 
							(ie. http://www.youtube.com/watch?v=txqiwrbYGrs)</div> 
					</div>
					<?PHP if(is_numeric($_GET['id'])){ ?><div><a id="toggle_media_upload" href="">or add from URL</a></div><?PHP } ?>
				</div> 
					
					
				<div class="field"> 
					<label class="primary">Title</label><br> 
					<input class="text" name="title" type="text" value="<?PHP echo esc_attr($media_detail['title']);?>">
				</div>
				 
				<?PHP 
				if($media_detail['date_created'] && $media_detail['date_created'] != '0000-00-00 00:00:00'){
					?>
					<div class="field"> 
						<label class="primary">Date Created</label><br> 
						<input class="text" name="date_created" type="text" disabled="disabled" value="<?PHP echo date('M. jS Y \a\t g:ia',strtotime($media_detail['date_created']));?>">
					</div>
					<?PHP
				}
				?>
				
				<div class="field"> 
					<label class="primary">Location</label><br> 
					<input class="text" name="location" type="text" value="<?PHP echo ($media_detail['location'])? esc_attr($media_detail['location']) : '';?>">
				</div>
				
				<div class="field"> 
					<label class="primary">Caption</label><br> 
					<textarea rows="8" name="caption"><?PHP echo esc_attr(br2nl($media_detail['caption']));?></textarea> 
				</div>
				
				<div class="field"> 
					<?PHP
					$tags = explode(',',$media_detail['tags']);
					$tags = array_filter($tags);
					?>
					<label class="primary">Tags</label> <br> 
					<input id="input_modal_tags" class="short_half tag_input tag_autocomplete" type="text"> <input tabindex="-1" field="modal_tags" id="tags" class="tag_add button pill" type="button" value="Add"> 
					<input id="hidden_modal_tags" type="hidden" name="tags" value="<?PHP echo $media_detail['tags'];?>" class=""> 
					<div id="tags_modal_tags" class="tags_list clearfix"> 
						<?PHP
						foreach($tags as $tag_key => $tag_value){
							?>
							<span class="tag" tag="<?PHP echo $tag_value;?>"><?PHP echo $tag_value;?><a class="tag_remove" tabindex="-1" href="#">&times;</a></span> 
							<?PHP
						}
						?>
					</div> 
				</div>
				
				<?PHP 
				if(is_numeric($_GET['id']) && count($settings['image_autothumb']) > 0){
					sort($settings['image_autothumb'], SORT_NUMERIC); 
					?>
					<div class="field"> 
						<label class="primary">Thumbnails</label><br> 
						<?PHP
						foreach($settings['image_autothumb'] as $autothumb){
							$thumb_dimensions = explode(",", $autothumb);
							$cropped = ($thumb_dimensions[2] == 'true')? 'Cropped' : 'Shrink to fit';
							echo '<a href="'.CMS_INSTALL_PATH.'media/thumbnails/'.$media_detail['file_name'].'.'.$media_detail['extension'].'?w='.$thumb_dimensions[0].'&h='.$thumb_dimensions[1].'&c='.$thumb_dimensions[2].'" target="_blank">'.$thumb_dimensions[0].' x '.$thumb_dimensions[1].' - '.$cropped.'</a>';
							echo ' <a href="'.CMS_INSTALL_PATH.'media/thumbnails/thumbnail.php?file_name='.$media_detail['file_name'].'&extension='.$media_detail['extension'].'&w='.$thumb_dimensions[0].'&h='.$thumb_dimensions[1].'&c='.$thumb_dimensions[2].'&refresh=true" class="ui-icon ui-icon-arrowrefresh-1-s" style="display:inline-block; margin-bottom:-4px;" target="iframe"></a><br>';
						}
						?>
					</div>
					<?PHP 
				} 
				?>
				
			</div>
			
			<?PHP
			
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// Choose from existing media
			
			if($_GET['type'] == 'relational' || $_GET['type'] == 'inline'){
				?>
				<div id="media_modal_area_choose" style="display:none;">
					<div class="table_actions clearfix">
						<!--
						<select id="media_date_range"> 
							<option value="all">All time</option> 
							<option value="year" <?PHP echo ($_GET['range'] == 'year')?'selected="selected"':'';?> >Past year</option>
							<option value="month" <?PHP echo ($_GET['range'] == 'month')?'selected="selected"':'';?> >Past month</option> 
							<option value="week" <?PHP echo ($_GET['range'] == 'week')?'selected="selected"':'';?> >Past week</option>  
						</select>
						-->
						<div id="live_filter_container"> 
							<label for="filter">Filter</label> 
							<input name="filter" id="modal_media_filter" type="text"> 
						</div>
					</div>
					<table id="media_table" class="table actions" cellpadding="0" cellspacing="0" border="0" style="width:100%"> 
						<thead> 
							<tr> 
								<th class="check"><!--<input id="status_action_check_all" type="checkbox" name="" value="">--></th> 
								<th class="thumb"></th> 
								<th class="field_title first_field"><div class="wrap">Title<span class="ui-icon"></span></div></th> 
								<th width="10%" class="field_type"><div class="wrap">Type<span class="ui-icon"></span></div></th> 
								<th width="10%" class="field_size"><div class="wrap">Size<span class="ui-icon"></span></div></th> 
								<th class="field_caption"><div class="wrap">Caption<span class="ui-icon"></span></div></th> 
								<th width="10%" class="field_user"><div class="wrap">User<span class="ui-icon"></span></div></th> 
								<th width="10%" class="field_date"><div class="wrap">Uploaded<span class="ui-icon"></span></div></th> 
							</tr> 
						</thead> 
						<tbody> 
							
							<?PHP
							// Loop through and show all media that is not a thumbnail
							$sth = $dbh->prepare("SELECT * FROM `directus_media` WHERE `active` = '1' $extension_sql ORDER BY `uploaded` DESC LIMIT 1000");
							$sth->bindParam(':extension', $_GET['extensions']);
							$sth->execute();
							while($media = $sth->fetch()){
								echo generate_media_item($media['id'], $media['title'], $media['extension'], $media['source'], $media['height'], $media['width'], $media['file_size'], $media['uploaded'], $media['user'], strip_tags($media['caption']), $search = false, $tags = '', true);
							}
							?>
							
						</tbody> 
					</table>
				</div>
				<?PHP
			}
			
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			?>
				
		</div> 
		
		<div class="modal_window_actions">
			<div class="pad_full_small">
				<input class="button big color now_activity" activity="<?PHP echo (is_numeric($_GET['id']))? 'saving':'uploading';?>" type="submit" value="Save Media"> 
				<span>or <a class="cancel cancel_modal" href="browse.php?table=<?PHP echo $table_rows['name']; ?>">Cancel</a></span>
			</div>
		</div>
	
	</form>
</div>