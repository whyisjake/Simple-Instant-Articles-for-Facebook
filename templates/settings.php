<?php
class Simple_FB_Instant_Articles_Options extends Simple_FB_Instant_Articles {
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		// This page will be under "Settings"
		add_options_page(
			'Simple Facebook Instant Articles Settings',
			'Instant Articles',
			'manage_options',
			'fb-instant-options',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		// Set class property
		$this->options = get_option( 'fb_instant' );
		?>
		<div class="wrap">
			<h2>Simple Instant Articles for Facebook</h2>
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'fb_instant_group' );
				do_settings_sections( 'fb-instant-options' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {
		register_setting(
			'fb_instant_group', // Option group
			'fb_instant', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'setting_section_id', // ID
			'Publisher Settings', // Title
			array( $this, 'print_section_info' ), // Callback
			'fb-instant-options' // Page
		);

		add_settings_field(
			'page_id_number', // ID
			'Publisher ID Number', // Title
			array( $this, 'page_id_number_callback' ), // Callback
			'fb-instant-options', // Page
			'setting_section_id' // Section
		);

	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input ) {
		$new_input = array();
		if( isset( $input['page_id_number'] ) )
			$new_input['page_id_number'] = absint( $input['page_id_number'] );

		if( isset( $input['title'] ) )
			$new_input['title'] = sanitize_text_field( $input['title'] );

		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {
		printf( 'Hello! Welcome to Simple Instant Articles for Facebook. First things first, if you are wondering where to find the RSS feed, you can <a href="%s">find it here</a>. If you need to add your publisher ID to the head of the document, you can do that here:', esc_url( home_url( 'feed/' . apply_filters( 'simple_fb_feed_slug', 'fb' ) ) ) );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function page_id_number_callback() {
		printf(
			'<input type="text" id="page_id_number" name="fb_instant[page_id_number]" value="%s" />',
			isset( $this->options['page_id_number'] ) ? esc_attr( $this->options['page_id_number']) : ''
		);
	}

}

if( is_admin() )
	$my_settings_page = new Simple_FB_Instant_Articles_Options();