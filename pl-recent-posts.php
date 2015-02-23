<?php
/*
Plugin Name: PressLabs Recent Posts
Plugin URI: http://www.presslabs.com
Description: Displays the most recent posts in a cache-friendly manner
Version: 0.1
Author: PressLabs
Author URI: http://www.presslabs.com/
*/

define( 'PLRCP_JSON_FILE_NAME', 'plrcp.json' );
define( 'PLRCP_JSON_PATH', wp_upload_dir()['basedir'] . '/' . PLRCP_JSON_FILE_NAME );
define( 'PLRCP_JSON_URL', wp_upload_dir()['baseurl'] . '/' . PLRCP_JSON_FILE_NAME );
define( 'PLRCP_WIDGET_ID', 'pl_recent_posts' );
define( 'PLRCP_TEMPLATE_NAME', 'plrcp-template' );
define( 'PLRCP_DEFAULT_TEMPLATE_PATH', __DIR__ . '/' . PLRCP_TEMPLATE_NAME . '.php' );
define( 'PLRCP_THEME_TEMPLATE_PATH', get_template_directory() . '/' . PLRCP_TEMPLATE_NAME . '.php' );

class PL_Recent_Posts extends WP_Widget {
	function __construct() {
		parent::__construct(
			PLRCP_WIDGET_ID,
			'PL Recent Posts',
			array(
				'description' => 'Recent Posts chache friendly.',
				'classname'   => 'widget_pl_recent_posts',
			)
		);

		$resource = 'js/widget.js';
		$path = __DIR__ . '/' . $resource;
		$url = plugins_url( $resource, __FILE__ );

		wp_register_script( 'plrcp', $url, array( 'jquery' ), filemtime( $path ), true );

		$json_url = str_replace( PL_PUBLIC_URL, PL_CDN_URL, PLRCP_JSON_URL );
		$plrcp_array = array( 'json_url' => $json_url );
		wp_localize_script( 'plrcp', 'plrcp', $plrcp_array );
	}


	function widget( $args, $instance ) {
		extract( $args );

		echo $before_widget;
		?>
		<div class='plrcp-panel'>
		<?php

			if ( $instance['title'] )
				echo $before_title . $instance['title'] . $after_title;

			echo '<div class="widget plrcp" data-id="' . $this->id . '"></div>';
		?>
		</div>
		<?php
		echo $after_widget;

		$this->enqueue();
	}


	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'] ? $new_instance['title'] : 'Recent Posts';
		$instance['category'] = ($new_instance['category'] == -1) ? 0 : $new_instance['category'];
		$instance['numberposts'] = $new_instance['numberposts'] ? $new_instance['numberposts'] : '5';
		$instance['default_thumb'] = filter_var($new_instance['default_thumb'], FILTER_VALIDATE_URL) ? $new_instance['default_thumb'] : '';

		$html = static::get_html( $instance );
		$identifier = $this->id;
		static::refresh_json( array( $identifier => $html ) );

		return $instance;
	}


	function form( $instance ) {
		$title = $instance['title'] ? $instance['title'] : 'Recent Posts';
		$category = ($instance['category'] == ''  || $instance['category'] == -1) ? -1 : $instance['category'];
		$numberposts = $instance['numberposts'] ? $instance['numberposts'] : '5';
		$default_thumb = $instance['default_thumb'];
		?>

    <p>
	    <label for="<?php print $this->get_field_id( 'title' ); ?>">Title:</label>
  	  <input id="<?php print $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>"
				type="text" value="<?php print $title; ?>" class='widefat' size="25" />
    </p>

    <p>
    	<label for="<?php print $this->get_field_id( 'category' ); ?>">Category:</label>
			<?php
				$args = array(
					'orderby' => 'name',
					'order' => 'ASC',
				);

				$categories = get_categories( $args );
				if ( 0 < count( $categories ) ):
					?>
						<select id="<?php print $this->get_field_id( 'category' ); ?>" class='widefat
							'name="<?php echo $this->get_field_name( 'category' ); ?>">
					<?php
					if ( $category == 'plrcp-all-categories' ) :
						$selected = ' selected="selected"';
					else :
						$selected = '';
					endif;
					?>

					<option value="<?php print '-1'; ?>" <?php print $selected; ?>><?php print "All Categories"; ?></option>

					<?php
					foreach ( $categories as $cat ):
						$selected = "";

						if ( ($category == $cat->term_id) || ($category == $cat->slug) )
							$selected = ' selected="selected"';
					?>
        		<option value="<?php print $cat->term_id; ?>" <?php print $selected; ?>><?php print $cat->name; ?></option>
					<?php endforeach; ?>
      	</select>

			<?php endif; ?>
    </p>

    <p>
    	<label for="<?php print $this->get_field_id( 'numberposts' ); ?>">Number of posts:</label>
    	<input id="<?php print $this->get_field_id( 'numberposts' ); ?>" name="<?php echo $this->get_field_name( 'numberposts' ); ?>"
				type="text" value="<?php print $numberposts; ?>" class='widefat' size="25" />
    </p>
    <p>
    	<label for="<?php print $this->get_field_id( 'default_thumb' ); ?>">Default thumb url:</label>
    	<input id="<?php print $this->get_field_id( 'default_thumb' ); ?>" name="<?php echo $this->get_field_name( 'default_thumb' ); ?>"
				type="text" value="<?php print $default_thumb; ?>" class='widefat' size="25" />
    </p>
		<?php
	}


	static function get_html( $instance ) {
		$title = $instance['title'] ? $instance['title'] : 'Recent Posts';
		$category = $instance['category'];
		$category = ($category == '' || $category == -1) ? 0 : $instance['category'];

		$numberposts = absint( $instance['numberposts'] ? $instance['numberposts'] : 5 );
		$default_thumb = $instance['default_thumb'];

		$title = apply_filters( 'widget_title', $title, $instance, PLRCP_WIDGET_ID );

		$recent_posts_args = array(
			'numberposts' => $numberposts,
			'post_status' => 'publish',
			'category'    => $category
		);

		$recent_posts = wp_get_recent_posts( $recent_posts_args );

		ob_start();
		include( plrcp_get_template_path() );
		return ob_get_clean();
	}


	static function refresh_json( array $data, $delete = false ) {
		if ( empty( $data ) )
			return;

		$string = @file_get_contents( PLRCP_JSON_PATH );
		if ( $string === false )
			$json_a = array();
		else
			$json_a = json_decode( $string, true );

		foreach ( $data as $identifier => $html  ) {
			if ( $delete === false ) {
				$json_a[ $identifier ] = $html;
			} else {
				if ( isset( $json_a[ $identifier ] ) )
					unset($json_a[ $identifier ]);
			}
		}

		$json = json_encode( $json_a, JSON_FORCE_OBJECT );
		file_put_contents( PLRCP_JSON_PATH, $json );

		do_action( 'plrcp_cache_refresh', PLRCP_JSON_PATH );
	}


	function enqueue() {
		$resource = 'css/style.css';
		$path = __DIR__ . '/' . $resource;
		$url = plugins_url( $resource, __FILE__ );

		wp_enqueue_style( 'plrcp-style', $url, array(), filemtime( $path ) );
		wp_enqueue_script( 'plrcp' );
	}
}



add_action( 'plrcp_cache_refresh', 'plrcp_presslabs_cache' );
function plrcp_presslabs_cache( $file ) {
	if ( ! defined('PL_INSTANCE_NAME') )
		return;

	$url = str_replace( WP_CONTENT_DIR, PL_CDN_URL . '/wp-content', $file );
	pl_update_cdn( $url );
}



add_action( 'widgets_init', 'plrcp_register_widgets' );
function plrcp_register_widgets() {
	register_widget( 'PL_Recent_Posts' );
}



function plrcp_get_template_path() {
	if ( file_exists(PLRCP_THEME_TEMPLATE_PATH) )
		return PLRCP_THEME_TEMPLATE_PATH;

	return PLRCP_DEFAULT_TEMPLATE_PATH;
}


function plrcp_get_thumb( $post_id = null, $default_thumb = '' ) {

	if ( $post_id === null )
		$post_id = get_the_ID();

	if ( has_post_thumbnail( $post_id ) )
		$thumbnail = get_the_post_thumbnail( $post_id, 'thumbnail' );
	else {

		$images = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_parent'    => $post_id,
				'posts_per_page' => 1,
			)
		);

		if ( $images )
			$thumbnail_url = wp_get_attachment_image_src( $images[0]->ID, 'thumbnail' )[0];
		else
			$thumbnail_url = $default_thumb;

		$thumbnail = '<img width="80" class="attachment-thumbnail wp-post-image" src="'
			. $thumbnail_url . '" alt="Image for ' . get_the_title( $post_id ) . '">';
	}

	return $thumbnail;
}



add_action( 'save_post', 'plrcp_recreate_json_on_post_change' );
add_action( 'delete_post', 'plrcp_recreate_json_on_post_change' );
function plrcp_recreate_json_on_post_change( $pid ) {
	$sidebars = get_option( 'sidebars_widgets', array( 'wp_inactive_widgets' => '', 'array_version' => '' ) );
	unset( $sidebars['wp_inactive_widgets'] );
	unset( $sidebars['array_version'] );

	$found_widgets = array();

	foreach ( $sidebars as $sidebar ) {
		foreach ( $sidebar as $widget_id ) {
			if ( strpos( $widget_id, PLRCP_WIDGET_ID ) === false )
				continue;

			$id = substr( $widget_id, strlen( PLRCP_WIDGET_ID ) );
			$id = trim( $id, ' -' );
			if ( $id === '' )
				$id = 0;
			else
				$id = intval( $id );

			$widget = get_option( 'widget_' . PLRCP_WIDGET_ID );
			$instance = $widget[ $id ];

			$html = PL_Recent_Posts::get_html( $instance );
			$found_widgets[ $widget_id ] = $html;
		}
	}

	PL_Recent_Posts::refresh_json( $found_widgets );
}



add_action( 'sidebar_admin_setup', 'plrcp_sidebar_admin_setup' );
function plrcp_sidebar_admin_setup() {
	if ( 'post' != strtolower( $_SERVER['REQUEST_METHOD'] ) )
		return;

	$widget_id = $_POST['widget-id'];

	if ( ! isset( $_POST['delete_widget'] ) )
		return;

	if ( strpos( $widget_id, PLRCP_WIDGET_ID) !== 0 )
		return;

	if ( 1 !== (int) $_POST['delete_widget'] )
		return;

	PL_Recent_Posts::refresh_json( array( $widget_id => '' ), true );
}
