			</div>
		</div>
	</div>

	<div class="container">
		<div id="footer">
			Powered by <a href="http://getdirectus.com">Directus (v<?PHP echo $settings['cms']['version']; ?>)</a>
		</div>
	</div>
	
</body>

</html>
<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Print out script times for speed optimization

// $optimize_time_check['After media foreach'] .= microtime(true);

$optimize_print = '';
foreach($optimize_time_check as $optimize_time_check_name => $optimize_time_check_moment){
	$optimize_print .= ($optimize_time_check_moment - $optimize_time_start) . " Seconds - " . $optimize_time_check_name . "\n";
}
echo "<!--\n".$optimize_print."-->";

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>