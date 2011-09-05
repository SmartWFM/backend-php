<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C)      2011 Morris Jobke <kabum@users.sourceforge.net>          #
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

		// HEADER
		$header = 	'MIME-Version: 1.0' . "\r\n";
		$header .= 	'Content-type: text/plain; charset=UTF-8' . "\r\n";
		$header .= 	'From: ' . SmartWFM_Registry::get('feedback_sender', 'nobody@example.com');
				
		if(!mail(
				SmartWFM_Registry::get('feedback_receiver', 'nobody@example.com'),
				'[SWFM-Feedback] ' . $params['subject'],
				str_replace('\\n', "\n", $params['text']),
				$header
			))
			throw new SmartWFM_Exception('Feedback couldn\'t be send.', -1);

		$response = new SmartWFM_Response();
		$response->data = true;
		return $response;
	}
}

SmartWFM_CommandManager::register('feedback.send', new FeedbackActions_Send());
