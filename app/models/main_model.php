<?php
Namespace SPR_Migrate\Model;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

class Main_Model extends Model{
	public $req_categories = array();
	

	
	/* Import it */
	
	public function import_posts(){
		// Set up our required cats for posts to be imported
		$this->req_categories = $this->setup_req_categories();  
	
		$this->core->sourcefile_model->open_sourcefile();
		$posts = $this->core->sourcefile_model->get_posts_from_source();		
		//$this->showme($posts, 'all posts '.__LINE__);
		
		if(empty($posts)) {
			$this->core->sourcefile_model->close_sourcefile();
			return __('No posts were found, 0', $this->core->get_config('prefix'));
		}
		
		// Put all the attachment images into an array so we can choose a thumbnail from them later
		$this->core->thumbnails = $this->core->sourcefile_model->get_thumbnails($posts);  
		//$this->showme($this->core->thumbnails, 'all thumbnails '.__LINE__);
			
		$count = $added = 0;
		foreach($posts as $post){
			$meta = $post->children('wp', true);
			if($meta->post_type != 'post') continue;
			
			// Some reasons we may not continue
			if($count >= $this->core->get_config('limit') + $this->core->get_config('offset')) break;			
	
			if($count < $this->core->get_config('offset') || $this->is_in_req_categories($post) === false) {
				$count++;
				continue; 
			}
			
			// Save the post incl author, tags, categories		
			$newid = $this->save_new_post($post);
		
			// Save thumbnail if app
			foreach($meta->postmeta as $row) {
				// THANKS A LOT WORDPRESS FOR CHANGING THIS META KEY 
				// (although honestly, it is a better system) 
				if($row->meta_key != 'post_thumbnail' && $row->meta_key != '_thumbnail_id') continue;
				
				$this->showme($row->meta_value->__toString(), 'saving thumbnail '.__LINE__);
				$this->save_thumbnail($newid, $row->meta_value->__toString());
			}
				
			// Insert Comments
			if(isset($meta->comment)) $this->save_comments($newid, $meta->comment);			
			
			// Save images if app. Will call save_img and be skipped if upload_images == 'n'
			$imgs = $this->get_images_from_postbody($post);
			$this->save_post_images($newid, $imgs);
			
			// Keep count
			$count++;
			$added++;
		} 
		
		$this->core->sourcefile_model->close_sourcefile();
		
		//$this->showme($this->tags, 'all tags, end'.__LINE__);
		return $added.' added of '.$count.' checked (offset '.$this->core->get_config('offset').')';		
	}


	
	
	
	/* Get post's Author, Categories, etc */
	
	private function post_get_author($post){
		if(!is_object($post)) die('Not a post.');
		
		$auth 	= $post->children('dc', true);
		$author	= apply_filters('pre_user_login', $auth->creator->__toString()); // You need those filters
	
		//$this->showme($author, 'post author '.__LINE__);
		//$this->showme($this->core->source_authors[$author], 'this post author '.__LINE__);
		//$this->showme($this->core->source_authors, 'all authors '.__LINE__);
		
		if(!isset($this->core->source_authors[$author])) $author = strtolower($author); // temp patch 
		if(!isset($this->core->source_authors[$author])) {
			// Let's just assign the first author so we never need the stop the script 
			$keys = array_keys($this->core->source_authors);
			$author = $keys[0];
		}
		
		// We won't need to do this
		if(!isset($this->core->source_authors[$author])) die('<br />Error: Author missing! '.$author); 
		
		//else die('found author '.$author);
		
		// Add user if not in system
		//if(!get_user_by('login',$this->authors[$author]['user_login'])) { // Let's not query the db on this one
		if(!isset($this->core->source_authors[$author]['id'])) {			
			$user_id = wp_insert_user($this->core->source_authors[$author]);
			
			$this->showme(array($author),'inserting or looking up author '.__LINE__);
			
			// This user is already in the system by another EMAIL
			if(is_wp_error($user_id) && isset($user_id->errors['existing_user_email'])) {
				$existing_user = get_user_by('email', $this->core->source_authors[$author]['user_email']);
				$user_id = $existing_user->ID;
			}
			
			// This user is already in the system by another USERNAME
			if(is_wp_error($user_id) && isset($user_id->errors['existing_user_login'])) {
				$existing_user = get_user_by('login', $this->core->source_authors[$author]['user_login']);
				$user_id = $existing_user->ID;
			}
			
			// Nope, just didn't work
			elseif(is_wp_error($user_id)) {
				$this->showme($user_id, 'wp error '.__LINE__);
				die('This author did not get their ID in the initial setup: '.$author);
			}
			
			$this->showme(array($user_id), 'user id '.__LINE__);
			
			$this->core->source_authors[$author]['id'] = $user_id;
		}
		
		return $this->core->source_authors[$author];
	}

	private function post_get_cats($post){
		$post_cats = array();
		if(!isset($post->category)) return $post_cats;
		
		//$this->showme($this->categories, 'all categories '.__LINE__);
		
		foreach($post->category as $row) {
			//$post_cats[] = $row->{'@attributes'}['nicename']; // not working for some reason
			$r = (array) $row;
			$r = $r['@attributes'];
			if($r['domain'] != 'category') continue;
			
			$this->showme('post category: '.$r['nicename'].' '.__LINE__);
			
			$key = $r['nicename'];
			
			// Add category if not in system
			if(!isset($this->core->source_cats[$key]['id'])) {
				$cat = $this->core->source_cats[$key];
				
				$arr = array(
					'cat_name' 				=> $cat['cat_name'],
					'category_description' 	=> $cat['category_description'],
					'category_nicename' 	=> $cat['category_nicename'],
					'category_parent' 		=> $cat['category_parent'],
					'taxonomy' 				=> $cat['taxonomy'],
					'slug' 					=> $cat['category_nicename']
				);
				
				$this->showme('', 'inserting category '.$arr['cat_name'].' ('.__LINE__.')');
				$cat_id = wp_insert_category($arr,true);
				
				if(is_object($cat_id) || is_wp_error($cat_id)){
					$this->showme($cat_id,'wp error obj '. __LINE__);
					$this->showme($cat, 'category record '.__LINE__);
					$this->showme($this->core->source_cats, 'all categories '.__LINE__);
					die('error inserting category '.$arr['cat_name']);
				}
				
				$this->core->source_cats[$key]['id'] = $cat_id;
				
			}
			
			$post_cats[$key] = $this->core->source_cats[$key];
		}
		
		return $post_cats;
	}
	
	private function post_get_tags($post){
		$post_tags = array();
		
		// Tags are categories! Finkel is Einhorn!
		if(!isset($post->category)) return $post_tags;
		
		foreach($post->category as $row) {
			$r = (array) $row;
			$r = $r['@attributes'];			
			if($r['domain'] != 'post_tag') continue;
			
			$key = $r['nicename'];
			// Add tag if not in system
			if(!isset($this->core->source_tags[$key]['id'])) {
				$this->showme($this->core->source_tags[$key], 'Adding tag to site '.__LINE__);
				
				$term = $this->core->source_tags[$key]['tag_name'];
				$arr = array('slug' => $this->core->source_tags[$key]['tag_slug']);
				
				//$this->showme($arr, 'tag to insert '.__LINE__);
				
				//~ $id = wp_insert_term($term, 'post_tag', $arr);
				//~ if($term == 'workshops' || is_object($id) || is_wp_error($id)){
					//~ var_dump($id);
					//~ die('error inserting tag '.$term);
				//~ }
				
				$id = 1; // lets try this
				 
				$this->core->source_tags[$key]['id'] = $id;
			}
			
			$post_tags[$key] = $this->core->source_tags[$key];
		}
		
		return $post_tags;
	}
	
	private function get_images_from_postbody($post){
		$cont = $post->children('http://purl.org/rss/1.0/modules/content/'); 
		$content = $cont->encoded->__toString(); // use SimpleXML toString() to catch CDATA
		
		return $this->get_images_from_str($content);		
	}
	
	
	
	
	/* Required Categories */
	
	public function setup_req_categories(){
		$ret = array();
		$cats = $this->core->get_config('cats');
		if(empty($cats)) return false;
		
		$arr = explode("\r\n", $cats);
		foreach($arr as $row) 
			// Save the lower case url_title style string into the category array
			$ret[] = trim(sanitize_title_with_dashes($row));

		return $ret;
	}
	
	private function is_in_req_categories($post){
		if(empty($this->req_categories)) return true;
		
		$use_post = false;
		
		$post_cats = array();
		foreach($post->category as $row) {
			//$post_cats[] = $row->{'@attributes'}['nicename']; // not working for some reason
			$r = (array) $row;
			$post_cats[] = $r['@attributes']['nicename'];
		}
		
		//$this->showme($post_cats, 'post categories '.__LINE__);
		//$this->showme($this->req_categories, 'required categories '.__LINE__);
		
		foreach($post_cats as $row) {
			if(in_array($row, $this->req_categories)) $use_post = true;
		}
		
		//$this->showme(($use_post === true ? 'Yes' : 'No'),'Post in req categories? '.__LINE__);
		
		return $use_post;
	}
	
	
	
	
	
	/* Save Posts and Image */
		
	private function save_new_post($post){
		$post_author	= $this->post_get_author($post);
		$post_cats 		= $this->post_get_cats($post);
		$post_tags 		= $this->post_get_tags($post);
	
		$cont = $post->children('http://purl.org/rss/1.0/modules/content/'); 
		$content = $cont->encoded->__toString(); // use SimpleXML toString() to catch CDATA
		
		$imgs = $this->get_images_from_postbody($post);	
		$body = $this->replace_urls($post->pubDate, $content, $imgs);
		
		$meta = $post->children('wp',true);
		//$this->showme($post, ' the post ot be saved '.__LINE__);
		
		$cats= $tags = array();
		foreach($post_cats as $row) $cats[] = $row['id'];
		foreach($post_tags as $row) $tags[] = $row['tag_name'];
		
		//$this->showme($post_author, 'post author '.__LINE__);
		//$this->showme($post_cats, 'categories '.__LINE__);
		//if(!empty($post_tags)) $this->showme($post_tags, 'tags '.__LINE__);
		if(!empty($tags)) $this->showme($tags, 'tags '.__LINE__);
		//$this->showme($cats, 'categories and tags '.__LINE__);
		
		$postdata = array(
			'post_name'      => sanitize_title($post->title),	
			'post_title'     => sanitize_text_field($post->title), 				
			'post_type'      => 'post', 
			'post_author'    => intval($post_author['id']),			 				
			'post_date'      => date('Y-m-d H:i:s', strtotime($post->pubDate)),
			'post_content'	 => $body,
			'post_category'	 => $cats,
			//'tag_input'		 => $tags, // wp will not add the tags as is_object_in_taxonomy returns false
									       // however it just calls wp_set_post_tags, so we'll do that below
			'post_status' 	 => sanitize_text_field($meta->status->__toString())
		);  
		
		//$this->showme($postdata,' post to insert '.__LINE__);
		//$this->showme(null, 'Adding post: '.$postdata['post_title'].'  ('.__LINE__.')');
		
		if($this->core->get_config('insert_posts') == 'y') {
			$newid = wp_insert_post($postdata);
			
			if($newid === false || is_wp_error($newid)) {
				$this->showme($newid, 'wp error obj '.__LINE__);
				die('Unable to insert post: '.$postdata['post_title']);
			}
			
			// Now set the post tags 
			// Not an extra hit to the db, wp_insert_post was going to call this function anyway 
			if($this->core->get_config('add_tags') == 'y') wp_set_post_tags($newid, $tags);
		}
		else $newid = 1;
		
		$this->showme(null, 'Insert complete: '.$newid.' - '.$postdata['post_title'].'  ('.__LINE__.')');
		
		return $newid;
	}
	
	private function save_post_images($newid, $imgs){
		$this->showme($imgs, 'imgs '.__LINE__);
		//$this->showme($newid, 'post newid '.__LINE__);
		
		if(empty($imgs)) return;
		
		foreach($imgs as $row) $this->save_img($row, $newid);
	}
	
	private function save_comments($newid, $comments){
		//$this->showme($comments, 'comments '.__LINE__);
		
		foreach($comments as $row){
			//$this->showme($row, 'comment object '.__LINE__);
			
			$data = array(
				'comment_post_ID' 		=> $newid,
				'comment_author' 		=> $row->comment_author->__toString(),
				'comment_author_email' 	=> $row->comment_author_email->__toString(),
				'comment_author_url' 	=> $row->comment_author_url->__toString(),
				'comment_content' 		=> $row->comment_content->__toString(),
				'comment_type' 			=> $row->comment_type->__toString(),
				'comment_parent' 		=> $row->comment_parent->__toString(),
				'user_id' 				=> $row->comment_user_id->__toString(),
				'comment_author_IP' 	=> $row->comment_author_IP->__toString(),
				'comment_date' 			=> $row->comment_date->__toString(),
				'comment_approved' 		=> $row->comment_approved->__toString(),
			);

			//$this->showme($data, 'inserting comment '.__LINE__);
			
			if($this->core->get_config('insert_posts') == 'y') $comm = wp_insert_comment($data);
			
			//$this->showme($comm, 'single comment new id'.__LINE__);
		}
	}
		
	private function replace_urls($post_date, $content, $imgs = array()){
		if(empty($imgs)) return $content;
		
		// Determine directory
		// Wordpress is going to use the post date, not today
		$arr = $this->core->get_config('upload_dir'); 
		
			
		$y = date('Y', strtotime($post_date));
		$m = date('m', strtotime($post_date));

		foreach($imgs as $img){
			$filename = substr($img, strrpos($img,'/')); // strip out domain, just the filename
			
			//$newpath = $arr['url'].$filename;
			$newpath = $arr['baseurl'].'/'.$y.'/'.$m.$filename;

			//$this->showme($newpath,' new path '.__LINE__);
			
			$content = str_replace($img, $newpath, $content);
		}
		
		return $content;
	}
	
	private function save_img($row, $post_id){
		if($this->core->get_config('upload_images') == 'n') return false; 
		
		// Allowed file types
		$allow = false;
		foreach(array('jpg','gif','png') as $type) if(strpos(strtolower($row), '.'.$type) !== false) $allow = true;
		if($allow === false) return;
		
		// Download file
		$file['name']		= basename($row);
		$file['tmp_name'] 	= download_url($row);

		// Storage error or bogus filename - unlink
		if(strlen($file['name']) > 200 || is_object($file['tmp_name']) || is_wp_error( $file['tmp_name'] )){
			return false;
			
			//@unlink($file['tmp']);
			//throw new exception('upload failed');
		}
		
		// do the validation and storage stuff
		$thumbid = media_handle_sideload($file, $post_id);
		
		if(is_wp_error($thumbid)) {
			@unlink($file['tmp']);
			var_dump($file, $thumbid);
			if($file['tmp_name']->errors == 'http_404') return '0'; // don't let it choke on a missing file
			else die('error trying to upload'); 
		}
		
		return $thumbid;
	}
	
	private function save_thumbnail($newid, $val){
		if(isset($this->core->thumbnails[$val])) $val = $this->core->thumbnails[$val];
		//$this->showme($val, 'thumbnail to save '.__LINE__);
		
		// Save thumbnail. save_img() will return false if upload_images == 'n' or file type is not jpg|png|gif
		$thumbid = $this->save_img($val, $newid);
		$this->showme($thumbid, 'thumbnail image uploaded '.__LINE__);
		
		if($thumbid === false) return;
		
		$feat = set_post_thumbnail($newid, $thumbid);
		$this->showme($feat, 'Featured image asset id '.__LINE__);
		
		if(is_wp_error($feat)) {
			var_dump($feat); // get the specific error message
			die('Thumbnail problem for post '.$newid.' - set_post_thumbnail() w/ '.$thumbid);
		}
	}
	
}

