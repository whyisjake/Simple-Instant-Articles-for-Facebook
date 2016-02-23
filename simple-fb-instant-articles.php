<?php
/*
Plugin Name: Simple Facebook Instant Articles
Version: 0.5.0
Description: Add support to Facebook Instant Articles
Author: Jake Spurlock
Author URI: http://jakespurlock.com
*/

require_once( 'includes/functions.php' );
require_once( 'includes/shortcodes.php' );
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
		add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_action( 'simple_fb_before_feed', array( $this, 'pre_render' ) );
		add_action( 'simple_fb_pre_render', array( $this, 'pre_render' ) );

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
		$this->endpoint = apply_filters( 'simple_fb_article_endpoint', 'fb-instant');
		if ( $this->is_redirectable_endpoint() ){
			add_rewrite_endpoint( $this->endpoint, EP_PERMALINK );
		}
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

		if ( false !== get_query_var( $this->endpoint, false ) && $this->is_redirectable_endpoint() ) {
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		}
	}

	function is_redirectable_endpoint(){
		if ('' === $this->endpoint || 0 == strpos($this->endpoint, '?') ){
			return false;
		} else {
			return true;
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

		if ( have_posts() ) {
			the_post();
			include( apply_filters( 'simple_fb_article_template_file', $this->template_path . '/article.php' ) );
		}

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
	 * Modify the query before getting any posts.
	 *
	 * @param  WP_Query $query WP Query object
	 *
	 * @return void
	 */
	public function pre_get_posts( WP_Query $query ) {

		$feed_slug = apply_filters( 'simple_fb_feed_slug', $this->token );

		if ( $query->is_main_query() && $query->is_feed( $feed_slug ) ) {

			$query->set( 'posts_per_rss', intval( apply_filters( 'simple_fb_posts_per_rss', get_option( 'posts_per_rss', 10 ) ) ) );

			// Orderby post modified date. Ensures updated posts get updated in FB IA.
			$query->set( 'orderby', 'modified' );

			// Allow easy access to modify query args for the FB IA feed.
			do_action( 'simple_fb_pre_get_posts', $query );

		}

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

	/**
	 * Modify content before render ready for FB IA.
	 *
	 * @return void
	 */
	public function pre_render() {

		add_filter( 'the_permalink_rss', array( $this, 'rss_permalink' ) );

		// Ensure oEmbeds are in the correct format.
		add_filter( 'embed_handler_html', array( $this, 'format_social_embed' ) );
		add_filter( 'embed_oembed_html', array( $this, 'format_social_embed' ) );

	}

	public function rss_permalink( $link ) {
		if ( '' !== $this->endpoint ){
			return trailingslashit( $link ) . $this->endpoint;
		} else {
			return $link;
		}
	}

	/**
	 * Ensure oEmbeds are in the correct format.
	 *
	 * Social embeds Ref: https://developers.facebook.com/docs/instant-articles/reference/social
	 *
	 * @param string   $html    HTML markup to be embeded into post sontent.
	 *
	 * @return string           HTML wrapped in FB IA markup for social embeds.
	 */
	public function format_social_embed( $html ) {
		return '<figure class="op-social"><iframe>' . $html . '</iframe></figure>';
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
