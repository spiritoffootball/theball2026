<?php /*
================================================================================
The Ball 2026 Child Theme Functions
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES

Theme amendments and overrides.

--------------------------------------------------------------------------------
*/



// set our version here
define( 'THEBALL2026_VERSION', '1.0.0' );



/**
 * Set the content width based on the theme's design and stylesheet.
 *
 * @since 1.0
 */
if ( ! isset( $content_width ) ) { $content_width = 660; }



/**
 * Augment the Base Theme's setup function.
 *
 * @since 1.0
 */
function theball2026_setup() {

	/*
	 * Make theme available for translation.
	 * Translations can be added to the /languages/ directory of the child theme.
	 */
	load_theme_textdomain(
		'theball2026',
		get_stylesheet_directory() . '/languages'
	);

}

// add after theme setup hook
add_action( 'after_setup_theme', 'theball2026_setup' );



/**
 * Add child theme's CSS file(s).
 *
 * @since 1.0
 */
function theball2026_enqueue_styles() {

	// enqueue file
	wp_enqueue_style(
		'theball2026_css',
		get_stylesheet_directory_uri() . '/assets/css/style-overrides.css',
		array( 'theball_screen_css' ),
		THEBALL2026_VERSION, // version
		'all' // media
	);

}

// add a filter for the above
add_filter( 'wp_enqueue_scripts', 'theball2026_enqueue_styles', 105 );



/**
 * Override image of The Ball.
 *
 * @since 1.0
 *
 * @param str $default The existing markup for the image file.
 * @return str $default The modified markup for the image file.
 */
function theball2026_theball_image( $default ) {

	// ignore default and set our own
	return '<a href="' . get_option( 'home' ) . '" title="' . __( 'Home', 'theball2026' ) . '" class="ball_image">' .
			'<img src="' . get_stylesheet_directory_uri() . '/assets/images/interface/the_ball_2026_200_sq.png" ' .
				 'alt="' . esc_attr( __( 'The Ball 2026', 'theball2026' ) ) . '" ' .
				 'title="' . esc_attr( __( 'The Ball 2026', 'theball2026' ) ) . '" ' .
				 'style="width: 100px; height: 100px;" ' .
				 'id="the_ball_header" />' .
			'</a>' ;

}

// add a filter for the above
add_filter( 'theball_image', 'theball2026_theball_image', 10, 1 );



/**
 * Override supporters footer template file.
 *
 * @since 1.0.0
 *
 * @param str $default The default path to the template file.
 * @return str $default The modified path to the template file.
 */
function theball2026_supporters_file( $default ) {

	// override with 2026 file
	return get_stylesheet_directory() . '/assets/includes/supporters_2026.php';

}

// add a filter for the above
add_filter( 'theball_supporters', 'theball2026_supporters_file', 10, 1 );



/**
 * Override users in "Team" template file.
 *
 * @since 1.0
 *
 * @param array $users The default set of users.
 * @return array $users The modified set of users.
 */
function theball2026_team_members( $default ) {

	// 2026 users
	return array( 3, 5, 8, 7, 2, 4 );

}

// add a filter for the above
//add_filter( 'theball_team_members', 'theball2026_team_members', 10, 1 );



/**
 * The Ball 2026 Gallery Shortcode Filter Class.
 *
 * A class that encapsulates the functionality required to replace the assets of
 * a gallery with those from a different blog.
 *
 * @since 1.0.3
 */
class The_Ball_2026_Gallery_Filter {

	/**
	 * Target Site ID from which assets should be pulled.
	 *
	 * @since 1.0.3
	 * @access public
	 * @var bool $site_id Target Site ID from which assets should be pulled.
	 */
	public $site_id;

	/**
	 * Gallery filter flag.
	 *
	 * @since 1.0.3
	 * @access public
	 * @var bool $gallery_filter True if currently filtering a gallery, false otherwise.
	 */
	public $gallery_filter = false;



	/**
	 * Initialises this object.
	 *
	 * @since 1.0.3
	 */
	public function __construct() {

		// add a filter for the gallery shortcode
		add_filter( 'post_gallery', array( $this, 'gallery_shortcode' ), 1010, 2 );

		// add a filter for the above
		add_filter( 'comments_open', array( $this, 'media_comment_status' ), 10, 2 );

	}



	/**
	 * Switch to target site for gallery shortcode assets.
	 *
	 * @since 1.0.3
	 *
	 * @param str $output The existing shortcode output.
	 * @param array $attr The shortcode attributes.
	 * @return str $output The modified shortcode output.
	 */
	public function gallery_shortcode( $output, $attr ) {

		// check for our custom attribute
		if( empty( $attr['sof_site_id'] ) ) return $output;
		if( ! is_numeric( $attr['sof_site_id'] ) ) return $output;

		// set site ID
		$this->site_id = absint( $attr['sof_site_id'] );

		// set filter flag
		$this->gallery_filter = true;

		// prevent recursion
		remove_filter( 'post_gallery', array( $this, 'gallery_shortcode' ), 1010 );

		// switch to SOF eV site and rebuild shortcode
		switch_to_blog( $this->site_id );
		$output = do_shortcode( '[gallery type="' . $attr['type'] . '" ids="' . $attr['ids'] . '"]' );
		restore_current_blog();

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'attr' => $attr,
			'output' => $output,
			'backtrace' => $trace,
		), true ) );
		*/

		// reset filter
		add_filter( 'post_gallery', array( $this, 'gallery_shortcode' ), 1010, 2 );

		// reset filter flag
		$this->gallery_filter = false;

		// --<
		return $output;

	}



	/**
	 * Remove comments from media attachments.
	 *
	 * This is done in order to remove the comments on the JetPack Carousel Slides.
	 * The class properties prevent
	 *
	 * @since 1.0.3
	 *
	 * @param bool $open The existing comment status.
	 * @param int $post_id The numeric ID of the post.
	 * @return bool $open The modified comment status.
	 */
	public function media_comment_status( $open, $post_id ) {

		// bail if not filtering a gallery
		if ( $this->gallery_filter === false ) return $open;

		// bail if site ID is not properly set
		if ( ! is_numeric( $this->site_id ) ) return $open;

		// --<
		return false;

	}

} // end class

// init class
//global $sof_gallery_filter;
//$sof_gallery_filter = new The_Ball_2022_Gallery_Filter();



