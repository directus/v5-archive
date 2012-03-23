<?PHP

//die(print_r($_GET, true));

// Redirect if exists (Ideally should be in htaccess)
$file_check_path = $_GET['file_name'] . "-" . $_GET['extension'] . "-" . $_GET['w'] . "-" . $_GET['h'] . "-" . $_GET['c'] . ".jpg";
if(file_exists($file_check_path) && $_GET['refresh'] != 'code' && $_GET['refresh'] != 'true' && $_GET['refresh'] != 'refresh') {
	header('content-type: image/jpeg'); 
	echo file_get_contents($file_check_path); 
	die();
}

//////////////////////////////////////////////////////////////////////////////

require_once("../../inc/setup.php");

//////////////////////////////////////////////////////////////////////////////

if($_GET['file_name'] && $_GET['w'] && $_GET['h'] && $_GET['c']){
	
	// Get image parent image
	$sth = $dbh->prepare("SELECT * FROM `directus_media` WHERE `file_name` = :file_name AND `extension` = :extension ");
	$sth->bindParam(':file_name', $_GET['file_name']);
	$sth->bindParam(':extension', $_GET['extension']);
	$sth->execute();
	if($parent = $sth->fetch()){
		
		// Variables
		$extension = ($parent['extension'] == "vimeo")? "jpg" : $parent['extension'];
		if($parent['extension'] == "vimeo"){
			$path = "../../../" . $settings['cms']['media_path'] . "vimeo_" . $parent['source'].".jpg";
		} else {
			$path = "../../../" . $settings['cms']['media_path'] . $parent['source'];
		}
		
		if($parent['extension'] == "vimeo"){
			list($width, $height, $type, $attr) = getimagesize($path);
			$parent_width = $new_width = $width;
			$parent_height = $new_height = $height;
		} else {
			$parent_width = $new_width = $parent['width'];
			$parent_height = $new_height = $parent['height'];
		}
		
		$width_max = $_GET['w'];
		$height_max = $_GET['h'];
		$crop = $_GET['c'];
		
		// Check if this size is allowed
		$thumb_check = "$width_max,$height_max,$crop";
		if(!in_array($thumb_check, $settings['image_autothumb'])){
			echo "Thumbnail not allowed";
		} else {
			
			// Set the quality of the thumbnail
			$quality = ($settings['cms']['thumb_quality'])? $settings['cms']['thumb_quality'] : 90;
			
			// Load image
			switch($extension){
				case "jpg":
					$source = imagecreatefromjpeg($path);
					break;
				case "gif":
					$source = imagecreatefromgif($path);
					break;
				case "png":
					$source = imagecreatefrompng($path);
					break;
				default:
					echo "Failed image extension";
			}
			
			// If we load the image then continue
			if($source){
	
				if($crop == "true" && $width_max && $height_max){
					
					$test_height = $parent_height*($width_max/$parent_width);
			
					if($test_height > $height_max){
						$new_width = $width_max;
						$new_height = $parent_height*($width_max/$parent_width);
						$new_x = 0;
						$new_y = -1 * (($new_height - $height_max)/2);
					} else {
						$new_height = $height_max;
						$new_width = $parent_width*($height_max/$parent_height);
						$new_x = -1 * (($new_width - $width_max)/2);
						$new_y = 0;
					}
			
					// Create a blank image
					$new_image = imagecreatetruecolor($width_max,$height_max);
			
					// Resize or crop image
					imagecopyresampled($new_image, $source, $new_x, $new_y, 0, 0, $new_width, $new_height, $parent_width, $parent_height);
			
					// For database sizes
					$new_width = $width_max;
					$new_height = $height_max;
			
				} else {
					// Resize for width
					if ($width_max && ($new_width > $width_max)) {
						$new_height = $parent_height*($width_max/$parent_width);
						$new_width = $width_max;
					}
			
					// Adjust for height if need be
					if ($height_max && ($new_height > $height_max)) {
						$new_width = $parent_width*($height_max/$parent_height);
						$new_height = $height_max;
					}
					
					// Create a blank image
					$new_image = imagecreatetruecolor($new_width,$new_height);
			
					// Resize or crop image
					imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $parent_width, $parent_height);
					
				}
				
				// Create it
				if(imagejpeg($new_image, $parent['file_name'] . "-" . $parent['extension'] . "-" . $width_max . "-" . $height_max . "-" . $crop . ".jpg", $quality)) {
					if($_GET['refresh'] == 'code') {
						echo "Success";
					} else {
						// Output headers for an image
						header("Content-type: image/jpeg");
						imagejpeg($new_image, NULL, $quality);
					}
				} else {
					echo "Failed to move image";
				}
				
				// Tidy up
				imagedestroy($source);
				imagedestroy($new_image);
					
			} else {
				echo "Failed to load image";
			}
		}
	} else {
		echo "Failed to find image";
	}
} else {
	echo "Missing required parameters";
}
?>