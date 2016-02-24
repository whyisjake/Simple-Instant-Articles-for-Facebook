<?php

global $post;

// Get the tags and categories.
$post_categories = get_the_category( $post );
$post_tags       = get_the_tags( $post );

// Turn the tags and categories into JS arrays.
$sr_categories = $sr_tags = array();

if ( is_array( $post_categories ) ) {
	foreach ( $post_categories as $post_category ) {
		$sr_categories[] = $post_category->name;
	}
}

if ( is_array( $post_tags ) ) {
	foreach ( $post_tags as $post_tag ) {
		$sr_tags[] = $post_tag->name;
	}
}

// Prepare Simple Reach config array for json encoding.
$sr_config_arr = array(
	'pid'      => get_option( 'lawrence_simple_reach_id', 0 ),
	'title'    => get_the_title( $post->ID ),
	'url'      => get_permalink( $post->ID ),
	'date'     => $post->post_date_gmt,
	'authors'  => array( get_the_author() ),
	'channels' => $sr_categories,
	'tags'     => $sr_tags,
);

?>

<figure class="op-tracker">
	<iframe>

		<!-- SimpleReach Analytics -->
		<script type='text/javascript' id='simplereach-analytics-tag'>
			var __reach_config = <?php echo wp_json_encode( $sr_config_arr ); ?>;
		</script>

		<script type="text/javascript">
			(function(){
				var s = document.createElement('script');
				s.async = true;
				s.type = 'text/javascript';
				s.src = document.location.protocol + '//d8rk54i4mohrb.cloudfront.net/js/reach.js';
				(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(s);
			})();
		</script>

	</iframe>
</figure>
