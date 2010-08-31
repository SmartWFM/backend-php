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
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="content-type" content="application/xhtml+xml;charset=utf-8" />
		<title>Installation SWFM - PHP Backend</title>		
		<script type="text/javascript" src="jquery.js"></script>
		<link rel="stylesheet" media="all" href="style.css" />
		<script type="text/javascript">
		<!--		
		function addNotice(text) {
			$('#notify').append('<div class="notice">'+text+'</div>');
		}
		
		function addError(text) {
			$('#notify').append('<div class="error">'+text+'</div>');
		}
		
		function setCorrectFlag(id){
			$('#'+id).attr('src', 'images/correct.png');
		}
		
		function setFalseFlag(id){
			$('#'+id).attr('src', 'images/false.png');
		}
		
		function checkConfigDir() {
			$.ajax({
				url: 'check.php',
				data: {'check': 1},
				success: function(data) {
					if(data.error == false){
						if(data.result.writable)
							addNotice('Directory is writable');
						else
							addError('Directory is not writable');
						if(data.result.overwrite)
							addError('Config file exists');
						else
							addNotice('Config file doesn\'t exists');
						// load data
						$('input[name="basepath"]').val(data.result.config.base_path);
						$('input[name="commandspath"]').val(data.result.config.commands_path);
						checkBasePath();
						checkCommandsPath();
					}
				}
			});
		}
		
		function init() {
			checkConfigDir();
			$('#loading').hide();
		}
		
		function checkBasePath() {
			$.ajax({
				url: 'check.php',
				data: {'check': 2, 'path': $('input[name="basepath"]').val()},
				success: function(data) {
					if(data.error == false){
						if(data.result.correct)
							setCorrectFlag('basepath-check');
						else
							setFalseFlag('basepath-check');
						
					}
					else
						setFalseFlag('basepath-check');
				}
			});
		}
		
		function checkCommandsPath() {
			$.ajax({
				url: 'check.php',
				data: {'check': 3, 'path': $('input[name="commandspath"]').val()},
				success: function(data) {
					if(data.error == false){
						if(data.result.correct) {
							setCorrectFlag('commandspath-check');
							loadCommands();
						} else {
							setFalseFlag('commandspath-check');
							$('#commands').html('');						
						}
						
					}
					else {
						setFalseFlag('commandspath-check');
						$('#commands').html('');	
					}
				}
			});
		}
		
		function loadCommands() {
			$.ajax({
				url: 'check.php',
				data: {'check': 4, 'path': $('input[name="commandspath"]').val()},
				success: function(data) {
					if(data.error == false){
						// TODO
						$('#commands').html(
							'<label for=\"commands\">commands</label><br/>');
						for(i in data.result) {
							$('#commands').append('<input name="commands[]" type="checkbox" value="'+data.result[i]+'" />'+data.result[i]+'<br />');
						}				
					}
					else
						setFalseFlag('commandspath-check');
				}
			});
		}
		//-->
		</script>
	</head>
<body onload="init()">
	<center>
	<div id="wrapper">
	
	<h1>Installation SWFM - PHP Backend</h1>
	<img src="images/ajax-loader.gif" title="Loading..." id="loading"/>
	<div id="notify"></div>
	
	
	<div id="settings">
		<form action="save.php" method="post">
			<p id="basepath">
				<label for="basepath">basepath of SWFM installation - access only under this directory</label><br />
				<input name="basepath" type="text" size="50" onchange="checkBasePath()" />
				<img id="basepath-check" src="images/false.png"/>
			</p>
			<p>
				<label for="commandspath">path to commands directory</label><br />
				<input name="commandspath" type="text" size="50" onchange="checkCommandsPath()" />
				<img id="commandspath-check" src="images/false.png"/>
			</p>
			<p id="commands">
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
				<label for="filesystem_type">type of filesystem</label><br />
				<select name="filesystem_type" size="1">
					<option value="local">local</option>
					<option value="afs">afs</option>
				</select>
			</p>
			<!--<p>
				<label for="use_x_sendfile">use_x_sendfile</label><br />
				<input name="use_x_sendfile" type="checkbox" value="true" />
			</p>
			<input type="submit" value="Submit" />-->
		
		</form>
	</div>
	
	
	</div>
	</center>
</body>
</html>
