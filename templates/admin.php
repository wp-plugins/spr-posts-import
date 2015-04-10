<?php
if(!is_object($spr_migrate)) $spr_migrate = \SPR_Migrate\App::get_instance();   
$data	= $spr_migrate->get_form_vars(); 
$td 	= $spr_migrate->text_domain();
?>

<div id="spr-import-posts">
	<header class="page-title">
		<div class="icon"></div>
		<h1><?php echo __($data['title'], $td); ?></h1>
		<div class="clear"></div>
		<hr />
	</header>

	<div id="all-done">
		<h2><?php echo __('All Done!', $td);?></h2>
		<p class="message"><?php echo __('Your import process is finished, check your posts and media library and if everything looks good it would be best to deactivate this plugin until you need it next.', $td);?></p>
		
	</div><!--/all-done -->
	 
	<form class="clear leftcol" id="spr-settings" method="post" action="<?php echo $data['action']; ?>">
		<?php wp_nonce_field('spr-import-posts'); ?>
		<input type="hidden" name="task" value="do-import" />
		<input type="hidden" name="entries-checked" value="<?php echo $data['entries-checked'];?>" />
		<input type="hidden" name="total-entries" value="<?php echo $data['total-entries']; ?>" />
		
		<fieldset>
			<p><label><?php echo __('# of Posts to Import', $td); ?>:</label> 
				<input type="number" name="limit" value="<?php echo $data['limit']; ?>" /> <br />
				<em><?php echo __('Choose a lower number if the process times out.', $td); ?></em></p>
			
			<p><label><?php echo __('# of Posts to Skip', $td);?>:</label> 
				<input type="number" name="offset" value="<?php echo $data['offset'];?>" /></p>
			
			<p><?php echo __('Posts must be in one of these categories', $td); ?>: <em>
				<?php echo __('(leave blank to import all, one per line)', $td); ?></em><br />
				<textarea name="cats" cols="50" rows="5"><?php echo $data['cats'];?></textarea></p>
			
			<!-- <p><label><?php echo __('Debug', $td);?>:</label> 
				<select name="debug">
					<option value="n"><?php echo __('Off', $td); ?></option>
					<option value="y"<?php echo $data['debug'];?>><?php echo __('On', $td);?></option>
				</select>
			</p> -->
			
			<div class="full-import">
				<label><strong><?php echo __('Automated Full Import', $td); ?></strong></label>
				<div>
					<input type="checkbox" name="keep-going" value="y"<?php echo $data['keep-going'];?>/> 
					<?php echo __('Yes', $td);?><br />
					<em><?php echo __('The entire source file will be imported.', $td); ?></em>
				</div>
				<div class="clear"></div>
			</div>
				
			<p class="clear"><input class="button-primary button-large" type="submit" name="submit-form" value="<?php echo __('Import Content', $td);?>" /></p>
		</fieldset>
	</form>
	
	<div class="rightcol">
		<h2><?php echo __('Your Import', $td);?></h2>
		
		<?php if(!empty($data['msg'])) : ?>
			<p><?php echo __($data['msg'], $td); ?></p>		
		<?php endif; ?>
		
		<?php if(!isset($_GET['cat_counts']) && !empty($data['categories'])): ?>
			<h3 class="clear"><?php echo __('Categories', $td);?></h3>
			<?php echo $data['categories']; ?>
			<p class="clear small"><a href="<?php echo $data['action'];?>&cat_counts=off"><?php echo __('Turn off Category Totals', $td);?></a> (<?php echo __('save time on large files', $td); ?>)</p>
		<?php elseif(isset($_GET['cat_counts'])) : ?>
			<p class="clear small"><a href="<?php echo str_replace('cat_counts=off', '', $data['action']);?>"><?php echo __('Turn Category Totals On', $td);?></a></p>
		<?php endif; ?>
		
		<h3 class="clear"><?php echo __('Upload Export File', $td);?></h3>
		<form enctype='multipart/form-data' action="<?php echo $data['action']; ?>" method="post">			
			<?php wp_nonce_field('spr-import-posts'); ?>		
			<input type="hidden" name="task" value="new-source" />
			<p><input type="file" name="src-file" /></p>
			<input type="submit" class="no-js button-primary" name="submitbtn" value="<?php echo __('Submit', $td);?>" />
		</form>
	</div>
	
	<div class="how-to">
		<h2 class="clear"><?php echo __('How To Import Posts', $td); ?></h2>
		<p><?php echo __('Here are the steps to getting your current Wordpress site\'s posts into a new install.', $td); ?></p>
		
		<ol>
			<li><?php echo __('You should have this plugin installed on your new site. Take a backup of this new site, for safety.', $td); ?></em></li>
			<li><?php echo __('Go to <strong>Tools > Export</strong> on your old site and create an export file that includes "All Content". It will contain <em>"all of your posts, pages, comments, custom fields, terms, navigation menus and custom posts."', $td); ?></em></li>
			<li><?php echo __('Use the file importer above to upload the file. Alternately, name the file "src.xml" and upload it to directory: '.$data['app_path'].'.', $td); ?> </li>
			<li><?php echo __('Choose a # of posts to import (or number per batch if you will), and a number to skip/offset. If you aren\'t sure, go with 10 posts and 0 skipped.', $td); ?> </li>
			<li><?php echo __('If you want to only import certain categories, enter or drag them to the text field. Leave it blank for all.', $td); ?> </li>
			<li><?php echo __('Check the box for Automated Full Import if you want the plugin to keep going until everything is added.', $td); ?> </li>
			<li><?php echo __('Click Import Content button and wait until the All Done message.', $td); ?> </li>
		</ol>
	</div><!--/how-->
	
	
	<div class="general-notes">
		<h3><span><?php echo __('General Notes', $td);?></span></h3>
		
		<p><?php echo __('This plugin will import all of your posts, images, categories, authors, tags, and comments. If specific categories are entered, any data pertaining to non-included posts will be ignored.', $td);?></p>
		
		<p><?php echo __('<strong>Take a backup of your site first.</strong> You may need to tweak your settings and fine tune the number of posts per batch, which could leave you with duplicate posts and photos in your media library. You may want to have a clean restore point.', $td);?></p>
		
		<p><?php echo __('If you chose Automated Full Import, <strong>do not close the window</strong> or stop the browser until you see the All Done message.', $td);?></p>
		
		<p><?php echo __('Only Posts will be imported, not pages or other custom post types. This may be revised for a future release.', $td);?></p>

		<p><?php echo __('Any posts missing authors will be assigned to one.', $td);?></p>
	
		<p><?php echo __('In the event of duplicate image filenames (eg "groupshot.jpg" is already in your Media Library) your original image will <strong>not</strong> be overwritten, and the link in your post will <strong>not</strong> be corrected to "groupshot1.jpg". You may need to revise by hand.', $td);?></p>
		
		<p><?php echo __('Images that are no longer available can not/will not be uploaded to the site. If your post is missing an image, please make sure the original image path was a valid.', $td);?></p>

		<p><?php echo __('If you have additional post importing needs, consider sponsoring a new release of this plugin.',$td);?> <a href="http://www.sprisemedia.com/hire-contact" target="_blank"><?php echo __('Contact the plugin developer with your requirements', $td);?>.</a></p>

	</div><!--/general-notes -->
		
</div><!--/spr-import-settings -->	
