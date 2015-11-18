<?php
/**
 * Generate the caption for a post thumbnail.
 * @return string Post content, with the simple-fb-caption filter tacked onto it.
 */
function simple_fb_thumbnail_caption() {
	$post_id    = get_the_id();
	$thumb_id   = get_post_thumbnail_id( $post_id );
	$thumb_post = get_post( $thumb_id );
	$caption    = apply_filters( 'simple-fb-caption', $thumb_post->post_excerpt );
	return $caption;
}

/**
 * Build the header for the story.
 * @return string header
 */
function simple_fb_header_figure() {
	$caption = sprintf( '<figcaption>%s</figcaption>', simple_fb_thumbnail_caption() );
	$content = sprintf( '<figure>%s%s</figure>', get_the_post_thumbnail( get_the_id(), 'full' ), $caption );
	return apply_filters( 'simple_fb_header_figure', $content );
}

/**
 * Get a list of all style tags, and return them in the meta attribute.
 * @return string
 */
function simple_fb_get_article_style() {
	$post_id = get_the_id();
	$terms   = get_the_terms( $post_id, 'style' );

	// Do we have anything to work with here?
	if ( ! empty( $terms ) ) {
		// Build a string to hold everything.
		$output = '';
		// Loop through, and concatenate the terms
		foreach ( $terms as $term ) {
			$output .= $term->name . ' ';
		}
		// Return them all.
		$output = rtrim( $output );
	} else {
		// Send the default
		$output =  'default';
	}

	// Send them all out.
	return apply_filters( 'simple_fb_post_styles', rtrim( $output ) );
}