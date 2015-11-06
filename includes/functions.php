<?php
/**
 * Generate the caption for a post thumbnail.
 * @return string Post content, with the simple-fb-caption filter tacked onto it.
 */
function simple_fb_thumbnail_caption() {
	$thumb_id   = get_post_thumbnail_id();
	$thumb_post = get_post( $thumb_id );
	$caption    = apply_filters( 'simple-fb-caption', $thumb_post->post_content );
	return $caption;
}