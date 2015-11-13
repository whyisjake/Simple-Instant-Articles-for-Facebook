<?php
/*
 * Shortcodes
 */
class Simple_FB_Shortcodes extends Simple_FB_Instant_Articles {
	/**
	 * The one instance of Simple_FB_Instant_Articles.
	 *
	 * @var Simple_FB_Instant_Articles
	 */
	private static $instance;

	/**
	 * Instantiate or return the one Simple_FB_Instant_Articles instance.
	 *
	 * @return Simple_FB_Instant_Articles
	 */
	public static function instance( $file, $version ) {
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
		add_filter( 'simple_fb_pre_render', array( $this, 'register_shortcodes' ) );
		add_filter( 'simple_fb_before_feed', array( $this, 'register_shortcodes' ) );
	}

	public function register_shortcodes() {
		add_shortcode( 'gallery', array( $this, 'gallery' ) );
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
				<?php $image = wp_get_attachment_image_src( $id, 'large' ); ?>
				<?php $url   = ( $image[0] ); ?>
				<figure>
					<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( get_the_title( $id ) ); ?>">
					<?php $caption = get_post_field( 'post_excerpt', $id ); ?>
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
function simple_fb_shortcodes( $file, $version ) {
	return Simple_FB_Shortcodes::instance( $file, $version );
}

// Kick off the plugin on init
simple_fb_shortcodes( __FILE__, '0.5.0' );