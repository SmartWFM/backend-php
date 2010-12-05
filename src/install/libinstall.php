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
	protected $enabled = True;
	protected $title;
	protected $description;

	/**
	  *	method converts "boolean" strings to a boolean value
	  *	@return boolean
	  */
	protected function boolIt($v) {
		$bool = array('False', 'false', '0', 'off', 'Off');
		if(in_array($v, $bool))
			return False;
		else
			return (boolean) $v;
	}
	
	public function __construct($n, $t, $d, $title, $description, $p = NULL) {
		$this->name = $n;
		$this->type = $t;
		$this->defaultValue = ($t == 'boolean') ? $this->boolIt($d) : $d;
		$this->possibleValues = $p;
		if(method_exists($this, 'checkAvailablity'))
			$this->checkAvailablity();
		$this->title = $title;
		$this->description = $description;
	}
	
	/**
	  * getter for defaultValue
	  */
	public function getDefault() {
		return $this->defaultValue;
	}
	
	
	/**
	  * getter for errorCode and errorMessage
	  *	@return	array	'code' and 'message' as keys for values
	  */
	public function getError() {
		return array(
			'code' 		=> $this->errorCode,
			'message' 	=> $this->errorMessage
		);
	}
	
	/**
	  * getter for name
	  */
	public function getName() {
		return $this->name;
	}
	
	/**
	  * getter for title
	  */
	public function getTitle() {
		return $this->title;
	}
	
	/**
	  * getter for description
	  */
	public function getDescription() {
		return $this->description;
	}

	/**
	  * setter for value - automatically converts boolean values to boolean
	  */
	protected function setValue($v) {
		if($this->type == 'boolean')
			$this->value = $this->boolIt($v);
		else
			$this->value = $v;
	}
	
	/**
	  * getter for value
	  */
	public function getValue() {
		if(!$this->enabled)
			return $this->defaultValue;
			
		return empty($this->value) ? $this->defaultValue : $this->value;
	}
	
	/**
	  * checks if option has an error
	  *	@return	boolean
	  */
	public function hasError() {
		return ($this->errorCode == NULL) ? False : True;
	}
	
	/**
	  *	generates html form element
	  *	@return	string	corresponding html code
	  */
	public function buildFormElement() {
		$disabled = $this->enabled ? '' : ' disabled="disabled"';
		if($this->possibleValues == NULL) {
			switch($this->type){
				case 'boolean':
					$checked = $this->getValue() ? ' checked="checked"' : '';
					$element = '<input name="'.$this->getName().'" ';
					$element .= 'type="checkbox"'.$checked.$disabled.' />';
					break;
				case 'array':
				case 'string':
				default:
					$element = '<input name="'.$this->getName();
					$element .= '" type="text" size="50" value="';
					$element .= $this->getValue().'"'.$disabled.' />';
					break;
			}
		} else {
			$element = '<select name="'.$this->getName().'" size="1"';
			$element .= $disabled.'>';
			foreach($this->possibleValues as $k => $v) {
				$selected = '';
				if($this->getValue() == $v)
					$selected = ' selected="selected"';
				$element .= '<option value="'.$v.'"'.$selected.'>';
				$element .= $v.'</option>';
			}
			$element .= '</select>';
		}
		return $element;
	}
	
	/**
	  * @return	value in PHP-syntax
	  */
	public function getPHPValue() {
		switch($this->type){
			case 'string':
				$value = '\''.$this->getValue().'\'';
				break;
			case 'array':
				$value = 'array(';
				foreach($this->getValue() as $v){
					$value .= '\''.$v.'\', ';
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
	public function __construct() {
		parent::__construct(
			'basepath', 
			'string', 
			'/var/www',
			'Basepath',
			'root path of SWFM' 
		);
	}	
	
	public function check($v) {
		$this->setValue($v);
		
		if($this->value == '') {
			$this->errorCode = 1;
			$this->errorMessage = 'path-string is empty';
		} else {
			if(file_exists($this->value)) {
				if(is_dir($this->value))
					return True;
				else {
					$this->errorCode = 2;
					$this->errorMessage = 'path is file';				
				}
			} else {
				$this->errorCode = 3;
				$this->errorMessage = 'path doesn\'t exists';				
			}
		}
		return False;
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
	public function __construct() {
		parent::__construct(
			'setting_filename', 
			'string', 
			'/tmp/.smartwfm.ini',
			'setting filename',
			'filename for settings file'
		);
	}
	
	public function check($v) {
		$this->setValue($v);
		
		if($this->value == '') {
			$this->errorCode = 1;
			$this->errorMessage = 'path-string is empty';
		} else {
			# allow any string here, because this could be an user related path
			# e.g. with username as path
			return True;

			if(file_exists($this->value)) {
				if(is_dir($this->value)) {
					$this->errorCode = 2;
					$this->errorMessage = 'path is directory';	
				} else	
					return True;	
			} else {
				if(!file_exists(dirname($this->value))) {					
					$this->errorCode = 3;
					$this->errorMessage = 'path doesn\'t exists';	
				} else 
					return True;								
			}
		}
		return False;
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
	public function __construct() {
		parent::__construct(
			'mimetype_detection_mode', 
			'string', 
			'internal', 
			'MIMETYPE detection mode',
			'kind of MIMETYPE detection',
			array('internal', 'cmd_file', 'file')
		);
	}
	
	public function check($v) {
		$this->setValue($v);
		
		if(in_array($this->value, $this->possibleValues))
			return True;
		else {
			$this->errorCode = 1;
			$this->errorMessage = 'value isn\'t correct';
			return False;
		}
	}
};

/**
  *	@author Morris Jobke
  *	@since	0.4
  *
  *	class to handle 'use_x_sendfile' option
  */
class UseXSendfileOption extends BaseOption {
	public function __construct() {
		parent::__construct(
			'use_x_sendfile',
			'boolean',
			'False',
			'use x-sendfile',
			'enables x-sendfile mode'
		);
	}
	
	public function check($v) {
		if(!$this->enabled) {
			$this->value = $this->defaultValue;
			return True;
		}
		$bool = array('False', 'False', '0');
		$this->value = in_array($v, $bool) ? False : (boolean) $v;
		
		return True;
	}
	
	/**
	  * overloads parent method
	  */
	public function checkAvailablity() {
		// TODO
		// $this->enabled = False;
	}
};

/**
  *	@author Morris Jobke
  *	@since	0.4
  *
  *	class to handle 'commands_path' option
  *
  *	errorCodes:
  *		1 	path-string is empty
  *		2	path is file  
  *		3	path doesn't exists
  */
class CommandsPathOption extends BaseOption {
	public function __construct() {
		parent::__construct(
			'commands_path', 
			'string', 
			'commands/',
			'commands path',
			'path to commands directory'
		);
	}
	
	public function check($v) {
		$this->setValue($v);
		
		if($this->value == '') {
			$this->errorCode = 1;
			$this->errorMessage = 'path-string is empty';
		} else {
			if(file_exists('../'.$this->value)) {
				if(is_dir('../'.$this->value))
					return True;
				else {
					$this->errorCode = 2;
					$this->errorMessage = 'path is file';				
				}
			} else {
				$this->errorCode = 3;
				$this->errorMessage = 'path doesn\'t exists';				
			}
		}
		return False;
	}
};

/**
  *	@author Morris Jobke
  *	@since	0.4
  *
  *	class to handle 'commands' option
  *
  *	errorCodes:
  *		1	couldn't read commands_path dir
  *		2	incorrect command name or commands_path is incorrect
  */
class CommandsOption extends BaseOption {
	public function __construct() {
		parent::__construct(
			'commands', 
			'array', 
			array(),
			'enabled commands',
			'commands ...'
		);
	}
	
	public function check($v) {
		$commands = $this->getCommands();
		if($commands == False) {			
			$this->errorCode = 1;
			$this->errorMessage = 'couldn\'t read commands_path dir';
			return False;
		}
		$checkedValues = array();
		foreach($v as $command) {
			if(in_array($command, $commands))
				$checkedValues[] = $command;
			else {
				$this->errorCode = 2;
				$this->errorMessage = 'incorrect command name or '.
					'commands_path is incorrect';
				return False;
			}
		}
		$this->setValue($checkedValues);
		return True;
	}
	
	/**
	  *	reads commands_path dir and extracts importable files
	  *	@return	array	list of filenames
	  */
	public function getCommands() {
		global $c;
		$path = '../'.$c->getValue('commands_path');
		if(!file_exists($path) || !is_dir($path))
			return False;
		else {
			$h = @opendir($path);
			$commands = array();
			if(is_resource($h)) {
				while( ($f = readdir($h)) !== false ) {
					if(preg_match('!^\.{1,2}$!', $f))
						continue;
					if(preg_match('!.*~$!', $f))
						continue;
					if(!is_dir($path.$f)) {
						if(strlen($f) >= 4 and substr($f, -4) == '.php') {
							$commands[] = substr($f,0,-4);
						}
					}
				}
				closedir($h);
			}
			return $commands;
		}
	}
	
	/**
	  *	overloads parent method
	  *	generates html form element
	  *	@return	string	corresponding html code
	  */
	public function buildFormElement() {
		$commands = $this->getCommands();
		$element = '';
		if($commands) {
			$disabled = $this->enabled ? '' : ' disabled="disabled"';
			foreach($commands as $c) {
				$checked = '';
				if($this->value and in_array($c, $this->value))
					$checked = ' checked="checked"';
				$element .= '<input name="'.$this->getName().'[]" ';
				$element .= 'type="checkbox" value="'.$c.'"'.$disabled;
				$element .= $checked.' />'.$c.'<br />';
			}
		}
		return $element;
	}
};

/**
  *	@author Morris Jobke
  *	@since	0.4
  *
  *	class to handle 'filesystem_type' option
  *
  *	errorCodes:
  *		1	value isn't correct
  */
class FilesystemTypeOption extends BaseOption {
	public function __construct() {
		parent::__construct(
			'filesystem_type', 
			'string', 
			'local', 
			'filesystem type',
			'kind of filesystem',
			array('local', 'afs')
		);
	}
	
	public function check($v) {
		$this->setValue($v);
		
		if(in_array($this->value, $this->possibleValues))
			return True;
		else {
			$this->errorCode = 1;
			$this->errorMessage = 'value isn\'t correct';
			return False;
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
	protected $save = False;
	protected $paths = array(
		'root'	=>	'../config/',
		'file'	=>	'local.php'
	);
	
	/**
	  *	adds option to config
	  * @params	o	object inheriting from BaseOption class
	  */
	public function addOption($o) {
		$this->options[$o->getName()] = $o;
	}
	
	/**
	  *	generates PHP config file
	  *	@return	file content
	  */
	public function generate() {
		$e = $this->errors;
		if($this->errors != array() and 
			!(
				count($this->errors != 1) and 
				array_key_exists('save', $this->errors)
			)
		)
			return array('error' => True, 'result' => $this->errors);
		$output = '';
		$output .= "<?php\n"; 
		foreach($this->options as $k => $o) {	
			if(!$o->hasError()) {		
				$output .= 'SmartWFM_Registry::set(\''.$o->getName();
				$output .= '\', '.$o->getPHPValue().");\n";
			}
		}
		$output .= '?>';
		return array('error' => False, 'result' => $output);
	}
	
	/**
	  * parses input array
	  * @params	input	associative array (option name - value)
	  */
	public function parse($input) {
		foreach($input as $k => $v) {
			if(array_key_exists($k, $this->options)) {
				if(!$this->options[$k]->check($v)) {
					$this->errors[$k] = $this->options[$k]->getError();
				}
			} else {
				if(!array_key_exists('general', $this->errors))
					$this->errors['general'] = array('incorrectKey' => array());
				$this->errors['general']['incorrectKey'][] = $k;
			}				
		}
	}
	
	/**
	  *	retrieves value for a given option name
	  *	@params	k		name of option
	  *	@return	value of corresponding option
	  */
	public function getValue($k) {
		if(array_key_exists($k, $this->options))
			return $this->options[$k]->getValue();
		return False;
	}
	
	/**
	  *	builds full using given config
	  *	@return html with form
	  */
	public function buildHTML() {
		$html = '<div id="settings">';
		$html .= '<form id="settingsform" method="post">';
		foreach($this->options as $k => $o) {
			$image = 'correct.png';
			$error = '';
			if(array_key_exists($k, $this->errors)) {
				$image = 'false.png';
				$error = '<p class="error less-margin">';
				$error .= $this->errors[$k]['message'].' (';
				$error .= $this->errors[$k]['code'].')</p>';
			}
			$html .= '<p id="'.$k.'">';
			$html .= '<img id="'.$k.'-check" ';
			$html .= 'src="images/'.$image.'"/> <label for="'.$k.'">';
			$html .= '<span class="title">'.$o->getTitle();
			$html .= '</span> - '.$o->getDescription().'</label><br />';
			$html .= $error;
			$html .= $o->buildFormElement();			
			$html .= '</p>';
		}
		$html .= '<p class="center"><input type="submit" name="submit" ';
		$html .= 'value="check config" /> <input type="submit" ';
		$html .= 'name="submit" value="save config" /></p></form>';
		if($this->save) {
			if(array_key_exists('save', $this->errors)) {
				$html .= '<p class="error less-margin">';
				$html .= 'config file not written<br />';
				$html .= $this->errors['save']['message'].' (';
				$html .= $this->errors['save']['code'].')</p>';
				if($this->errors['save']['code'] != 1) {
					$html .= '<p class="notice">Create a file "';
					$a = $_SERVER['SCRIPT_FILENAME'];
					$html .= substr($a, 0, strlen($a) -
						strlen('install/index.php'));
					$html .= 'config/local.php" with following';
					$html .= ' content:</p>';	
					$html .= '<p class="code">';
					$o = $this->generate();
					$html .= nl2br(htmlentities($o['result']));
					$html .= '</p>';	
				
				}
			} else {
				$html .= '<p class="notice less-margin">Config file successful';
				$html .= ' written</p>';	
			}
		}
		$html .= '</div>';
		return $html;
	}
	
	/**
	  *	saves config to file
	  *	@return True = successful // False - unsuccessful
	  * 	creates error message:
	  *			1	errors occured
	  *			2	config file already exists
	  *			3	config file is dir
	  *			4	config dir isn't writable
	  *			5	config dir is file
	  *			6	config dir doesn't exists
	  *			7	error while writing file
	  */
	public function save() {
		$this->save = True;
		$r = $this->generate();
		if($r['error']) {
			$this->errors['save'] = array(
				'message' => 'errors occured - see above',
				'code' => 1
			);
			return False;
		}
		if(file_exists($this->paths['root'])) {
			if(is_dir($this->paths['root'])) {
				if(is_writable($this->paths['root'])) {
					if($this->fileExists()) {
						if(!is_dir($this->paths['root'].$this->paths['file'])) {
							$this->errors['save'] = array(
								'message' => 'config file already exists',
								'code' => 2
							);
						} else {
							$this->errors['save'] = array(
								'message' => 'config file is dir',
								'code' => 3
							);
						}
					} else {
						$f = fopen(
							$this->paths['root'].$this->paths['file'], 
							'w'
						);
						$o = $this->generate();
						if(!fwrite($f, $o['result'])) {
							$this->errors['save'] = array(
								'message' => 'error while writing file',
								'code' => 7
							);
							fclose($f);
							return False;
						}
						fclose($f);
						return True;
					}
				} else {
					$this->errors['save'] = array(
						'message' => 'config dir isn\'t writable',
						'code' => 4
					);
				}
			} else {
				$this->errors['save'] = array(
					'message' => 'config dir is file',
					'code' => 5
				);
			}
		} else {
			// 		
			$this->errors['save'] = array(
				'message' => 'config dir doesn\'t exists',
				'code' => 6
			);
		}
		return False;			
	}
	
	/**
	  * checks if config file already exists
	  * @return	boolean
	  */
	public function fileExists() {
		return(file_exists($this->paths['root'].$this->paths['file']));
	}
};

?>
