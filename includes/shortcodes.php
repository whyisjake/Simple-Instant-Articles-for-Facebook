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
		add_shortcode( 'caption', array( $this, 'caption' ) );
		add_shortcode( 'audio',   array( $this, 'audio' ) );
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

	/**
	 * Builds the Caption shortcode output.
	 *
	 * Allows a plugin to replace the content that would otherwise be returned. The
	 * filter is 'img_caption_shortcode' and passes an empty string, the attr
	 * parameter and the content parameter values.
	 *
	 * The supported attributes for the shortcode are 'id', 'align', 'width', and
	 * 'caption'.
	 *
	 * @since 2.6.0
	 *
	 * @param array  $attr {
	 *     Attributes of the caption shortcode.
	 *
	 *     @type string $id      ID of the div element for the caption.
	 *     @type string $align   Class name that aligns the caption. Default 'alignnone'. Accepts 'alignleft',
	 *                           'aligncenter', alignright', 'alignnone'.
	 *     @type int    $width   The width of the caption, in pixels.
	 *     @type string $caption The caption text.
	 *     @type string $class   Additional class name(s) added to the caption container.
	 * }
	 * @param string $content Shortcode content.
	 * @return string HTML content to display the caption.
	 */
	public function caption( $attr, $content = null ) {
		$atts = shortcode_atts( array(
			'id'	  => '',
			'align'	  => 'alignnone',
			'width'	  => '',
			'caption' => '',
			'class'   => '',
		), $attr, 'caption' );

		if ( ! empty( $atts['id'] ) )
			$atts['id'] = 'id="' . esc_attr( sanitize_html_class( $atts['id'] ) ) . '" ';

		$class = trim( 'wp-caption ' . $atts['align'] . ' ' . $atts['class'] );

		return '<figure ' . $atts['id'] . 'class="' . esc_attr( $class ) . '">' . do_shortcode( $content ) . '<figcaption class="wp-caption-text">' . $atts['caption'] . '</figcaption></figure>';

	}

	/**
	 * Builds the Audio shortcode output.
	 *
	 * This implements the functionality of the Audio Shortcode for displaying
	 * WordPress mp3s in a post.
	 *
	 * @param array  $attr {
	 *     Attributes of the audio shortcode.
	 *
	 *     @type string $src      URL to the source of the audio file. Default empty.
	 *     @type string $loop     The 'loop' attribute for the `<audio>` element. Default empty.
	 *     @type string $autoplay The 'autoplay' attribute for the `<audio>` element. Default empty.
	 *     @type string $preload  The 'preload' attribute for the `<audio>` element. Default empty.
	 *     @type string $class    The 'class' attribute for the `<audio>` element. Default 'wp-audio-shortcode'.
	 *     @type string $style    The 'style' attribute for the `<audio>` element. Default 'width: 100%'.
	 * }
	 * @param string $content Shortcode content.
	 * @return string|void HTML content to display audio.
	 */
	function audio( $attr, $content = '' ) {

		$post_id = get_post() ? get_the_ID() : 0;

		/**
		 * Filter the default audio shortcode output.
		 *
		 * If the filtered output isn't empty, it will be used instead of generating the default audio template.
		 *
		 * @since 3.6.0
		 *
		 * @param string $html     Empty variable to be replaced with shortcode markup.
		 * @param array  $attr     Attributes of the shortcode. @see wp_audio_shortcode()
		 * @param string $content  Shortcode content.
		 */
		$override = apply_filters( 'simple_fb_audio_shortcode_override', '', $attr, $content );
		if ( '' !== $override ) {
			return $override;
		}

		$audio = null;

		$default_types = wp_get_audio_extensions();
		$defaults_atts = array(
			'src'      => '',
			'loop'     => '',
			'autoplay' => '',
			'preload'  => 'none'
		);
		foreach ( $default_types as $type ) {
			$defaults_atts[$type] = '';
		}

		$atts = shortcode_atts( $defaults_atts, $attr, 'audio' );

		$primary = false;
		if ( ! empty( $atts['src'] ) ) {
			$type = wp_check_filetype( $atts['src'], wp_get_mime_types() );
			if ( ! in_array( strtolower( $type['ext'] ), $default_types ) ) {
				return sprintf( '<a class="wp-embedded-audio" href="%s">%s</a>', esc_url( $atts['src'] ), esc_html( $atts['src'] ) );
			}
			$primary = true;
			array_unshift( $default_types, 'src' );
		} else {
			foreach ( $default_types as $ext ) {
				if ( ! empty( $atts[ $ext ] ) ) {
					$type = wp_check_filetype( $atts[ $ext ], wp_get_mime_types() );
					if ( strtolower( $type['ext'] ) === $ext ) {
						$primary = true;
					}
				}
			}
		}

		if ( ! $primary ) {
			$audios = get_attached_media( 'audio', $post_id );
			if ( empty( $audios ) ) {
				return;
			}

			$audio = reset( $audios );
			$atts['src'] = wp_get_attachment_url( $audio->ID );
			if ( empty( $atts['src'] ) ) {
				return;
			}

			array_unshift( $default_types, 'src' );
		}

		/**
		 * Filter the class attribute for the audio shortcode output container.
		 *
		 * @since 3.6.0
		 *
		 * @param string $class CSS class or list of space-separated classes.
		 */
		$html_atts = array(
			'class'    => apply_filters( 'wp_audio_shortcode_class', 'wp-audio-shortcode' ),
			'id'       => sprintf( 'audio-%d', $post_id ),
			'loop'     => wp_validate_boolean( $atts['loop'] ),
			'autoplay' => wp_validate_boolean( $atts['autoplay'] ),
			'preload'  => $atts['preload'],
			'style'    => 'width: 100%; visibility: hidden;',
		);

		// These ones should just be omitted altogether if they are blank
		foreach ( array( 'loop', 'autoplay', 'preload' ) as $a ) {
			if ( empty( $html_atts[$a] ) ) {
				unset( $html_atts[$a] );
			}
		}

		$attr_strings = array();
		foreach ( $html_atts as $k => $v ) {
			$attr_strings[] = $k . '="' . esc_attr( $v ) . '"';
		}

		$html = '';
		$html .= sprintf( '<audio %s controls="controls">', join( ' ', $attr_strings ) );

		$fileurl = '';
		$source = '<source type="%s" src="%s" />';
		foreach ( $default_types as $fallback ) {
			if ( ! empty( $atts[ $fallback ] ) ) {
				if ( empty( $fileurl ) ) {
					$fileurl = $atts[ $fallback ];
				}
				$type = wp_check_filetype( $atts[ $fallback ], wp_get_mime_types() );
				$url = add_query_arg( '_', $atts[ $fallback ] );
				$html .= sprintf( $source, $type['type'], esc_url( $url ) );
			}
		}

		$html .= '</audio>';

		/**
		 * Filter the audio shortcode output.
		 *
		 * @since 3.6.0
		 *
		 * @param string $html    Audio shortcode HTML output.
		 * @param array  $atts    Array of audio shortcode attributes.
		 * @param string $audio   Audio file.
		 * @param int    $post_id Post ID.
		 */
		return apply_filters( 'wp_audio_shortcode', $html, $atts, $audio, $post_id );
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