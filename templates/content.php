<?php
/**
 * Content for the Facebook Instant Articles
 */
?>
<article>
	<header>

		<?php // Kickers aren't really a universal thing on WordPress, so let's just add a filter here for people to hook onto. ?>
		<?php $kicker = apply_filters( 'simple_fb_kicker', '' ); ?>
		<?php if ( ! empty( $kicker ) ) : ?>
			<details>
				<summary><?php echo esc_html( $kicker ); ?></summary>
			</details>
		<?php endif; ?>

		<time class="op-published" dateTime="<?php echo esc_attr( get_post_time( 'Y-m-d\TH:i:s\Z' ) ); ?>"><?php echo esc_html( get_post_time( get_option( 'date_format' ) ) ); ?></time>
		<time class="op-modified" dateTime="<?php echo esc_attr( get_the_modified_date('Y-m-d\TH:i:s\Z') ); ?>"><?php echo esc_html( get_the_modified_date( get_option( 'date_format' ) ) ); ?></time>


		<address>
			<?php the_author_posts_link(); ?>
		</address>

		<?php echo simple_fb_header_figure(); ?>

		<?php the_title( '<h1>', '</h1>' ); ?>

	</header>

	<?php do_action( 'simple_fb_before_the_content' ); ?>
	<?php the_content(); ?>
	<?php do_action( 'simple_fb_after_the_content' ); ?>

</article>