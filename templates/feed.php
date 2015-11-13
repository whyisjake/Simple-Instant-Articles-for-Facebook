<?php
/**
 * Instant Articles RSS feed template
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set RSS header
header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );

// Use `echo` for first line to prevent any extra characters at start of document
echo '<?xml version="1.0" encoding="' . esc_attr( get_option( 'blog_charset' ) ) . '"?>'; ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php do_action( 'rss2_ns' ); ?>
>

<channel>
	<title><?php echo esc_html( wp_title() ); ?></title>
	<atom:link href="<?php esc_url( self_link() ); ?>" rel="self" type="application/rss+xml" />
	<link><?php echo esc_url( apply_filters( 'simple_fb_home_url', home_url() ) ) ?></link>
	<description><?php echo esc_html( bloginfo( 'description' ) ); ?></description>
	<lastBuildDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ); ?></lastBuildDate>
	<language><?php echo esc_html( bloginfo( 'language' ) ); ?></language>
	<?php

	// Add RSS2 headers
	do_action( 'rss2_head' );

	// How many posts? Let's add a specific filter for this.
	$num_posts = intval( apply_filters( 'simple_fb_posts_per_rss', get_option( 'posts_per_rss', 10 ) ) );

	// And the default args.
	$args = array(
		'posts_per_page' => $num_posts,
	);

	// Add a filter for all of the args.
	$args = apply_filters( 'simple_fb_feed_query_args', $args );

	// Kick off the query.
	$query = new WP_Query( $args );
	?>

	<?php if ( $query->have_posts() ) : ?>
		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
			<item>
				<title><?php esc_html( the_title_rss() ); ?></title>
				<link><?php esc_url( the_permalink_rss() ); ?></link>
				<pubDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ) ); ?></pubDate>
				<dc:creator><?php the_author(); ?></dc:creator>
				<guid isPermaLink="false"><?php esc_html( the_guid() ); ?></guid>
				<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
				<content:encoded><![CDATA[<?php include( 'article.php' ); ?>]]></content:encoded>
			</item>
		<?php endwhile; ?>
	<?php endif; ?>
</channel>
</rss><?php exit; ?>