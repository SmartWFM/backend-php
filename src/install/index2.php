<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2010 Philipp Seidel <phibo@oss.dinotools.de>                  #
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
	</head>
<body>
	<h1>Instalation SWFM - PHP Backend</h1>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<label for="basepath">basepath</label>
		<input name="basepath" type="text" size="100" maxlength="100">
		<br />
		<label for="commands">basepath</label>
		<input name="commands" type="checkbox" value="a">a
		<input name="commands" type="checkbox" value="b">b
		<input name="commands" type="checkbox" value="c">c
		<br />
		<label for="commandspath">commands_path</label>
		<input name="commandspath" type="text" size="100" maxlength="100">
		<br />
		<label for="mimetype_detection_mode">mimetype_detection_mode</label>
		<select name="mimetype_detection_mode" size="1">
			<option value="internal">internal</option>
			<option value="cmd_file">cmd_file</option>
			<option value="file">file</option>
		</select>
		<br />
		<label for="filesystem_type">filesystem_type</label>
		<select name="filesystem_type" size="1">
			<option value="local">local</option>
			<option value="afs">afs</option>
		</select>
		<br />
		<label for="use_x_sendfile">use_x_sendfile</label>
		<input name="use_x_sendfile" type="checkbox" value="true">
		<br />
		
	</form>
</body>
</html>
