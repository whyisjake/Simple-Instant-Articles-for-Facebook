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
	if ( $thumb_id = get_post_thumbnail_id() ) :
	?>
		<figure>
			<?php echo wp_kses_post( wp_get_attachment_image( $thumb_id, array( 2048, 2048 ) ) ); ?>
		</figure>

	<?php endif; ?>

	<?php the_title( '<h1>', '</h1>' ); ?>

	<?php
	// The author(s) of the article.
	foreach ( get_coauthors() as $author ) :
		$author = get_user_by( 'id', $author->ID );
		?>

		<address>
			<a><?php echo esc_html( $author->display_name ); ?></a>
		</address>

	<?php endforeach; ?>

	<!-- The published and last modified time stamps -->
	<time class="op-published" dateTime="<?php echo esc_attr( get_the_time( 'c' ) ); ?>"><?php echo esc_html( lawrence_display_date() ); ?></time>
	<time class="op-modified" dateTime="<?php echo esc_attr( get_the_modified_time( 'c' ) ); ?>"><?php echo esc_html( lawrence_display_date() ); ?></time>

<header>
