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
  * @since	0.4
  *
  *	This is an abstract class for other options saved by this script.
  */  
abstract class BaseOption {
	protected $defaultValue;
	protected $value;
	protected $name;	
	protected $type;	
	protected $errorCode = 0;	
	protected $errorMessage = '';	
	
	public function __construct($n, $t, $d) {
		$this->name = $n;
		$this->type = $t;
		$this->defaultValue = $d;
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
		echo $this->getValue();
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
  * @since	0.4
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


class Config {
	protected $options = array();
	
	public function addOption($o) {
		$this->options[] = $o;
	}
	
	public function generate() {
		$output = '';
		$output .= "<?php\n";
		foreach($this->options as $o) {			
			$output .= "SmartWFM_Registry::set('".$o->getName();
			$output .= "', ".$o->getPHPValue().");\n";
		}
		$output .= "?>\n";
		return $output;
	}
	
	public function parse($input) {}
};

$c = new Config();
$c->addOption( new BasePathOption('basepath', 'string', '/var/www') );

$c->parse('asd');

$c->generate();


?>
