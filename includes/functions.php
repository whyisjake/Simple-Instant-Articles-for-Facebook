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
	ob_start();
	echo '<figure>';
	the_post_thumbnail( 'full' );
	echo '<figcaption>';
	echo simple_fb_thumbnail_caption();
	echo '</figcaption></figure>';
	$content = ob_get_contents();
	return apply_filters( 'simple_fb_header_figure', $content );
}