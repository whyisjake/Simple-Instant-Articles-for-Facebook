<?php
/*
Plugin Name: Simple Facebook Instant Articles
Version: 0.5.0
Description: Add support to Facebook Instant Articles
Author: Jake Spurlock, Human Made Limited
*/

class Simple_FB_Instant_Articles {

	/**
	 * The one instance of Simple_FB_Instant_Articles.
	 *
	 * @var Simple_FB_Instant_Articles
	 */
	private static $instance;

	/**
	 * Endpoint query var.
	 */
	private $token = 'fb';

	/**
	 * Endpoint query var.
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
	 * Template Path.
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
		$this->version       = $version;
		$this->dir           = dirname( $file );
		$this->file          = $file;
		$this->template_path = trailingslashit( $this->dir ) . 'templates/';
		$this->home_url      = trailingslashit( home_url() );
	}

	/**
	 * Kickoff method.
	 *
	 * @return void
	 */
	public function init() {
		add_rewrite_endpoint( $this->endpoint, EP_PERMALINK );
	}

	/**
	 * Add the template redirect.
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
	 */
	public function template_redirect() {
		$this->render( get_queried_object_id() );
		exit;
	}

	/**
	 * Based on the post ID, render the Instant Articles page.
	 *
	 * @param  int   $post_id Post ID.
	 *
	 * @return void
	 */
	public function render( $post_id ) {
		do_action( 'simple_fb_pre_render', $post_id );
		include( apply_filters( 'simple_fb_article_template_file', $this->template_path . '/article.php' ) );
	}

	/**
	 * Register FB feed.
	 *
	 * @return void
	 */
	public function add_feed() {
		$feed_slug = apply_filters( 'simple_fb_feed_slug', $this->token );
		add_feed( $feed_slug, array( $this, 'feed_template' ) );
	}

	/**
	 * Load feed template.
	 *
	 * @return void
	 */
	public function feed_template() {
		global $wp_query;

		// Prevent 404 on feed
		$wp_query->is_404 = false;
		status_header( 200 );

		// Any functions hooked in here must NOT output any data or else feed will break.
		do_action( 'simple_fb_before_feed' );

		$template = trailingslashit( $this->template_path ) . 'feed.php';

		if ( 0 === validate_file( $template ) ) {
			require( $template );
		}

		// Any functions hooked in here must NOT output any data or else feed will break.
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
		add_shortcode( 'caption', array( $this, 'caption_shortcode' ) );

		// Shortcodes - custom galleries.
		add_shortcode( 'sigallery', array( $this, 'api_galleries_shortcode' ) );

		// Shortcodes - remove related lawrence content.
		add_shortcode( 'lawrence-related', '__return_empty_string' );
		add_shortcode( 'lawrence-auto-related', '__return_empty_string' );

		// Render social embeds into FB IA format.
		add_filter( 'embed_handler_html', array( $this, 'reformat_social_embed' ), 10, 3 );
		add_filter( 'embed_oembed_html', array( $this, 'reformat_social_embed' ), 10, 4 );

		// Modify the content.
		add_filter( 'the_content', array( $this, 'reformat_post_content' ), 1000 );
		add_action( 'the_content', array( $this, 'append_google_analytics_code' ), 1100 );
		add_action( 'the_content', array( $this, 'append_ad_code' ), 1100 );

		// Post URL for the feed.
		add_filter( 'the_permalink_rss', array( $this, 'rss_permalink' ) );

		// Render post content into FB IA format - using DOM object.
		add_action( 'simple_fb_reformat_post_content', array( $this, 'render_pull_quotes' ), 10, 2 );
		add_action( 'simple_fb_reformat_post_content', array( $this, 'render_images' ), 10, 2 );
		add_action( 'simple_fb_reformat_post_content', array( $this, 'cleanup_empty_p' ), 10, 2 );
		add_action( 'simple_fb_reformat_post_content', array( $this, 'fix_headings' ), 10, 2 );

	}

	public function rss_permalink( $link ) {

		return trailingslashit( $link ) . $this->endpoint;
	}

	/**
	 * Gallery Shortcode.
	 *
	 * @param  array     $atts       Array of attributes passed to shortcode.
	 * @param  string    $content    The content passed to the shortcode.
	 *
	 * @return string                The generated content.
	 */
	public function gallery_shortcode( $atts, $content = '' ) {

		// Get the image IDs.
		$ids = array_map( 'absint', explode( ',', $atts['ids'] ) );

		ob_start();

		echo '<figure class="op-slideshow">';
		foreach ( $ids as $id ) {
			$this->render_image_markup( $id, $this->get_image_caption( $id ) );
		}
		echo '</figure>';

		return ob_get_clean();

	}

	/**
	 * Caption shortcode.
	 *
	 * Overwrite WP native shortcode.
	 * Format images in caption shortcodes into FB IA format.
	 *
	 * @param array  $atts    Array of attributes passed to shortcode.
	 * @param string $content The content passed to the shortcode.
	 *
	 * @return string|void    FB IA formatted images markup.
	 */
	public function caption_shortcode( $atts, $content = '' ) {

		// Get attachment ID from the shortcode attribute.
		$attachment_id = isset( $atts['id'] ) ? (int) str_replace( 'attachment_', '', $atts['id'] ) : '';

		if ( ! $attachment_id ) {
			return;
		}

		// Get image caption.
		$reg_ex = preg_match( '#^<img.*?\/>(.*)$#', trim( $content ), $matches );
		$caption = isset( $matches[1] ) ? trim( $matches[1] ) : '';

		ob_start();
		$this->render_image_markup( $attachment_id, $caption );
		return ob_get_clean();

	}

	/**
	 * Outputs image markup in FB IA format.
	 *
	 * @param int    $image_id Image ID to output in FB IA format.
	 * @param string $caption  Image caption to display in FB IA format.
	 */
	public function render_image_markup( $image_id, $caption = '' ) {

		$image = wp_get_attachment_image_src( $image_id, $this->image_size );

		if ( ! $image ) {
			return;
		}

		$template = trailingslashit( $this->template_path ) . 'image.php';
		$src      = $image[0] ;

		require( $template );

	}

	public function get_image_caption( $id ) {

		$attachment_post = get_post( $id );

		// Stop if - attachment post not found or caption is empty.
		if ( $attachment_post && $attachment_post->post_excerpt ) {
			return trim( $attachment_post->post_excerpt );
		}

	}

	/**
	 * Convert custom gallery shortcode - sigallery,
	 * into FB IA image gallery format.
	 *
	 * @param $atts        Array of attributes passed to shortcode.
	 *
	 * @return string|void Return FB IA image gallery markup for sigallery shortcode,
	 *                     On error - nothing.
	 */
	public function api_galleries_shortcode( $atts ) {

		// Stop - if gallery ID is empty.
		if ( ! $atts['id'] ) {
			return;
		}

		// Stop - if can't get the API gallery.
		if ( ! $gallery = \USAT\API_Galleries\get_gallery( $atts['id'] ) ) {
			return;
		}

		// Display API gallery in FB IA format.
		ob_start();
		?>

		<figure class="op-slideshow">

			<?php

			foreach ( $gallery->images as $key => $image ) {
				$this->render_image_markup( $image->url, $image->custom_caption );
			}

			?>

			<?php if ( $atts['title'] ) : ?>
				<figcaption><h1><?php echo esc_html( $atts['title'] ); ?></h1></figcaption>
			<?php endif;?>

		</figure>

		<?php
		return ob_get_clean();
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
	public function reformat_social_embed( $html, $url, $attr, $post_ID = null ) {

		return '<figure class="op-social"><iframe>' . $html . '</iframe></figure>';
	}

	/**
	 * Setup DOM and XPATH objects for formatting post content.
	 * Introduces `simple_fb_reformat_post_content` filter, so that post content
	 * can be formatted as necessary and dom/xpath objects re-used.
	 *
	 * @param $post_content Post content that needs to be formatted into FB IA format.
	 *
	 * @return string|void  Post content in FB IA format if dom is generated for post content,
	 *                      Otherwise, nothing.
	 */
	public function reformat_post_content( $post_content ) {

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
		do_action_ref_array( 'simple_fb_reformat_post_content', array( &$dom, &$xpath ) );

		// Get the FB IA formatted post content HTML.
		$body_node = $dom->getElementsByTagName( 'body' )->item( 0 );

		return $this->get_node_inner_html( $body_node );
	}

	/**
	 * Renders pull quotes into FB IA format.
	 * Ref: https://developers.facebook.com/docs/instant-articles/reference/pullquote
	 *
	 * @param DOMDocument $dom   DOM object generated for post content.
	 * @param DOMXPath    $xpath XPATH object generated for post content.
	 */
	public function render_pull_quotes( \DOMDocument &$dom, \DOMXPath &$xpath ) {

		// Pull quotes - with <cite> element.
		foreach ( $xpath->query( '//blockquote[descendant::cite]' ) as $node ) {

			// Get and remove <cite> element.
			$cite = $node->getElementsByTagName( 'cite' )->item( 0 );
			@$cite->parentNode->removeChild( $cite );

			$pull_quote_html = $this->get_node_inner_html( $node );

			// FB IA pull quote format.
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

			// Replace original pull quotes with FB IA marked up ones.
			$new_node = $dom->createDocumentFragment();
			$new_node->appendXML( $fb_pull_quote );
			$node->parentNode->replaceChild( $new_node, $node );
		}
	}

	/**
	 * Reformat images into FB IA format.
	 *
	 * Ensure they are child of <figure>.
	 * Consider <img> with parent <figure> already been converted to FB IA format.
	 *
	 * Ref: https://developers.facebook.com/docs/instant-articles/reference/image
	 *
	 * @param DOMDocument $dom   DOM object generated for post content.
	 * @param DOMXPath    $xpath XPATH object generated for post content.
	 */
	public function render_images( \DOMDocument &$dom, \DOMXPath &$xpath ) {

		// Get all images that are not children of figure already.
		foreach ( $xpath->query( '//img[not(parent::figure)]' ) as $node ) {

			$figure   = $dom->createElement( 'figure' );
			$top_node = $node;

			// If image node is not a direct child of the body, we need to move it there.
			// Recurse up the tree looking for highest level parent/grandparent node.
			while ( $top_node->parentNode && 'body' !== $top_node->parentNode->nodeName ) {
				$top_node = $top_node->parentNode;
			}

			// Insert after the parent/grandparent node.
			// Workaround to handle the fact only insertBefore exists.
			try {
				$top_node->parentNode->insertBefore( $figure, $top_node->nextSibling );
			} catch( \Exception $e ) {
				$top_node->parentNode->appendChild( $figure );
			}

			$figure->appendChild( $node );
		}
	}

	/**
	 * Remove all empty <p> elements.
	 *
	 * @param  \DOMDocument &$dom   DOM object generated for post content.
	 * @param  \DOMXPath    &$xpath XPATH object generated for post content.
	 *
	 * @return void
	 */
	public function cleanup_empty_p( \DOMDocument &$dom, \DOMXPath &$xpath ) {

		foreach ( $xpath->query( '//p[not(node())]' ) as $node ) {
			$node->parentNode->removeChild( $node );
		}
	}

	/**
	 * Facebook throws a warning for all headings below h2.
	 *
	 * Replace with h2s.
	 *
	 * @param  \DOMDocument &$dom   DOM object generated for post content.
	 * @param  \DOMXPath    &$xpath XPATH object generated for post content.
	 *
	 * @return void
	 */
	public function fix_headings( \DOMDocument &$dom, \DOMXPath &$xpath ) {

		$headings = array( 'h3', 'h4', 'h5', 'h6' );

		foreach ( $headings as $heading_tag ) {

			$headings = $dom->getElementsByTagName( $heading_tag );

			while ( $headings->length ) {

				$node = $headings->item( 0 );
				$h2   = $dom->createElement( 'h2' );

				while ( $node->childNodes->length > 0 ) {
					$h2->appendChild( $node->childNodes->item( 0 ) );
				}

				$node->parentNode->replaceChild( $h2, $node );

			}
		}
	}

	/**
	 * Append Google Analytics (GA) script in the FB IA format
	 * to the post content.
	 *
	 * @param string $post_content Post content.
	 *
	 * @return string Post content with added GA script in FB IA format.
	 */
	public function append_google_analytics_code( $post_content ) {

		$post_content .= $this->get_google_analytics_code();
		return $post_content;
	}

	/**
	 * Get GA script in the FB IA format.
	 *
	 * Ref: https://developers.facebook.com/docs/instant-articles/reference/analytics
	 *
	 * @return string GA script in FB IA format.
	 */
	public function get_google_analytics_code() {

		$analytics_template_file = trailingslashit( $this->template_path ) . 'script-ga.php';
		$ga_profile_id           = get_option( 'lawrence_ga_tracking_id' );

		if ( ! $ga_profile_id ) {
			return;
		}

		ob_start();
		require( $analytics_template_file );
		return ob_get_clean();

	}

	/**
	 * Append Ad script in the FB IA format to the post content.
	 *
	 * @param string $post_content Post content.
	 *
	 * @return string Post content with added ad script in FB IA format.
	 */
	public function append_ad_code( $post_content ) {

		$post_content .= $this->get_ad_code();
		return $post_content;
	}

	/**
	 * Get Ad code in the FB IA format.
	 *
	 * @return string Ad script in FB IA format.
	 */
	public function get_ad_code() {

		ob_start();
		require( trailingslashit( $this->template_path ) . 'ad.php' );
		return ob_get_clean();
	}

	/**
	 * Get Ad targeting args.
	 *
	 * @return array Targeting params.
	 */
	protected function get_ad_targeting_params() {

		// Note use of get_the_terms + wp_list_pluck as these are cached ang get_the_* is not.
		$tags    = wp_list_pluck( (array) get_the_terms( get_the_ID(), 'post_tag' ), 'name' );
		$cats    = wp_list_pluck( (array) get_the_terms( get_the_ID(), 'category' ), 'name' );
		$authors = wp_list_pluck( get_coauthors( get_the_ID() ), 'display_name' );

		$url_bits = parse_url( home_url() );

		$targeting_params = array(
			// Merge, Remove dupes, and fix keys order.
			'kw'         => array_values( array_unique( array_merge( $cats, $tags, $authors ) ) ),
			'category'   => $cats,
			'domainName' => isset( $url_bits['host'] ) ? $url_bits['host'] : '',
		);

		return $targeting_params;
	}

	/**
	 * Output Ad targeting JS.
	 *
	 * @return void
	 */
	public function ad_targeting_js() {

		foreach ( $this->get_ad_targeting_params() as $key => $value ) {
			printf( ".setTargeting( '%s', %s )", esc_js( $key ), wp_json_encode( $value ) );
		}
	}

	/**
	 * Generates HTML string for DOM node's inner markup.
	 *
	 * @param DOMNode $node DOM Node object to generate the HTML string for
	 *                      its inner markup.
	 *
	 * @return string       Inner HTML markup for the supplied DOM node.
	 */
	protected function get_node_inner_html( \DOMNode $node ) {

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

// Kick off the plugin on init.
simple_fb_instant_articles( __FILE__, '0.5.0' );
