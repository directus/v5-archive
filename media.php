<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once("inc/setup.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function show_media_rows() {

	global $dbh;

	$query = "SELECT * FROM `directus_media` WHERE `active` = '1' ";
	if($_GET['range'] && $_GET['range'] != 'all'){
		if($_GET['range'] == 'week'){
			$query .= "AND `uploaded` >= DATE_SUB(NOW(), INTERVAL 7 DAY) ";
		} elseif($_GET['range'] == 'month'){
			$query .= "AND `uploaded` >= DATE_SUB(NOW(), INTERVAL 1 MONTH) ";
		} elseif($_GET['range'] == 'year'){
			$query .= "AND `uploaded` >= DATE_SUB(NOW(), INTERVAL 1 YEAR) ";
		}
	}
	
	$sql_order = ($_GET['order'] == 'desc') ? 'ASC' : 'DESC';
	
	if($_GET['sort'] == 'title' || $_GET['sort'] == 'extension' || $_GET['sort'] == 'file_size' || $_GET['sort'] == 'caption' || $_GET['sort'] == 'user'){
		$sql_sort = $_GET['sort'];
	} else {
		$sql_sort = 'uploaded';
	}
	
	$query .= "ORDER BY `$sql_sort` $sql_order ";
	
	foreach($dbh->query($query) as $media){	
		echo generate_media_item($media['id'], $media['title'], $media['extension'], $media['source'], $media['height'], $media['width'], $media['file_size'], $media['uploaded'], $media['user'], strip_tags($media['caption']), false, $media['tags']);
	}
	
	// Empty row
	echo '<tr class="item no_rows"><td colspan="8" raw="NULL">No media</td></tr>';
}

// Only get images if we're ajaxing the results
if(isset($_GET['ajax'])){
	show_media_rows();
	die();
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$cms_html_title = 'Media';
require_once("inc/header.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>

<div id="hidden_vars" style="display:none;">
	<div id="cms_table">directus_media</div>
</div>
	
<div id="page_header" class="clearfix"> 
	<h2 class="col_8">Media</h2> 
	<?PHP if($cms_user['media']){ ?><a class="modal button big color right add_header" id="add_item_button" href="inc/media_modal.php">Add New Media</a><?PHP } ?>
</div> 

<hr class="chubby"> 

<div class="table_actions clearfix"> 
		
		<?PHP if($cms_user['media']){ ?>
		<ul id="status_actions"> 
			<li id="status_action_delete"><a href="">Delete</a></li>
			<?PHP
			// Display batch upload button if there are files in the batch folder
			if(file_exists('media/batch/')){
				$batch_media = dir_list('media/batch/');
				if(count($batch_media) > 0){
					?>
					<li id="media_batch_upload"><a href="">Batch upload <span id="media_batch_total"><?PHP echo count($batch_media); ?></span> items<span id="media_batch_percent"></span></a></li>
					<?PHP
				}
			}
			?>
		</ul> 
		<?PHP } ?>
	
		<select id="media_date_range"> 
			<option value="all">All time</option> 
			<option value="year" <?PHP echo ($_GET['range'] == 'year')?'selected="selected"':'';?> >Past year</option>
			<option value="month" <?PHP echo ($_GET['range'] == 'month')?'selected="selected"':'';?> >Past month</option> 
			<option value="week" <?PHP echo ($_GET['range'] == 'week')?'selected="selected"':'';?> >Past week</option>  
		</select>
	
		<div id="live_filter_container"> 
			<label for="filter">Filter</label> 
			<input name="filter" id="media_filter" type="text"> 
		</div> 

</div> 

<table id="media_table" class="table actions" cellpadding="0" cellspacing="0" border="0" style="width:100%"> 
	<thead> 
		<tr> 
			<th class="check header"><!-- <input id="status_action_check_all" type="checkbox" name="" value=""> --></th> 
			<th class="thumb"></th> 
			<th class="field_title first_field header"><div class="wrap">Title<span class="ui-icon"></span></div></th> 
			<th width="10%" class="field_extension header"><div class="wrap">Type<span class="ui-icon"></span></div></th> 
			<th width="10%" class="field_size header"><div class="wrap">Size<span class="ui-icon"></span></div></th> 
			<th class="field_caption header"><div class="wrap">Caption<span class="ui-icon"></span></div></th> 
			<th width="10%" class="field_user header"><div class="wrap">User<span class="ui-icon"></span></div></th> 
			<th width="10%" class="field_date header headerSortUp"><div class="wrap">Uploaded<span class="ui-icon"></span></div></th> 
		</tr> 
	</thead> 
	<tbody class="check_no_rows media_dropzone_target" parent_item="" extensions="" media_type=""> 
		<?PHP show_media_rows(); ?>
	</tbody> 
</table>

<?PHP
require_once("inc/footer.php");
?>