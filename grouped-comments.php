<?php
/*
Plugin Name: Grouped Comments Widget
Plugin URI: http://croberts.me/grouped-comments-widget/
Description: This plugin adds a widget which displays your recent comments, grouped by post.
Version: 1.5.1
Author: Chris Roberts
Author URI: http://croberts.me/
*/

/*  Copyright 2013 Chris Roberts (email : chris@dailycross.net)

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
		
		if ($instance['show_only_posts']) {
			$show_type = 'post';
		} else {
			$show_type = '';
		}
		
		$recent_comments = get_comments(array(
											  'status' => 'approve',
											  'number' => 35,
											  'post_type' => $show_type,
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
				
				if ($instance['post_link_to'] == "post") {
					$post_link = get_permalink($comment_post_ID);
				} else {
					$post_link = get_permalink($comment_post_ID) .'#comments';
				}
				
				echo '<li class="grouped_recent_comments_post"><div class="grouped_recent_comments_post_name"><a href="'. $post_link .'">'. get_the_title($comment_post_ID) .'</a>';
				
				if ($instance['show_post_count']) {
					echo '&nbsp;<strong>('. $post_comment_count .')</strong>';
				}
				
				echo '</div>';
				
				echo '<ul class="grouped_recent_comments_post">';
				
				// Don't show more than $comments_per_post recent comments per post.
				$post_comments_shown = 0;
				foreach ($post_comments as $post_comment) {
					$comment_text = get_comment_excerpt($post_comment->comment_ID);
					
					// Search the excerpt for links and trim them down
					// $url_match = '\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))';
					$url_match = '@\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))@';
					if (preg_match($url_match, $comment_text, $matches)) {
						$found_url = $matches[0];
						$replace_url = $found_url;
						
						if (strlen($found_url) >= 20) {
							$replace_url = substr($found_url, 0, 17) ."...";
						}
						
						$comment_text = str_replace($found_url, $replace_url, $comment_text);
					}
					
					$comment_display = '<li class="grouped_recent_comment"><a href="'. get_permalink($post_comment->comment_post_ID) .'#comment-'. $post_comment->comment_ID .'">'. $post_comment->comment_author .'</a>: '. $comment_text .'</li>';
					echo $comment_display;
					
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
		$instance['post_link_to'] = strip_tags($new_instance['post_link_to']);
		
		if ($new_instance['show_post_count'] == 'showComments') {
			$instance['show_post_count'] = true;
		} else {
			$instance['show_post_count'] = false;
		}
		
		if ($new_instance['show_only_posts'] == 'showPosts') {
			$instance['show_only_posts'] = true;
		} else {
			$instance['show_only_posts'] = false;
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
		
		if (isset($instance['show_only_posts'])) {
			if ($instance['show_only_posts']) {
				$show_only_posts = 'checked="checked"';
			} else {
				$show_only_posts = '';
			}
		} else {
			$show_only_posts = '';
		}
		
		if (isset($instance['post_link_to'])) {
			if ($instance['post_link_to'] == "post") {
				$post_link_to_post = 'checked="checked"';
				$post_link_to_comments = '';
			} else {
				$post_link_to_comments = 'checked="checked"';
				$post_link_to_post = '';
			}
		} else {
			$post_link_to_post = 'checked="checked"';
			$post_link_to_comments = '';
		}
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /><br /><br />
			
			<label for="<?php echo $this->get_field_id('comments_total'); ?>">Max # of comments to show:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id('comments_total'); ?>" name="<?php echo $this->get_field_name('comments_total'); ?>" type="text" value="<?php echo esc_attr($comments_total); ?>" /><br /><br />
			
			<label for="<?php echo $this->get_field_id('comments_per_post'); ?>">Max # of comments per post:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id('comments_per_post'); ?>" name="<?php echo $this->get_field_name('comments_per_post'); ?>" type="text" value="<?php echo esc_attr($comments_per_post); ?>" /><br /><br />
			
			<input id="<?php echo $this->get_field_id('show_post_count'); ?>" name="<?php echo $this->get_field_name('show_post_count'); ?>" type="checkbox" value="showComments" <?php echo esc_attr($show_post_count); ?> /> <label for="<?php echo $this->get_field_id('show_post_count'); ?>">Show # of comments per post</label><br />
			
			<input id="<?php echo $this->get_field_id('show_only_posts'); ?>" name="<?php echo $this->get_field_name('show_only_posts'); ?>" type="checkbox" value="showPosts" <?php echo esc_attr($show_only_posts); ?> /> <label for="<?php echo $this->get_field_id('show_only_posts'); ?>">Show only comments on posts</label><br /><br />
			
			Where should the post link go?<br />
			<input id="<?php echo $this->get_field_id('post_link_to_comments'); ?>" name="<?php echo $this->get_field_name('post_link_to'); ?>" type="radio" value="comments" <?php echo esc_attr($post_link_to_comments); ?> />
				<label for="<?php echo $this->get_field_id('post_link_to_comments'); ?>">Post link goes to top of comments</label><br />
			
			<input id="<?php echo $this->get_field_id('post_link_to_post'); ?>" name="<?php echo $this->get_field_name('post_link_to'); ?>" type="radio" value="post" <?php echo esc_attr($post_link_to_post); ?> />
				<label for="<?php echo $this->get_field_id('post_link_to_post'); ?>">Post link goes to top of post</label><br />
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