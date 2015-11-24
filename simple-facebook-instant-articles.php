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
		add_action( 'pre_get_posts', array( $this, 'customise_feed_query' ) );

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

		if ( have_posts() ) {
			the_post();

			$template = apply_filters( 'simple_fb_article_template_file', $this->template_path . '/article.php' );

			if ( 0 === validate_file( $template ) ) {
				require( $template );
			}
		}
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
	 * Set WP query variables for FB IA feed, so we can customise
	 * what posts are considered for the feed.
	 *
	 * @param $query WP_Query object.
	 */
	public function customise_feed_query( $query ) {

		$feed_slug = apply_filters( 'simple_fb_feed_slug', $this->token );

		// Customise FB IA feed query.
		if ( $query->is_main_query() && $query->is_feed( $feed_slug ) ) {

			$query->set( 'posts_per_rss', 25 );
			$query->set( 'orderby', 'modified' );

			do_action( 'simple_fb_pre_get_posts', $query );
		}
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

		// Try and fix misc shortcodes.
		$this->make_shortcode_figure_op_social( 'protected-iframe' );

		// Shortcodes - custom galleries.
		add_shortcode( 'sigallery', array( $this, 'api_galleries_shortcode' ) );

		// Shortcodes - remove related lawrence content.
		add_shortcode( 'lawrence-related', '__return_empty_string' );
		add_shortcode( 'lawrence-auto-related', '__return_empty_string' );

		// Render social embeds into FB IA format.
		add_filter( 'embed_handler_html', array( $this, 'reformat_social_embed' ), 10, 3 );
		add_filter( 'embed_oembed_html', array( $this, 'reformat_social_embed' ), 10, 4 );

		// Fix embeds that need some extra attention.
		add_filter( 'embed_handler_html', array( $this, 'load_facebook_scripts' ), 5, 3 );
		add_filter( 'embed_brightcove', array( $this, 'load_brightcove_scripts' ), 10, 4 );

		// Modify the content.
		add_filter( 'the_content', array( $this, 'reformat_post_content' ), 1000 );
		add_action( 'the_content', array( $this, 'append_google_analytics_code' ), 1100 );
		add_action( 'the_content', array( $this, 'append_ad_code' ), 1100 );
		add_action( 'the_content', array( $this, 'append_omniture_code' ), 1100 );
		add_action( 'the_content', array( $this, 'prepend_full_width_media' ), 1100 );

		// Post URL for the feed.
		add_filter( 'the_permalink_rss', array( $this, 'rss_permalink' ) );

		// Render post content into FB IA format - using DOM object.
		add_action( 'simple_fb_reformat_post_content', array( $this, 'render_pull_quotes' ), 10, 2 );
		add_action( 'simple_fb_reformat_post_content', array( $this, 'render_images' ), 10, 2 );
		add_action( 'simple_fb_reformat_post_content', array( $this, 'cleanup_empty_p' ), 10, 2 );
		add_action( 'simple_fb_reformat_post_content', array( $this, 'fix_headings' ), 10, 2 );
		add_action( 'simple_fb_reformat_post_content', array( $this, 'fix_social_embed' ), 1000, 2 );
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
		$attachment_id = isset( $atts['id'] ) ? (int) str_replace( 'attachment_', '', $atts['id'] ) : null;

		if ( ! $attachment_id ) {
			return;
		}

		// Get image caption.
		$reg_ex  = preg_match( '#^<img.*?\/>(.*)$#', trim( $content ), $matches );
		$caption = isset( $matches[1] ) ? trim( $matches[1] ) : '';

		ob_start();
		$this->render_image_markup( $attachment_id, $caption );
		return ob_get_clean();
	}

	/**
	 * Outputs image markup in FB IA format.
	 *
	 * @param int|string $src     Image ID or source to output in FB IA format.
	 * @param string     $caption Image caption to display in FB IA format.
	 */
	public function render_image_markup( $src, $caption = '' ) {

		// Handle passing image ID.
		if ( is_numeric( $src ) ) {
			$image = wp_get_attachment_image_src( $src, $this->image_size );
			$src   = $image ? $image[0] : null;
		}

		if ( empty( $src ) ) {
			return;
		}

		$template = trailingslashit( $this->template_path ) . 'image.php';
		require( $template );
	}

	/**
	 * Get caption for image.
	 *
	 * @param int $id Attachment/image ID.
	 *
	 * @return string Attachment/image caption, if specified.
	 */
	public function get_image_caption( $id ) {

		$attachment_post = get_post( $id );

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
		if ( empty( $atts['id'] ) ) {
			return;
		}

		$gallery = null;

		if ( function_exists( 'usat_newscred_get_gallery' ) ) {
			$gallery = usat_newscred_get_gallery( $atts['id'], 'sigallery' );
		} elseif ( function_exists( '\USAT\API_Galleries\get_gallery' ) ) {
			$gallery = \USAT\API_Galleries\get_gallery( $atts['id'] );
		}

		if ( ! $gallery ) {
			return;
		}

		ob_start();

		echo '<figure class="op-slideshow">';

		foreach ( $gallery->images as $key => $image ) {

			$caption = $image->custom_caption ? $image->custom_caption : $image->caption;

			$this->render_image_markup( $image->url, $caption );
		}

		if ( $atts['title'] ) {
			printf( '<figcaption><h1>%s</h1></figcaption>', esc_html( $atts['title'] ) );
		}

		echo '</figure>';

		return ob_get_clean();
	}

	/**
	 * Render social embeds into FB IA format.
	 *
	 * Social embeds Ref: https://developers.facebook.com/docs/instant-articles/reference/social
	 *
	 * @param string   $html    HTML markup to be embeded into post content.
	 * @param string   $url     The attempted embed URL.
	 * @param array    $attr    An array of shortcode attributes.
	 * @param int|null $post_ID Post ID for which embeded URLs are processed.
	 *
	 * @return string           FB IA formatted markup for social embeds.
	 */
	public function reformat_social_embed( $html, $url, $attr, $post_ID = null ) {

		// Stop - if embed markup starts with `<figure class="op`,
		// which means it's already been converted to FB IA format.
		if ( false !== strpos( $html, '<figure class="op' ) ) {
			return $html;
		}

		return sprintf( '<figure class="op-social"><iframe>%s</iframe></figure>', $html );
	}

	/**
	 * Some markup fixes for embeds.
	 *
	 * @param DOMDocument $dom   DOM object generated for post content.
	 * @param DOMXPath    $xpath DOMXpath object generated for post content.
	 *
	 * @return void
	 */
	public function fix_social_embed( \DOMDocument $dom, \DOMXPath $xpath ) {

		// Matches all divs and spans that have class like ~=embed- and are descendants of figure.
		// Unwrap, or remove if no children.
		$items = $xpath->query( '//figure[contains(@class, \'op-social\')]//*[self::span or self::div][contains(@class, \'embed-\')]' );

		foreach ( $items as $node ) {

			// Remove empty <span> and <div> nodes.
			if ( ! $node->hasChildNodes() ) {
				$node->parentNode->removeChild( $node );
			}

			// Insert inner content of <span> and <div> nodes just before themselves,
			// So they are siblings (on the same level).
			while ( $node->childNodes->length > 0 ) {
				$node->parentNode->insertBefore(
					$node->childNodes->item( $node->childNodes->length - 1 ),
					$node
				);
			}

			// Remove original <span> and <div> elements.
			$node->parentNode->removeChild( $node );
		}

		// If the op-social embed iframe is the only child of another iframe, unwrap.
		foreach ( $xpath->query( '//figure[contains(@class, \'op-social\')]/iframe/iframe' ) as $node ) {
			if ( 1 === $node->parentNode->childNodes->length ) {

				$outer_iframe = $node->parentNode;

				// Insert inner <iframe> before outer <iframe> element - so they are both children of <figure>.
				$node->parentNode->parentNode->insertBefore( $node, $node->parentNode );

				// Remove outer <iframe>.
				$outer_iframe->parentNode->removeChild( $outer_iframe );
			}
		}
	}

	/**
	 * Ensure facebook scripts are loaded for facebook embeds.
	 *
	 * @param string   $html    HTML markup to be embeded into post content.
	 * @param string   $url     The attempted embed URL.
	 * @param array    $attr    An array of shortcode attributes.
	 *
	 * @return string           Facebook embed code with required script.
	 */
	public function load_facebook_scripts( $html, $url, $attr ) {

		// If the embed is any kind of facebook embed, try and load the Facebook SDK.
		// Can't use precise regex, as we don't really know what WP.com is doing here!
		// Check this hasn't been added already.
		if ( false !== strpos( $url, 'facebook.com' ) && false === strpos( $html, 'connect.facebook.net' ) ) {
			$html .= '<div id="fb-root"></div> <script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_US/all.js#xfbml=1"; fjs.parentNode.insertBefore(js, fjs); }(document, "script", "facebook-jssdk"));</script>';
		}

		return $html;
	}

	/**
	 * Ensure brightcove scripts are loaded.
	 *
	 * @param  string $embed   Embed markup.
	 * @param  string $matches Embed url regex matches.
	 * @param  array  $attr    Attr.
	 * @param  string $url     URL.
	 *
	 * @return string Embed markup.
	 */
	public function load_brightcove_scripts( $embed, $matches, $attr, $url ) {

		ob_start();
		do_action( 'wp_enqueue_scripts' );
		wp_print_scripts( 'brightcove' );
		$embed .= ob_get_clean();

		return $embed;
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

		$dom = new \DomDocument();

		// Parse post content to generate DOM document.
		// Use loadHTML as it doesn't need to be well-formed to load.
		// Charset meta tag required to ensure it correctly detects the encoding.
		@$dom->loadHTML( sprintf(
			'<html><head><meta http-equiv="Content-Type" content="%s" charset="%s"/></head><body>%s</body></html>',
			get_bloginfo( 'html_type' ),
			get_bloginfo( 'charset' ),
			$post_content
		) );

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
	 * Append the omniture code.
	 *
	 * @param string $content Post content HTML string.
	 * @param mixed $post_id  Post ID.
	 *
	 * @return string $content Post content HTML string.
	 */
	function append_omniture_code( $content, $post_id = null ) {
		$post_id  = $post_id ?: get_the_ID();
		return $content . $this->get_omniture_code( $post_id );
	}

	/**
	 * Get the omniture code markup.
	 *
	 * @param mixed $post_id  Post ID.
	 *
	 * @return string HTML string.
	 */
	function get_omniture_code( $post_id ) {

		$tags     = wp_list_pluck( (array) get_the_terms( $post_id, 'post_tag' ), 'name' );
		$cats     = wp_list_pluck( (array) get_the_terms( $post_id, 'category' ), 'name' );
		$keywords = array_values( array_unique( array_merge( $cats, $tags ) ) );

		$omniture_data = array(
			'cobrand_vendor'   => 'facebookinstantarticle',
			'assetid'          => $post_id,
			'byline'           => coauthors( ',', ' and ', null, null, false ),
			'contenttype'      => 'text',
			'cst'              => 'sports/ftw',
			'eventtype'        => 'page:load',
			'linkTrackVars'    => 'prop1',
			'ssts'             => 'sports/ftw',
			'pathName'         => get_permalink( $post_id ),
			'taxonomykeywords' => implode( ',', $keywords ),
			'topic'            => 'sports',
			'videoincluded'    => 'No'
		);

		$url_bits = parse_url( home_url() );

		ob_start();
		require( trailingslashit( $this->template_path ) . 'omniture.php' );
		return ob_get_clean();
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
	 * Prepend full width media.
	 *
	 * This functionality is mostly a duplicate of parts/single/format-video.
	 *
	 * @param  string $content Post content.
	 * @param  mixed  $post_id Post id.
	 *
	 * @return string Post content.
	 */
	public function prepend_full_width_media( $content, $post_id = null ) {
		global $wp_embed;

		$post_id       = $post_id ?: get_the_ID();
		$url           = get_post_meta( $post_id, '_format_video_embed', true );
		$path_info     = pathinfo( $url );
		$image_formats = array( 'png', 'jpg', 'jpeg', 'tiff', 'gif' );
		$is_image      = ! empty( $path_info['extension'] ) && in_array( strtolower( $path_info['extension'] ), $image_formats );
		$media_html    = '';

		if ( $url && $is_image ) {
			$media_html = sprintf( '<figure><img src="%s"/></figure>', esc_url( $url ) );
		} elseif ( $url && ! $is_image ) {
			$media_html = $wp_embed->autoembed( $url );
		}

		return $media_html . $content;
	}

	/**
	 * Wrap shortcode output in figure op-social + iframe markup to sandbox functionality.
	 *
	 * Used to handle generic shortcodes that we don't really want to mess with might be broken.
	 *
	 * @param  string $shortcode_tag Shortcode.
	 * @return void
	 */
	protected function make_shortcode_figure_op_social( $shortcode_tag ) {
		global $shortcode_tags;

		if ( ! isset( $shortcode_tags[ $shortcode_tag ] ) ) {
			return;
		}

		$old_callback = $shortcode_tags[ $shortcode_tag ];

		$shortcode_tags[ $shortcode_tag ] = function() use ( $old_callback ) {

			$r = '<figure class="op-social"><iframe>';
			$r .= call_user_func_array( $old_callback, func_get_args() );
			$r .= '</iframe></figure>';
			return $r;
		};
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
