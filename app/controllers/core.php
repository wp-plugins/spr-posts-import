<?php
Namespace SPR_Migrate;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

// This Core class holds all the config information and provides a single point of access to models.
class Core {	
	private static $instance = null;	
	protected $config 		= array();
	public $msg 			= '';
	
	// Model objects
	public $sourcefile_model;
	public $site_model;
	public $main_model;
	
	// Source details
	public $source_authors 	= array();
	public $source_cats 	= array();
	public $source_tags 	= array();
	public $thumbnails 		= array();
	
	private function __construct(){		
		// Default setup
		$this->config['debug'] 			= 'n';
		$this->config['app_path'] 		= str_replace('/app/controllers', '', dirname(__FILE__)).'/';
		$this->config['app_uri']		= str_replace('/app/controllers', '', plugins_url('', __FILE__)).'/';
		$this->config['use_logfile'] 	= 'n';
		$this->config['title'] 			= 'SPR Posts Import';
		$this->config['slug'] 			= 'spr-posts-import.php';
		$this->config['prefix']			= 'spr-posts-import';
		$this->config['src'] 			= $this->config['app_path'].'src.xml'; 
		$this->config['limit'] 			= 1;
		$this->config['offset'] 		= 0;
		$this->config['cats'] 			= '';
		$this->config['upload_images'] 	= 'y';
		$this->config['insert_posts']	= 'y';
		$this->config['add_tags'] 		= 'y';
		$this->config['keep-going'] 	= '';
		$this->config['total-entries'] 	= '';
		$this->config['upload_dir'] 	= wp_upload_dir();
		$this->config['img_base'] 		= $this->config['upload_dir']['baseurl'].'/'; 
		$this->msg 						= '';
		
		$load_models = array('main_model', 'sourcefile_model', 'site_model');
		
		foreach($load_models as $row) {
			$path = $this->config['app_path'].'app/models/'.$row.'.php';
			require_once($path);
			
			$class = '\SPR_Migrate\Model\\'.str_replace(' ', '_', ucwords(str_replace('_',' ', $row)));
			
			$this->{$row} = new $class($this);
		}
	}
	
	public static function get_instance(){
		if(self::$instance === null) self::$instance = new self();

		return self::$instance;
	}

	public function get_config($opt = '', $val = ''){
		if($opt == '') return false;
		
		return $this->config[$opt];
	}

	public function get_config_array(){		
		return $this->config;
	}
	
	public function set_config($opt = '', $val = ''){
		if($opt == '') return false;
		
		$this->config[$opt] = $val;
	}
	
	public function set_source_authors($authors = array()){
		$this->source_authors = $authors;
	}
	
	public function set_source_cats($cats = array()){
		$this->source_cats = $cats;
	}
	
	public function set_source_tags($tags = array()){
		$this->source_tags = $tags;
	}

} // end class
