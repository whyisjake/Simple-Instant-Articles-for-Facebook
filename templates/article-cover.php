<?php
/**
 * Facebook Instant Article Cover template, containing the following info:
 *
 * 1) Media type: Image, Video or Slideshow
 * 2) Title
 * 3) Subtitle
 * 4) Author(s)
 * 5) Kicker (aka "A tertiary blurb in the headline of the article.")
 * 6) Published Time
 * 7) Modified Time
 *
 * @see https://developers.facebook.com/docs/instant-articles/guides/articlecreate#specify-cover
 */
?>
<header>
	<figure>
		<?php the_post_thumbnail( 'medium' ); ?>
		<figcaption><?php echo simple_fb_thumbnail_caption(); ?></figcaption>
	</figure>

	<?php the_title( '<h1>', '</h1>' ); ?>

	<?php // Kickers aren't really a universal thing on WordPress, so let's just add a filter here for people to hook onto. ?>
	<?php $kicker = apply_filters( 'simple_fb_kicker', '' ); ?>
	<?php if ( ! empty( $kicker ) ) : ?>
		<details>
			<summary><?php echo esc_html( $kicker ); ?></summary>
		</details>
	<?php endif; ?>

	<address>
		<?php the_author_posts_link(); ?>
	</address>

	<time class="op-published" dateTime="<?php echo esc_attr( get_post_time( 'Y-m-d\TH:i:s\Z' ) ); ?>"><?php echo esc_html( get_post_time( get_option( 'date_format' ) ) ); ?></time>
	<time class="op-modified" dateTime="<?php echo esc_attr( get_the_modified_date('Y-m-d\TH:i:s\Z') ); ?>"><?php echo esc_html( get_the_modified_date( get_option( 'date_format' ) ) ); ?></time>
</header>
