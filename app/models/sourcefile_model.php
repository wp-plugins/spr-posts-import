<?php
Namespace SPR_Migrate\Model;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

class Sourcefile_Model extends Model {
	private $reader;
	private $reade_temp;
	
	
		
	/* XML Reader Functions */

	public function open_sourcefile(){
		// Crack it open
		$this->reader = new \XMLReader();
		$this->reader->open($this->core->get_config('src'));	
	
		// The iterator object will read the data
		$it 				= new \XMLElementIterator($this->reader);
		$this->reader_temp	= $it->getSimpleXMLElement();
	
		// Add and set up all authors, categories, and tags from the source
		$src = (array) $this->reader_temp->channel->children('wp',true); // typecasting fixes again
		$this->parse_source($src);
	}
	
	public function close_sourcefile(){
		$this->reader->close();
	}

	public function get_posts_from_source(){		
		$ret = array();
		
		foreach($this->reader_temp->channel->item as $item) $ret[] = $item;
		
		return $ret;
	}



	/* Analyze */
	
	public function analyze_xml($cat_counts = false){		
		$ret = array();
		
		// Lets start counting stuff
		$this->open_sourcefile();
		$wp_data 	= (array) $this->reader_temp->channel->children('wp',true);
		$posts 		= $this->reader_temp->channel->item;
		
		// Calculate num authors and num images
		$ret['num_posts'] 	= 0; //number_format(count($posts));
		$ret['num_auths'] 	= count($wp_data['author']);
		$ret['num_cats'] 	= count($wp_data['category']);
		$ret['cat_counts'] 	= array();
		$ret['num_imgs'] 	= $ret['num_comments'] = 0;
		
		if($cat_counts === true) {
			foreach($wp_data['category'] as $cat) $ret['cat_counts'][$cat['category_nicename']] = 0;
		}
		
		foreach($posts as $post){		
			// Count image tags in content
			//$cont = $post->children('http://purl.org/rss/1.0/modules/content/'); 
			//$content = $cont->encoded->__toString(); // use SimpleXML toString() to catch CDATA
			//$imgs = substr_count($content, '<img');
			//$ret['num_imgs'] += $imgs;
			//$this->showme($imgs,'number of images in post'); 
			
			// Count Images and Posts by post_type
			$post_data = $post->children('wp',true);
			if($post_data->post_type == 'post') $ret['num_posts']++;
			elseif($post_data->post_type == 'attachment') $ret['num_imgs']++;
			
			// Update category counts
			if($cat_counts === true && isset($post->category)){			
				foreach($post->category as $row) {
					$r = (array) $row;
					$r = $r['@attributes'];
					
					if($r['domain'] == 'category') $ret['cat_counts'][$r['nicename']]++;
				}
			}
						
			// Comment count
			$comms = (isset($post_data->comment) ? count($post_data->comment) : 0); 
			if($comms > 0) $ret['num_comments'] += $comms; 		
		}
		
		$this->close_sourcefile();
		
		return $ret;
	}

	public function upload_new_src(){
		$td = $this->core->get_config('prefix');
		if(!isset($_FILES['src-file'])) return __('No file uploaded.', $td);
		
		// Upload process had errors
		if($_FILES['src-file']['error'] != 0) return __('Upload failed.', $td);
		
		// File is not XML
		if($this->valid_xml_file($_FILES['src-file']['tmp_name']) === false) 
			return __('File is not a valid XML file.', $td);
		
		// Upload file as src.xml
		$src = $this->core->get_config('app_path').'src.xml';
		if(file_exists($src)) unlink($src); // remove current
        move_uploaded_file($_FILES['src-file']['tmp_name'], $src);
        
        return __('File ready!', $td);
	}
	
	public function valid_xml_file($file = ''){
		if(empty($file)) return false;
		
		$temp = new \XMLReader();
		$temp->open($file);
		$temp->setParserProperty(\XMLReader::VALIDATE, true); // gotta do this
		
		if($temp->isValid() === false) return false;
	
		return true;
	}



	/* Glean all Authors, Categories, Tags, and Images from source  */
	
	public function parse_source($wp_data = array()){		
		$authors 	= $this->get_authors($wp_data['author']);	
		$cats 		= $this->get_categories($wp_data['category']);	
		$tags 		= $this->get_tags($wp_data['tag']);
	
		// Master list of all potential authors
		$this->core->set_source_authors($authors); 
		
		// Master list of all potential categories
		$this->core->set_source_cats($cats);
		
		// Master list of all potential tags
		$this->core->set_source_tags($tags); 
	}
	
	public function get_authors($auths = array()){		 
		$ret = array();		
		$site_auths = $this->core->site_model->get_authors();
		
		// Patch for single author passed
		if(is_object($auths)) $auths = array($auths);
		
		//$this->showme($auths, 'supplied src authors '.__LINE__);
		//$this->showme($site_auths, 'site authors '.__LINE__);
		
		if(empty($auths)) return $ret;
		
		foreach($auths as $row){
			//$this->showme($row,__LINE__);
			
			/* Wordpress is playing some dirty fking pool here with the user_login field...
			 * You can insert a user_login MyUser and it will transform it into myuser
			 * but apply_filters('pre_user_login',$str) does nothing.... for now, strtolower().
			 * 
			 * It needs to be exactly the same, in order to add existing users' ID in the the $authors array.
			 * 
			 * Will have to figure this out later. */
			 
			$data = array(
				'user_login'  	=> strtolower($row->author_login->__toString()),
				'user_email' 	=> $row->author_email->__toString(),
				'display_name' 	=> $row->author_display_name->__toString(),
				'first_name' 	=> $row->author_first_name->__toString(),
				'last_name' 	=> $row->author_last_name->__toString(),
				'role'			=> 'Contributor'
			);			
	
			$key = $data['user_login'];
			
			// Need to hang that ID on there somehow
			if(isset($site_auths[$key]['id'])) $data['id'] = $site_auths[$key]['id'];
			
			//$this->showme($data['user_login'].': '.$site_auths[$data['user_login']]['id'],__LINE__);	
			//$this->showme($data,__LINE__);
			
			// Add to a list and we will decide when importing if we are going to create this user
			$ret[$key] = $data;		
		}
	
		return $ret;
	}
	
	public function get_categories($cats){
		$ret = array();
		if(empty($cats)) return $ret;
		
		$site_cats = $this->core->site_model->get_categories();
		//$this->showme($site_cats,'site cats'.__LINE__); // array of objects
				
		foreach($cats as $row){
			$data = array(
				'cat_name' 				=> $row->cat_name->__toString(),
				'category_description' 	=> $row->category_description->__toString(),
				'category_nicename' 	=> $row->category_nicename->__toString(),
				'category_parent' 		=> $row->category_parent->__toString(),
				'taxonomy' 				=> 'category' 
			 );
			 
			 $key = $data['category_nicename'];
			 
			 if(isset($site_cats[$key]->cat_ID)) $data['id'] = $site_cats[$key]->cat_ID;
	
			//$this->showme($site_cats[$data['category_nicename']], 'current category '.__LINE__);
			//$this->showme($data, 'proposed category '.__LINE__);
			
			// Add to a list and we will decide when importing if we are going to create this category
			$ret[$key] = $data;
		}
		
		return $ret;
	}
	
	public function get_tags($tags = array()){
		$ret = array();
		if(empty($tags)) return $ret;
		
		$site_tags = $this->core->site_model->get_tags();
		//$this->showme($site_tags, 'site tags'.__LINE__); // array of objects
		//$this->showme($tags, 'tags '.__LINE__);
					
		foreach($tags as $row){
			$data = array(
				'tag_name' => $row->tag_name->__toString(),
				'tag_slug' => $row->tag_slug->__toString(),
			 );
			
			$key = $data['tag_slug'];
			
			// $this->showme($site_tags[$data['tag_slug']]);
			 if(isset($site_tags[$key]->term_id)) $data['id'] = $site_tags[$key]->term_id;
			
			//$this->showme($site_cats[$data['category_nicename']], 'current category '.__LINE__);
			//$this->showme($data, 'proposed category '.__LINE__);
			
			// Add to a list and we will decide when importing if we are going to create this category
			$ret[$key] = $data;
		}
		
		return $ret;
	}
	
	public function get_thumbnails($posts){	
		//$this->showme($posts, 'posts '.__LINE__);
			
		if(empty($posts)) return false;
		$ret = array();
		
		foreach($posts as $row){
			$meta = $row->children('wp', true);
			if($meta->post_type != 'attachment') continue;
			
			$key = $meta->post_id->__toString();
			if(isset($ret[$key])) continue;
			
			$url = $meta->attachment_url->__toString();
			
			// Allowed file types
			$allow = false;
			foreach(array('jpg','gif','png') as $type) if(strpos(strtolower($url), '.'.$type) !== false) $allow = true;
			if($allow === false) continue;
		
			$ret[$key] = $url;
		}
		
		return $ret;
	}

	public function src_file_exists(){
		if(file_exists($this->core->get_config['src'])) return true;
		
		return false;
	}

	
} // end class
