<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2010 Morris Jobke <kabum@users.sourceforge.net>               #
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

ini_set('display_errors',1);
error_reporting(E_ALL|E_STRICT);

$CONFIG_PATH = '../config/';
$CONFIG_FILE = 'local.php';


# SETTINGS

$COMMANDS_PATH = 'commands/';
$BASE_PATH = '/var/www';

$writable = false;
$overwrite = false;
if(file_exists($CONFIG_PATH)) {
	if(is_writable($CONFIG_PATH))
		$writable = true;
}

if(file_exists($CONFIG_PATH.$CONFIG_FILE)) {
	//TODO read config
	$overwrite = true;
}

	
$commands = array();

$h = @opendir('../'.$COMMANDS_PATH);
if(is_resource($h)) {
	while( ($f = readdir($h)) !== false ) {
		if(preg_match('!^\.{1,2}$!', $f))
			continue;
		if(preg_match('!.*~$!', $f))
			continue;
		if(!is_dir('../'.$COMMANDS_PATH.$f)) {
			if(strlen($f) >= 4 and substr($f, -4) == '.php') {
				$commands[] = substr($f,0,-4);
			}
		}
	}
	closedir($h);
}
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="content-type" content="application/xhtml+xml;charset=utf-8" />
		<title>Installation SWFM - PHP Backend</title>		
		<script type="text/javascript" src="jquery.js"></script>
		<style type="text/css"><!--
			.notice {
				padding:5px;
				border:2px solid #009900;
				background-color:#CCFFCC;
				text-align:center;
				margin:20px 50px;
			}
			.error {
				padding:5px;
				border:2px solid #CC0000;
				background-color: #FFCCCC;
				text-align:center;
				margin:20px 50px;
			}
			body {
				text-align: center;
			}
			#settings {
				margin: 0px 50px;
				text-align: left;
			}
			#wrapper {
				width: 700px;
			}
		//--></style>
	</head>
<body>
	<center>
	<div id="wrapper">
	
	<h1>Installation SWFM - PHP Backend</h1>
	
	<div id="notify">
	<?php
		if($writable) {
			?>
			<div class="notice">Directory is writable</div>
			<?php
		} else {
			?>
			<div class="error">Directory is not writable</div>
			<?php		
		}	
		if($overwrite) {
			?>
			<div class="error">Config file exists</div>
			<?php
		} else {
			?>
			<div class="error">Config file doesn't exists</div>
			<?php		
		}	
	?>
	</div>
	
	<div id="settings">
		<form action="save.php" method="post">
			<p>
				<label for="basepath">basepath</label><br />
				<input name="basepath" type="text" size="50" value="<?php echo $BASE_PATH; ?>" />
			</p>
			<p>
				<label for="commandspath">commands_path</label><br />
				<input name="commandspath" type="text" size="50" value="<?php echo $COMMANDS_PATH; ?>" >
			</p>
			<p>
				<label for="commands">commands</label><br />
				<?php
				foreach($commands as $c){
					echo '<input name="commands[]" type="checkbox" value="'.$c.'" />'.$c.'<br />';
				}
				
				?>
			</p>
			<p>
				<label for="mimetype_detection_mode">mimetype_detection_mode</label><br />
				<select name="mimetype_detection_mode" size="1">
					<option value="internal">internal</option>
					<option value="cmd_file">cmd_file</option>
					<option value="file">file</option>
				</select>
			</p>
			<p>
				<label for="filesystem_type">filesystem_type</label><br />
				<select name="filesystem_type" size="1">
					<option value="local">local</option>
					<option value="afs">afs</option>
				</select>
			</p>
			<p>
				<label for="use_x_sendfile">use_x_sendfile</label><br />
				<input name="use_x_sendfile" type="checkbox" value="true" />
			</p>
			<input type="submit" value="Submit" />
		
		</form>
	</div>
	
	</div>
	
	</center>
</body>
</html>
