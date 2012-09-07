<?php
/*
Plugin Name: Grouped Comments Widget
Plugin URI: http://croberts.me/grouped-comments-widget/
Description: This plugin adds a widget which displays your recent comments, grouped by post.
Version: 1.0
Author: Chris Roberts
Author URI: http://croberts.me/
*/

/*  Copyright 2012 Chris Roberts (email : columcille@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class grouped_comments_widget extends WP_Widget
{
	function grouped_comments_widget()
	{
		parent::__construct(false, 'Grouped Comments', array('description' => 'Display recent comments, grouped by post.', 'classname' => 'grouped_comments'));
	}

	function widget($args, $instance)
	{
		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? 'Recent Comments' : $instance['title'], $instance, $this->id_base);
		echo $before_widget;
		
		if ($title) {
			echo $before_title . $title . $after_title . "\n";
		}

		$comment_posts = array();
		$recent_comments = get_comments(array(
											  'status' => 'approve',
											  'number' => 35,
											  'post_type' => 'post',
											  'type' => 'comment'
											  ));
		$recent_counter = 0;
		
		foreach ($recent_comments as $this_comment) {
			if ($this_comment->comment_type == '') {
				if (!isset($comment_posts[$this_comment->comment_post_ID])) {
					$comment_posts[$this_comment->comment_post_ID] = array();
				}
				
				$comment_posts[$this_comment->comment_post_ID][] = $recent_comments[$recent_counter];
				
				$recent_counter++;
			}
		}
		
		if (sizeof($comment_posts) > 0) {
			echo '<ul class="grouped_recent_comments">';
			
			// Don't show more than $comments_total total comments.
			$comments_shown = 0;
			
			foreach ($comment_posts as $comment_post_ID => $post_comments) {
				// Get post comment count.
				$post_comment_count = get_comments(array('status' => 'approve', 'post_id' => $comment_post_ID, 'count' => true, 'type' => 'comment'));
				
				echo '<li class="grouped_recent_comments_post"><div class="grouped_recent_comments_post_name"><a href="'. get_permalink($comment_post_ID) .'#comments">'. get_the_title($comment_post_ID) .'</a>';
				
				if ($instance['show_post_count']) {
					echo ' <strong>('. $post_comment_count .')</strong>';
				}
				
				echo '</div>';
				
				echo '<ul class="grouped_recent_comments_post">';
				
				// Don't show more than $comments_per_post recent comments per post.
				$post_comments_shown = 0;
				foreach ($post_comments as $post_comment) {
					echo '<li class="grouped_recent_comment"><a href="'. get_permalink($post_comment->comment_post_ID) .'#comment-'. $post_comment->comment_ID .'">'. $post_comment->comment_author .'</a>: '. get_comment_excerpt($post_comment->comment_ID) .'</li>';
					
					$post_comments_shown++;
					$comments_shown++;
					
					if ($post_comments_shown == $instance['comments_per_post'] || $comments_shown == $instance['comments_total']) {
						break;
					}
				}
				
				echo '</li></ul>';
				
				if ($comments_shown == $instance['comments_total']) {
					break;
				}
			}
			
			echo '</ul>';
		} else {
			echo '<ul class="grouped_recent_comments">';
				echo '<li>No comments.</li>';
			echo '</ul>';
		}
		
		echo $after_widget;
	}

	function update($new_instance, $old_instance)
	{
		$instance = array();
		
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['comments_total'] = intval($new_instance['comments_total']);
		$instance['comments_per_post'] = intval($new_instance['comments_per_post']);
		
		if ($new_instance['show_post_count'] == 'showComments') {
			$instance['show_post_count'] = true;
		} else {
			$instance['show_post_count'] = false;
		}

		return $instance;
	}

	function form($instance)
	{
		if (isset($instance['title'])) {
			$title = $instance['title'];
		} else {
			$title = 'Recent Comments';
		}
		
		if (isset($instance['comments_total'])) {
			$comments_total = $instance['comments_total'];
		} else {
			$comments_total = 10;
		}
		
		if (isset($instance['comments_per_post'])) {
			$comments_per_post = $instance['comments_per_post'];
		} else {
			$comments_per_post = 4;
		}
		
		if (isset($instance['show_post_count'])) {
			if ($instance['show_post_count']) {
				$show_post_count = 'checked="checked"';
			} else {
				$show_post_count = '';
			}
		} else {
			$show_post_count = 'checked="checked"';
		}
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /><br />
			
			<label for="<?php echo $this->get_field_id('comments_total'); ?>">Max # of comments to show:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id('comments_total'); ?>" name="<?php echo $this->get_field_name('comments_total'); ?>" type="text" value="<?php echo esc_attr($comments_total); ?>" /><br />
			
			<label for="<?php echo $this->get_field_id('comments_per_post'); ?>">Max # of comments per post:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id('comments_per_post'); ?>" name="<?php echo $this->get_field_name('comments_per_post'); ?>" type="text" value="<?php echo esc_attr($comments_per_post); ?>" /><br />
			
			<input id="<?php echo $this->get_field_id('show_post_count'); ?>" name="<?php echo $this->get_field_name('show_post_count'); ?>" type="checkbox" value="showComments" <?php echo esc_attr($show_post_count); ?> /> <label for="<?php echo $this->get_field_id('show_post_count'); ?>">Show # of comments per post</label><br />
		</p>
		<?php 
	}
}

if (!function_exists('grouped_get_comments')) {
	function grouped_get_comments()
	{
		register_widget('grouped_comments_widget');
	}

	add_action('widgets_init', 'grouped_get_comments');
}
?>