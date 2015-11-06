<?php
/*
Plugin Name: Simple Facebook Instant Articles
Version: 0.5.0
Description: Add support to Facebook Instant Articles
Author: Jake Spurlock
Author URI: http://jakespurlock.com
*/

require_once( 'includes/functions.php' );
class Simple_FB_Instant_Articles {
	/**
	 * The one instance of Simple_FB_Instant_Articles.
	 *
	 * @var Simple_FB_Instant_Articles
	 */
	private static $instance;

	/**
	 * Endpoint query var
	 */
	private $token = 'fb';

	/**
	 * Endpoint query var
	 */
	private $endpoint = 'fb-instant';


	/**
	 * Image Size - 2048x2048 recommended resolution.
	 * @see https://developers.facebook.com/docs/instant-articles/reference/image
	 */
	public $image_size = array( 2048, 2048 );

	/**
	 * Instantiate or return the one Simple_FB_Instant_Articles instance.
	 *
	 * @return Simple_FB_Instant_Articles
	 */
	public static function instance( $file = null, $version = '' ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $file, $version );
		}

		return self::$instance;
	}

	/**
	 * Template Path
	 */
	private $template_path;

	/**
	 * Initiate actions.
	 *
	 * @return Simple_FB_Instant_Articles
	 */
	public function __construct( $file, $version ) {

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'add_feed' ) );
		add_action( 'wp', array( $this, 'add_actions' ) );
		add_filter( 'simple_fb_pre_render', array( $this, 'register_shortcodes' ) );
		add_filter( 'simple_fb_before_feed', array( $this, 'register_shortcodes' ) );
		add_filter( 'simple_fb_before_feed', array( $this, 'update_rss_permalink' ) );

		// Setup the props.
		$this->version = $version;
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->template_path = trailingslashit( $this->dir ) . 'templates/';
		$this->home_url = trailingslashit( home_url() );
	}

	/**
	 * Kickoff method
	 * @return void
	 */
	public function init() {
		add_rewrite_endpoint( $this->endpoint, EP_PERMALINK );
		register_activation_hook( __FILE__,   'flush_rewrite_rules' );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	}

	/**
	 * Add the template redirect, and maybe more!
	 */
	function add_actions() {
		if ( ! is_singular() ) {
			return;
		}

		if ( false !== get_query_var( $this->endpoint, false ) ) {
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		}
	}

	/**
	 * Redirect the template for the Instant Article post.
	 * @return [type] [description]
	 */
	public function template_redirect() {
		$this->render( get_queried_object_id() );
		exit;
	}

	/**
	 * Based on the post ID, render the Instant Articles page.
	 * @param  int   $post_id Post ID
	 * @return void
	 */
	public function render( $post_id ) {
		do_action( 'simple_fb_pre_render', $post_id );
		include( apply_filters( 'simple_fb_article_template_file', $this->template_path . '/article.php' ) );
	}

	/**
	 * Register FB feed
	 * @return void
	 */
	public function add_feed() {
		$feed_slug = apply_filters( 'simple_fb_feed_slug', $this->token );
		add_feed( $feed_slug, array( $this, 'feed_template' ) );
	}

	/**
	 * Load feed template
	 * @return void
	 */
	public function feed_template() {
		global $wp_query;

		// Prevent 404 on feed
		$wp_query->is_404 = false;
		status_header( 200 );

		$file_name = 'feed.php';

		$user_template_file = apply_filters( 'simple_fb_feed_template_file', trailingslashit( get_template_directory() ) . $file_name );

		// Any functions hooked in here must NOT output any data or else feed will break
		do_action( 'simple_fb_before_feed' );

		// Load user feed template if it exists, otherwise use plugin template
		if ( file_exists( $user_template_file ) ) {
			require( $user_template_file );
		} else {
			require( $this->template_path . $file_name );
		}

		// Any functions hooked in here must NOT output any data or else feed will break
		do_action( 'simple_fb_after_feed' );
	}

	public function register_shortcodes() {
		add_shortcode( 'gallery', array( $this, 'gallery' ) );
	}

	public function update_rss_permalink() {
		add_filter( 'the_permalink_rss', array( $this, 'rss_permalink' ) );
	}

	public function rss_permalink( $link ) {
		return esc_url( $link . $this->endpoint );
	}

	/**
	 * Gallery Shortcode
	 * @param  array     $atts       Array of attributes passed to shortcode.
	 * @param  string    $content    The content passed to the shortcode.
	 * @return string                The generated content.
	 */
	public function gallery( $atts, $content = '' ) {
		// Get the IDs
		$ids = explode( ',', $atts['ids'] );

		ob_start(); ?>
		<figure class="op-slideshow">
			<?php foreach ( $ids as $id ) : ?>
				<?php $image = wp_get_attachment_image_src( $id, $this->image_size ); ?>
				<?php $url   = ( $image[0] ); ?>
				<figure>
					<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( get_the_title( $id ) ); ?>">
					<?php $caption = get_post_field( 'post_content', $id ); ?>
					<?php if ( ! empty( $caption ) ) : ?>
						<figcaption><?php echo esc_html( $caption ); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</figure>
		<?php return ob_get_clean();
	}

}

/**
 * Instantiate or return the one Simple_FB_Instant_Articles instance.
 *
 * @return Simple_FB_Instant_Articles
 */
function simple_fb_instant_articles( $file, $version ) {
	return Simple_FB_Instant_Articles::instance( $file, $version );
}

// Kick off the plugin on init
simple_fb_instant_articles( __FILE__, '0.5.0' );