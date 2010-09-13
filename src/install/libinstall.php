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

/**
  *	@author Morris Jobke
  *	@since	0.4
  *
  *	This is an abstract class for other options saved by this script.
  */  
abstract class BaseOption {
	protected $defaultValue;
	protected $value;
	protected $name;	
	protected $type;	
	protected $possibleValues;
	protected $errorCode = NULL;	
	protected $errorMessage = NULL;	
	
	public function __construct($n, $t, $d, $p = NULL) {
		$this->name = $n;
		$this->type = $t;
		$this->defaultValue = $d;
		$this->possibleValues = $p;
	}
	
	public function getDefault() {
		return $this->defaultValue;
	}
	
	public function getError() {
		return array(
			'code' 		=> $this->errorCode,
			'message' 	=> $this->errorMessage
		);
	}
		
	public function getName() {
		return $this->name;
	}
	
	protected function getValue() {
		return empty($this->value) ? $this->defaultValue : $this->value;
	}
	
	/**
	  * @return	value in PHP-syntax
	  */
	public function getPHPValue() {
		switch($this->type){
			case 'string':
				$value = "'".$this->getValue()."'";
				break;
			case 'array':
				$value = 'array(';
				foreach($this->getValue() as $v){
					$value .= "'".$v."', ";
				}
				$value .= ')';
				break;
			case 'boolean':
				$value = $this->getValue() ? 'True' : 'False';
				break;
			default:
				$value = $this->getValue();
				break;
		}
		return $value;
	}
	
	abstract public function check($v);
};

/**
  *	@author Morris Jobke
  *	@since	0.4
  *
  *	class to handle 'basepath' option
  *
  *	errorCodes:
  *		1 	path-string is empty
  *		2	path is file  
  *		3	path doesn't exists
  */ 
class BasePathOption extends BaseOption {
	public function check($v) {
		$this->value = $v;
		
		if($this->value == '') {
			$this->errorCode = 1;
			$this->errorMessage = 'path-string is empty';
		} else {
			if(file_exists($this->value)) {
				if(is_dir($this->value))
					return true;
				else {
					$this->errorCode = 2;
					$this->errorMessage = 'path is file';				
				}
			} else {
				$this->errorCode = 3;
				$this->errorMessage = 'path doesn\'t exists';				
			}
		}
		return false;
	}
};

/**
  *	@author Morris Jobke
  *	@since	0.4
  *
  *	class to handle 'setting_filename' option
  *
  *	errorCodes:
  *		1 	path-string is empty
  *		2	path is directory  
  *		3	path doesn't exists
  */ 
class SettingFilenameOption extends BaseOption {
	public function check($v) {
		$this->value = $v;
		
		if($this->value == '') {
			$this->errorCode = 1;
			$this->errorMessage = 'path-string is empty';
		} else {
			if(file_exists($this->value)) {
				if(is_dir($this->value)) {
					$this->errorCode = 2;
					$this->errorMessage = 'path is directory';	
				} else	
					return true;	
			} else {
				if(!file_exists(dirname($this->value))) {					
					$this->errorCode = 3;
					$this->errorMessage = 'path doesn\'t exists';	
				} else 
					return true;
								
			}
		}
		return false;
	}
};

/**
  *	@author Morris Jobke
  *	@since	0.4
  *
  *	class to handle 'mimetype_detection_mode' option
  *
  *	errorCodes:
  *		1	value isn't correct
  */ 
class MimetypeDetectionModeOption extends BaseOption {
	public function check($v) {
		$this->value = $v;
		
		if(in_array($this->value, $this->possibleValues))
			return true;
		else {
			$this->errorCode = 1;
			$this->errorMessage = 'value isn\'t correct';
			return false;
		}
	}
};


/**
  *	@author Morris Jobke
  * @since	0.4
  *
  *	class to handle whole config file generation process
  */ 
class Config {
	protected $options = array();
	protected $errors = array();
	
	/**
	  *	adds option to config
	  */
	public function addOption($o) {
		$this->options[$o->getName()] = $o;
	}
	
	/**
	  *	generates PHP config file
	  *	@return	file content
	  */
	public function generate() {
		$output = '';
		$output .= "< ?php\n";
		foreach($this->options as $o) {			
			$output .= "SmartWFM_Registry::set('".$o->getName();
			$output .= "', ".$o->getPHPValue().");\n";
		}
		$output .= "?>\n";
		return $output;
	}
	
	/**
	  * parses input array
	  * @params	input	associative array (option name - value)
	  */
	public function parse($input) {
		foreach($input as $k => $v) {
			if(array_key_exists($k, $this->options)) {
				if(!$this->options[$k]->check($v))
					$this->errors[$k] = $this->options[$k]->getError();
			} else {
				if(!array_key_exists('general', $this->errors))
					$this->errors['general'] = array('incorrectKey' => array());
				$this->errors['general']['incorrectKey'][] = $k;
			}				
		}
	}
};


$c = new Config();
$c->addOption( new BasePathOption(
	'basepath', 
	'string', 
	'/var/www'
) );
$c->addOption( new SettingFilenameOption(
	'setting_filename', 
	'string', 
	'/tmp/.smartwfm.ini'
) );
$c->addOption( new MimetypeDetectionModeOption(
	'mimetype_detection_mode', 
	'string', 
	'internal', 
	array('internal', 'cmd_file', 'file')
) );

// $a = $_GET;
$a = array(
	'basepath' => '/home/kabum',
	'setting_filename' => '/home/kabum/.smartwfm.ini',
	'mimetype_detection_mode' => 'file',
);
$c->parse($a);

// DEBUG
echo '<pre>'.$c->generate().'</pre>';
echo '<pre>'.print_r($c,1).'</pre>';
// DEBUG END


?>
