<?php

class SmartWFM_Excaption_Params extends SmartWFM_Exception { }

class SmartWFM_Param {
	public $type = Null;
	public $items = array();
	function __construct($type, $items = NULL) {
		$this->type = $type;
		$this->items = $items;
	}
	function validate($params) {
		switch($this->type) {
			case 'array':
				if(is_array($params)) {
					for($i = 0; $i <count($params); $i++) {
						$this->items->validate($params[$i]);
					}
				} else {
					throw new SmartWFM_Excaption_Params();
				}
				break;	
			case 'integer':
				if(!is_integer($params)) {
					throw new SmartWFM_Excaption_Params();
				}
				break;
			case 'object':
				if(is_object($params)) {
					$keys = get_object_vars($params);
					foreach($this->items as $key => $value) {
						if(!array_key_exists($key, $keys)) {
							throw new SmartWFM_Excaption_Params();
						} else {
							$value->validate($params->$key);
						}
					}
				} else {
					throw new SmartWFM_Excaption_Params();
				}
				break;
			case 'string':
				if(!is_string($params)) {
					throw new SmartWFM_Excaption_Params();
				}
				break;
			default:
				throw new SmartWFM_Excaption_Params();

		}
	}
}
/*
$j = json_decode('{"abc": [1,2,3,a]}');
print_r($j);
print_r(get_object_vars($j));
$a = new SmartWFM_Param(
	$type = 'object',
	$items = array(
		'abc' => new SmartWFM_Param(
			$type = 'array',
			$items = new SmartWFM_Param(
				$type = 'integer'
			)
		)
	)
);
try {
	//$a->validate(array('test' => 'foo'));
	$a->validate($j);
} catch (SmartWFM_Excaption_Params $e) {
	print "error\n";
}
 */
?>
