<?php
/**
 * Outputs FB IA formatted caption for an image or a post thumbnail.
 *
 * If caption string is specified - it takes precedence over getting
 * the caption directly from image/attachment post.
 *
 * @param int    $image_id Image/attachment ID. Optional.
 * @param string $caption  Image caption string. Optional.
 *
 * @return string          FB IA formatted image caption.
 *                         Empty string if attachment post isn't found.
 */
function simple_fb_image_caption( $image_id = 0, $caption = '' ) {

	$caption_str = '';

	// Specified caption string takes precedence over image ID.
	if ( $caption ) {
		$caption_str = $caption;

	} else {
		// If image ID is not specified - try to get thumbnail ID.
		if ( ! $image_id = intval( $image_id ) ) {
			$image_id = get_post_thumbnail_id();
		}

		// Get attachment post and its caption, aka post excerpt.
		$attachment_post = get_post( $image_id );

		// Stop if - attachment post not found or caption is empty.
		if ( ! $attachment_post || ! $attachment_post->post_excerpt ) {
			return '';
		}

		$caption_str = $attachment_post->post_excerpt;
	}

	// Output image caption in FB IA format.
	printf(
		'<figcaption><h1>%s</h1></figcaption>',
		esc_html( $caption_str )
	);
}
