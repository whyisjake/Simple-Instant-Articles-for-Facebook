<?php
/**
 * Content for the Facebook Instant Articles
 */
?>
<article>

	<?php include( apply_filters( 'simple_fb_article_cover_template_file', 'article-cover.php' ) ); ?>

	<?php do_action( 'simple_fb_before_the_content' ); ?>
	<?php the_content(); ?>
	<?php do_action( 'simple_fb_after_the_content' ); ?>

</article>