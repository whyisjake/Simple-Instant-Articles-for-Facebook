<?php

function style_init() {
	register_taxonomy( 'style', array( 'post' ), array(
		'hierarchical'      => true,
		'public'            => true,
		'show_in_nav_menus' => true,
		'show_ui'           => true,
		'show_admin_column' => false,
		'query_var'         => true,
		'rewrite'           => true,
		'capabilities'      => array(
			'manage_terms'  => 'edit_posts',
			'edit_terms'    => 'edit_posts',
			'delete_terms'  => 'edit_posts',
			'assign_terms'  => 'edit_posts'
		),
		'labels'            => array(
			'name'                       => __( 'Styles', 'simple-instant-articles-for-facebook' ),
			'singular_name'              => _x( 'Style', 'taxonomy general name', 'simple-instant-articles-for-facebook' ),
			'search_items'               => __( 'Search styles', 'simple-instant-articles-for-facebook' ),
			'popular_items'              => __( 'Popular styles', 'simple-instant-articles-for-facebook' ),
			'all_items'                  => __( 'All styles', 'simple-instant-articles-for-facebook' ),
			'parent_item'                => __( 'Parent style', 'simple-instant-articles-for-facebook' ),
			'parent_item_colon'          => __( 'Parent style:', 'simple-instant-articles-for-facebook' ),
			'edit_item'                  => __( 'Edit style', 'simple-instant-articles-for-facebook' ),
			'update_item'                => __( 'Update style', 'simple-instant-articles-for-facebook' ),
			'add_new_item'               => __( 'New style', 'simple-instant-articles-for-facebook' ),
			'new_item_name'              => __( 'New style', 'simple-instant-articles-for-facebook' ),
			'separate_items_with_commas' => __( 'Styles separated by comma', 'simple-instant-articles-for-facebook' ),
			'add_or_remove_items'        => __( 'Add or remove styles', 'simple-instant-articles-for-facebook' ),
			'choose_from_most_used'      => __( 'Choose from the most used styles', 'simple-instant-articles-for-facebook' ),
			'not_found'                  => __( 'No styles found.', 'simple-instant-articles-for-facebook' ),
			'menu_name'                  => __( 'Styles', 'simple-instant-articles-for-facebook' ),
		),
	) );

}
add_action( 'init', 'style_init' );
