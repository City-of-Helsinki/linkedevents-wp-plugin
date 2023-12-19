<?php

namespace Evermade\LinkedEvents\MenuPage;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

add_action( 'admin_menu', __NAMESPACE__ . '\\register_menu_page' );
add_action( 'linked_events_settings_page', __NAMESPACE__ . '\\render_settings_page_title', 5 );
add_filter( 'linked_events_settings_page_slug', __NAMESPACE__ . '\\settings_page_slug' );

/**
  * Menu page
  */
function settings_page_capability(): string
{
	return 'manage_options';
}

function settings_page_slug(): string
{
	return 'linked-events';
}

function register_menu_page(): string
{
	return add_menu_page(
		__( 'Linked events', 'linkedevents' ),
		__( 'Linked events', 'linkedevents' ),
		settings_page_capability(),
		settings_page_slug(),
		__NAMESPACE__ . '\\render_settings_page',
		'dashicons-calendar-alt',
		85
	);
}

function render_settings_page(): void
{
	echo '<div class="wrap">';

	do_action( 'linked_events_settings_page' );

	echo '</div>';
}

function render_settings_page_title(): void
{
    printf(
        '<h1>%s</h1>',
        esc_html( get_admin_page_title() )
    );
}
