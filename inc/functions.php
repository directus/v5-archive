<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get local time from GMT/UTC/ZULU


function gmt_datetime_to_local($datetime) {
	$time = strtotime($datetime);
	return date("Y-m-d H:i:s", $time + date("Z",$time));
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BR to NL


function br2nl($text){
	return preg_replace('/<br\\s*?\/??>/i', '', $text);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Make string readable


function clean_db_item($text){
	return preg_replace('/[^a-zA-Z0-9_]/', '', $text);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Preserve chars when in attributes


function esc_attr($text){
	//return str_replace('"', '&quot;', $text);
	return htmlentities($text, ENT_COMPAT, "UTF-8");
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Make string readable


function uc_table($text){

	global $db_prefix;

	// Check for table prefix
	if($db_prefix && substr($text, 0, strlen($db_prefix)) === $db_prefix){
		$text = substr($text, strlen($db_prefix));
	}
	
	return uc_convert($text);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Make media title


function uc_media_title($text){
	return uc_convert(preg_replace('/[^a-zA-Z0-9_!?]/', ' ', str_replace("'", '', convert_smart_quotes(stripslashes($text)))));
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Fix capitalization issues


function uc_convert($text){
	$phrase = preg_replace('!\s+!', ' ', trim(ucwords(strtolower(str_replace('_', ' ', $text)))));
	$uc_caps 		= array("Faq", "Iphone", "Ipad", "Ipod", "Pdf", "Pdfs", "Url", "Ip", "Ftp", "Db", "Wysiwyg", "Cv", "Id", "Ph", "Php", "Html", "Js", "Css", "Mccaddon", "Rngr");
	$special_caps 	= array("FAQ", "iPhone", "iPad", "iPod", "PDF", "PDFs", "URL", "IP", "FTP", "DB", "WYSIWYG", "CV", "ID", "pH", "PHP", "HTML", "JS", "CSS", "McCaddon", "RNGR");
	
	foreach($uc_caps as $key => $value){
		$uc_caps[$key] = ("/\b".$value."\b/");
	}
	
	return preg_replace($uc_caps, $special_caps, $phrase);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Highlight in string


function highlight($needle, $haystack) {
	$needle = urlencode($needle);
	$haystack = urlencode($haystack);
	$result = ($needle)? (preg_replace("/($needle)/i", "<span class='hi'>$1</span>", $haystack)) : $haystack;
	return urldecode($result);
}

//////////////////////////////////////////////////////////////////////////////

function highlight_custom($needle, $haystack, $t1, $t2) {
	$needle = urlencode($needle);
	$haystack = urlencode($haystack);
	$result = ($needle)? (preg_replace("/($needle)/i", "$t1$1$t2", $haystack)) : $haystack;
	return urldecode($result);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Remove NULLs from array


function array_trim($array) {
    foreach($array as $key => $value){
		if($value == ' ' || strlen($value) === 0){
			unset($array[$key]);
		}
	}
    return $array;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// List files in a directory


function dir_list($directory, $plugins = false, $alphabetical = false) {

    $results = array();
    $handle = opendir($directory);
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..' && $file != '.htaccess'){
        	if(!$plugins || is_dir(BASE_PATH . 'plugins/'.$file)){
            	$results[] = $file;
            }
		}
    }
    closedir($handle);
    if($alphabetical){
    	sort($results);
    }
    return $results;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get absolute file path


function get_absolute_path($path) {
	$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
	$protocol = explode('://', $path);
	$parts = array_filter(explode(DIRECTORY_SEPARATOR, $protocol[1]), 'strlen');
	$absolutes = array();
	foreach ($parts as $part) {
		if ('.' == $part) continue;
		if ('..' == $part) {
			array_pop($absolutes);
		} else {
			$absolutes[] = $part;
		}
	}
	return ($protocol[0])? $protocol[0] . '://' . implode(DIRECTORY_SEPARATOR, $absolutes) : implode(DIRECTORY_SEPARATOR, $absolutes);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Format file size


function byte_convert($bytes) {
	if($bytes<=0){
		return '0 Byte';
	}
	$convention = 1000; //[1000->10^x|1024->2^x]
	$s = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB');
	$e = floor(log($bytes,$convention));
	if($s[$e] == 'kB' || $s[$e] == 'B'){
		return round($bytes/pow($convention,$e)).' '.$s[$e];
	} else {
		return round($bytes/pow($convention,$e),2).' '.$s[$e];
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Format time from seconds


function seconds_convert($total_seconds) {
	$hms = "";
	$hours = intval(intval($total_seconds) / 3600); 
	$hms .= ($hours > 0)? $hours. ':' : '';
	$minutes = intval(($total_seconds / 60) % 60); 
	$hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';
	$seconds = intval($total_seconds % 60);
	$hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
	return $hms;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get string between two strings


function get_string_between($string, $start, $end){
	$string = " ".$string;
	$ini = strpos($string,$start);
	if ($ini == 0) return "";
	$ini += strlen($start);
	$len = strpos($string,$end,$ini) - $ini;
	return substr($string,$ini,$len);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Contextual Time


function contextual_time($time) {
	$secs = (time() - date("Z",time())) - $time;
	
	// Handles things in the past OR future
	$in_future = ($secs < 0)? true : false;
	$past = ($in_future)? '' : ' ago';
	$future = ($in_future)? 'in ' : '';
	$secs = abs($secs);
	
	if($secs == 0) { 										return 'now'; }
	if($secs < (60)) { 										return $future . $secs . ' second' . ($secs > 1 ? 's' : '') . $past; }
	if($secs < (60*60)) { $minutes = round($secs/60); 		return $future . $minutes . ' minute' . ($minutes > 1 ? 's' : '') . $past; }
	if($secs < (60*60*16)) { $hours = round($secs/(60*60)); return $future . $hours . ' hour' . ($hours > 1 ? 's' : '') . $past; }
	if($secs < (time() - strtotime('yesterday'))) { 		return ($in_future)? 'tomorrow' : 'yesterday'; }
	if($secs < (60*60*24)) { $hours = round($secs/(60*60)); return $future . $hours . ' hour' . ($hours > 1 ? 's' : '') . $past; }
	if($secs < (60*60*24*7)) { 								return $future . round($secs/(60*60*24)) . ' day' . (round($secs/(60*60*24)) > 1 ? 's' : '') . $past; }
	if($secs < (time() - strtotime('last week'))) { 		return ($in_future)? 'next week' : 'last week'; }
	
	// Comment out the line below to remove the awesome "fortnight" display
	if(round($secs/(60*60*24)) == 14) { 					return $future . 'a fortnight' . $past; }
	
	if($secs < (60*60*24*7*4)) { 							return $future . round($secs/(60*60*24*7)) . ' week' . (round($secs/(60*60*24*7)) > 1 ? 's' : '') . $past; }
	if($secs < (time() - strtotime('last month'))) { 		return ($in_future)? 'next month' : 'last month'; }
	if($secs < (60*60*24*7*4*12)) { 						return $future . round($secs/(60*60*24*7*4)) . ' month' . (round($secs/(60*60*24*7*4)) > 1 ? 's' : '') . $past; }
	if($secs < (time() - strtotime('last year'))) { 		return ($in_future)? 'next year' : 'last year'; }
	if($secs >= (60*60*24*7*4*12*10)) { 					return 'never'; }
	if($secs >= (60*60*24*7*4*12)) { 						return $future . round($secs/(60*60*24*7*4*12)) . ' year' . (round($secs/(60*60*24*7*4*12)) > 1 ? 's' : '') . $past; }
	return false;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Format string with ellipses


function ellipses($string, $length = 60, $center = false, $append = null){

	$string = strip_tags($string);

	if ($append === null) {
        $append = ($center === true) ? ' ... ' : ' ...';
    }

    $len_string = strlen($string);
    $len_append = strlen($append);

    if ($len_string > $length) {
        if ($center === true) {
            $len_start = $length / 2;
            $len_end = $len_start - $len_append;

            $seg_start = substr($string, 0, $len_start);
            $seg_end = substr($string, $len_string - $len_end, $len_end);

            $string = $seg_start . $append . $seg_end;
        } else {
            $string = substr($string, 0, $length - $len_append) . $append;
        }
    }

    return $string;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Convert quotes


function convert_smart_quotes($string) {
	// - “ - ” - ‘ - ’ -
	// UTF8
	$search = array(chr(0xe2) . chr(0x80) . chr(0x98),
		chr(0xe2) . chr(0x80) . chr(0x99),
		chr(0xe2) . chr(0x80) . chr(0x9c),
		chr(0xe2) . chr(0x80) . chr(0x9d),
		chr(0xe2) . chr(0x80) . chr(0x93),
		chr(0xe2) . chr(0x80) . chr(0x94),
		chr(0xe2) . chr(0x80) . chr(0xa6)
	);

	$replace = array('\'',
		'\'',
		'"',
		'"',
		'&ndash;',	// &#8212;
		'&mdash;',	// &#8212;
		'...'
	);

	$string = str_replace($search, $replace, $string);

	// Regular (Breaks full UTF* charset (chinese)
	//$search = array(chr(145), chr(146), chr(147), chr(148), chr(151));
	//$replace = array("'", "'", '"', '"', '-');

	$string = str_replace($search, $replace, $string);

	return $string;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Convert URLs in text to links


function convert_urls_to_links($data){
	$data = preg_replace_callback('/(<a href=.+?<\/a>)/','guard_url',$data);
	
	$data = preg_replace_callback('/(http:\/\/.+?)([ \\n\\r])/','link_url',$data);
	$data = preg_replace_callback('/^(http:\/\/.+?)/','link_url',$data);
	$data = preg_replace_callback('/(http:\/\/.+?)$/','link_url',$data);
	
	$data = preg_replace_callback('/{{([a-zA-Z0-9+=]+?)}}/','unguard_url',$data);
	
	return $data;
}

function guard_url($arr) { return '{{'.base64_encode($arr[1]).'}}'; }
function unguard_url($arr) { return base64_decode($arr[1]); }
function link_url($arr) { return guard_url(array('','<a href="'.$arr[1].'">'.$arr[1].'</a>')).$arr[2]; }

//////////////////////////////////////////////////////////////////////////////

function convert_urls_to_links_unguarded($text) { 
	
	$text = eregi_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)', '<a href="\\1">\\1</a>', $text); 
	$text = eregi_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)', '\\1<a href="http://\\2">\\2</a>', $text); 
	$text = eregi_replace('([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})', '<a href="mailto:\\1">\\1</a>', $text); 
	
	return $text; 

} 


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


$rex_protocol = '(https?://)?'; 
$rex_domain   = '((?:[-a-zA-Z0-9]{1,63}\.)+[-a-zA-Z0-9]{2,63}|(?:[0-9]{1,3}\.){3}[0-9]{1,3})'; 
$rex_port     = '(:[0-9]{1,5})?'; 
$rex_path     = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?'; 
$rex_query    = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?'; 
$rex_fragment = '(#[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?'; 
$rex_url_linker = "{\\b$rex_protocol$rex_domain$rex_port$rex_path$rex_query$rex_fragment(?=[?.!,;:\"]?(\s|$))}"; 

// This array should be updated 
// Source:  http://data.iana.org/TLD/tlds-alpha-by-domain.txt 

$valid_tlds = array_fill_keys(explode(" ", ".ac .ad .ae .aero .af .ag .ai .al .am .an .ao .aq .ar .arpa .as .asia .at .au .aw .ax .az .ba .bb .bd .be .bf .bg .bh .bi .biz .bj .bm .bn .bo .br .bs .bt .bv .bw .by .bz .ca .cat .cc .cd .cf .cg .ch .ci .ck .cl .cm .cn .co .com .coop .cr .cu .cv .cx .cy .cz .de .dj .dk .dm .do .dz .ec .edu .ee .eg .er .es .et .eu .fi .fj .fk .fm .fo .fr .ga .gb .gd .ge .gf .gg .gh .gi .gl .gm .gn .gov .gp .gq .gr .gs .gt .gu .gw .gy .hk .hm .hn .hr .ht .hu .id .ie .il .im .in .info .int .io .iq .ir .is .it .je .jm .jo .jobs .jp .ke .kg .kh .ki .km .kn .kp .kr .kw .ky .kz .la .lb .lc .li .lk .lr .ls .lt .lu .lv .ly .ma .mc .md .me .mg .mh .mil .mk .ml .mm .mn .mo .mobi .mp .mq .mr .ms .mt .mu .museum .mv .mw .mx .my .mz .na .name .nc .ne .net .nf .ng .ni .nl .no .np .nr .nu .nz .om .org .pa .pe .pf .pg .ph .pk .pl .pm .pn .pr .pro .ps .pt .pw .py .qa .re .ro .rs .ru .rw .sa .sb .sc .sd .se .sg .sh .si .sj .sk .sl .sm .sn .so .sr .st .su .sv .sy .sz .tc .td .tel .tf .tg .th .tj .tk .tl .tm .tn .to .tp .tr .travel .tt .tv .tw .tz .ua .ug .uk .us .uy .uz .va .vc .ve .vg .vi .vn .vu .wf .ws .xn--0zwm56d .xn--11b5bs3a9aj6g .xn--80akhbyknj4f .xn--9t4b11yi5a .xn--deba0ad .xn--fiqs8s .xn--fiqz9s .xn--fzc2c9e2c .xn--g6w251d .xn--hgbk6aj7f53bba .xn--hlcj6aya9esc7a .xn--j6w193g .xn--jxalpdlp .xn--kgbechtv .xn--kprw13d .xn--kpry57d .xn--mgbaam7a8h .xn--mgbayh7gpa .xn--mgberp4a5d4ar .xn--o3cw4h .xn--p1ai .xn--pgbs0dh .xn--wgbh1c .xn--xkc2al3hye2a .xn--ygbi2ammx .xn--zckzah .ye .yt .za .zm .zw"), true); 

function escape_and_link_urls($text){ 
	
	global $rex_url_linker, $valid_tlds; 
	
	$result = ""; 
	
	$position = 0; 
	while (preg_match($rex_url_linker, $text, $match, PREG_OFFSET_CAPTURE, $position)){ 
		list($url, $url_position) = $match[0]; 
		
		// Add the text leading up to the URL. 
		$result .= htmlspecialchars(substr($text, $position, $url_position - $position)); 
		
		$domain = $match[2][0]; 
		$port   = $match[3][0]; 
		$path   = $match[4][0]; 
		
		// Check that the TLD is valid or that $domain is an IP address. 
		$tld = strtolower(strrchr($domain, '.')); 
		if(preg_match('{^\.[0-9]{1,3}$}', $tld) || isset($valid_tlds[$tld])){ 
			// Prepend http:// if no protocol specified 
			$completeUrl = $match[1][0] ? $url : "http://$url"; 
			
			// Add the hyperlink. 
			$result .= '<a href="' . htmlspecialchars($completeUrl) . '">' . htmlspecialchars("$domain$port$path") . '</a>'; 
		} else { 
			// Not a valid URL. 
			$result .= htmlspecialchars($url); 
		} 
		
		// Continue text parsing from after the URL. 
		$position = $url_position + strlen($url); 
	} 
	
	// Add the remainder of the text. 
	$result .= htmlspecialchars(substr($text, $position)); 
	return $result; 
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Convert CSV string to an array


function csv_to_array($csv){
	
	$len = strlen($csv);
	$table = array();
	$cur_row = array();
	$cur_val = "";
	$state = 1;
	
	// States
	// 1 = first item
	// 2 = we're quoted hea
	// 3 = wait for a line feed, if so close out row!
	// 4 = gather not quote
	// 5 = potential end quote found
	
	for ($i = 0; $i < $len; $i++){
		$ch = substr($csv,$i,1);
		if ($state == 1){
			if ($ch == '"'){
				$state = 2;
			} elseif ($ch == ","){
				$cur_row[] = ""; //done with first one
				$cur_val = "";
				$state = 1;
			} elseif ($ch == "\n"){
				$cur_row[] = $cur_val;
				$table[] = $cur_row;
				$cur_row = array();
				$cur_val = "";
				$state = 1;
			} elseif ($ch == "\r"){
				$state = 3;
			} else {
				$cur_val .= $ch;
				$state = 4;
			}
		} elseif ($state == 2){
			if ($ch == '"'){
				$state = 5;
			} else {
				$cur_val .= $ch;
			}
		} elseif ($state == 5){
			if ($ch == '"'){
				$cur_val .= '"';
				$state = 2;
			} elseif ($ch == ','){
				$cur_row[] = $cur_val;
				$cur_val = "";
				$state = 1;
			} elseif ($ch == "\n"){
				$cur_row[] = $cur_val;
				$table[] = $cur_row;
				$cur_row = array();
				$cur_val = "";
				$state = 1;
			} elseif ($ch == "\r"){
				$state = 3;
			} else {
				$cur_val .= $ch;
				$state = 2;
			}
		} elseif ($state == 3){
			if ($ch == "\n"){
				$cur_row[] = $cur_val;
				$cur_val = "";
				$table[] = $cur_row;
				$cur_row = array();
				$state = 1;
			} else {
				$cur_row[] = $cur_val;
				$table[] = $cur_row;
				$cur_row = array();
				$cur_val = $ch;
				$state = 4;
			}	
		} elseif ($state == 4){
			if ($ch == ","){
				$cur_row[] = $cur_val;
				$cur_val = "";
				$state = 1;
			} elseif ($ch == "\n"){
				$cur_row[] = $cur_val;
				$table[] = $cur_row;
				$cur_row = array();
				$cur_val = "";
				$state = 1;
			} elseif ($ch == "\r"){
				$state = 3;
			} else {
				$cur_val .= $ch;
			}
		}
	
	}
	
	return $table;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Convert number to letter for column headers of tables


function num_to_chars($num, $start=65, $end=90){
	$sig = ($num < 0);
	$num = abs($num);
	$str = "";
	$cache = ($end-$start);
	while($num != 0){
		$str = chr(($num%$cache)+$start-1).$str;
		$num = ($num-($num%$cache))/$cache;
	}
	if($sig){
		$str = "-".$str;
	}
	return $str;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Check for user cookie, validate and log-in


function check_user_cookie(){

	global $dbh;

	if(!isset($_SESSION['cms_user_id']) && isset($_COOKIE['token'])){
		$sth = $dbh->prepare("SELECT * FROM `directus_users` WHERE `token` = :token ");
		$sth->bindParam(':token', $_COOKIE['token']);
		$sth->execute();
		if($row = $sth->fetch()){
			$_SESSION['cms_user_id'] = $row["id"];
		} else {
			// Cookie login failed
		}
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Create a nonce - 256 bit == 32byte


function nonce($size = 32){
	for($x=0;$x<$size;$x++){
		$ret.=chr(mt_rand(0,255));
	}
	return base64_encode($ret);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Portable PHP password hashing framework - Version 0.3 / genuine


class PasswordHash {
	var $itoa64;
	var $iteration_count_log2;
	var $portable_hashes;
	var $random_state;

	function PasswordHash($iteration_count_log2, $portable_hashes)
	{
		$this->itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

		if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31)
			$iteration_count_log2 = 8;
		$this->iteration_count_log2 = $iteration_count_log2;

		$this->portable_hashes = $portable_hashes;

		$this->random_state = microtime();
		if (function_exists('getmypid'))
			$this->random_state .= getmypid();
	}

	function get_random_bytes($count)
	{
		$output = '';
		if (@is_readable('/dev/urandom') &&
		    ($fh = @fopen('/dev/urandom', 'rb'))) {
			$output = fread($fh, $count);
			fclose($fh);
		}

		if (strlen($output) < $count) {
			$output = '';
			for ($i = 0; $i < $count; $i += 16) {
				$this->random_state =
				    md5(microtime() . $this->random_state);
				$output .=
				    pack('H*', md5($this->random_state));
			}
			$output = substr($output, 0, $count);
		}

		return $output;
	}

	function encode64($input, $count)
	{
		$output = '';
		$i = 0;
		do {
			$value = ord($input[$i++]);
			$output .= $this->itoa64[$value & 0x3f];
			if ($i < $count)
				$value |= ord($input[$i]) << 8;
			$output .= $this->itoa64[($value >> 6) & 0x3f];
			if ($i++ >= $count)
				break;
			if ($i < $count)
				$value |= ord($input[$i]) << 16;
			$output .= $this->itoa64[($value >> 12) & 0x3f];
			if ($i++ >= $count)
				break;
			$output .= $this->itoa64[($value >> 18) & 0x3f];
		} while ($i < $count);

		return $output;
	}

	function gensalt_private($input)
	{
		$output = '$P$';
		$output .= $this->itoa64[min($this->iteration_count_log2 +
			((PHP_VERSION >= '5') ? 5 : 3), 30)];
		$output .= $this->encode64($input, 6);

		return $output;
	}

	function crypt_private($password, $setting)
	{
		$output = '*0';
		if (substr($setting, 0, 2) == $output)
			$output = '*1';

		$id = substr($setting, 0, 3);
		# We use "$P$", phpBB3 uses "$H$" for the same thing
		if ($id != '$P$' && $id != '$H$')
			return $output;

		$count_log2 = strpos($this->itoa64, $setting[3]);
		if ($count_log2 < 7 || $count_log2 > 30)
			return $output;

		$count = 1 << $count_log2;

		$salt = substr($setting, 4, 8);
		if (strlen($salt) != 8)
			return $output;

		# We're kind of forced to use MD5 here since it's the only
		# cryptographic primitive available in all versions of PHP
		# currently in use.  To implement our own low-level crypto
		# in PHP would result in much worse performance and
		# consequently in lower iteration counts and hashes that are
		# quicker to crack (by non-PHP code).
		if (PHP_VERSION >= '5') {
			$hash = md5($salt . $password, TRUE);
			do {
				$hash = md5($hash . $password, TRUE);
			} while (--$count);
		} else {
			$hash = pack('H*', md5($salt . $password));
			do {
				$hash = pack('H*', md5($hash . $password));
			} while (--$count);
		}

		$output = substr($setting, 0, 12);
		$output .= $this->encode64($hash, 16);

		return $output;
	}

	function gensalt_extended($input)
	{
		$count_log2 = min($this->iteration_count_log2 + 8, 24);
		# This should be odd to not reveal weak DES keys, and the
		# maximum valid value is (2**24 - 1) which is odd anyway.
		$count = (1 << $count_log2) - 1;

		$output = '_';
		$output .= $this->itoa64[$count & 0x3f];
		$output .= $this->itoa64[($count >> 6) & 0x3f];
		$output .= $this->itoa64[($count >> 12) & 0x3f];
		$output .= $this->itoa64[($count >> 18) & 0x3f];

		$output .= $this->encode64($input, 3);

		return $output;
	}

	function gensalt_blowfish($input)
	{
		# This one needs to use a different order of characters and a
		# different encoding scheme from the one in encode64() above.
		# We care because the last character in our encoded string will
		# only represent 2 bits.  While two known implementations of
		# bcrypt will happily accept and correct a salt string which
		# has the 4 unused bits set to non-zero, we do not want to take
		# chances and we also do not want to waste an additional byte
		# of entropy.
		$itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$output = '$2a$';
		$output .= chr(ord('0') + $this->iteration_count_log2 / 10);
		$output .= chr(ord('0') + $this->iteration_count_log2 % 10);
		$output .= '$';

		$i = 0;
		do {
			$c1 = ord($input[$i++]);
			$output .= $itoa64[$c1 >> 2];
			$c1 = ($c1 & 0x03) << 4;
			if ($i >= 16) {
				$output .= $itoa64[$c1];
				break;
			}

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 4;
			$output .= $itoa64[$c1];
			$c1 = ($c2 & 0x0f) << 2;

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 6;
			$output .= $itoa64[$c1];
			$output .= $itoa64[$c2 & 0x3f];
		} while (1);

		return $output;
	}

	function HashPassword($password)
	{
		$random = '';

		if (CRYPT_BLOWFISH == 1 && !$this->portable_hashes) {
			$random = $this->get_random_bytes(16);
			$hash =
			    crypt($password, $this->gensalt_blowfish($random));
			if (strlen($hash) == 60)
				return $hash;
		}

		if (CRYPT_EXT_DES == 1 && !$this->portable_hashes) {
			if (strlen($random) < 3)
				$random = $this->get_random_bytes(3);
			$hash =
			    crypt($password, $this->gensalt_extended($random));
			if (strlen($hash) == 20)
				return $hash;
		}

		if (strlen($random) < 6)
			$random = $this->get_random_bytes(6);
		$hash =
		    $this->crypt_private($password,
		    $this->gensalt_private($random));
		if (strlen($hash) == 34)
			return $hash;

		# Returning '*' on error is safe here, but would _not_ be safe
		# in a crypt(3)-like function used _both_ for generating new
		# hashes and for validating passwords against existing hashes.
		return '*';
	}

	function CheckPassword($password, $stored_hash)
	{
		$hash = $this->crypt_private($password, $stored_hash);
		if ($hash[0] == '*')
			$hash = crypt($password, $stored_hash);

		return $hash == $stored_hash;
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get Avatar


function get_avatar($id) {
	
	global $cms_all_users;
	global $directus_path;
	
	if(file_exists(BASE_PATH . "media/users/".$id . ".jpg")){
		return $directus_path . "media/users/" . $id . ".jpg";
	} else {
		return "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $cms_all_users[$id]['email'] ) ) ) . "?d=identicon&s=50";
		//return $directus_path . "media/users/default.png";
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get a username from an id


function get_username($id) {

	global $cms_all_users;
	global $cms_user;

	if($id === '0'){
		return 'Directus';
	} else {
		return ($id == $cms_user['id'])? 'You' : $cms_all_users[$id]['username'];
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get usernames from a csv string


function get_usernames($ids) {
	
	global $cms_all_users;
	global $cms_user;

	if($ids == 'all'){
		$results[] = 'Everyone';
		return $results;
	} else {
		$ids = explode(',',$ids);
		if(is_array($ids)){
			$ids = array_filter($ids);
			foreach($ids as $id){
				$results[] = ($id == $cms_user['id'])? 'You' : $cms_all_users[$id]['username'];
			}
			return $results;
		} else {
			return false;
		}
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Send a directus email


function send_email($subject, $body, $to = false, $from = false, $bcc = false){
	
	global $settings;
	
	$subject = "[" . stripslashes($settings['cms']['site_name']) . "] " . ellipses(stripslashes($subject), 50);
	
	$body .= "\n<br>\n<br>\n<br><i>Note that this is an automatically generated e-mail.\n<br>Please do not reply to this e-mail address.</i>\n<br><a href='".CMS_PAGE_PATH."users.php'>Stop receiving emails</a>\n<br>\n<br>--\n<br>Directus\n<br>".$settings['cms']['version'];
	$body = wordwrap(stripslashes($body), 70);
	
	$to = ($to)? $to : "You <messages@example.com>";
	$from = ($from)? $from : "Directus <messages@getdirectus.com>";
	
	//$headers = 'MIME-Version: 1.0' . "\n";
	$headers = 'Content-type: text/html; charset=utf-8' . "\n";
	$headers .= 'From: ' . $from . "\n";
	if($bcc && count($bcc) > 0){
		$headers .= 'Bcc: ' . implode(',', $bcc) . "\n";
	}
	$headers .= "\n";
	
	return mail($to, $subject, $body, $headers);

}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Create full database Backup


function backup_database(){

	global $cms_user;
	global $db_server;
	global $db_username;
	global $db_password;
	global $db_database;

	$backup_file = 'inc/backups/' . date("Y-m-d_H-i-s_") . str_replace(" ", "-", strtolower($cms_user['first_name'].'-'.$cms_user['last_name'])) . '.sql';
	$output = system("mysqldump -h$db_server -u$db_username -p$db_password $db_database > $backup_file");
	if ($output !== false) {
		$filesize = filesize($backup_file);
		if(insert_activity($table = $backup_file, $row = $filesize, $type = 'backed up', $sql = '')){
			$_SESSION['alert'] = "backup";
		}
		return 'success';
	} else {
		return 'backup_error';
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get an array of the non CMS table names


function get_tables() {
	
	global $dbh;
	global $cms_user;
	global $db_prefix;

	$results = array();
	$sth = $dbh->query("SHOW TABLES");
	while($row = $sth->fetch()){
		
		// Don't get directus tables
		if(substr($row[0],0,9) != "directus_"){
		
			// Dont get tables without the table prefix if there is one
			if( ($db_prefix && substr($row[0], 0, strlen($db_prefix)) === $db_prefix) || !$db_prefix){
			
				$results[] = $row[0];
				
			}
		}
	}

	return $results;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get an array of field info for a given table (no data)


function get_rows_info($table) {
	
	global $dbh;
	
	$results = array();
	$results['active'] = false;
	$results['sort'] = false;
	$results['fields'] = array();
	$results['info'] = array();
	
	// Reminder:  Match against array of tables for better security	
	$sth = $dbh->query("SHOW FULL COLUMNS FROM `$table` "); 	// Same as DESCRIBE only holds comments
	while($row = $sth->fetch()){
		
		// Field, Type, Collation, Null, Key, Default, Extra, Privileges, Comment
		
		// Do not return cms fields
		if($row['Field'] == 'id' || $row['Field'] == 'active' || $row['Field'] == 'sort') {
			
			// Remember that this table has an active field
			if($row['Field'] == "active"){
				$results['active'] = true;
			}
			
			// Remember that this table has an sort field
			if($row['Field'] == "sort"){
				$results['sort'] = true;
			}
			
		} else {
			
			// Add this to the list of fields
			$results['fields'][] = $row['Field'];
			
			// Get the length and type of the field
			if($pos = strpos($row['Type'], "(")){
				$trimmed = str_replace(")", "", $row['Type']);
				$type_length = substr($trimmed, ($pos+1));
				$type_lengthless = substr($trimmed, 0, $pos);
			} else {
				$type_length = 0;
				$type_lengthless = $row['Type'];
			}

			$results['info'][$row['Field']] = array();
			
			$results['info'][$row['Field']]['type'] = $row['Type'];
			$results['info'][$row['Field']]['type_lengthless'] = $type_lengthless;
			$results['info'][$row['Field']]['type_length'] = $type_length;
			$results['info'][$row['Field']]['null'] = $row['Null'];
			$results['info'][$row['Field']]['default'] = $row['Default'];
			$results['info'][$row['Field']]['comment'] = $row['Comment'];
		}

	}

	// Get total number of active rows for this table
	$query = "SELECT * FROM `$table` ";
	$query .= ($results['active']) ? "WHERE `active` = '1' " : "";
	$sth = $dbh->query($query);
	$results['num'] = count($sth->fetchAll());
	
	return $results;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get an array field info for a given table (with data)


function get_rows($table_id, $id = false) {
	
	global $dbh;
	global $cms_user;
	
	$results = array();
	
	// Get table name info based on ID... forces to check if table exists
	$tables = get_tables();
	$table = $tables[$table_id];

	if($table){
		
		// If the user has header preferences for this table
		$results['header_fields'] = false;
		$results['sort_field'] = false;
		
		$sth = $dbh->prepare("SELECT * FROM `directus_preferences` WHERE `user` = :user AND `name` = :name ");
		$sth->bindParam(':user', $cms_user['id']);
		$sth->bindParam(':name', $table);
		$sth->execute();
		while($user_table_preferences = $sth->fetch()){
			$results[$user_table_preferences['type']] = $user_table_preferences['value'];
		}
		
		// Set the table names 
		$results['table_id'] 	= $table_id;
		$results['name'] 		= $table;
		$results['name_uc'] 	= uc_table($table);
		
		// Get and set the table info
		$table_info 			= get_rows_info($table);
		$results['info'] 		= $table_info['info'];
		$results['active'] 		= $table_info['active'];
		$results['sort'] 		= $table_info['sort'];
		$results['num'] 		= $table_info['num'];
		$results['fields'] 		= $table_info['fields'];
		
		// Get the rows
		if($id != 'bypass'){
			
			$query_rows = "SELECT * FROM `$table` WHERE 1=1 ";
			
			if($id !== false){
				// Check to make sure this is JUST an ID
				$id = intval($id);
				
				// Limit results to just this ID if given
				$query_rows .= "AND `id` = '$id' LIMIT 1 ";
				$results['item_id'] = $id;
			} else {
			
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Add or Update user field sort preference
				
				// Clean variables
				$_GET['direction'] = ($_GET['direction'] == 'DESC')? 'DESC' : 'ASC';
				$_GET['sort'] = ($_GET['sort'] == 'sort' || in_array($_GET['sort'], $results['fields']))? $_GET['sort'] : false;
				
				if($_GET['sort'] && $_GET['direction']){
					
					if($results['sort_field']){
						$query = "UPDATE `directus_preferences` SET `value` = :value WHERE `user` = :user AND `name` = :name AND `type` = 'sort_field' ";
					} else {
						$query = "INSERT INTO `directus_preferences` SET `value` = :value, `user` = :user, `name` = :name, `type` = 'sort_field' ";
					}
					
					$results['sort_field'] = $_GET['sort'] . ' ' . $_GET['direction'];
					
					$sth = $dbh->prepare($query);
					$sth->bindParam(':user', $cms_user['id']);
					$sth->bindParam(':name', $table);
					$sth->bindParam(':value', $results['sort_field']);
					$sth->execute();
				}
				
				if($results['sort_field']){
					// Sort by user preferences
					$query_rows .= ($table_info['sort']) ? "ORDER BY ".$results['sort_field'].", `sort` ASC " : "ORDER BY ".$results['sort_field']." ";
				} else {
					// Sort by SORT and ID if there is an sort field, or just ID if not
					$query_rows .= ($table_info['sort']) ? "ORDER BY `sort` ASC, `id` ASC " : "ORDER BY `id` ASC ";
				}
			}
	
			$results['sql'] = $query_rows;
			
			$results['rows'] = array();
			$sth = $dbh->query($query_rows);
			while($row_rows = $sth->fetch()){
				$results['rows'][$row_rows['id']] = $row_rows;
			}
		}
		
		return $results;
		
	} else {
		return false;
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get user permissions for a given table


function get_permissions($table){
	
	global $cms_user;
	global $settings;
	
	// If user has TABLE or ALL access, or user is admin
	// !in_array($table,$settings['table_hidden']) && 
	$return['view'] = (strpos($cms_user["view"],','.$table.',') !== false || $cms_user["view"] == 'all' || $cms_user["admin"] == '1')? true : false;
	$return['add'] = (strpos($cms_user["add"],','.$table.',') !== false || $cms_user["add"] == 'all' || $cms_user["admin"] == '1')? true : false;
	$return['edit'] = (strpos($cms_user["edit"],','.$table.',') !== false || $cms_user["edit"] == 'all' || $cms_user["admin"] == '1')? true : false;
	$return['reorder'] = (strpos($cms_user["reorder"],','.$table.',') !== false || $cms_user["reorder"] == 'all' || $cms_user["admin"] == '1')? true : false;
	$return['delete'] = (strpos($cms_user["delete"],','.$table.',') !== false || $cms_user["delete"] == 'all' || $cms_user["admin"] == '1')? true : false;
	
	return $return;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get the field value from the primary field of a table


function get_primary_field_value($table, $id){
	
	global $dbh;
	global $settings;
	
	$field = $settings['field_primary'][$table];
	
	if($field){
		// Reminder: Compare against table names
		$sth = $dbh->prepare("SELECT `$field` FROM `$table` WHERE `id` = :id LIMIT 1 ");
		$sth->bindParam(':id', $id);
		$sth->execute();
		if($row = $sth->fetch()){
			return ($row[$field])? $row[$field] : 'Item';
		}
	}
	
	return 'Missing Item';
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get the title of a media item


function get_media_title($id){
	
	global $dbh;
	
	$sth = $dbh->prepare("SELECT title FROM `directus_media` WHERE `id` = :id ");
	$sth->bindParam(':id', $id);
	$sth->execute();
	if($row = $sth->fetch()){
		return ($row['title'])? $row['title'] : '<i>No title</i>';
	}
	
	return 'Missing Item';
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get items description with link from activity row: "<Item Link> within table"


function get_item_description($activity){
	if($activity['type'] == 'backed up'){
		$temp['attributes'] = ' target="_blank"';
		$temp['link'] = $activity['table'];
		$temp['link_text'] = 'File';
		$missing = (file_exists($activity['table']))? '' : ' - <span class="warning">Missing File!</span>';
		$temp['text'] = byte_convert($activity['row']) . $missing;
		
	} elseif($activity['type'] == 'error'){
		$temp['attributes'] = ' class="dialog_note" title="Error Log" message="<b>User:</b> '.get_username($activity['user']).'<br><b>Page:</b> '.$activity['table'].'<br><b>Time:</b> '.date('M jS Y, g:i:s a', strtotime($activity['datetime'])).'<br><br>'.str_replace('"', '\"',strip_tags($activity['sql'])).'"';
		$temp['link'] = '#';
		$temp['link_text'] = 'View Error';
		$temp['text'] = ellipses($activity['sql']);
		
	} elseif($activity['type'] == 'installed'){
		$temp['attributes'] = '';
		$temp['link'] = '#';
		$temp['link_text'] = '';
		$temp['text'] = 'Directus install';
		
	} elseif($activity['type'] == 'structure'){
		$temp['attributes'] = '';
		if(strpos($activity['sql'], "CREATE TABLE") !== false){
			$temp['link'] = 'browse.php?table=' . $activity['table'];
			$temp['link_text'] = uc_table($activity['table']);
			$temp['text'] = 'has been added';
		} elseif(strpos($activity['sql'], "ALTER TABLE `") !== false){
			$temp['link'] = 'browse.php?table=' . $activity['table'];
			$temp['link_text'] = uc_convert($activity['row']);
			$temp['text'] = 'has been added to ' . uc_table($activity['table']);
		}
		
	} elseif($activity['table'] == 'directus_users'){
		$temp['attributes'] = '';
		$temp['link'] = 'users.php';
		$temp['link_text'] = $activity['sql'];
		$temp['text'] = 'has been added to Directus Users';
		
	} elseif($activity['table'] == 'directus_media'){
		$temp['attributes'] = ' class="open_media" media_id="' . $activity['row'] . '"';
		$temp['link'] = '#';
		$temp['link_text'] = ellipses(get_media_title($activity['row']), 20);
		$batch = ($activity['sql'] == 'batch')? ' - <b>Batch upload</b>' : '';
		$temp['text'] = 'within Directus Media' . $batch;
		
	} elseif($activity['table'] && $activity['row']){
		$first_field = get_primary_field_value($activity['table'], $activity['row']);
		$temp['attributes'] = ' title="' . ellipses(str_replace('"','\"',$first_field), 200) . '"';
		$temp['link'] = 'edit.php?table=' . $activity['table'] . '&item=' . $activity['row'];
		$temp['link_text'] = ellipses($first_field, 20);
		$temp['text'] =  'within ' . uc_table($activity['table']);	
	}
	
	return $temp;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Insert an event into revisions


function insert_activity($table = 'None', $row = 'None', $type = '', $sql = '', $active = 1){

	global $dbh;
	global $cms_user;
	
	$cms_user['id'] = ($cms_user['id'])? $cms_user['id'] : 0;
	
	$sth = $dbh->prepare("INSERT INTO `directus_activity` (`active`, `table`, `row`, `type`, `datetime`, `user`, `sql`) VALUES (:active, :table, :row, :type, :datetime, :user, :sql) ");
	$sth->bindParam(':active', $active);
	$sth->bindParam(':table', $table);
	$sth->bindParam(':row', $row);
	$sth->bindParam(':type', $type);
	$sth->bindValue(':datetime', CMS_TIME);
	$sth->bindParam(':user', $cms_user['id']);
	$sth->bindParam(':sql', $sql);
	
	return $sth->execute();
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Create thumb


function make_thumb($cms_save_path, $parent_id, $path, $extension, $width_max, $height_max, $force) {
	
	global $dbh;
	global $settings;
	global $image_non_sequential;
	global $cms_user;
	
	// Set the quality of the thumbnail, 75 for CMS thumbs
	$temp_quality = (strpos($cms_save_path, 'cms_thumbs') !== false)? 75 : $settings['cms']['thumb_quality'];
	
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
			return "Thumb extension failed";
	}

	// If load works, create a thumb and save it
	if($source){

		// Get the next ID
		$sth = $dbh->query("SELECT max(id) FROM `directus_media` ");
		$next_id = ($row = $sth->fetch())? ($row["max(id)"]+1) : 1;
		
		// Get file paths and info
		$typless_name = str_replace("." . end(explode('.', $path)),"",end(explode('/', $path)));
		
		// Name the file based on the media settings
		if($settings['cms']['media_naming'] == 'sequential'){
			$new_name = str_pad($next_id, 6, "0", str_pad_left);
		} elseif($settings['cms']['media_naming'] == 'original'){
			$new_name = $typless_name.'-'.$width_max.'x'.$height_max.'-'.$force;
		} else {
			$new_name = md5("Directus Image " . $next_id);
		}
		
		// Create the file path for the thumb
		$new_path = $cms_save_path . $new_name . "." . $extension;

		// Get and set image dimensions
		$width_max = (is_numeric($width_max))? 	$width_max 		: false;
		$height_max = (is_numeric($height_max))? 	$height_max 	: false;
		$width = imagesx($source);		// $width = $newWidth = imagesx($img);
		$height = imagesy($source);
		$new_width = $width;
		$new_height = $height;

		if($force == "true" && $width_max && $height_max){

			$test_height = $height*($width_max/$width);

			if($test_height > $height_max){
				$new_width = $width_max;
				$new_height = $height*($width_max/$width);
				$new_x = 0;
				$new_y = -1 * (($new_height - $height_max)/2);
			} else {
				$new_height = $height_max;
				$new_width = $width*($height_max/$height);
				$new_x = -1 * (($new_width - $width_max)/2);
				$new_y = 0;
			}

			// Create a blank image
			$new_img = imagecreatetruecolor($width_max,$height_max);

			// Resize or crop image
			imagecopyresampled($new_img, $source, $new_x, $new_y, 0, 0, $new_width, $new_height, $width, $height);

			// For database sizes
			$new_width = $width_max;
			$new_height = $height_max;

		} else {
			// Resize for width
			if ($width_max && ($new_width > $width_max)) {
				$new_height = $height*($width_max/$width);
				$new_width = $width_max;
			}

			// Adjust for height if need be
			if ($height_max && ($new_height > $height_max)) {
				$new_width = $width*($height_max/$height);
				$new_height = $height_max;
			}

			// Create a blank image
			$new_img = imagecreatetruecolor($new_width,$new_height);

			// Resize or crop image
			imagecopyresampled($new_img, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

		}

		// CMS Thumb or Normal thumb
		if($parent_id == false){
			$move_thumb = imagejpeg($new_img, $cms_save_path, $temp_quality);
			if(!$move_thumb){
				return "Thumb '$new_width x $new_height' Failed - Move";
			}
		} else {

			// Make the image (JPG only?)
			$move_thumb = imagejpeg($new_img, "../../".$new_path, $temp_quality);

			// Insert into database as thumb
			if ($move_thumb) {

				$file_size  = filesize( "../../".$new_path );
				
				$sth = $dbh->prepare("INSERT INTO `directus_media` SET `uploaded` = :uploaded, `user` = :user, `source` = :source, `title` = 'Thumbnail', `extension` = 'jpg', `width` = :width, `height` = :height, `file_size` = :file_size");
				$sth->bindValue(':uploaded', CMS_TIME);
				$sth->bindParam(':user', $cms_user["id"]);
				$sth->bindValue(':source', $new_name . "." . $extension); // MIGHT BE AN ERROR HERE!
				$sth->bindParam(':width', $new_width);
				$sth->bindParam(':height', $new_height);
				$sth->bindParam(':file_size', $file_size);
				if ( $sth->execute() ){
					// return "Thumb Success";
				} else {
					return "Failed to add: Thumb '$new_width x $new_height'";
				}
			} else {
				return "Failed to move: Thumb '$new_width x $new_height'";
			}
		}

		// Tidy up
		imagedestroy($source);
		imagedestroy($new_img);
		
		return false;

	} else {
		return "Failed to create: Thumb '$width_max x $height_max'<br><i>".$path."</i>";
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Generate media item


function generate_media_item($id, $title, $extension, $source, $height, $width, $file_size, $uploaded, $user, $caption, $search = false, $tags = '', $modal = false) {
	
	global $cms_all_users;
	global $cms_user;
	
	$click_type = ($modal)? 'checkable': 'editable';
	
	?>
	<tr class="media_item" id="media_<?PHP echo $id; ?>">
		<td class="check" raw="X"><?PHP if($cms_user['media'] || $modal){ ?><input class="status_action_check" type="checkbox" name="media_ids_checked[]" value="<?PHP echo $id; ?>" id="<?PHP echo $id; ?>"><?PHP } ?></td> 
		<td class="thumb <?PHP echo $click_type;?>" raw="X">
			
			<?PHP 
			if(!$modal){ 
				?>
				<input name="media_ids[]" type="hidden" value="<?PHP echo $id; ?>" />
				<?PHP
			}
			
			generate_media_image($extension, $source, $height, $width, $file_size);
			$extension = strtoupper($extension);
			$file_size_text = ($extension == 'YOUTUBE' || $extension == 'VIMEO')? seconds_convert($file_size) : byte_convert($file_size);
			?>
			
		</td> 
		<td class="field_title first_field <?PHP echo $click_type;?>"><div class="wrap"><?PHP echo $title;?></div></td> 
		<td class="field_type <?PHP echo $click_type;?>"><div class="wrap"><?PHP echo $extension; ?></div></td> 
		<td class="field_size <?PHP echo $click_type;?>"><div class="wrap"><?PHP echo $file_size_text;?></div></td> 
		<td class="field_caption <?PHP echo $click_type;?>"><div class="wrap" title="<?PHP echo $tags;?>"><?PHP echo $caption;?> <span class="hide"><?PHP echo $tags;?></span></div></td> 
		<td class="field_user <?PHP echo $click_type;?>"><div class="wrap"><?PHP echo ($user == $cms_user['id'])? '<strong>'.$cms_all_users[$user]['username'].'</strong>' : $cms_all_users[$user]['username'];?></div></td> 
		<td class="field_date <?PHP echo $click_type;?>"><div class="wrap" title="<?PHP echo date('Y-m-d H:i:s',strtotime($uploaded));?>"><?PHP echo contextual_time(strtotime($uploaded));?></div></td> 
	</tr>
	<?PHP
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Generate relational media item

function generate_media_relational($id, $title, $parent_item, $extension, $source, $height, $width, $file_size) {
	?>
	<tr class="item" replace_with="media_<?PHP echo $id;?>"> 
		<td class="order handle"><img src="media/site/icons/ui-splitter-horizontal.png" width="16" height="16" /></td> 
		<td class="thumb"><?PHP generate_media_image($extension, $source, $height, $width, $file_size);?></td> 
		<td>
			<div class="wrap">
				<?PHP echo $title;?>
				<input type="hidden" name="<?PHP echo $parent_item;?>[]" value="<?PHP echo $id;?>">
				<a class="badge edit_fancy modal" href="inc/media_modal.php?type=relational&replace=true&parent_item=<?PHP echo $parent_item;?>&id=<?PHP echo $id;?>" style="display:none;">Edit</a>
			</div>
		</td> 
		<td width="10%"><a class="ui-icon ui-icon-close right remove_media" href=""></a></td> 
	</tr>
	<?PHP
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Generate media image


function generate_media_image($extension, $source, $height, $width, $file_size, $size = 50) {
	
	global $settings;
	
	$extension = strtolower($extension);
	
	if($extension == 'jpg' || $extension == 'gif' || $extension == 'png'){
		if( $settings['cms']['thumb_path'] && file_exists($settings['cms']['thumb_path'] . basename($source)) ){
			$file_path = $settings['cms']['thumb_path'] . basename($source);
		} else {
			//$file_path =  "../" . $source;
			$file_path = $settings['cms']['thumb_path'] . basename($source);	// This works, despite the file_exists function returning false (modal windows)
		}
	} else {
		$file_path = '';
	}
	
	//////////////////////////////////////////////////////////////////////////////
	
	if($extension == 'mp3' || $extension == 'ogg' || $extension == 'wav'){
		// Check for HTML5
		if(true){
			?>
			<div class="audio_player">
				<audio class="audio_file" preload="auto" name="audio_file">
					<!-- <source src="file.ogg" /> -->
					<source src="../<?PHP echo $settings['cms']['media_path'] . $source; ?>" />
				</audio>
				<span class="audio_play_pause audio_play">
					<span></span>
				</span>
				<span class="audio_time">00:00</span>
			</div>
			<?PHP
		} else {
			?>
			<div class="doc_thumb">
				Audio
			</div>
			<?PHP
		}
	} elseif($extension == 'jpg' || $extension == 'gif' || $extension == 'png') {
	
		if($height > $width){
			$thumb_height = $size;
			$thumb_width = round(($size / $height) * $width);
		} else {
			$thumb_width = $size;
			$thumb_height = round(($size / $width) * $height);
		}
		// ?fs= echo $file_size;
		// Make true to lazy load, alternatively you can try this method: lazy-src="" class="lazyload"
		if(true){
			?>
			<div class="image_thumb viewport_image" src="<?PHP echo $file_path; ?>?<?PHP echo $file_size;?>" width="<?PHP echo $thumb_width; ?>" height="<?PHP echo $thumb_height; ?>">&nbsp;</div>
			<?PHP
		} else {
			?>
			<img src="<?PHP echo $file_path; ?>?<?PHP echo $file_size;?>" width="<?PHP echo $thumb_width; ?>" height="<?PHP echo $thumb_height; ?>">
			<?PHP
		}
	} elseif($extension == 'youtube' || $extension == 'vimeo') {
		?>
		<div class="video_thumb">
			<img src="<?PHP echo $settings['cms']['thumb_path']; ?><?PHP echo $extension; ?>_<?PHP echo $source; ?>.jpg" width="<?PHP echo $size;?>px" style="z-index:1;" />
			<span class="video_icon <?PHP echo ($extension == 'youtube')?'video_youtube':'video_vimeo'?>"></span>
		</div>
		<?PHP
	} else {
		?>
		<div class="doc_thumb">
			<?PHP echo strtoupper($extension); ?>
		</div>
		<?PHP
	}
}





































//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get available field formats for each datatype (first format in array should be that datatypes default)



function get_datatype_formats($datatype, $primary_only = false) {
	
	$return = array();
	
	$return['varchar'] 		= array('text_field', 'text_area', 'media', 'relational', 'options', 'tags', 'email', 'short_name', 'password', 'numeric', 'color', 'rating');
	$return['char'] 		= array('text_field', 'text_area', 'media', 'relational', 'options', 'tags', 'email', 'short_name', 'password', 'numeric', 'color', 'rating');
	
	$return['text'] 		= array('text_area', 'wysiwyg', 'table_view', 'text_field', 'media', 'relational', 'options', 'tags');
	$return['tinytext'] 	= array('text_area', 'wysiwyg', 'table_view', 'text_field', 'media', 'relational', 'options', 'tags');
	$return['mediumtext'] 	= array('text_area', 'wysiwyg', 'table_view', 'text_field', 'media', 'relational', 'options', 'tags');
	$return['longtext'] 	= array('text_area', 'wysiwyg', 'table_view', 'text_field', 'media', 'relational', 'options', 'tags');
	
	$return['blob'] 		= array('text_area', 'wysiwyg', 'table_view', 'text_field', 'media', 'relational', 'options', 'tags');
	$return['tinyblob'] 	= array('text_area', 'wysiwyg', 'table_view', 'text_field', 'media', 'relational', 'options', 'tags');
	$return['mediumblob'] 	= array('text_area', 'wysiwyg', 'table_view', 'text_field', 'media', 'relational', 'options', 'tags');
	$return['longblob'] 	= array('text_area', 'wysiwyg', 'table_view', 'text_field', 'media', 'relational', 'options', 'tags');
	
	$return['int'] 			= array('numeric', 'dropdown_int', 'rating', 'histogram');
	$return['smallint'] 	= array('numeric', 'dropdown_int', 'rating', 'histogram');
	$return['mediumint'] 	= array('numeric', 'dropdown_int', 'rating', 'histogram');
	$return['bigint'] 		= array('numeric', 'dropdown_int', 'rating', 'histogram');
	$return['tinyint'] 		= array('checkbox', 'numeric', 'dropdown_int', 'rating', 'histogram');
	
	$return['float'] 		= array('numeric');
	$return['double'] 		= array('numeric');
	$return['decimal'] 		= array('numeric');
	
	$return['enum'] 		= array('text_field');
	$return['set'] 			= array('text_field');
	$return['bool'] 		= array('text_field');
	$return['binary'] 		= array('text_field');
	$return['varbinary'] 	= array('text_field');
	
	$return['date'] 		= array('date_chooser');
	$return['time'] 		= array('dropdowns', 'time_field');
	$return['datetime'] 	= array('datetime_fields');
	$return['year'] 		= array('dropdown', 'year_field');
	$return['timestamp'] 	= array('text_field');
	
	return ($primary_only)? $return[$datatype][0] : $return[$datatype];
}



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get field value (format = text, value)


function get_field_value($table, $field, $value, $format = 'text') {
	
	global $dbh;
	global $settings;
	
	$t_f = $table.','.$field;
	$field_format = ($settings['field_format'][$t_f])? $settings['field_format'][$t_f] : 'text_field';
	
	//echo '|||'.$table.','.$field.','.$value.' = '.$field_format.'|||';
	
	// Format the string appropriately
	if($field_format == 'checkbox'){
		if($format == 'code'){
			$value = ($value == '1')? '<input type="checkbox" checked="checked" disabled="disabled">': '<input type="checkbox" disabled="disabled">';
		} else {
			$value = ($value == '1')? '&#10003;': '&#10007;';
		}
	} elseif($field_format == 'password' || $field_format == 'password_confirm'){
		$value = str_repeat("*", strlen($value));
	} else {
		// $value
	}
	
	return $value;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Generate the fields for the edit page


function generate_fields($data) {
	
	global $dbh;
	global $settings;
	
	$item = $data['rows'][$data['item_id']];
	foreach($data['info'] as $key => $field){
		
		$value = $item[$key];
		$t_f = $data['name'].','.$key;
		$note = ($settings['field_note'][$t_f])? ' <span class="note">' . $settings['field_note'][$t_f] . '</span>' : '';
		$required_mark = ($settings['field_required'][$t_f] == 'true')? '<span class="loud">*</span>' : '';
		$required = ($settings['field_required'][$t_f] == 'true')? 'required' : '';
		$field_format = ($settings['field_format'][$t_f])? $settings['field_format'][$t_f] : get_datatype_formats($field['type_lengthless'], $primary_only = true); // 'text_field'
		$is_relational = ($settings['field_format'][$t_f] == 'relational')? true : false;
		$field_option = $settings['field_option'][$t_f];
		
		// If field is hidden, add a hidden input and skip
		if($settings['field_hidden'][$t_f] == 'true'){
			?>
			<input type="hidden" name="<?PHP echo $key;?>" value="<?PHP echo esc_attr($value);?>">
			<?PHP
			continue;
		}
		
		//////////////////////////////////////////////////////////////////////////////
		?>
		<div class="field">
			<?PHP
			//////////////////////////////////////////////////////////////////////////////s
			// Check if field is relational
			
			if($is_relational){
			
				// Get options
				// REMINDER: MUST CHECK THE SQL STATEMENT TO ENSURE THERE IS NOTHING MALICIOUS!
				if($sth = $dbh->query($field_option['relational']['sql'])){
					$option_fields = explode(",", $field_option['relational']["option"]);
					
					if($field_option['relational']["style"] == 'fancy'){
						unset($row_options);
						while($row_array = $sth->fetch()){
							$row_options[$row_array[$field_option['relational']["value"]]] = $row_array;
						}
						?>
						<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
						<table id="fancy_<?PHP echo $key;?>" cellpadding="0" cellspacing="0" border="0" class="simple_sortable">
							<tbody class="check_no_rows">
								<tr class="item no_rows"><td colspan="3">No items</td></tr>
								
								<?PHP
								$value_array = array_filter(explode(',',$value));
								foreach($value_array as $ordered_value){
									
									// Should save only ID for fancy... so we can edit items with a unique key
									$id = str_replace('"', "&#34;", $ordered_value);
									
									// Make visible option with multiple fields  (field1,field2)
									unset($option_array);
									foreach($option_fields as $temp){
										$option_array[] = get_field_value($field_option['relational']["add_from_table"], $temp, $row_options[$ordered_value][$temp], 'code');
									}
			
									// Only show found
									$found = ($value == $ordered_value)? true : strpos($value, ",".$ordered_value.",");
									if($found !== false){
										?>
										<tr class="item" replace_with="<?PHP echo $id;?>"> 
											<td class="order handle"><img src="media/site/icons/ui-splitter-horizontal.png" width="16" height="16" /></td> 
											<td>
												<div class="wrap">
													<?PHP echo implode($field_option['relational']["option_glue"],$option_array);?>
													<input type="hidden" name="<?PHP echo $key;?>[]" value="<?PHP echo $id;?>">
													<a class="badge edit_fancy modal" href="edit.php?modal=<?PHP echo $key;?>&table=<?PHP echo $field_option['relational']["add_from_table"];?>&parent_table=<?PHP echo $_GET['table'];?>&item=<?PHP echo $id;?>" style="display:none;">Edit</a>
												</div>
											</td> 
											<td width="10%"><a class="ui-icon ui-icon-close right remove_fancy" href=""></a></td> 
										</tr> 
										<?PHP
									}
								}
								?>
																
							</tbody>
						</table> 
						
						<?PHP if(!$field_option['relational']["only_new"]){ ?>
							<span class="fancy_new"> 
								<select field="<?PHP echo $key;?>" table="<?PHP echo $field_option['relational']["add_from_table"];?>" parent_table="<?PHP echo $_GET['table'];?>"> 
									<option value="">Select One</option> 
									<?PHP
									foreach($row_options as $row_option){
										
										$option_value = $row_option[$field_option['relational']["value"]];
										
										// Make visible option with multiple fields  (field1,field2)
										$safe_value = str_replace('"', "&#34;", $option_value);
										unset($option_array);
										foreach($option_fields as $temp){
											$option_array[] = get_field_value($field_option['relational']["add_from_table"], $temp, $row_option[$temp], 'text');
										}
				
										// Only show non-used
										//$found = ($value == $option_value)? true : strpos($value, ",".$option_value.",");
										//if($found === false){
										?>
										<option value="<?PHP echo $safe_value;?>"><?PHP echo implode($field_option['relational']["option_glue"],$option_array);?></option>
										<?PHP
									}
									?> 
								</select> 
								
								<span> or </span>
							<?PHP } ?>
							
							<a class="modal button pill" href="edit.php?modal=<?PHP echo $key;?>&table=<?PHP echo $field_option['relational']["add_from_table"];?>&parent_table=<?PHP echo $_GET['table'];?>">Add New</a></span>
							
						<?PHP
					} elseif($field_option['relational']["style"] == 'checkboxes_radios'){
						//////////////////////////////////////////////////////////////////////////////
						// Checkboxes or Radio Buttons
						
						$multiple = ($field_option['relational']["multiple"])? 'checkbox':'radio';
						$array_code = ($field_option['relational']["multiple"])? '[]':'';
						?>
						<label class="primary" <?PHP echo ($required!='')?'class="required_multi" require="'.$key.$array_code.'"':'';?>><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
						<?PHP
						while($row_options = $sth->fetch()){
						
							$option_value = $row_options[$field_option['relational']["value"]];
							
							// Make visible option with multiple fields  (field1,field2)
							$safe_value = str_replace('"', "&#34;", $option_value);
							unset($option_array);
							foreach($option_fields as $temp){
								$option_array[] = $row_options[$temp];
							}
	
							if($field_option['relational']["multiple"]){
								$found 		= ($value == $option_value)? 1: strpos($value, ",".$option_value.",");
								$selected 	= ($found !== false)? 'checked="checked"':'';
							} else {
								$selected 	= ($value == $option_value)? 'checked="checked"':'';
							}
							?>
							<label class="multi_inputs"><input name="<?PHP echo $key.$array_code;?>" value="<?PHP echo $safe_value;?>" <?PHP echo $selected;?> type="<?PHP echo $multiple;?>"><?PHP echo implode($field_option['relational']["option_glue"],$option_array);?></label>
							<?PHP
						}
							
					} else {
						//////////////////////////////////////////////////////////////////////////////
						// Default dropdown or multi-select list
						
						$multiple 	= ($field_option['relational']["multiple"])? 'multiple="multiple"':'';
						$array_code = ($field_option['relational']["multiple"])? '[]':'';
						
						?>
						<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
						<select name="<?PHP echo $key.$array_code;?>" <?PHP echo $multiple;?> class="<?PHP echo $required;?>"> 
							<?PHP 
							
							if(!$field_option['relational']["multiple"]){ ?><option value="NULL">Select One</option><?PHP } 
							
							while($row_options = $sth->fetch()){
							
								$option_value = $row_options[$field_option['relational']["value"]];
								
								// Make visible option with multiple fields  (field1,field2)
								$safe_value = str_replace('"', "&#34;", $option_value);
								unset($option_array);
								foreach($option_fields as $temp){
									$option_array[] = $row_options[$temp];
								}
		
								if($field_option['relational']["multiple"]){
									$found 		= ($value == $option_value)? 1: strpos($value, ",".$option_value.",");
									$selected 	= ($found !== false)? 'selected="selected"':'';
								} else {
									$selected 	= ($value == $option_value)? 'selected="selected"':'';
								}
								?>
								<option value="<?PHP echo $safe_value;?>" <?PHP echo $selected;?> ><?PHP echo implode($field_option['relational']["option_glue"],$option_array);?></option>
								<?PHP
							}
							?>
						</select>
						<?PHP
					}
				} else{
					echo '<b class="warning">Error:</b> Update the options for this field on the <a class="warning" href="settings.php">settings page</a>.';
				}

			} elseif($field_format == 'media') { ?>
			
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<table id="media_<?PHP echo $key;?>" cellpadding="0" cellspacing="0" border="0" class="simple_sortable media_dropzone_target" parent_item="<?PHP echo $key;?>" extensions="<?PHP echo $field_option['media']["extensions"];?>" media_type="relational"> 
					<tbody class="check_no_rows">
						<tr class="item no_rows"><td colspan="4">No media</td></tr>
						
						<?PHP
						$media_array = explode(',', $value);
						$media_array = array_filter($media_array);
						
						// Prepare the query once and run it for each media item
						// Reminder: This can be moved FURTHER up to before foreach to prepare only ONCE
						$sth = $dbh->prepare("SELECT * FROM `directus_media` WHERE `active` = '1' AND `id` = :id ");
						$sth->bindParam(':id', $media_id);
							
						foreach($media_array as $media_id){
							$sth->execute();
							if($media = $sth->fetch()){
								?>
								<tr class="item" replace_with="media_<?PHP echo $media_id;?>"> 
									<td class="order handle"><img src="media/site/icons/ui-splitter-horizontal.png" width="16" height="16" /></td> 
									<td class="thumb"><?PHP generate_media_image($media['extension'], $media['source'], $media['height'], $media['width'], $media['file_size']);?></td> 
									<td>
										<div class="wrap">
											<?PHP echo $media['title'];?>
											<input type="hidden" name="<?PHP echo $key;?>[]" value="<?PHP echo $media_id;?>">
											<a class="badge edit_fancy modal" href="inc/media_modal.php?type=relational&replace=true&parent_item=<?PHP echo $key;?>&id=<?PHP echo $media['id'];?>" style="display:none;">Edit</a>
										</div>
									</td> 
									<td width="10%"><a tabindex="-1" class="ui-icon ui-icon-close right remove_media" href=""></a></td> 
								</tr>
								<?PHP
							}
						}
						?>
												
					</tbody>
				</table> 
				<span class="fancy_new">
					<a tabindex="-1" class="modal button pill" href="inc/media_modal.php?type=relational&parent_item=<?PHP echo $key;?><?PHP echo ($field_option['media']["new_only"])? '&new_only=true':'';?><?PHP echo ($field_option['media']["extensions"])?'&extensions='.$field_option['media']["extensions"]:'';?>">Add Media</a>
					<?PHP echo ($field_option['media']["extensions"])?' <span class="note">('.implode(' or ', array_filter(explode(',', str_replace(' ', '', strtolower($field_option['media']["extensions"]))))).')</span>':'';?>
				</span>
			
			<?PHP } elseif($field_format == 'options'){ ?>
				
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				
				<?PHP
				$array_code = ($field_option['options']["multiple"])? '[]':'';
				
				// Get multiple options
				if($field_option['options']["style"] != 'checkboxes_radios'){
					$select_code = 'selected="selected"';
					$multiple 	= ($field_option['options']["multiple"])? 'multiple="multiple"':'';
					?><select name="<?PHP echo $key.$array_code;?>" <?PHP echo $multiple;?> class="<?PHP echo $required;?>"> <?PHP 
				} else {
					$select_code = 'checked="checked"';
					$multiple = ($field_option['options']["multiple"])? 'checkbox':'radio';
				}
				
				
				if(!$field_option['options']["multiple"]){ ?><option value="NULL">Select One</option><?PHP } 
				
				$option_array = array_filter(explode(',', $field_option['options']["values"]));
				
				foreach($option_array as $option_value){
					
					// Clean up the value a bit
					$option_value = trim($option_value);
					
					// Make safe the attribute value
					$safe_value = str_replace('"', "&#34;", $option_value);
					
					
					
					if($field_option['options']["multiple"]){
						$found 		= ($value == $option_value)? 1: strpos($value, ",".$option_value.",");
						$selected 	= ($found !== false)? $select_code :'';
					} else {
						$selected 	= ($value == $option_value)? $select_code :'';
					}
					
					if($field_option['options']["style"] == 'checkboxes_radios'){
						?><label class="multi_inputs"><input name="<?PHP echo $key.$array_code;?>" value="<?PHP echo $safe_value;?>" <?PHP echo $selected;?> type="<?PHP echo $multiple;?>"><?PHP echo $option_value;?></label><?PHP
					} else {
						?><option value="<?PHP echo $safe_value;?>" <?PHP echo $selected;?> ><?PHP echo $option_value;?></option><?PHP
					}
				}
					
				if($field_option['options']["style"] != 'checkboxes_radios'){
					?></select><?PHP 
				}
			
			} elseif($field_format == 'tags'){
			
				$tags = explode(',',$value);
				$tags = array_filter($tags);
				?>
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<input id="input_<?PHP echo $key;?>" class="short_half tag_input <?PHP echo ($field_option['tags']["autocomplete"])?'tag_autocomplete':'';?>" type="text"> <input tabindex="-1" field="<?PHP echo $key;?>" class="tag_add button pill" type="button" value="Add"> 
				<input id="hidden_<?PHP echo $key;?>" type="hidden" name="<?PHP echo $key;?>" value="<?PHP echo esc_attr($value);?>" class="<?PHP echo $required;?>">
				<div id="tags_<?PHP echo $key;?>" class="tags_list clearfix">
					<?PHP
					foreach($tags as $tag_key => $tag_value){
						?>
						<span class="tag" tag="<?PHP echo $tag_value;?>"><?PHP echo $tag_value;?><a class="tag_remove" tabindex="-1" href="#">&times;</a></span> 
						<?PHP
					}
					?>
				</div>
				
			<?PHP } elseif($field_format == 'checkbox'){ ?>
				
				<label class="primary" for="<?PHP echo $key;?>">
				<input name="<?PHP echo $key;?>" type="checkbox" value="1" <?PHP echo ($value == 1)?'checked="checked"':'';?>>
				 <?PHP echo uc_convert($key) . $required_mark;?>
				</label> <?PHP echo $note;?>
			
			<?PHP } elseif($field['type_lengthless'] == 'date') {
			
				$value = ($value)? $value : date('Y-m-d');
				?>	
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<input name="<?PHP echo $key;?>" value="<?PHP echo ($value)?$value:date('Y-m-d');?>" maxlength="10" class="small short_10 text_center date datepicker <?PHP echo $required;?>" type="text" size="10"> 
			
			<?PHP } elseif($field['type_lengthless'] == 'time') {
			
				$value = ($value)? $value : date('h:i:s');
				?>	
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<input name="<?PHP echo $key;?>" value="<?PHP echo $value;?>" maxlength="8" class="small short_8 text_center date <?PHP echo $required;?>" type="text" size="10"> 
			
			<?PHP } elseif($field['type_lengthless'] == 'year') {
			
				$value = ($value)? $value : date('Y');
				?>	
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<input name="<?PHP echo $key;?>" value="<?PHP echo $value;?>" maxlength="4" class="small short_4 text_center year force_numeric <?PHP echo $required;?>" type="text" size="10"> 
			
			<?PHP } elseif($field['type_lengthless'] == 'datetime') {
			
				$value_time = ($value)? strtotime($value) : strtotime('now');
				$month = date('m',$value_time);
				?>
				
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<div class="datetime_fields">
					<select class="month"> 
						<option value="01" <?PHP echo ($month == '01')?'selected="selected"':'';?>>Jan</option> 
						<option value="02" <?PHP echo ($month == '02')?'selected="selected"':'';?>>Feb</option> 
						<option value="03" <?PHP echo ($month == '03')?'selected="selected"':'';?>>Mar</option> 
						<option value="04" <?PHP echo ($month == '04')?'selected="selected"':'';?>>Apr</option> 
						<option value="05" <?PHP echo ($month == '05')?'selected="selected"':'';?>>May</option> 
						<option value="06" <?PHP echo ($month == '06')?'selected="selected"':'';?>>Jun</option> 
						<option value="07" <?PHP echo ($month == '07')?'selected="selected"':'';?>>Jul</option> 
						<option value="08" <?PHP echo ($month == '08')?'selected="selected"':'';?>>Aug</option> 
						<option value="09" <?PHP echo ($month == '09')?'selected="selected"':'';?>>Sep</option> 
						<option value="10" <?PHP echo ($month == '10')?'selected="selected"':'';?>>Oct</option> 
						<option value="11" <?PHP echo ($month == '11')?'selected="selected"':'';?>>Nov</option> 
						<option value="12" <?PHP echo ($month == '12')?'selected="selected"':'';?>>Dec</option> 
					</select> 
					<input class="small short_1 text_center day force_numeric" type="text" size="2" maxlength="2" value="<?PHP echo date('j',$value_time);?>">, 
					<input class="small short_4 text_center year force_numeric" type="text" size="4" maxlength="4" value="<?PHP echo date('Y',$value_time);?>"> @ 
					<input class="small short_1 text_center hour force_numeric" type="text" size="2" maxlength="2" value="<?PHP echo date('H',$value_time);?>"> : 
					<input class="small short_1 text_center minute force_numeric" type="text" size="2" maxlength="2" value="<?PHP echo date('i',$value_time);?>"> : 
					<input class="small short_1 text_center second force_numeric" type="text" size="2" maxlength="2" value="<?PHP echo date('s',$value_time);?>"> 
					<input class="small short_6 text_center datetime <?PHP echo $required;?>" type="hidden" name="<?PHP echo $key;?>" value="<?PHP echo ($value)? $value : date('Y-m-j H:i:s',$value_time);?>">
				</div>
			
			<?PHP } elseif($field_format == 'text_area') { ?>
				
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<div class="textarea_format clearfix"> 
					<ul> 
						<li><a href="#" class="text_format_button" tabindex="-1" format="bold"><strong>Bold</strong></a></li> 
						<li><a href="#" class="text_format_button" tabindex="-1" format="italic"><em>Italic</em></a></li> 
						<li><a href="#" class="text_format_button" tabindex="-1" format="link">Link</a></li> 
						<li><a href="#" class="text_format_button" tabindex="-1" format="mail">Mail</a></li>
						<li><a href="#" class="text_format_button" tabindex="-1" format="image">Insert Image</a></li> 
						<li><a href="#" class="text_format_button" tabindex="-1" format="excerpt">Excerpt</a></li> 
						<li><a href="#" class="text_format_button" tabindex="-1" format="blockquote">Blockquote</a></li> 
					</ul>
				</div> 
				<textarea class="textarea_formatted media_dropzone_target <?PHP echo $required;?>" parent_item="text_area_<?PHP echo $key;?>" extensions="jpg,gif,png" media_type="inline" rows="<?PHP echo ($field_option['text_area']["height"])? $field_option['text_area']["height"] : '8';?>" id="text_area_<?PHP echo $key;?>" name="<?PHP echo $key;?>"><?PHP echo esc_attr(br2nl($value));?></textarea> 
			
			<?PHP } elseif($field_format == 'wysiwyg') { ?>
				
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<textarea class="textarea_formatted wysiwyg_advanced <?PHP echo $required;?>" parent_item="text_area_<?PHP echo $key;?>" extensions="jpg,gif,png" media_type="inline" rows="<?PHP echo ($field_option['wysiwyg']["height"])? $field_option['wysiwyg']["height"] : '8';?>" id="text_area_<?PHP echo $key;?>_<?PHP echo rand(0,999999); ?>" name="<?PHP echo $key;?>"><?PHP echo esc_attr($value);?></textarea> 
			
			<?PHP } elseif($field_format == 'table_view') { ?>
				
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br> 
				<textarea rows="4" id="text_area_<?PHP echo $key;?>" name="<?PHP echo $key;?>" class="<?PHP echo $required;?>"><?PHP echo $value;?></textarea> 
				<a tabindex="-1" class="edit_table_view button pill" csv-id="text_area_<?PHP echo $key;?>" href="inc/edit_table.php">Edit in table view</a>
				<span class="note">You can always paste a CSV file above to start with</span>
			
			<?PHP } elseif($field_format == 'short_name'){ ?>
			
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<input class="track_max_length short_name <?PHP echo $required;?>" short_name="<?PHP echo $field_option['short_name']["field"];?>" name="<?PHP echo $key;?>" type="text" value="<?PHP echo esc_attr($value);?>" maxlength="<?PHP echo $field['type_length'];?>">
				<span class="char_count"><?PHP echo $field['type_length'] - strlen($value);?></span> 
				
			<?PHP } elseif($field_format == 'numeric'){ ?>
			
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<input class="track_max_length force_numeric <?PHP echo $required;?>" name="<?PHP echo $key;?>" type="text" value="<?PHP echo esc_attr($value);?>" maxlength="<?PHP echo $field['type_length'];?>">
				<span class="char_count"><?PHP echo $field['type_length'] - strlen($value);?></span> 
				
			<?PHP } elseif($field_format == 'password'){ ?>
			
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<input class="track_max_length <?PHP echo $required;?> <?PHP echo ($field_option['password']["unmask"])? 'unmask':'';?>" name="<?PHP echo $key;?>" type="password" value="<?PHP echo esc_attr($value);?>" maxlength="<?PHP echo $field['type_length'];?>">
				<span class="char_count"><?PHP echo $field['type_length'] - strlen($value);?></span> 
				
			<?PHP } elseif($field_format == 'password_confirm'){ ?>
			
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<input class="track_max_length <?PHP echo $required;?>" name="<?PHP echo $key;?>" type="password" value="<?PHP echo esc_attr($value);?>" maxlength="<?PHP echo $field['type_length'];?>">
				<span class="char_count"><?PHP echo $field['type_length'] - strlen($value);?></span> 
				
			<?PHP } elseif($field_format == 'color'){ ?>
			
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				# <input class="small short_6 text_center color_field <?PHP echo $required;?>" name="<?PHP echo $key;?>" type="text" value="<?PHP echo ($value)? strtoupper(esc_attr($value)) : 'FFFFFF';?>" maxlength="6"> <span class="color_box" color_box="<?PHP echo $key;?>" style="background-color:#<?PHP echo ($value)?esc_attr($value):'FFFFFF';?>;"></span>
			
			<?PHP } elseif($field_format == 'histogram'){ 
				
				// Get highest value for this field across the table
				$histogram_table = get_rows_info($data['name']);
				$histogram_sql = ($histogram_table['active'] == true)? "AND `active` = '1' " : "";
				$sth = $dbh->query("SELECT max($key) as peak_amount FROM `".$data['name']."` WHERE id != '".$data['item_id']."' $histogram_sql");
				$peak_amount = ($peak = $sth->fetch())? ($peak["peak_amount"]) : 0;
			?>
			
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<input class="small short_6 force_numeric histogram <?PHP echo $required;?>" name="<?PHP echo $key;?>" type="text" value="<?PHP echo esc_attr($value);?>" maxlength="<?PHP echo $field['type_length'];?>"> <span class="histogram_bar" peak_amount="<?PHP echo $peak_amount;?>"><span this_amount="<?PHP echo esc_attr($value);?>"></span></span> <span title="<?PHP echo esc_attr($value).'/'.$peak_amount;?>" class="histogram_percent">0%</span>
				
			<?PHP } elseif($field_format == 'rating'){ ?>
			
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<div class="editable_rating">
					<div class="rating_system" style="width:<?PHP echo 16*$field_option['rating']["max"];?>px;">
						<div class="rating_bar" style="width:<?PHP echo 16*$value;?>px;">&nbsp;</div>
						<?PHP
						for($i=1;$i<=$field_option['rating']["max"];$i++) {
							?><span class="star" alt="<?PHP echo $i;?>" count="<?PHP echo $i;?>"></span><?PHP
						}
						?>
					</div>
					<input class="small short_2 text_center rating_input <?PHP echo $required;?>" name="<?PHP echo $key;?>" type="text" value="<?PHP echo esc_attr($value);?>" maxlength="<?PHP echo $field['type_length'];?>"> out of <?PHP echo $field_option['rating']["max"];?>
				</div>
				
			<?PHP } else { ?>
			
				<label class="primary" for="<?PHP echo $key;?>"><?PHP echo uc_convert($key) . $required_mark;?></label> <?PHP echo $note; ?><br>
				<input class="<?PHP echo ($field_format == 'email')?'validate_email short_half':'';?> <?PHP echo ($field['type_length'] > 0)? 'track_max_length': '';?> <?PHP echo $required;?>" name="<?PHP echo $key;?>" type="<?PHP echo ($field_format == 'email')?'email':'text';?>" value="<?PHP echo esc_attr($value);?>" <?PHP echo ($field['type_length'] > 0)? 'maxlength="'.$field['type_length'].'"': '';?>>
				<?PHP
				if($field['type_length'] > 0){
					?><span class="char_count"><?PHP echo $field['type_length'] - strlen($value);?></span> <?PHP
				}
				?>
			<?PHP } ?>
			
		</div>
		<?PHP
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
