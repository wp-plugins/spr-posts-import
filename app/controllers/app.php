<?php
Namespace SPR_Migrate;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

// The ringleader. This class is what sets it all off and directs traffic. Singleton.
class App {	
	private static $instance;
	private $core;
	
	private function __construct(){ 
		// Lifeline to the core object
		$this->core = \SPR_Migrate\Core::get_instance();
			
		// Create settings page
		add_action( 'admin_menu', array($this, 'admin_pane') );
	}
	
	public static function get_instance(){
		if(self::$instance === null) self::$instance = new self();

		return self::$instance;
	}
	
	private function styles_scripts(){
		$pref = $this->core->get_config('prefix');
		
		// CSS
		wp_enqueue_style($pref.'-style', $this->core->get_config('app_uri').'css/style.css');
		wp_enqueue_style($pref.'-roboto-font', 'http://fonts.googleapis.com/css?family=Roboto:400italic,500italic');
		
		// JS
		wp_enqueue_script($pref.'-js', $this->core->get_config('app_uri').'js/scripts.js', array('jquery'));
	}	
			
	
	/* WP Admin Page */
	
	public function admin_pane() {	
		// Add a link to the settings page
		$t = $this->core->get_config('title');	
		
		add_plugins_page($t, $t, 'edit_posts', $this->core->get_config('slug'), array($this, 'admin_pane_render'));
	}

	public function admin_pane_render(){	
		$config = $this->core->get_config_array(); // easier than writing a bunch of core->get_config()'s
		$td = $config['prefix'];
		
		// Load composer classes, right now just XML Iterator
		require_once($config['app_path'].'vendor/autoload.php');

		// Add CSS and JS where appropriate
		$this->styles_scripts();
		
		// Set up the LIMIT, OFFSET, etc type options using POST values and $config
		$this->setup_options();
		
		// Nonce fail?
		if(!empty($_POST) && !wp_verify_nonce($_POST['_wpnonce'], 'spr-import-posts')) 
			$this->core->msg = __('WP Nonce Failed. Please reload the page and try again.', $td);
		
		// Do import	
		elseif(isset($_POST['task']) && $_POST['task'] == 'do-import') $this->core->msg = $this->do_import();
		
		// Upload new XML file
		elseif(isset($_POST['task']) && $_POST['task'] == 'new-source') {
			$this->core->msg .= $this->core->sourcefile_model->upload_new_src();
			//$this->core->msg .= $this->source_stats();
		}
	
		// Get stats
		$this->core->msg .= $this->source_stats();
		
		if($config['upload_images'] != 'y')	$this->core->msg .= '<br>- '.__('Image upload is disabled.', $td);
		if($config['insert_posts'] != 'y') 	$this->core->msg .= '<br>- '.__('Post insert disabled.', $td);
		if($config['add_tags'] != 'y') 		$this->core->msg .= '<br>- '.__('Tags disabled.', $td);
			
		$this->do_template('admin');
	}
	
	public function get_form_vars(){
		$config = $this->core->get_config_array(); // easier than writing a bunch of core->get_config()'s
		$ret = array();
		
		$keep = ($config['keep-going'] == 'y' ? ' checked' : '');
		$entries = (isset($_POST['offset']) ? intval($_POST['offset']) + intval($_POST['limit']) : 0 );

		$ret['keep'] 		= ($config['keep-going'] == 'y' ? ' checked' : '');
		$ret['entries'] 	= (isset($_POST['offset']) ? intval($_POST['offset']) + intval($_POST['limit']) : 0 );
		$ret['debug'] 		= ($config['debug'] == 'y' ? ' selected="selected"' : '');
		$ret['have_src'] 	= $this->core->sourcefile_model->src_file_exists();

		$ret['action']			= site_url().'/wp-admin/admin.php?page='.$config['slug'];
		if(isset($_GET['cat_counts'])) $ret['action'] .= '&cat_counts=off';
		
		$ret['slug']			= $config['slug'];
		$ret['app_path']		= $config['app_path'];
		$ret['msg']				= $this->core->msg;
		$ret['limit']			= $config['limit'];
		$ret['offset']			= $config['offset'];
		$ret['keep-going']		= $keep;
		$ret['entries-checked']	= $entries;
		$ret['cats']			= $config['cats'];
		$ret['total-entries']	= $config['total-entries'];
		$ret['title'] 			= $config['title'];
		$ret['categories'] 		= $this->category_breakdown();
			
		return $ret;
	}	
	
	

	
	/* Lets do it */
	
	
	public function do_import(){				
		$qty = $this->core->main_model->import_posts();
		
		return $qty. __(' entries were imported.', $this->core->get_config('prefix'));
	}
	
	protected function setup_options(){
		// Integer options
		foreach(array('limit','offset', 'total-entries') as $row)
			if(isset($_POST[$row]))	$this->core->set_config($row, intval($_POST[$row]));
		
		// String options
		foreach(array('debug','cats','sort','keep-going') as $row) {
			if(isset($_POST[$row]))	
				$this->core->set_config($row, $this->core->main_model->sanitize($_POST[$row])); 	 	
		}
	}
	
	public function source_stats(){
		$src = $this->core->get_config('src');
		if(empty($src) || !file_exists($src)) return '';
		
		$td 			= $this->core->get_config('prefix');
		$cat_counts 	= (isset($_GET['cat_counts']) ? false : true);
		$totals 		= $this->core->sourcefile_model->analyze_xml($cat_counts);
		
		$html = '<div id="source-stats">';
		$html .= '<p>'.__('Your source file has', $td).' <span class="num-posts">'.$totals['num_posts'].
				__(' posts', $td).'</span> '.__('to import with ',$td).
				'<span class="stats num-auths">'.$totals['num_auths'].__(' authors', $td).'</span>, '.
				'<span class="stats num-cats">'.$totals['num_cats'].__(' categories', $td).'</span>, '.
				'<span class="stats num-comments">'.$totals['num_comments'].__(' comments', $td).'</span>, '.
				__('and', $td).' <span class="stats num-comments">'.$totals['num_imgs'].__(' images', $td).'</span>.</p>';
		$html .= '</div><!--/stats-->';
		
		// Update total entries count
		$this->core->set_config('total-entries', $totals['num_posts']);
		
		// Save categories with post counts to Core
		$this->core->set_config('cat-counts', $totals['cat_counts']);

		return $html;
	}
		
	public function category_breakdown(){
		$cats = $this->core->get_config('cat-counts');
		if(empty($cats)) return '';
		
		$html = '<div class="category-breakdown">';
	
		foreach($cats as $key=>$val){
			if(empty($key)) continue;
			
			$html .= '<div class="single-cat">'.$key.' ('.$val.')</div>'; // Don't translate this, leave as it is
		}

		$html .= '</div>';
		
		return $html;
	}
	
	
	
	/* Helpers */
		
	public function showme($x,$m = ''){
		$this->core->main_model->showme($x,$m);
		
		if($this->core->get_config('debug') != 'y') return;
		
		echo $m.'<pre>';
		print_r($x);
		echo '</pre>';
	}
	
	public function do_template( $template ){
		$path = $this->core->get_config('app_path').'templates/'.$template.'.php';
		
		if(file_exists($path)) require_once($path);
		else die('Missing template '.$path);
	}

	public function text_domain(){
		return $this->core->get_config('prefix');
	}
}
