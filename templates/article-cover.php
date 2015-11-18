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

	<?php
	// Post featured image as FB IA cover image.
	if ( $thumb_id = get_post_thumbnail_id() ) {
		Simple_FB_Instant_Articles::instance()->render_image_markup( $thumb_id );
	}
	?>

	<?php the_title( '<h1>', '</h1>' ); ?>

	<?php lawrence_the_subheading( '<h2>', '</h2>' ); ?>

	<?php if ( function_exists( 'coauthors' ) ) : ?>
		<?php coauthors( '</address>, <address>', ' </address> and <address> ', '<address>', '</address>' ); ?>
	<?php else : ?>
		<address><?php the_author(); ?></address>
	<?php endif; ?>

	<!-- The published and last modified time stamps -->
	<time class="op-published" dateTime="<?php echo esc_attr( get_the_time( 'c' ) ); ?>"><?php echo esc_html( lawrence_display_date() ); ?></time>
	<time class="op-modified" dateTime="<?php echo esc_attr( get_the_modified_time( 'c' ) ); ?>"><?php echo esc_html( lawrence_display_date() ); ?></time>

</header>
