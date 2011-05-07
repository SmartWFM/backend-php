<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C)      2011 Morris Jobke <kabum@users.sourceforge.net>          #@needle@
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

class FeedbackActions_Send extends SmartWFM_Command {
	function process($params) {
		// check params
		$paramTest = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'subject' => new SmartWFM_Param('string'),
				'text' => new SmartWFM_Param('string')
			)
		);
		
		$params = $paramTest->validate($params);

		$user = 'mjob'/*$_SERVER['PHP_AUTH_USER']*/;

		// HEADER
		$header = 	'MIME-Version: 1.0' . "\r\n";
		$header = 	'Content-type: text/plain; charset=UTF-8' . "\r\n";
		$header = 	'From: '.$user.'@hrz.tu-chemnitz.de';
				
		if(!mail(
				'mjob@hrz.tu-chemnitz.de'/* . ', webmaster@tu-chemnitz.de'*/,
				'[SWFM-Feedback] ' . $params['subject'],
				nl2br($params['text']),
				'From: '.$user.'@hrz.tu-chemnitz.de'
			))
			throw new SmartWFM_Exception('Feedback couldn\'t be send.', -1);

		$response = new SmartWFM_Response();
		$response->data = true;
		return $response;
	}
}

SmartWFM_CommandManager::register('feedback.send', new FeedbackActions_Send());
