<?php
/**
 * Article template for Facebook Instant Articles.
 */
?>
<!doctype html>
<html lang="en" prefix="op: http://media.facebook.com/op#">
<head>
	<meta property="op:markup_version" content="v1.0">
	<link rel="canonical" href="<?php the_permalink(); ?>">
	<meta property="fb:article_style" content="<?php echo esc_attr( simple_fb_get_article_style() ); ?>">
</head>
<body>
	<?php include( 'content.php' ); ?>
</body>
</html>
