<?php defined('ABSPATH') or die("No script kiddies please!");
/*

Plugin Name: WP eCommerce Wishlist

Plugin URI: http://www.websitedesignwebsitedevelopment.com/wp-e-commerce-wish-list

Description: This is Wishlist plugin for WP eCommerce Site. It has a widget which you can use with ultimate convenience.

Version: 1.0

Author: Fahad Mahmood 

Author URI: http://www.androidbubbles.com

License: GPL3

*/ 
 /*  Copyright YEAR  Fahad Mahmood  (email : fahad@androidbubbles.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
add_action('wpsc_product_form_fields_end', 'initialize_wpecwl');
add_action('wp_footer', 'ajaxUrl');

function wpecwl_scripts() {
		
		
		wp_register_style('wpecwl-style', plugins_url('css/style.css', (__FILE__)));
		wp_enqueue_style( 'wpecwl-style' );
		
	
	}
add_action( 'wp_enqueue_scripts', 'wpecwl_scripts' );
	
function initialize_wpecwl(){
	if(!is_user_logged_in())
	return false;	
	global $current_user;
	?>
    
	<input type="image" title="Add to Wishlist" src="<?php echo plugins_url( 'images/heart-icon.png', __FILE__); ?>"class="wpecwl_add" proid="<?php echo wpsc_the_product_id();?>" value="&nbsp;" />
    <?php echo get_user_meta( $current_user->ID, 'wp_smart_wpecwl', true ) ;?>
    <?php
}
function ajaxUrl() {
    ?>
	<script type="text/javascript" language="javascript">
	function loadWishlist(){
		var data = { 'action': 'load_wish_list'};
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
			jQuery.post(ajaxurl, data, function(response) {
			jQuery('#wpecwl').html(response);
		});
	}
	
    jQuery(document).ready(function($) {
		loadWishlist();
		jQuery('.wpecwl_add').click(function(){
			<?php if ( is_user_logged_in() ) {?>
			var data = { 'action': 'add_wish_list', 'proid': jQuery(this).attr('proid') };
			var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
				jQuery.post(ajaxurl, data, function(response) {
				loadWishlist();
			});
			<?php }else{?>
				alert('Please login after use this option');
			<?php }?>
		});
		
		
		jQuery('.wpecwl_remove').live('click', function(){
			console.log(jQuery(this));
			var proid = jQuery(this).attr('id').replace('r_0', '');
		
			var data = { 'action': 'remove_wish_list', 'proid': proid };
			var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
			jQuery.post(ajaxurl, data, function(response) {
			loadWishlist();
			
			 });
			
		 });
		
    });
    </script>
	<?php
}

add_action( 'wp_ajax_add_wish_list', 'add_wish_list_callback' );

function add_wish_list_callback() {
	global $wpdb;

	$proid = $_POST['proid'];
	$user_ID = get_current_user_id();

	$wpecwl = get_user_meta( $user_ID , 'wpecwl', true);
	$array = unserialize ( $wpecwl );
	if( ! in_array($proid ,$array) ){
		$array[] .= $proid;
		$new_wish = serialize( $array );
		update_user_meta( $user_ID , 'wpecwl' , $new_wish, $wpecwl );	
	}
	
	die();
}

add_action( 'wp_ajax_remove_wish_list', 'remove_wish_list_callback' );

function remove_wish_list_callback() {
	global $wpdb;

	$proid = $_POST['proid'];
	$user_ID = get_current_user_id();

	$wpecwl = get_user_meta( $user_ID , 'wpecwl', true);
	$array = unserialize ( $wpecwl );
	if( in_array($proid ,$array) ){
		$key = array_search($proid ,$array); 
		unset( $array[$key]);
		$new_wish = serialize( $array );
		update_user_meta( $user_ID , 'wpecwl' , $new_wish, $wpecwl );	
	}
	
	die();
}

add_action( 'wp_ajax_load_wish_list', 'load_wish_list_callback' );

function load_wish_list_callback() {
	global $wpdb;

	$user_ID = get_current_user_id();

	$wpecwl = get_user_meta( $user_ID , 'wpecwl', true);
	$array = unserialize ( $wpecwl );
	
	$html = '<ul>
						';
						$i = 1;
		if( ! empty($array) ){
			foreach( $array as $k => $v ){
				global $post;
				$post = get_post( $v );
				setup_postdata($post);
				
				if ( has_post_thumbnail() ) {
					$img = get_the_post_thumbnail( $post->ID, array(64,64) );
				}
				else {
					$img = '<img src="' . plugins_url( 'images/notfound.png' , __FILE__ ).'" alt="Image" width="64" height="64" />';
				}
	
				//Template Start
				$html .= '	<li><a href="'.get_permalink().'">'.$img.'</a><a href="'.get_permalink().'">'. get_the_title() .'</a><a class="wpecwl_remove" id="r_0'.get_the_ID().'"><img src="' . plugins_url( 'images/remove.png' , __FILE__ ).'" alt="Remove" width="16" height="16" /></a></li>';
				//Template End
				$i++;
			}
		}
		else{
			$html .= '<tr><td colspan="4">No Wishlist Found</td></tr>';
		}
		$html .= '	</ul>';
		echo $html;
		
	
	die();
}

/********WIDGET SECTION*********/

class wpecwlWidget extends WP_Widget
{
  function wpecwlWidget()
  {
    $widget_ops = array('classname' => 'wpecwlWidget', 'description' => 'Displays Wishlist' );
    $this->WP_Widget('wpecwlWidget', 'eCommerce Wishlist', $widget_ops);
  }
 
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
    $title = $instance['title'];
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget($args, $instance)
  {
	if(!is_user_logged_in())
	return false;
		  
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
 
    if (!empty($title))
      echo $before_title . $title . $after_title;;
 
    
    echo "<div id='wpecwl'></div>";
 
    echo $after_widget;
  }
 
}
add_action( 'widgets_init', create_function('', 'return register_widget("wpecwlWidget");') );

?>