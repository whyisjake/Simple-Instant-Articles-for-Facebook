<?php
/**
 * Generate FB IA formatted caption for an image or a post thumbnail.
 * Allow it to be filtered and changed.
 *
 * @param int $image_id Image/attachment ID. Optional.
 *
 * @return string|void FB IA formatted image caption.
 *                     Nothing if attachment post isn't found.
 */
function get_simple_fb_image_caption( $image_id = 0 ) {

	// Use thumbnail image - if image ID isn't specified.
	if ( ! $image_id ) {
		$image_id = get_post_thumbnail_id();
	}

	// Get attachment post and its caption, aka post excerpt.
	$attachment_post = get_post( $image_id );

	// Stop if - attachment post not found or caption is empty.
	if ( ! $attachment_post || ! $attachment_post->post_excerpt) {
		return;
	}

	$caption_html = sprintf(
		'<figcaption><h1>%s</h1></figcaption>',
		$attachment_post->post_excerpt
	);

	$caption_html = apply_filters( 'simple_fb_img_caption', $caption_html, $attachment_post->post_excerpt );

	return $caption_html;
}
