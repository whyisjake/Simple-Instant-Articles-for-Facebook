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

		// Render post content into FB IA format.
		add_action( 'simple_fb_pre_render', array( $this, 'setup_content_mods' ) );
		add_action( 'simple_fb_before_feed', array( $this, 'setup_content_mods' ) );

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
	public function add_actions() {
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

	/**
	 * Setup all filters to modify content ready for Facebook IA.
	 *
	 * Hooked in just before the content is rendered in both feeds and single post view
	 * for Facebook IA only.
	 *
	 * This function is added to the following actions:
	 * 1) simple_fb_pre_render
	 * 2) simple_fb_before_feed
	 */
	public function setup_content_mods() {

		// Shortcodes - overwrite WP native ones with FB IA format.
		add_shortcode( 'gallery', array( $this, 'gallery_shortcode' ) );
		add_shortcode( 'caption', array( $this, 'image_shortcode' ) );

		// Render social embeds into FB IA format.
		add_filter( 'embed_handler_html', array( $this, 'fb_formatted_social_embeds' ), 10, 3 );
		add_filter( 'embed_oembed_html', array( $this, 'fb_formatted_social_embeds' ), 10, 4 );

		// Render post content via DOM - to format it into FB IA format.
		// DO it last, so content was altered via WP native hooks as much as possible.
		add_filter( 'the_content', array( $this, 'fb_formatted_post_content' ), 1000 );

		// Post URL for the feed.
		add_filter( 'the_permalink_rss', array( $this, 'rss_permalink' ) );

		// Render post content into FB IA format - using DOM object.
		add_action( 'simple_fb_formatted_post_content', array( $this, 'render_pull_quotes' ), 10, 2 );
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
	public function gallery_shortcode( $atts, $content = '' ) {
		// Get the IDs
		$ids = explode( ',', $atts['ids'] );

		ob_start(); ?>
		<figure class="op-slideshow">
			<?php foreach ( $ids as $id ) : ?>
				<?php $image = wp_get_attachment_image_src( $id, $this->image_size ); ?>
				<?php $url   = ( $image[0] ); ?>
				<figure>
					<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( get_the_title( $id ) ); ?>">
					<?php simple_fb_image_caption( $id ); ?>
				</figure>
			<?php endforeach; ?>
		</figure>
		<?php return ob_get_clean();
	}

	/**
	 * Caption shortcode - overwrite WP native shortcode.
	 * Format caption of inserted images into post content into
	 * FB IA format.
	 *
	 * @param $atts           Array of attributes passed to shortcode.
	 * @param string $content The content passed to the shortcode.
	 *
	 * @return string|void    FB IA formatted images markup.
	 */
	public function image_shortcode( $atts, $content = '' ) {

		// Get attachment ID from the shortcode attribute.
		$attachment_id = isset( $atts['id'] ) ? (int) str_replace( 'attachment_', '', $atts['id'] ) : '';

		// Get image info.
		$image     = wp_get_attachment_image_src( $attachment_id, $this->image_size );
		$image_url = isset( $image[0] ) ? $image[0] : '';

		// Stop - if image URL is empty.
		if ( ! $image_url ) {
			return;
		}

		// FB IA image format.
		ob_start(); ?>
		<figure>
			<img src="<?php echo esc_url( $image_url ); ?>" />
			<?php simple_fb_image_caption( $attachment_id ); ?>
		</figure>
		<?php return ob_get_clean();
	}

	/**
	 * Setup dom and xpath objects for formatting post content.
	 * Introduces `simple_fb_formatted_post_content` filter, so that post content
	 * can be formatted as necessary and dom/xpath objects re-used.
	 *
	 * @param $post_content Post content that needs to be formatted into FB IA format.
	 *
	 * @return string|void  Post content in FB IA format if dom is generated for post content,
	 *                      Otherwise, nothing.
	 */
	public function fb_formatted_post_content( $post_content ) {

		$dom = new \DOMDocument();

		// Parse post content to generate DOM document.
		// Use loadHTML as it doesn't need to be well-formed to load.
		@$dom->loadHTML( '<html><body>' . $post_content . '</body></html>' );

		// Stop - if dom isn't generated.
		if ( ! $dom ) {
			return;
		}

		$xpath = new \DOMXPath( $dom );

		// Allow to render post content via action.
		do_action_ref_array( 'simple_fb_formatted_post_content', array( &$dom, &$xpath ) );

		// Get the FB formatted post content HTML.
		$body_node = $dom->getElementsByTagName( 'body' )->item( 0 );
		return $this->get_html_for_node( $body_node );
	}

	/**
	 * Renders pull quotes into FB AI format.
	 * Ref: https://developers.facebook.com/docs/instant-articles/reference/pullquote
	 *
	 * @param DOMDocument $dom   Dom object generated for post content.
	 * @param DOMXPath    $xpath Xpath object generated for post content.
	 */
	public function render_pull_quotes( \DOMDocument &$dom, \DOMXPath &$xpath ) {

		// Pull quotes - with <cite> element.
		foreach ( $xpath->query( '//blockquote[descendant::cite]' ) as $node ) {

			// Get and remove <cite> element.
			$cite = $node->getElementsByTagName( 'cite' )->item( 0 );
			@$cite->parentNode->removeChild( $cite );

			$pull_quote_html = $this->get_html_for_node( $node );

			// FB AI pull quote format.
			$fb_pull_quote = sprintf(
				'<aside>%s<cite>%s</cite></aside>',
				wp_kses( $pull_quote_html,
					array(
						'em'     => array(),
						'i'      => array(),
						'b'      => array(),
						'strong' => array()
					)
				),
				esc_html( $cite->nodeValue )
			);

			// Replace original pull quotes with FB AI marked up ones.
			$new_node = $dom->createDocumentFragment();
			$new_node->appendXML( $fb_pull_quote );
			$node->parentNode->replaceChild( $new_node, $node );
		}
	}

	/**
	 * Render social embeds into FB IA format.
	 *
	 * Social embeds Ref: https://developers.facebook.com/docs/instant-articles/reference/social
	 *
	 * @param string   $html    HTML markup to be embeded into post sontent.
	 * @param string   $url     The attempted embed URL.
	 * @param array    $attr    An array of shortcode attributes.
	 * @param int|null $post_ID Post ID for which embeded URLs are processed.
	 *
	 * @return string           FB IA formatted markup for social embeds.
	 */
	public function fb_formatted_social_embeds( $html, $url, $attr, $post_ID = null ) {

		return '<figure class="op-social"><iframe>' . $html . '</iframe></figure>';
	}

	/**
	 * Generates HTML string for DOM node object.
	 *
	 * @param DOMNode $node Node object to generate the HTML string for.
	 *
	 * @return string       HTML string/markup for supplied DOM node.
	 */
	protected function get_html_for_node( \DOMNode $node ) {

		$node_html  = '';
		foreach ( $node->childNodes as $child_node ) {
			$node_html .= $child_node->ownerDocument->saveHTML( $child_node );
		}

		return $node_html;
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
