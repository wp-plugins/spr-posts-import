<?php
Namespace SPR_Migrate\Model;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

// This model pulls the data from the WP site, which we will compare to the source file.
class Site_Model {
	
	/* Get this Site's authors, Categories, etc */

	public function get_authors(){
		$ret = array();
		
		$args = array('blog_id' => get_current_blog_id());
		$query = get_users($args);
		
		foreach($query as $row) {			
			/* Again, if we  don't strtolower() Wordpress is really screwing us
			 * on consistency and looping through this array */
			 
			$ret[strtolower($row->data->user_login)] = array(
				'user_login' => $row->data->user_login,
				'id' => $row->ID,
				'display_name' => $row->display_name
			);
		}
		
		return $ret;
	}

	public function get_categories(){
		$ret = array();
		
		$args = array('taxonomy' => 'category', 'hide_empty' => false);
		$cats = get_categories($args);
		
		foreach($cats as $row) $ret[$row->category_nicename] = $row;
				
		return $ret;
	}
	
	public function get_tags(){
		$ret = array();
		
		$args = array('hide_empty' => false);
		$tags = get_tags($args);
		
		foreach($tags as $row) $ret[$row->slug] = $row;
				
		return $ret;
	}
	
} // end class

