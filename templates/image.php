<figure>

	<img src="<?php echo esc_url( $src ); ?>" />

	<?php if ( ! empty( $caption ) ) : ?>
		<figcaption>
			<h1><?php echo esc_html( $caption ); ?></h1>
		</figcaption>
	<?php endif; ?>

</figure>
