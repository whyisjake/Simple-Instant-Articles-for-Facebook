<?php

global $wp_scripts;

if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}

// Clone the global $wp_scripts object.
// Reset it, ready for outputting.
$_scripts = clone $wp_scripts;
$_scripts->done = array();

?>

<figure class="op-tracker">
<h1>Simple Reach </h1>
	<iframe>
		<?php lawrence_simple_reach_analytics(); ?>
		<?php $_scripts->do_items( 'simple-reach-script' ); ?>
	</iframe>
</figure>
