//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// jquery-ui = Sortable and Shake and Datepicker

var ask_to_save = false;
var base_path = '';
var batch_id = 1;
var caret_info = new Object();
var modal_windows_open = new Array();
var dragging_file = false;
var autologoff_seconds = 0;

$(document).ready(function(){
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Alternating row color
	
	reorder_live();

	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Get base path to javascript file for relative paths
	
	base_path = $('#base_path').attr('base_path');
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Lazy-Load images (instant)
	
	$("img.lazyload").each(function() {
		$(this).attr("src", $(this).attr("lazy-src"));
		//$(this).removeAttr("lazy-src");
	});
		
	// Run initially
	load_images_above_fold();
	
	// Run on window scroll
	$(window).scroll(function() {
		load_images_above_fold();
	});
	
	// Also updates on modal scroll -- see live()
	
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Global
	
	// Close stay logged in alert
	$("#stay_logged_in").live("click", function(event) {
		event.stopPropagation();
		close_alert();
		window.clearTimeout(logoff_timer);
		autologoff_seconds = 0;
		return false;
	});
	
	// To fix alert links
	$("#alert_container a").live("click", function(event) {
		event.stopPropagation();
		window.clearTimeout(logoff_timer);
		window.open($(this).attr('href'));
		return false;
	});
	
	// Close open stuff when clicking elsewhere
	$("html").live("click", function(event) {
        if(! mouse_is_inside_view) {
			$(".view_dropdown").siblings("ul").hide();
		}
		if(! mouse_is_inside_header) {
			$(".header_dropdown").siblings("ul").hide();
		}
		if(! mouse_is_inside_edit_save) {
			$("#save_options").hide();
			$("#save_button").removeClass("open");
		}
		
		// Close alerts
		$("#alert_container .alert_box:not(.persistent)").fadeOut(200);
    });
    
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Modal windows

	$(".modal").live("click", function() {
		get_modal( $(this).attr("href") );
		return false;
	});
	
	$(".edit_table_view").live("click", function() {
		var id = $(this).attr("csv-id");
		var csv = $("#"+id).val();
		get_modal($(this).attr("href"), "csv-id="+id+"&csv="+csv);
		return false;
	});
	
	$("#done_table_view").live("click", function() {
		var id = $(this).attr("csv-parent");
		
		var csv = new Array();
		$(".edit_table_content tr.save").each(function(index) {
			var md_array = new Array();
			$(this).find("textarea").each(function(index) {
				md_array.push($(this).val());
			});
			csv.push(md_array);
		});
		
		csv_str = array_to_csv(csv);
		$("#"+id).val(csv_str);
		close_alert();
		close_modal();
		return false;
	});
	
	$(window).resize(function() {
		modal_resize();
		edit_table_resize();
		load_images_above_fold();
	});
	
	$(".fog, .close_modal, .cancel_modal").live('click', function() {
		close_modal();
		close_edit_table();
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Close dialog
	
	$(".close_dialog, .cancel_dialog").live('click', function() {
		close_dialog();
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Open dialog notes
	
	$(".dialog_note").live('click', function() {
		directus_dialog(false, $(this).attr('title'), $(this).attr('message'));
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Table Rows Hover
	
	
	// $("table tr:not(.field_options tr, #inbox tr, #message tr)").live("mouseover mouseout", function(event) {
	// 		if (event.type == "mouseover") {
	// 			$(this).addClass("focus");
	// 		} else {
	// 			$(this).removeClass("focus");
	// 		}
	// 	});
	
	// $("table tr").live("mouseover mouseout", function(event) {
	// 		if (event.type == "mouseover") {
	// 			$(this).find(".item_actions").show();
	// 		} else {
	// 			$(this).find(".item_actions").hide();
	// 		}
	// 	});
	
	$("#users_table tr").live("mouseover mouseout", function(event) {
		if (event.type == "mouseover") {
			$(this).find(".edit_user").show();
		} else {
			$(this).find(".edit_user").hide();
		}
	});
	
	$(".simple_sortable tr").live("mouseover mouseout", function(event) {
		if(event.type == "mouseover") {
			$(this).find(".edit_fancy").show();
		} else {
			$(this).find(".edit_fancy").hide();
		}
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Toggle modules by clicking header
	
	$(".item_module.closed .item_module_title").addClass("collapsed");
	
	$(".item_module.toggle .item_module_title").live('click', function() {
		if($(this).parent().hasClass("closed")) {
			$(this).parent().removeClass("closed");
			$(this).find(".item_module_toggle").removeClass("ui-icon-carat-1-n");
			$(this).find(".item_module_toggle").addClass("ui-icon-carat-1-s");
			$(this).removeClass("collapsed");
			$(this).siblings(".item_module_box").show();
		} else {
			$(this).parent().addClass("closed");
			$(this).find(".item_module_toggle").removeClass("ui-icon-carat-1-s");
			$(this).find(".item_module_toggle").addClass("ui-icon-carat-1-n");
			$(this).addClass("collapsed");
			$(this).siblings(".item_module_box").hide();
		}
		
		// Now update the session so it stays open
		var settings_open_tables = ",";
		$("#settings_tables div.item_module").each(function(index) {
			if(!$(this).hasClass("closed")){
				settings_open_tables = settings_open_tables + $(this).attr('id').substr(20) + ",";
			}
		});
		set_session('settings_open_table', settings_open_tables);
		
	});
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Numeric only control handler
	
	jQuery.fn.ForceNumericOnly = function(){
		return this.each(function(){
			$(this).keydown(function(e){
				var key = e.charCode || e.keyCode || 0;
				// allow backspace, tab, delete, arrows, numbers/letters and keypad numbers ONLY
				return (
				key == 8 || 
				key == 9 ||
				key == 46 ||
				(key >= 37 && key <= 40) ||
				(key >= 48 && key <= 90) ||
				(key >= 96 && key <= 105));
			});
		});
	};
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Forgot password
	
	$("#forgot_password").live('click', function() {
		if($("#login_email").val() == ''){
			directus_alert('email_required');
			//return false;
		} else {
			document.forms['login_form'].forgot.value = 'true';  
			document.login_form.submit();
		}
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Media DropZones
	
	$("#media_dropzone_input").change(function() { 
		directus_alert('now_uploading');
		$('#media_dropzone').submit();
		$('#media_dropzone').delay(50).hide();
	});
	
	//////////////////////////////////////////////////////////////////////////////
	
	$('.media_dropzone_target').live("dragover", function(event){
    	
    	// Move the dropzone to this element
		var offset = $(this).offset();
		$('#media_dropzone').css('top', offset.top);
		$('#media_dropzone').css('left', offset.left);
		$('#media_dropzone').width( $(this).outerWidth() );
		$('#media_dropzone').height( $(this).outerHeight() );
    	
    	$('#media_dropzone_type').val( $(this).attr('media_type') );
    	$('#media_dropzone_parent_item').val( $(this).attr('parent_item') );
    	$('#media_dropzone_extensions').val( $(this).attr('extensions') );
    	
    	$('#media_dropzone').delay(50).show();
    });
	
	//////////////////////////////////////////////////////////////////////////////
	// dragexit dragleave drop mouseout
	
	$('#media_dropzone').live("dragexit dragleave drop mouseout", function(){
		//$('#media_dropzone').delay(50).hide();
		setTimeout("$('#media_dropzone').hide()",50); // Fix for "Aw, snap!" error within webkit
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$(".now_activity").live('click', function() {
		directus_alert('now_'+$(this).attr('activity'));
	});
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Dashboard
	
	$("#create_backup").click(function(event){
		directus_alert('now_backing_up');
		$.ajax({
			type: "POST",
			data: "backup=now",
			url: "dashboard.php",
			success: function(msg){
				if(msg == 'success'){
					window.location.href = 'dashboard.php';
				} else {
					directus_alert(msg);
				}
			}
		});
	});
	
	//////////////////////////////////////////////////////////////////////////////
	
	$(".open_media").live('click', function(){
		id = $(this).attr('media_id');
		open_media(id);
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// On dashboard page, add endless auto-pagination
	
	if(sPage == 'dashboard.php'){
		$(window).scroll(function(){
			if($(document).scrollTop()+$(window).height() == $(document).height()){
				directus_alert('now_loading');
				$.ajax({
					type: "POST",
					data: "limit_start="+$('#activity tbody tr').size(),
					url: "dashboard.php",
					success: function(msg){
						if(msg){
							$('#activity tbody').append(msg);
						} else {
							directus_alert('all_activity_visible');
						}
						close_alert();
					}
				});
			}
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Browse Page
	
	$('#table_filter').focus();
	
	// view options dropdown 
	$(".view_dropdown").live("click", function() {
		$(this).siblings("ul").toggle();
		return false;
	});
	
	// header options dropdown 
	$(".header_dropdown").live("click", function() {
		$(this).siblings("ul").toggle();
		return false;
	});
	
	// count active and inactive items
	var countactive = $("tr.status_active").size();
	var countinactive = $("tr.status_inactive").size();
	var countall = countactive + countinactive;
	
	// build the status view options
	$(".view_dropdown").find(".count").text("(" + countall + ")");
	$(".toggle_all").find(".count").text("(" + countall + ")");
	$(".toggle_active").find(".count").text("(" + countactive + ")");
	$(".toggle_inactive").find(".count").text("(" + countinactive + ")");
	
	// toggle status view options
	$("#view_options li ul li a").live("click", function() {
		$("#view_options li ul li").removeClass("current");
		$(this).parent("li").addClass("current");
		$("#status_action_check_all").attr("checked",false);
		$(".status_action_check").attr("checked",false);
		$(".view_dropdown").siblings("ul").toggle();
		var count = $(this).find(".count").text();
		if($(this).hasClass("toggle_all")) {
			$("tbody tr").show();
			$("tbody tr.status_deleted").hide();
			$("#status_action_delete").removeClass("inactive");
			$(".view_dropdown").html("Viewing All <span class='count'>" + count + "</span><span class='icon'></span>");
		}
		if($(this).hasClass("toggle_active")) {
			$("tbody tr").hide();
			$("tbody tr.status_active").show();
			$("#status_action_delete").removeClass("inactive");
			$(".view_dropdown").html("Viewing Active <span class='count'>" + count + "</span><span class='icon'></span>");
		}
		if($(this).hasClass("toggle_inactive")) {
			$("tbody tr").hide();
			$("tbody tr.status_inactive").show();
			$("#status_action_delete").removeClass("inactive");
			$(".view_dropdown").html("Viewing Inactive <span class='count'>" + count + "</span><span class='icon'></span>");
		}
		if($(this).hasClass("toggle_deleted")) {
			$("tbody tr").hide();
			$("tbody tr.status_deleted").show();
			$("#status_action_delete").addClass("inactive");
			$(".view_dropdown").html("Viewing Trash<span class='icon'></span>");
		}
		
		check_no_rows();
		return false;
	});
		
	// check all bulk items
	$("#status_action_check_all").live("click", function() {
		$(".status_action_check").not(":hidden").attr("checked", $("#status_action_check_all").is(":checked"));
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// kill options dropdowns 
	
	var mouse_is_inside_view = false;
	var mouse_is_inside_header = false;

    $("#view_options").hover(function(){ 
        mouse_is_inside_view = true; 
    }, function() { 
        mouse_is_inside_view = false; 
    });

	$("#header_options").hover(function(){ 
        mouse_is_inside_header = true; 
    }, function() { 
        mouse_is_inside_header = false; 
    });

	//////////////////////////////////////////////////////////////////////////////
	// Generate email list
	
	$("a.generate_email_list").click(function(){
		var emails = [];
		$('.field_'+$(this).attr('field')).each(function(index) {
			email = $(this).not('.header').find('div:visible').html();
			if(email){
				emails.push(email);
			}
		});
		directus_dialog('email_list', 'Addresses from "'+uc_convert($(this).attr('field'))+'"', emails.join(','));
		
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Click on item in browse
	
	$("#browse_table .item .editable").live("click", function() {
		id = $(this).parent().attr('id').substring(5);
		table = $('#cms_table').text();
		window.location.href = 'edit.php?table='+table+'&item='+id;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Save header options
	
	$("#header_options .header_option").live("click", function() {
		
		headers_checked = $('.header_option').filter(':checked').length;
		
		// Check that only 6 or less columns are set
		if(headers_checked > 8){
			$(this).attr('checked', false);
			directus_alert('header_limit');
		} else {
		
			if(headers_checked == 8){
				$('.header_option').not(':checked').attr('disabled', true);
			} else {
				$('.header_option').not('.primary_field').attr('disabled', false);
			}
			
			$('#throbber').animate({ opacity: 1.0 }, 0);
			var tdfield = "." + $(this).attr("name");
			var table = $("#cms_table").html();
			field_names = ',';
			$('.header_option:checked').each(function () {
				field_names += $(this).attr("name").substring(6) + ",";
			});
			
			/* URL might need base_path if used in plugins */
			data = 'action=set_preference&type=header_fields&name='+table+'&value='+field_names;
			$.ajax({
				url: "inc/ajax.php",
				type: "POST",
				data: data,
				success: function(msg){
					if(msg){
						// Uncomment to see "Preference Saved" each time
						//directus_alert(msg);
					}
					$('#throbber').animate({ opacity: 0.0 }, 1000);
				}
			});
				
			if($(this).is(":checked")) {
				$(tdfield).show();
			} else {      
				$(tdfield).hide();
			}
		}
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Media and Browse page status change buttons
	
	$("#status_action_active,#status_action_inactive,#status_action_delete").click(function(event){

		var table = $('#cms_table').text();
		var status = false;
		var selected_ids = [];
		
		$("input.status_action_check:checked").each(function(){
			selected_ids.push($(this).attr('id'));
		});

		if(selected_ids.length == 0){
			directus_alert('status_change_items_required');
			return false;
		}

		if($(this).attr('id') == 'status_action_active'){
			status = 1;
		} else if($(this).attr('id') == 'status_action_delete'){
			status = 0;
		} else {
			status = 2;
		}
	
		if(table == "directus_media"){
			var r = confirm("Are you sure? You can't undo this...");
			if(r!=true) {
				return false;
			}
		}
  
		change_status(table, selected_ids, status, type='reload');

		return false;
	});

	//////////////////////////////////////////////////////////////////////////////
	// Browse page reorder

	$(".sortable").sortable({
		axis: 'y',
		containment: 'parent',
		/*helper: function(e, tr){
			var $originals = tr.children();
			var $helper = tr.clone();
			//$helper.css("width", "1000px");
			//$helper.width("100%");
			//console.log("TR width: "+$helper.width());
			$helper.children().each(function(index){
				// Set helper cell sizes to match the original sizes
				temp_width = $originals.eq(index).width();
				$(this).width(temp_width);
				//$(this).attr('width', 1000);
			});
			return $helper;
		},*/
		handle: '.handle',
		items: '.item:visible',
		opacity: '0.4',
		//placeholder: 'sortable_placeholder_browse',
		tolerance: 'pointer',
		//appendTo: 'body',
		update: function(e, ui){
			directus_alert('now_reordering');
			
			data = $(this).sortable("serialize") + "&action=set_sort&table=" + $('#cms_table').text();
			
			$(this).trigger("update");
			order = 1;
			$(this).children().each(function(){
				$(this).find('.order_field').attr('raw', order++);
			});
			
			$.ajax({
				url: "inc/ajax.php",
				type: "POST",
				data: data,
				success: function(msg){
					if(msg){
						directus_alert(msg);
					}
					
					$("#alert_container .alert_box").fadeOut(200);
				}
			});
			
		}
	});
	$( "#sortable" ).disableSelection();
	
	//////////////////////////////////////////////////////////////////////////////
	// Sort table columns	
	
	$('#browse_table th.header').live('click', function(){
		directus_alert('now_resorting');
		
		if($(this).hasClass('headerSortDown')){
			direction = "DESC";
			direction_class = "headerSortUp";
		} else {
			direction = "ASC";
			direction_class = "headerSortDown";
		}
		
		$('#browse_table th.header').removeClass('headerSortUp');
		$('#browse_table th.header').removeClass('headerSortDown');
		$(this).addClass(direction_class);
		
		sort = $(this).attr('sort');
		table = $('#cms_table').text();
		
		$.ajax({
			url: "browse.php?table="+table+"&ajax=true&sort="+sort+"&direction="+direction,
			success: function(msg){
				if(msg){
					$('#browse_table tbody').html(msg);
					load_images_above_fold();
					check_no_rows();
					histogram_live();
				} else{
					directus_alert('error_sorting');
				}
				$("#alert_container .alert_box").fadeOut(200);
			}
		});
		
	});
	
	/*
	$("#browse_table").tablesorter({
		sortList: [[0,0]],
		textExtraction: function(node) { 
			// extract data from markup and return it  
			//console.dir(child.getAttribute("raw"));
			// Beware of text nodes (not element nodes) - remove all spaces/returns between tags for this
			if(node.firstChild.lastChild){
				return node.firstChild.lastChild.getAttribute("raw");
			}
		}, 
		headers: { 1: { sorter: false } }
	}); 
	*/
	
	//////////////////////////////////////////////////////////////////////////////
	// Live Search tables
	
	$("#table_filter").keyup(function(){
		table_filter($(this).val(), 'browse_table');
	});
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Edit / Add Page
	
	$("#save_toggle").live('click', function(){
		if($("#save_button").hasClass("open")){
			$("#save_options").hide();
			$("#save_button").removeClass("open");
		} else {
			$("#save_options").show();
			$("#save_button").addClass("open");
		}
	});
	
	var mouse_is_inside_edit_save = false;

    $("#save_actions").hover(function(){ 
        mouse_is_inside_edit_save = true; 
    }, function() { 
        mouse_is_inside_edit_save = false; 
    });
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Save item
	
	$(".edit_save_button").live('click', function(){

		ask_to_save=false;
		var submit_errors = [];
		
		parent_form = (modal_windows_open.length > 0)? '#modal_level_'+modal_windows_open.length+' ' : '';
		
		// Ensure all tag inputs are submitted
		$(parent_form+'.tag_input').each(function(index){
			if($(this).val() != ''){
				//submit_errors.push('<b>' + uc_convert($(this).attr('name')) + '</b> tags have not been added');
				$(this).parent().find('.tag_add').click();
			}
		});
		
		// Validate email address format
		$(parent_form+'.validate_email').each(function(index){
			if($(this).val() != '' && !$(this).val().match(/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/)){
				submit_errors.push('email_format_' + uc_convert($(this).attr('name')));
				//$(this).focus();
			}
		});
		
		// Ensure required fields have a value
		$(parent_form+'.required').each(function(index){
			if(!$(this).val() || $(this).val() == 'NULL'){
				submit_errors.push('required_field_'+uc_convert($(this).attr('name')));
				//$(this).focus();
			}
		});
		
		// Ensure required multi fields have a value
		$(parent_form+'.required_multi').each(function(index){
			var name = $(this).attr('require');
			var checked_count = $(parent_form+'input[name="'+name+'"]:checked').length;
			if(checked_count == 0){
				submit_errors.push('required_field_'+uc_convert(name.replace('[]','')));
			}
		});
		
		// Submit the forms
		if(submit_errors.length > 0){
			directus_alert(submit_errors);
		} else if(modal_windows_open.length > 0){
			directus_alert('now_saving');
			$(parent_form+'form').submit();
		} else {
			$('#save_and').val($(this).attr('save_and'));
			document.edit_form.submit();
		}
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Send an item message
	
	$("#item_message_send").click(function(){
		
		directus_alert('now_sending');
		
		var table = $(this).attr('table');
		var row = $(this).attr('row');
		var message = $('#item_message').val();
		
		$.ajax({
			url: "messages.php",
			type: "POST",
			data: "submit=true&item_message=true&table="+table+"&row="+row+"&message="+message,
			success: function(msg){
				$('#item_messages').prepend(msg);
				$('#item_message').val('');
				$('#no_item_messages').remove();
				$('#item_message_count').text( $('#item_messages').children().length );
				
				$("#alert_container .alert_box").fadeOut(200);
			}
		});
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Are you sure you want to leave
	
	$('#edit_form .field :input').change(function(){
		if(!ask_to_save){
			ask_to_save = true;
		}
	});
    
	window.onbeforeunload = function(){
		if(ask_to_save){
			return "You have not saved you changes, are you sure you want to leave this page?";
		}
	};
    
    //////////////////////////////////////////////////////////////////////////////
    
	$("#edit_page_delete").click(function(){
		var table = $('#cms_table').text();
		var id = $('#cms_id').text();
		change_status(table, id, status=0, type='edit');
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Revisions
	
	$(".revert_item").click(function(){
		directus_alert('now_reverting');
		datetime = $(this).attr('title');
		confirm_href = $(this).attr('confirm_href');
		var revision_confirm = confirm('Are you sure?\nThis item will revert to EXACTLY how it was on:\n\n'+datetime+'\n');
		if (revision_confirm == true) {
			window.location.href = confirm_href;
		} else {
			close_alert();
		}
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Buttons for edit page text fields
	
	$('.text_format_button').live('click', function(){
		
		format = $(this).attr('format');
		el = $(this).parent().parent().parent().next('textarea');
		id = el.attr('id');
		
		if(format == 'bold'){
			format_text(id,'<b>','</b>');
		} else if(format == 'italic'){
			format_text(id,'<i>','</i>');
		} else if(format == 'link'){
			format_text(id,'link','</a>');
		} else if(format == 'mail'){
			format_text(id,'mail','</a>');
		} else if(format == 'excerpt'){
			format_text(id,'excerpt','<!-- more -->');
		} else if(format == 'blockquote'){
			format_text(id,'<blockquote>','</blockquote>');
		} else if(format == 'image'){
			
			range = el.caret();
			caret_info.start = range.start;
			caret_info.end = range.end;
			
			get_modal("inc/media_modal.php?type=inline&parent_item="+id+"&extensions=jpg,gif,png");
		}
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Remove media item
	
	$('.remove_media').live('click', function(){
		$(this).parent().parent().remove();
		check_no_rows();
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Remove fancy item
	
	$('.remove_fancy').live('click', function(){
		$(this).parent().parent().remove();
		check_no_rows();
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Tag System
	
	$('.tag_add').live('click', function(){
		
		id = $(this).attr('field');
		input_tags = $(this).siblings("#hidden_"+id).val() + ',' + $(this).siblings("#input_"+id).val();
		tag_terms = input_tags.split(',');
	
		// Get tags from inputs within the tags!
		
		for(var i = 0; i < tag_terms.length; i++){
			tag_terms[i] = tag_format(tag_terms[i]);
		}
		
		tag_terms = array_unique(tag_terms);
		tag_terms.sort();
		
		tag_code = '';
		tag_save = ',';
		for(i = 0; i < tag_terms.length; i++){
			if(tag_terms[i].length > 0){
				tag_code += '<span class="tag" tag="' + tag_terms[i] + '">' + tag_terms[i] + '<a class="tag_remove" href="">&times;</a></span>';
				tag_save += tag_terms[i] + ',';
			}
		}
		$(this).siblings("#tags_"+id).html(tag_code);
		$(this).siblings("#hidden_"+id).val(tag_save);
		$(this).siblings("#input_"+id).val('');
		$(this).siblings("#input_"+id).focus();
	});
	
	//////////////////////////////////////////////////////////////////////////////
	
	$(".tag_input").live('keypress', function(event){
		if (event.which == 13 || event.which == 188 || event.which == 44) { // Enter or Comma
			$(this).parent().find('.tag_add').click();
			return false;
		}
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Remove Tag
	
	$('.tag_remove').live('click', function(){
		tag = $(this).parent().attr('tag');
		id = $(this).parent().parent().attr('id').substring(4);
		value = $("#hidden"+id).val();
		$("#hidden"+id).val(value.replace(tag+',',''));
		$(this).parent().remove();
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Tag Autocomplete
	
	function split( val ) {
		return val.split( /,\s*/ );
	}
	
	function extractLast( term ) {
		return split( term ).pop();
	}
	
	$('.tag_autocomplete')
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === $.ui.keyCode.TAB &&
					$( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			minLength: 0,
			source: function( request, response ) {
				$.post("inc/ajax.php", {
					term: extractLast( request.term ),
					action: 'tag_autocomplete',
					table: $('#cms_table').text(),
					field: $(this)[0].element.context.id.substr(6)
				}, response, "json");
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.value );
				// add placeholder to get the comma-and-space at the end
				terms.push( "" );
				this.value = terms.join( ", " );
				return false;
			}
		});
	
	//////////////////////////////////////////////////////////////////////////////
	// Copy name value into short_name
	
	$('input[short_name]').each(function(index) {
		$('input[name="'+$(this).attr('short_name')+'"]').live('keyup', function (e) {	
			
			str = $(this).val().replace(/^\s+|\s+$/g, ''); // trim
  			str = str.toLowerCase();
  
			// remove accents, swap ñ for n, etc
			var from = "àáäâèéëêìíïîòóöôùúüûñç·/_,:;";
			var to   = "aaaaeeeeiiiioooouuuunc------";
			for (var i=0, l=from.length ; i<l ; i++) {
				str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
			}
			
			// remove invalid chars - collapse whitespace and replace by - collapse dashes
			str = str.replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-'); 
			
			// If there is a modal window open then use it
			if(modal_windows_open.length > 0){
				$('#modal_level_'+modal_windows_open.length+' input[short_name="'+$(this).attr('name')+'"]').val(str);
			} else {
				$('input[short_name="'+$(this).attr('name')+'"]').val(str);
			}
		});
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Histogram

	histogram_live();
	
	$('.histogram').live('keyup change', function(event){
		$(this).val( $(this).val().toUpperCase().replace(/[^0-9]/g,'') );
		percent = Math.round( 100 * ($(this).val() / $(this).siblings('.histogram_bar').attr('peak_amount')) );
		percent = (percent > 100)? 100 : percent;
		$(this).siblings('.histogram_bar').children('span').width(percent+'%');
		$(this).siblings('.histogram_percent').text(percent+'%');
		if(event.type == 'change' && $(this).val() == ''){
			$(this).val('0');
		}
	});

	//////////////////////////////////////////////////////////////////////////////
	// Rating system
	
	$('.editable_rating .rating_system span').live('click', function(){
		$(this).parent().find('.rating_bar').css('width', ($(this).attr('count')*16) );
		$(this).parent().parent().find('input').val($(this).attr('count'));
		return false;
	});
	
	$('.editable_rating .rating_system span').live('mouseover mouseout', function(event) {
		if (event.type == 'mouseover') {
			$(this).parent().find('.rating_bar').css('width', ($(this).attr('count')*16) );
		} else {
			$(this).parent().find('.rating_bar').css('width', ( $(this).parent().parent().find('input').val()*16) );
		}
	});
	
	$('.rating_input').live('keyup change', function(event){
		$(this).parent().find('.rating_bar').css('width', ($(this).val()*16) );
		if(event.type == 'change' && $(this).val() == ''){
			$(this).val('0');
		}
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Color system
	
	$('.color_field').live('keyup change', function(event){
		$(this).val( $(this).val().toUpperCase().replace(/[^a-fA-F0-9]/g,'') );
		$(this).siblings('.color_box[color_box="'+$(this).attr('name')+'"]').css('background-color', '#'+$(this).val() );
		if(event.type == 'change' && $(this).val() == ''){
			$(this).val('FFFFFF');
		}
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Unmask password fields (Doesn't work in IE, could clone)
	
	$('.unmask').live('focus', function(event){
		this.type = 'text';
	});
	
	$('.unmask').live('blur', function(event){
		this.type = 'password';
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Initial link for edit page
	
	modal_edit_live();
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Media Page
	
	$('#media_filter').focus();
	
	$("#media_date_range").change(function(){
		window.location.href = "media.php?range=" + $(this).val();
	});
	
	//////////////////////////////////////////////////////////////////////////////
	
	$('#media_table th.header:not(.check)').live('click', function(){
		directus_alert('now_resorting');
		
		if($(this).hasClass('headerSortDown')){
			order = "asc";
			order_class = "headerSortUp";
		} else {
			order = "desc";
			order_class = "headerSortDown";
		}
		
		$('#media_table th.header').removeClass('headerSortUp');
		$('#media_table th.header').removeClass('headerSortDown');
		$(this).addClass(order_class);
		
		if($(this).hasClass('field_title')){
			sort = "title";
		} else if($(this).hasClass('field_extension')) {
			sort = "extension";
		} else if($(this).hasClass('field_size')) {
			sort = "file_size";
		} else if($(this).hasClass('field_caption')) {
			sort = "caption";
		} else if($(this).hasClass('field_user')) {
			sort = "user";
		} else {
			sort = "uploaded";
		}
		
		$.ajax({
			url: "media.php?ajax=true&sort="+sort+"&order="+order,
			success: function(msg){
				
				$("#alert_container .alert_box").fadeOut(200);
				
				if(msg){
					$('#media_table tbody').html(msg);
					load_images_above_fold();
					audio_live();
				} else{
					directus_alert('error_sorting');
				}
			}
		});
		
	});
	
	/*
	$("#media_table").tablesorter({
		debug: true,
		sortList: [[7,1]],
		textExtraction: function(node) { 
			// extract data from markup and return it 
			return node.getAttribute("raw"); 
			//return node.childNodes[0].attr('raw');
		},
		headers: { 0: { sorter: false }, 1: { sorter: false } }
	}); 
	
	$("#media_table").bind("sortStart",function() { 
		$('#throbber').animate({ opacity: 1.0 }, 0);
	}).bind("sortEnd",function() { 
		$('#throbber').animate({ opacity: 0.0 }, 1000);
	});
	*/
	
	//////////////////////////////////////////////////////////////////////////////
	
	$("#media_filter").keyup(function(){
		table_filter($(this).val(), 'media_table');
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//////////////////////////////////////////////////////////////////////////////
	// Click on item in media (open it in modal)
	
	$("#media_table .media_item .editable").live('click', function(){
		id = $(this).parent().attr('id').substring(6);
		open_media(id);
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Click on item in media (check it in modals)
	
	$("#media_table .media_item .checkable").live('click', function(){
		$(this).parent().find(".status_action_check").attr('checked', true);
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	
	function open_media(id) {
		$.ajax({
			url: base_path + "inc/media_modal.php?id="+id,
			type: "POST",
			data: false,
			success: function(msg){
				open_modal(msg);
			}
		});
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Delete media item from detail
	
	$('#media_modal_delete').live('click', function(){
		id = $(this).attr('media_id');
		change_status('directus_media', id, 0, 'delete_media_modal');
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Swap media item from detail
	
	$('#media_modal_swap').live('click', function(){
		$('#upload_media_pane').toggle();
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Toggle between upload and url
	
	$(".media_modal_type_change").live("click", function() {
	
		area = $(this).attr('area');
	
		if(area == 'upload'){
			$('#media_modal_area_add').show();
			$('#media_modal_area_upload').show();
			$('#media_modal_area_url').hide();
			$('#media_modal_area_choose').hide();
		} else if(area == 'url'){
			$('#media_modal_area_add').show();
			$('#media_modal_area_upload').hide();
			$('#media_modal_area_url').show();
			$('#media_modal_area_choose').hide();
		} else if(area == 'video'){
			$('#media_modal_area_add').show();
			$('#media_modal_area_upload').hide();
			$('#media_modal_area_url').show();
			$('#media_modal_area_choose').hide();
		} else if(area == 'choose'){
			$('#media_modal_area_add').hide();
			$('#media_modal_area_upload').hide();
			$('#media_modal_area_url').hide();
			$('#media_modal_area_choose').show();
		}
		
		$(".media_modal_type_change").removeClass("current");
		$(this).addClass("current");
		
		load_images_above_fold();
		
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$("#toggle_media_upload").live("click", function() {
		if ($("#media_modal_area_upload").css("display") == "block") {
			$("#media_modal_area_upload").hide();
			$("#media_modal_area_url").show();
			$(this).text("or upload file from computer");
		} else {
			$("#media_modal_area_upload").show();
			$("#media_modal_area_url").hide();
			$(this).text("or add from URL");
		}
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Audio player
	// var audioTagSupport = !!(document.createElement('audio').canPlayType); // Checks for support
	
	
	$('div.audio_player span.audio_play_pause').live('click', function(){
		var audio_file = $(this).parent().find('audio');
		
		if(audio_file[0].ended){
			audio_file[0].currentTime = 0;
			audio_file[0].play();
			$(this).removeClass('audio_play');
			$(this).addClass('audio_pause');
		} else {
			if(audio_file[0].paused){
				audio_file[0].play();
				$(this).removeClass('audio_play');
				$(this).addClass('audio_pause');
			} else {
				audio_file[0].pause();
				$(this).removeClass('audio_pause');
				$(this).addClass('audio_play');
			}
		}
		
		$(this).parent().find('.audio_time').html(seconds_time(audio_file[0].duration));
		
		return false;
	});

	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$("audio").bind('timeupdate', update_time);
	$("audio").bind('ended', end_time);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Upload media from FTP upload as batch
	
	$('#media_batch_upload').click(function(event){
		directus_alert('now_uploading<span id="batchpercent"></span>');
		batch_upload_image();
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Drag files onto browser for input highlight
	
	$("body").live("dragover", function(event){ dragging_file = true; dragging_file_event = event; $("#upload_media_dropzone").stop(true, true).fadeTo(200, 1); });
	$("body").live("dragexit dragleave drop", function(event){ dragging_file = false; dragging_file_event = event; $("#upload_media_dropzone").fadeTo(200, 0); });
    
    //////////////////////////////////////////////////////////////////////////////
    
    $("#upload_media_input").live("dragover mouseover", function(event){ 
    	if(dragging_file){
    		$("#upload_media_dropzone").css("background-color","#999999");
		} else  {
			$("#upload_media_dropzone").css("background-color","#dcdcdc");
			$("#upload_media_dropzone").stop(true, true).fadeTo(200, 1);
		} 
    });
    
    //////////////////////////////////////////////////////////////////////////////
	
	$("#upload_media_input").live("dragexit dragleave drop mouseout", function(event){
		if(dragging_file){
    		$("#upload_media_dropzone").css("background-color","#dcdcdc");
		} else  {
			$("#upload_media_dropzone").stop(true, true).fadeTo(200, 0);
		}  
	});
    
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Messages Page
	
	if(get_url_vars()["m"] == 'compose'){
		$('#message_subject').focus();
	}
	
	if(window.location.href.split("#")[1] == 'reply'){
		$('#message_reply').focus();
	}
	
	$("#message_everyone").change(function(){
		if($(this).attr('checked')){
			$('.message_user').attr('checked', true).attr('disabled', true);
		} else {
			$('.message_user').attr('checked', false).attr('disabled', false);
		}
	});
	
	$("#mark_all_as_read").click(function(){
		$.ajax({
			url: "messages.php",
			type: "POST",
			data: 'mark_all_as_read=true',
			success: function(msg){
				if(msg == 'success'){
					top.location = top.location;
				} else {
					directus_alert(msg);
				}
				$('#throbber').animate({ opacity: 0.0 }, 1000);
			}
		});
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Validate message
	
	$("#message_submit").click(function(){
		var errors = [];
		var has_to = false;
		$('.message_user').each(function(index){
			if($(this).attr('checked')){
				has_to = true;
			}
		});
		
		if($("#message_everyone").attr('checked')){
			has_to = true;
		}
		
		if(!has_to){
			errors.push("message_to_required");
		}
		
		if(!$("#message_subject").val()){
			errors.push("message_subject_required");
			$("#message_subject").focus();
		}
		
		if(!$("#message_message").val()){
			errors.push("message_message_required");
			$("#message_message").focus();
		}
		
		if(errors.length == 0){
			document.edit_form.submit();
		} else {
			directus_alert( errors.join(',') );
		}
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Delete message
	
	$(".archive_message").click(function(){
		$('#throbber').animate({ opacity: 1.0 }, 0);
		data = 'archive='+$(this).attr('archive_id');
		item_to_delete = $(this);
		$.ajax({
			url: "messages.php",
			type: "POST",
			data: data,
			success: function(msg){
				if(msg){
					directus_alert(msg);
					if(item_to_delete.attr('href') != '#'){
						window.location.href = item_to_delete.attr('href');
					} else {
						item_to_delete.parent().parent().remove();
					}
				}
				$('#throbber').animate({ opacity: 0.0 }, 1000);
			}
		});
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Settings Page
	
	$("#settings_extended_details").click(function(){
		
		directus_alert('now_loading');
		
		$.ajax({
			url: "inc/info.php?clean=true",
			success: function(msg){
				open_modal(msg);
				$("#alert_container .alert_box").fadeOut(200);
			}
		});
		return false;
	});
	
	// Settings toggle checkboxes
	
	$(".privileges_toggle_table").click(function(){
		$toggle = ($('input[value="'+$(this).attr("link")+'"]:not(:disabled)').attr('checked'))? false : true;
		$('input[value="'+$(this).attr("link")+'"]:not(:disabled)').attr('checked', $toggle);
	});
	
	$(".privileges_toggle").click(function(){
		if($(this).attr("link") == 'all'){
			$toggle = ($('input[priv="all"]:not(:disabled)').attr('checked'))? false : true;
			$('input[priv="all"]:not(:disabled)').attr('checked', $toggle);
		} else {
			$toggle = ($('input[name="'+$(this).attr("link")+'"]:not(:disabled)').attr('checked'))? false : true;
			if(!$toggle && $(this).attr("link") == 'view[]'){
				$('input[priv="all"]:not(:disabled)').attr('checked', false);
			} else {
				$('input[name="'+$(this).attr("link")+'"]:not(:disabled)').attr('checked', $toggle);
			}
		}
	});
	
	$("#user_settings_admin").change(function(){
		if($(this).attr('checked')){
			// Admin on
			$('input[priv="all"]').attr('checked', true);
			$('input[priv="all"]').attr('disabled', true);
			$('#user_settings_media').attr('checked', true);
			$('#user_settings_media').attr('disabled', true);
			$('#user_settings_notes').attr('checked', true);
			$('#user_settings_notes').attr('disabled', true);
			$('#user_settings_editable').attr('checked', true);
			$('#user_settings_editable').attr('disabled', true);
		} else {
			$('input[priv="all"]').attr('disabled', false);
			$('#user_settings_media').attr('disabled', false);
			$('#user_settings_notes').attr('disabled', false);
			$('#user_settings_editable').attr('disabled', false);
		}
	});
	
	$("#user_settings_active").change(function(){
		if(!$(this).attr('checked')){
			if($(this).hasClass('disable_self')){
				directus_alert('suicide');
			} else {
				directus_alert('murder');
			}
		}
	});
	
	/*
	// Not THAT bad
	$("input[name='cms[media_naming]'][value='original']").change(function(){
		if($(this).attr('checked')){
			directus_alert('This media preference is strongly discouraged.');
		}
	});
	*/
	
	$("#user_privileges input[name='view[]']").change(function(){
		$this_value = $(this).val();
		if(!$(this).attr('checked')){	
			$("#user_privileges input[value='"+$this_value+"']").not(this).attr('checked', false);
			//$("#user_privileges input[value='"+$this_value+"']").not(this).attr('disabled', true);
		} else {
			//$("#user_privileges input[value='"+$this_value+"']").attr('checked', false);
			//$("#user_privileges input[value='"+$this_value+"']").attr('disabled', false);
		}
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Thumbnail settings
	
	$("#thumb_add").click(function(){
		var w = $('#thumb_width').val();
		var h = $('#thumb_height').val();
		var c = ($('#thumb_crop').attr('checked'))? 'true' : 'false';
		var c_text = ($('#thumb_crop').attr('checked'))? 'Crop to fit' : 'Shrink to fit';
		
		if(isNaN(w) || w<1){ directus_alert('thumb_required_width'); return false; }
		if(isNaN(h) || h<1){ directus_alert('thumb_required_height'); return false; }
		
		$('#cms_media_thumbs').append('<li><div class="clearfix">'+w+' &times; '+h+'&nbsp;&nbsp;&nbsp;<span class="quiet">'+c_text+'</span> <span class="alert">Save settings to add</span> <a class="ui-icon ui-icon-close right thumb_remove" href="#"></a><input type="hidden" name="image_autothumb[]" value="'+w+','+h+','+c+'" /></li>');
		$('#thumb_width, #thumb_height').val('');
		$('#thumb_crop').attr('checked', false); 
		check_no_rows();
		return false;
	});
	
	$(".thumb_remove").live('click', function() {
		$(this).parent().parent().remove();
		check_no_rows();
		return false;
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// More Options
	
	$(".datatype_options").change(function(){
		$('#throbber').animate({ opacity: 1.0 }, 0);
		data = 'datatype='+$(this).val()+'&tablefield='+$(this).attr('tablefield');
		options_container = $(this).parent().find('.options_container');
		datatype_more_options = $(this).parent().find('.datatype_more_options');
		
		$.ajax({
			url: "inc/datatype_options.php",
			type: "POST",
			data: data,
			success: function(msg){	
				if(msg) {
					options_container.html(msg);
					// Autoopen "more options" popup if RELATIONAL or other datatypes with required settings
					$(".force_numeric").ForceNumericOnly();
					datatype_more_options.show();
				} else {
					datatype_more_options.hide();
				}
				$('#throbber').animate({ opacity: 0.0 }, 1000);
			}
		});
	});
	
	$('.options_container').each(function(index){
		if($(this).html() == ''){
			// Hide "More options" when there are no options
			$(this).parent().find('.datatype_more_options').hide();
		}
	});
	
	$(".datatype_more_options").click(function(){
		if($(this).html() == 'more options'){
			$(this).html('hide options');
			$(this).parent().find('.options_container').show();
		} else {
			$(this).html('more options');
			$(this).parent().find('.options_container').hide();
		}
		return false;
	});
	
	$(".save_datatype_options").live('click', function() {
		$(this).parent().parent().find('.datatype_more_options').html('more options');
		$(this).parent().hide();
		return false;
	});	
	
	
	//////////////////////////////////////////////////////////////////////////////
	
	
	$("#add_table_button").click(function(){
		
		directus_dialog('add_table', 'Add new table', true);
		
		return false;
	});
	
	$(".force_safe").live('keyup',function(){
		current_val = $(this).val();
		current_val = current_val.replace(/[^0-9a-zA-Z_]/g,'');
		$(this).val(current_val);
	}); 
	
	
	//////////////////////////////////////////////////////////////////////////////
	
	
	
	
	
	
	
	
	
	
	
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Plugins
	
	$("#plugin_info").click(function(){
		$('#throbber').animate({ opacity: 1.0 }, 0);
		var url = $(this).attr('href');
		$.ajax({
			url: url,
			success: function(msg){
				if(msg){
					directus_dialog(false, "Plugin Info", msg)
				}
				$('#throbber').animate({ opacity: 0.0 }, 1000);
			}
		});
		return false;
	});
	
	
	/*!
	 * Autogrow Textarea Plugin Version v2.0
	 * http://www.technoreply.com/autogrow-textarea-plugin-version-2-0
	 *
	 * Copyright 2011, Jevin O. Sewaruth
	 *
	 * Date: March 13, 2011
	 */
	jQuery.fn.autoGrow = function(){
		return this.each(function(){
			// Variables
			var colsDefault = this.cols;
			var rowsDefault = this.rows;
			
			//Functions
			var grow = function() {
				growByRef(this);
			}
			
			var growByRef = function(obj) {
				var linesCount = 0;
				var lines = obj.value.split('\n');
				
				for (var i=lines.length-1; i>=0; --i)
				{
					linesCount += Math.floor((lines[i].length / colsDefault) + 1);
				}
	
				if (linesCount >= rowsDefault)
					obj.rows = linesCount + 1;
				else
					obj.rows = rowsDefault;
			}
			
			var characterWidth = function (obj){
				var characterWidth = 0;
				var temp1 = 0;
				var temp2 = 0;
				var tempCols = obj.cols;
				
				obj.cols = 1;
				temp1 = obj.offsetWidth;
				obj.cols = 2;
				temp2 = obj.offsetWidth;
				characterWidth = temp2 - temp1;
				obj.cols = tempCols;
				
				return characterWidth;
			}
			
			// Manipulations
			this.style.width = "auto";
			this.style.height = "auto";
			this.style.overflow = "hidden";
			this.style.width = ((characterWidth(this) * this.cols) + 6) + "px";
			this.onkeyup = grow;
			this.onfocus = grow;
			this.onblur = grow;
			growByRef(this);
		});
	};
	
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Check for no rows
	
	check_no_rows();
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
});









//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Code to add tags to text in text fields


jQuery.fn.extend({
	insertAtCaret: function(myValue){
		return this.each(function(i) {
			if (document.selection) {
				this.focus();
				sel = document.selection.createRange();
				sel.text = myValue;
				this.focus();
			} else if (this.selectionStart || this.selectionStart == '0') {
				var startPos = this.selectionStart;
				var endPos = this.selectionEnd;
				var scrollTop = this.scrollTop;
				this.value = this.value.substring(0, startPos)+myValue+this.value.substring(endPos,this.value.length);
				this.focus();
				this.selectionStart = startPos + myValue.length;
				this.selectionEnd = startPos + myValue.length;
				this.scrollTop = scrollTop;
			} else {
				this.value += myValue;
				this.focus();
			}
		});
	}
});


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// For getting and setting selection


(function($) {
	$.extend($.fn, {
		caret: function (start, end) {
			var elem = this[0];

			if (elem) {							
				
				if (typeof start == "undefined") {
					// get caret range
					
					if (elem.selectionStart) {
						start = elem.selectionStart;
						end = elem.selectionEnd;
					} else if (document.selection) {
						var val = this.val();
						var range = document.selection.createRange().duplicate();
						range.moveEnd("character", val.length)
						start = (range.text == "" ? val.length : val.lastIndexOf(range.text));

						range = document.selection.createRange().duplicate();
						range.moveStart("character", -val.length);
						end = range.text.length;
					}
				} else {
					// set caret range
					var val = this.val();

					if (typeof start != "number") start = -1;
					if (typeof end != "number") end = -1;
					if (start < 0) start = 0;
					if (end > val.length) end = val.length;
					if (end < start) end = start;
					if (start > end) start = end;

					elem.focus();

					if (elem.selectionStart) {
						elem.selectionStart = start;
						elem.selectionEnd = end;
					} else if (document.selection) {
						var range = elem.createTextRange();
						range.collapse(true);
						range.moveStart("character", start);
						range.moveEnd("character", end - start);
						range.select();
					}
				}

				return {start:start, end:end};
			}
		}
	});
})(jQuery);

/*
var input = $("#selector");
var range = input.caret();
var text = null;
// Get selected text
text = input.val().substr(range.start, range.end - 1);

// Insert text at caret then restore caret
var value = input.val();
text = " New Text ";
input.val(value.substr(0, range.start) + text + value.substr(range.end, value.length));
input.caret(range.start + text.length);

// Select first ten characters of text
input.caret(0, 10);
*/

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////























//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Convert array to CSV string


function array_to_csv(arr){
	var csv = '';
	for(var row in arr){
		for(var col in arr[row]){
			var value = arr[row][col].replace(/"/g, '""');
			csv += '"' + value + '"';
			if(col != arr[row].length - 1){
				csv += ",";
			}
		}
		csv += "\n";
	}
	return csv;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Histogram calculations

function histogram_live(){
	$('.histogram_bar').each(function(index) {
		if($(this).attr('peak_amount') == 'calculate'){
			var field = $(this).children('span').attr('field');
			var list = $('span[field="'+field+'"]').map(function(){
				return parseInt($(this).attr("this_amount"), 10);
			}).get();
			var peak_amount = Math.max.apply( Math, list );
		} else {
			var peak_amount = $(this).attr('peak_amount');
		}
		percent = Math.round( 100 * ($(this).children('span').attr('this_amount') / peak_amount) );
		percent = (percent > 100)? 100 : percent;
		$(this).children('span').width(percent+'%');
		$(this).siblings('.histogram_percent').text(percent+'%');
	});
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Set after reordering

function reorder_live(){
	$(".table tr:visible:even").addClass("alt");
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// AUDIO functions

function update_time() {
	$(this).parent().find('.audio_time').html(seconds_time(this.duration - this.currentTime));
}

function end_time() {
	this.pause();
	$(this).parent().find('.audio_time').html(seconds_time(this.duration));
	$(this).parent().find('.audio_play_pause').removeClass('audio_pause');
	$(this).parent().find('.audio_play_pause').addClass('audio_play');
}

function audio_live(){
	// For AUDIO player
	$("audio").bind('timeupdate', update_time);
	$("audio").bind('ended', end_time);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Link all functions for Modal edit pages
	
function modal_edit_live(){
	
	histogram_live();
	
	//////////////////////////////////////////////////////////////////////////////
	// Filter for modal_media
	
	$("#modal_media_filter").keyup(function(){
		table_filter($(this).val(), 'media_table');
	});
    
	//////////////////////////////////////////////////////////////////////////////
	// Datetime aggregation of fields to hidden
	
	$('.datetime_fields input, .datetime_fields select').change(function(){
	
		month = $(this).parent().find('.month').val();
		day = $(this).parent().find('.day').val();
		year = $(this).parent().find('.year').val();
		
		hour = $(this).parent().find('.hour').val();
		minute = $(this).parent().find('.minute').val();
		second = $(this).parent().find('.second').val();
		
		$(this).parent().find('.datetime').val( year+'-'+month+'-'+day+' '+hour+':'+minute+':'+second );
		
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Limit text in field
	
	$(".track_max_length").focus(function(){	
		$(this).siblings(".char_count").show();
	});

	$(".track_max_length").blur(function(){	
		$(this).siblings(".char_count").hide();
	});
	
	$('.track_max_length').bind('keydown keyup', function(){
		limit = $(this).attr('maxlength');
		value = $(this).val();
		if(value.length > limit) {
			value = value.substring(0, limit);
		} else {
			$(this).next().html(limit - value.length);
		}
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// Datepicker popup
	
	$('.datepicker').datepicker({
		dateFormat: 'yy-mm-dd'
		//showAnim: 'slideDown',
		//changeMonth: true,
		//changeYear: true
	});

	//////////////////////////////////////////////////////////////////////////////
	// Force Numeric fields
	
	$(".force_numeric").ForceNumericOnly();
	
	//////////////////////////////////////////////////////////////////////////////
	// Fancy Relational
	
	$(".simple_sortable").sortable({
		axis: 'y',
		//containment: 'parent',
		//helper: 'clone',
		handle: '.handle',
		items: '.item',
		opacity: '0.6',
		//placeholder: 'sortable_placeholder_browse',
		tolerance: 'intersect',
		appendTo: 'body',
		update: function(e, ui){
			// Success
		}
	});

	//////////////////////////////////////////////////////////////////////////////
	// Fancy Select
	
	$('.fancy_new select').change(function(){
		safe_value = $(this).val();
		field = $(this).attr('field');
		table = $(this).attr('table');
		parent_table = $(this).attr('parent_table');
		value = $(this).find('option').filter(':selected').text();
		
		code = '<tr class="item" replace_with="'+safe_value+'">   <td class="order handle"><img src="media/site/icons/ui-splitter-horizontal.png" width="16" height="16"></td>   <td><div class="wrap">'+value+'<input type="hidden" name="'+field+'[]" value="'+safe_value+'">   <a class="badge edit_fancy modal" href="edit.php?modal='+field+'&table='+table+'&parent_table='+parent_table+'&item='+safe_value+'">Edit</a>   </div>   </td>     <td width="10%"><a class="ui-icon ui-icon-close right remove_fancy" href=""></a></td>   </tr>';
		if(safe_value){
			$(this).parent().parent().children('table').append(code);
			check_no_rows();
		}
		$(this).val('');
	});
	
	//////////////////////////////////////////////////////////////////////////////
	// WYSIWYG editor (Tiny MCE)
	
	// Remove existing editors
	var i, t = tinyMCE.editors;
	for (i in t){
		if (t.hasOwnProperty(i)){
			t[i].remove();
		}
	}
	
	// Add new editors
	tinyMCE.init({
		// General options
		mode : "specific_textareas",
		editor_selector : "wysiwyg_advanced",
		theme : "advanced",
		plugins : "autolink,lists,style,table,iespell,inlinepopups,media,paste,directionality,noneditable,visualchars,xhtmlxtras,advlist",
		
		// Theme options
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,forecolor,backcolor,|,formatselect",
		theme_advanced_buttons2 : "pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,code,removeformat",
		theme_advanced_buttons3 : "tablecontrols,|,sub,sup,|,hr,charmap,iespell",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_resizing : true,
		width : "91.5%",
		
		// Skin options
		//skin : "o2k7",
		//skin_variant : "silver",
		
		// Example content CSS (should be your site CSS)
		content_css : "inc/js/tiny_mce/themes/advanced/skins/default/content.css",
	});
	
	
	//////////////////////////////////////////////////////////////////////////////
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Batch upload media from folder

function batch_upload_image() {

	batch_total = $('#media_batch_total').html();

	$.ajax({
		type: "POST",
		url: "inc/upload.php?batch="+batch_id,
		success: function(msg){
			if(msg != 'success'){
				directus_alert(msg);
			} else {
				percent = Math.round( ( batch_id / batch_total ) * 100);
				$('#batchpercent').html(' '+percent+'%');
				if(batch_id == batch_total){
					top.location = top.location;
				} else {
					batch_id++;
					batch_upload_image();
				}
			}
		}
	});
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Time is seconds to readable

function seconds_time(sec) {
	sec = Math.floor(sec);
	var hr = Math.floor(sec / 3600);
	var min = Math.floor((sec - (hr * 3600))/60);
	sec -= ((hr * 3600) + (min * 60));
	sec += ''; min += '';
	while (min.length < 2) {min = '0' + min;}
	while (sec.length < 2) {sec = '0' + sec;}
	hr = (hr)?hr+':':'';
	return hr + min + ':' + sec;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Set SESSION[] in PHP

function set_session(name, value){
	//alert(name+value);
	$('#throbber').animate({ opacity: 1.0 }, 0);
	data = 'action=set_session&session='+name+'&value='+value;
	$.ajax({
		url: "inc/ajax.php",
		type: "POST",
		data: data,
		success: function(msg){
			//alert(msg);
			$('#throbber').animate({ opacity: 0.0 }, 1000);
		}
	});
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Allow JS to get $_GET


function check_no_rows(){
	$('.check_no_rows').each(function(i){
		var num_rows = $(this).children(':not(.no_rows)').filter(function() { return $(this).css('display') !== 'none'; }).length;
		if(num_rows == 0){
			$(this).find('.no_rows').show();
		} else {
			$(this).find('.no_rows').hide();
		}
	});
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Allow JS to get $_GET


function get_url_vars(){
	var vars = {};
	var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
		vars[key] = value;
	});
	return vars;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Remove duplicates from array


function array_unique(s) {
    var unique_array = [];
    for(var i = s.length; i--; ){
        var val = s[i];
        if ($.inArray(val, unique_array) === -1) {
            unique_array.unshift(val);
        }
    }
    return unique_array;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Trim leading and ending space from string


function tag_format(s) {
	s = s.replace(/[^a-zA-Z 0-9-]+/g,"");
	s = s.replace(/(^\s*)|(\s*$)/gi,"");
	s = s.replace(/[ ]{2,}/gi," ");
	s = s.replace(/\n /,"\n");
	s = s.toLowerCase();
	return s;
}


function uc_convert(s){
	s = s.replace("_"," ");
    return s.replace(/\w\S*/g, function(txt){
    	return txt.charAt(0).toUpperCase() + txt.substring(1).toLowerCase();
    });
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Live-Search through table cells


function table_filter(phrase, id){
	var words = phrase.toLowerCase().split(" ");
	var table = document.getElementById(id);
	var ele;
	for(var r = 1; r < table.rows.length; r++){
		ele = table.rows[r].innerHTML.replace(/<[^>]+>/g,"");
		var display_style = 'none';
		for(var i = 0; i < words.length; i++){
			if(ele.toLowerCase().indexOf(words[i])>=0){
				display_style = '';
			} else {
				display_style = 'none';
				break;
			}
		}
		table.rows[r].style.display = display_style;
	}
	check_no_rows();
	load_images_above_fold();
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Change status of items


function change_status(table, id, status, type){
		
	if(status == 1){
		action = 'activating';
	} else if(status == 0){
		action = 'deleting';
	} else {
		action = 'deactivating';
	}
	
	directus_alert('now_'+action);

	// var r = confirm("Are you sure about "+action+" these items?");
	// if (r == true) {
	
	data = 'action=set_active&table='+table+'&id='+id+'&status='+status;
	$.ajax({
		url: "inc/ajax.php",
		type: "POST",
		data: data,
		success: function(msg){
			if(msg){
				directus_alert(msg);
			} else {
				if(type == 'reload'){
					top.location = top.location;
				} else if(type == 'edit') {
					window.location.href = "browse.php?table="+table;
				} else if(type == 'delete_media_modal'){
					close_modal();
					$('#media_'+id).hide();
					directus_alert('removed');
				}
			}
			$('#throbber').animate({ opacity: 0.0 }, 1000);
		}
	});
}


//////////////////////////////////////////////////////////////////////////////

function format_text(id,tagstart,tagend){
	
	var link_to = false;
	type = tagstart;
	
	// If there is a modal window open then use it
	if(modal_windows_open.length > 0){
		el = $('#modal_level_'+modal_windows_open.length+' #'+id)[0];
	} else {
		el = document.getElementById(id);
	}
	
	if(type == "excerpt"){
		$('#'+id).insertAtCaret(tagend);
		return false;
	}
	
	if(type == "image"){
		
		//if(modal_windows_open.length > 0){
		//tagstart = $('#modal_level_'+modal_windows_open.length+' #iframe').contents().find('#modal_html').html();
		tagstart = $('#iframe').contents().find('#modal_html').html();
		
		close_modal();
		$("#alert_container .alert_box").fadeOut(200);
		
		if(tagstart == 'image' || tagstart == null){
			directus_alert("text_format_required_selection");
		} else {
			$('#'+id).caret(caret_info.start);
			$('#'+id).insertAtCaret(tagstart);
		}
		return false;
	}
	
	if(type == "link"){
		link_to = prompt('Enter the link:', 'http://');
		if (link_to != null){
			tagstart = '<a href="'+link_to+'">';
		} else {
			return false;
		}
	}
	
	if(type == "mail"){
		var mail_to= prompt('Enter the email address:');
		if (mail_to != null){
			tagstart = '<a href="mailto:'+mail_to+'">';
		} else {
			return false;
		}
	}
	
	if (el.setSelectionRange) {
		selLength	= el.selectionEnd - el.selectionStart;
		selStart 	= el.selectionStart;
		selEnd 		= el.selectionEnd + tagstart.length + tagend.length;
		
		if(selLength > 0){
			
			tagstart_exist = el.value.substring(el.selectionStart, el.selectionStart + tagstart.length);
			tagend_exist = el.value.substring(el.selectionEnd - tagend.length, el.selectionEnd);
			tag_removed = el.value.substring(el.selectionStart + tagstart.length, el.selectionEnd - tagend.length);
			
			// Handle tag removal
			if(tagstart == tagstart_exist && tagend == tagend_exist){
				el.value = el.value.substring(0,el.selectionStart) + tag_removed + el.value.substring(el.selectionEnd,el.value.length);
				selEnd = el.selectionEnd - (tagstart.length + tagend.length);
			} else {
				el.value = el.value.substring(0,el.selectionStart) + tagstart + el.value.substring(el.selectionStart,el.selectionEnd) + tagend + el.value.substring(el.selectionEnd,el.value.length);
			}
		
			if(el.setSelectionRange){
				el.setSelectionRange(selStart,selEnd);
				el.focus();
			} else {
				directus_alert("not_supported");
			}
		} else {
			directus_alert("text_format_required_selection");
		}
		
	} else {
		// IE
		var selectedText = document.selection.createRange().text;
		if (selectedText != "") {
			var newText = tagstart + selectedText + tagend;
			document.selection.createRange().text = newText;
		} else {
			directus_alert("text_format_required_selection");
		}
	}
}
















//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Still logged in (for those sitting on pages)


var sPath = window.location.pathname;
var sPage = sPath.substring(sPath.lastIndexOf('/') + 1);
var plugin = (sPath.indexOf('/plugins/') >= 0)? true : false;
if((sPage != 'login.php' && sPage != 'index.php' && sPage != 'install.php') || plugin){
	var login_timer = self.setInterval("logged_in()", 60000);	// Every Minute (60000)
}
var logoff_timer = false;


function logged_in(){

	//window.clearInterval(login_timer)
	$('#throbber').animate({ opacity: 1.0 }, 0);
	
	// Combine with global variable above
	var current_path = window.location.pathname;
	var current_page = current_path.substring(current_path.lastIndexOf('/') + 1);
	var current_qs = (window.location.search.substring(1))? encodeURIComponent('?'+window.location.search.substring(1)) : '';
	
	$.ajax({
		type: "POST",
		url: base_path + "inc/logged_in.php",
		data: "current_page=" + current_page + current_qs,
		success: function(msg){
			if(msg){
				if(msg == 'timeout'){
					directus_alert("stay_logged_in");
					logoff_timer = self.setInterval("autologoff()", 1000); // Countdown to logoff
				} else if(msg.length > 100) {
					// We were redirected to login.php… so there's more than 100 chars
					logoff();
				} else {
					directus_alert(msg);
				}
			}
			
			$('#throbber').animate({ opacity: 0.0 }, 1000);
		}
	});
}

//////////////////////////////////////////////////////////////////////////////

function autologoff(){
	seconds_remaining = 29 - autologoff_seconds;
	autologoff_seconds++;
	$('#stay_logged_in').val('Stay logged in? '+seconds_remaining);
	if(seconds_remaining == 0){
		logoff();
	}
}

//////////////////////////////////////////////////////////////////////////////

function logoff(){
	window.clearInterval(login_timer);
	window.clearInterval(logoff_timer);
	window.location.href = base_path + 'inc/logoff.php';
}













//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Show Directus Alert


function directus_alert(alerts){

	var data = (alerts.constructor == Array)? alerts.join(',') : alerts;
	
	// Activity must be faster than AJAX
	if(data.substr(0,4) == 'now_'){
		var instant_alert = '<div class="alert_box persistent" style="display:none;"><div class="alert_box_message activity"><div class="alert_box_icon"></div><ul><li>'+uc_convert(alerts.substr(4))+'</li></ul></div></div>';
		$("#alert_container").html(instant_alert);
		alert_open();
	} else {	
		$.ajax({
			type: "POST",
			data: "alert=" + data,
			url: base_path + "inc/alert.php",
			success: function(msg){
				// Was using html(), but now adds individual alerts
				$("#alert_container").html(msg);
				alert_open();
			}
		});
	}
	
	return false;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Open Directus alert box


function alert_open(){

	//$("#myDiv").css({'position':'absolute','visibility':'hidden','display':'block'});

	// Show alerts for a certain period of time
	$('#alert_container .alert_box .alert_box_message').each(function(index) {
		if($(this).is('.success, .users') && !$(this).parent().hasClass('persistent')){
			$(this).parent().fadeIn(0).delay(2000).fadeOut(200);
		} else {
			$(this).parent().fadeIn(0);
		}
	});
	
	// Center alert vertically
	margin_top = -($('#alert_container').outerHeight()/2);
    $('#alert_container').css('margin-top', margin_top);
	
	return false;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Close Directus alerts


function close_alert(not_persistent){
	if(not_persistent){
		$("#alert_container .alert_bmodalox:not(.persistent)").fadeOut(200);
	} else {
		$("#alert_container .alert_box").fadeOut(200);
	}
}
















//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get contents of a modal window


function get_modal(page, data){
	data = typeof data !== 'undefined' ? data : "";
	directus_alert('now_opening');
	$.ajax({
		url: page,
		type: "POST",
		data: data,
		success: function(msg){
			open_modal(msg);
			$("#alert_container .alert_box").fadeOut(200);
		}  
	});
	return false;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Open a modal window


function open_modal(msg){

	modal_id = modal_windows_open.length + 1;
	modal_windows_open.push(modal_id);
	
	if(modal_id > 1){
		// Track level number
		developer_check = (modal_id > 3)? '!' : '';
		$('#modal_current_level').text(modal_id+developer_check);
		$('#modal_current_level').show();
	}
	
	$('body').append('<span id="modal_level_'+modal_id+'">'+msg+'</span>');
	
	// Run media loader on modal scroll
	$(".modal_window_content").scroll(function() {
		load_images_above_fold();
	});
	
	check_no_rows();
	audio_live();
	modal_edit_live();
	modal_resize();
	edit_table_resize();
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Close a modal window


function close_modal(){

	// Get the highest modal level
	modal_id = modal_windows_open.length;

	$('#modal_level_'+modal_id).fadeOut(250, function() {
		$(this).remove();
		modal_windows_open.pop();
		if(modal_windows_open.length == 0){
			$('#modal_current_level').hide();
		} else {
			$('#modal_current_level').text(modal_windows_open.length);
		}
	});
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Close a table edit


function close_edit_table(){

	$('.edit_table, .fog').fadeOut(250, function() {
		$(this).remove();
	});
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Transfer HTML from modal a window to an element


function modal_html_transfer(parent_id, element_id){
	
	// Get the code from the top level
	//code = $('#modal_level_'+modal_windows_open.length+' #iframe').contents().find('#modal_html tr').parent().html();
	code = $('#iframe').contents().find('#modal_html tr').parent().html();
	
	// Close the top level
	close_modal();
	$("#alert_container .alert_box").fadeOut(200);

	// Add the code to the next level down
	if(modal_windows_open.length > 0){
		$('#modal_level_'+modal_windows_open.length+' #'+parent_id+' tbody').append(code);
	} else {
		if(element_id){
			$('#'+parent_id+' tbody tr[replace_with="'+element_id+'"]').replaceWith(code);
		} else{
			$('#'+parent_id+' tbody').append(code);
		}
	}
	
	// Clear iFrame… otherwise it tries to run logged_in scripts unsuccessfully
	$('#iframe').attr('src', "about:blank");
	$('#iframe').empty();
	
	load_images_above_fold();
	
	// Update any ULs that might now have rows
	check_no_rows();
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Resize the modal windows on resize and when opening a new one


function modal_resize(){
	var modalheight = $(window).height() - 100;
	var modalmargin = modalheight / 2;
	var modalactions = 86;
	$(".modal_window_content").height(modalheight-modalactions);
	$(".modal_window").css({"height":modalheight,"margin-top":-modalmargin});
	$(".modal_window").fadeIn(250, function(){
		// Run initially too
		load_images_above_fold();
	});
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Resize edit tables view


function edit_table_resize(){
	var edit_table_height = $(window).height() - 100;
	var edit_table_width = $(window).width() - 100;
	var edit_table_top_margin = edit_table_height / 2;
	var edit_table_left_margin = edit_table_width / 2;
	var modalactions = 86;
	$(".edit_table_content").height(edit_table_height-modalactions);
	$(".edit_table").css({
		"height": edit_table_height,
		"width": edit_table_width,
		"margin-top": -edit_table_top_margin,
		"margin-left": -edit_table_left_margin
	});
	$(".edit_table").fadeIn(250);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Show Directus Dialog


function directus_dialog(type, title, message){
	$('#throbber').animate({ opacity: 1.0 }, 0);
	
	$.ajax({
		url: base_path + "inc/dialog.php",
		type: "POST",
		data: "type="+type+"&title="+title+"&message="+message,
		success: function(msg){
		
			console.log("here:"+msg);
		
			$("#dialog_window").html(msg);
			$("#dialog_window").fadeIn(250);
			$('#throbber').animate({ opacity: 0.0 }, 1000);
		}
	});
	
	return false;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Close dialog


function close_dialog(){
	$("#dialog_window").fadeOut(250, function() {
		$("#dialog_window").html('');
	});
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Run check on media in viewport (lazy load)
function load_images_above_fold() {
	
	// Get scrolled distance from top
	var scroll_view = $(window).scrollTop() + $(window).height() + 400;
	
	$('div.viewport_image:not(:has(img))').each(function() {
		
		// Check if item is supposed to be visible
		if ($(this).is(':visible') == true) {
			
			var $image = $(this);
			var url = $(this).attr("src");
			var existing_attributes = "";
			
			// Get all existing attributes
			$.each(this.attributes, function(i, attrib){
				if(attrib.name != 'src' && attrib.value != '' && attrib.value != 'viewport_image'){
					existing_attributes = existing_attributes+' '+attrib.name+'="'+$.trim(attrib.value.replace("viewport_image", ""))+'"';
				}
			});
			
			if (scroll_view > $image.offset().top) {
				
				//$image.removeClass("viewport_image");
				
				$('<img'+existing_attributes+' />')
					.hide()
					.attr('src', url + ($.browser.msie ? '?r=' + Math.random() : ''))
					.load(function() {
						$image.html($(this));
						$(this).unwrap().fadeIn(500);
					});
			}
		}
	});
}







//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


window.onload = function() {
	alert_open();
	$('#throbber').animate({ opacity: 0.0 }, 500);
	
	// Get times for all audio elements (not loading times each time without this due to metadata not loading fast enough?)
	$('audio').each(function(index) {
		var time = seconds_time($(this)[0].duration);
		$(this).parent().find('.audio_time').html(time);
	});
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


window.onscroll = function() {
	if( window.XMLHttpRequest ) {
		if (document.documentElement.scrollTop > 131 || self.pageYOffset > 131) {
			$('#sidebar_sticky').css('position','fixed');
			$('#sidebar_sticky').css('top','4.45em');
		} else if (document.documentElement.scrollTop < 131 || self.pageYOffset < 131) {
        	$('#sidebar_sticky').css('position','relative');
        	$('#sidebar_sticky').css('top','0');
    	}
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////