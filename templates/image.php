<?php global $fb_instant_image_id, $fb_instant_caption, $fb_instant_src; ?>
<figure data-feedback="fb:likes,fb:comments">

	<img src="<?php echo esc_url( $fb_instant_src ); ?>" />
	<?php $credit = get_post_meta( $fb_instant_image_id, '_credit', true ); ?>
	<?php if ( ! empty( $fb_instant_caption ) || ! empty( $credit ) ) : ?>
		<figcaption>
			<?php ! empty( $fb_instant_caption ) ? printf( '<h1>%s</h1>', wp_strip_all_tags( $fb_instant_caption ) ) : ''; ?>
			<?php ! empty( $credit ) ? printf( '<cite>%s</cite>', trim( esc_html( $credit ) ) ) : ''; ?>
		</figcaption>
	<?php endif; ?>

</figure>
