<?php
/**
 * Article template for Facebook Instant Articles.
 */
?>
<!doctype html>
<html lang="en" prefix="op: http://media.facebook.com/op#">
<head>
	<meta property="op:markup_version" content="v1.0">
	<title><?php wp_title(); ?></title>
	<link rel="canonical" href="<?php the_permalink(); ?>">
</head>
<body>
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<?php include( 'content.php' ); ?>
	<?php endwhile; else : ?>
		<p><?php _e( 'Sorry, no posts matched your criteria.' ); ?></p>
	<?php endif; ?>
</body>
</html>