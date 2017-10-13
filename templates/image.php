<figure data-feedback="fb:likes,fb:comments">

	<img src="<?php echo esc_url( $data['fb_instant_src'] ); ?>" />
	<?php $credit = get_post_meta( $data['fb_instant_image_id'], '_credit', true ); ?>
	<?php if ( ! empty( $data['fb_instant_caption'] ) || ! empty( $credit ) ) : ?>
		<figcaption>
			<?php ! empty( $data['fb_instant_caption'] ) ? printf( '<h1>%s</h1>', wp_strip_all_tags( $data['fb_instant_caption'] ) ) : ''; ?>
			<?php ! empty( $credit ) ? printf( '<cite>%s</cite>', trim( esc_html( $credit ) ) ) : ''; ?>
		</figcaption>
	<?php endif; ?>

</figure>
