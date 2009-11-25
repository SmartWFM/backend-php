<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2009 Philipp Seidel <phibo@oss.dinotools.de>                  #
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

//emulate json_decode and json_encode functions for PHP < 5.2

if(!function_exists('json_decode')) {
	require_once('lib/JSON/JSON.php');

	function json_decode($string, $assoc = false) {
		if($assoc == true) {
			$services_json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		} else {
			$services_json = new Services_JSON();
		}
		return $services_json->decode($string);
	}
}

if(!function_exists('json_encode')) {
	require_once('lib/JSON/JSON.php');	
	function json_encode($string) {
		$services_json = new Services_JSON();
		return $services_json->encode($string);
	}
}

/**
 * The Response object.
 * Use it for all connector functions
 */

class SmartWFM_Exception extends Exception { }

class SmartWFM_Response {
	public $data = NULL;
	public $error_code = NULL;
	public $error_message = NULL;
	public $error_data = NULL;
	
	/**
	 * Generate the output.
	 */

	function generate($type = 'json') {
		if($type == 'json') {
			$d = array(
				'jsonrpc' => '2.0',
			);
			if($this->error_code != NULL) {
				$d['error'] = array(
						'code' => $this->error_code,
						'message' => $this->error_message
					);
				if($this->error_data != NULL) {
					$d['error']['data'] = $this->error_data;
				}	
			} else {
				$d['result'] = $this->data;
			}
			return json_encode($d);
		}
	}
}

/**
 * The Registry handles all the config stuff
 */

class SmartWFM_Registry {
	private static $values = array();

	/**
	 * Get a value from the Registry
	 */
	static function get($name, $default = NULL) {
		if(array_key_exists($name, self::$values)) {
			return self::$values[$name];
		} else {
			return $default;
		}
	}

	/**
	 * Set a value with the given name
	 */

	static function set($name, $value) {
		self::$values[$name] = $value;
	}

}

/**
 * The CommandManager handles all the available commands
 */

class SmartWFM_CommandManager {
	private static $commands = array();

	/**
	 * Register a new command with the given name
	 */
	public static function register($name, $class) {
		//print "register";
		self::$commands[$name] = $class;
	}


	/**
	 * Get a command.
	 */
	public static function get($name) {
		if(array_key_exists($name, self::$commands)) {
			return self::$commands[$name];
		} else {
			return NULL;
		}
	}

	/**
	 * Count the available commands
	 */
	public static function count() {
		return count(self::$commands);
	}
}

/**
 * The main class of the Connector
 */

class SmartWFM {
	function __construct() {
	
	}

	/**
	 * Initalize the SmartWFM Connector
	 * - load the config files
	 * - load the commands
	 */
	function init() {
		//TODO: change this
		include("config/local.php");

		$commands = SmartWFM_Registry::get('commands');
		foreach($commands as $command) {
			$filename = SmartWFM_Registry::get('commands_path').'/'.$command.'.php';
			FB::log($filename);
			if(file_exists($filename)) {
				require_once($filename);
			}
		}
	}

	/**
	 * Process the action
	 * - detect what command to call
	 * - handle errors
	 */
	function process() {
		if(array_key_exists('data', $_REQUEST)) {
			$data = json_decode(stripslashes($_REQUEST['data']), true);
			if(array_key_exists('method', $data)) {
				$command = SmartWFM_CommandManager::get($data['method']);
				$d = NULL;
				if($command != NULL) {
					$d = $data['params'];
				}
				try {
					$response = $command->process($d);
				} catch (SmartWFM_Excaption $e) {
					$response = new SmartWFM_Response();
					$response->error_code = -32602;
					$msg = $e->getMessage();
					if($msg == NULL) {
						$msg = 'Invalid method parameters.';
					}
					$response->error_message = $msg;
				}
				header("Content-Type: application/json");
				print $response->generate();
			}
		}
	}


}

class SmartWFM_Command {
	function __construct() {
	
	}

	function process($params) {
	}
}

?>
