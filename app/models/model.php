<?php
Namespace SPR_Migrate\Model;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

class Model {
	protected $core;
	
	function __construct($core){
		// Lifeline to the core object
		$this->core = $core; // Do not call core::get_instance here, or else the world will implode
	}

	
	/* Things any model can do */
		
	public function showme($x, $m = ''){
		// Log
		if($this->core->get_config('use_logfile') == 'y')
			file_put_contents($this->core->get_config('app_path').'/spr.log', "\r\n".$x.' - '.$m, FILE_APPEND );
		
		if($this->core->get_config('debug') != 'y') return;
		
		echo $m.'<pre>';
		print_r($x);
		echo '</pre>';
	}
	
	public function sanitize($var){
		return $var;
		return trim(filter_var($var, FILTER_SANITIZE_STRING, 'FILTER_ENCODE_HIGH'));
	}
		
	public function get_images_from_str($content){
		$ret = array();
		
		preg_match_all('/(https?:\/\/\S+\.(?:jpg|png|gif))/', $content, $imgs);
			
		// Now strip any duplicates
		foreach($imgs[0] as $row) if(!in_array($row, $ret)) $ret[] = $row;
		
		return $ret;
	}
	
}

