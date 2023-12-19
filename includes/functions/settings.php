<?php

namespace Evermade\LinkedEvents\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

add_action( 'admin_init', __NAMESPACE__ . '\\register_plugin_settings' );
add_action( 'linked_events_settings_page', __NAMESPACE__ . '\\render_settings_form', 10 );

add_filter( 'linked_events_tprek_id', __NAMESPACE__ . '\\linked_events_tprek_id' );
add_filter( 'linked_events_is_wp_title_rewrite_enabled', __NAMESPACE__ . '\\is_wp_title_rewrite_enabled' );

/**
  * Settings
  */
function plugin_settings_group(): string
{
	return 'linked_events';
}

function setting_tprek_id(): string {
	return plugin_settings_group() . '_tprek_id';
}

function linked_events_tprek_id(): string {
	return get_option( setting_tprek_id(), '' );
}

function setting_wp_title_rewrite_enabled(): string {
	return plugin_settings_group() . '_wp_title_rewrite_enabled';
}

function is_wp_title_rewrite_enabled(): bool {
	return get_option( setting_wp_title_rewrite_enabled(), true );
}

function register_plugin_settings(): void
{
    $group = plugin_settings_group();
    $page = apply_filters( 'linked_events_settings_page_slug', '' );

	add_settings_section(
        $group,
        '',
        '__return_empty_string',
        $page
    );

    register_setting(
        $group,
        setting_tprek_id(),
        array(
        	'type' => 'string',
        	'description' => '',
        	'sanitize_callback' => 'sanitize_text_field',
        	'show_in_rest' => false,
        	'default' => '',
        )
    );

	add_settings_field(
        setting_tprek_id(),
        __( 'TPREK ID', 'linkedevents' ),
        __NAMESPACE__ . '\\render_tprek_id_field',
        $page,
        $group
    );

    register_setting(
        $group,
        setting_wp_title_rewrite_enabled(),
        array(
        	'type' => 'boolean',
        	'description' => '',
        	'sanitize_callback' => 'boolval',
        	'show_in_rest' => false,
        	'default' => false,
        )
    );

	add_settings_field(
        setting_wp_title_rewrite_enabled(),
        __( 'Rewrite WP titles', 'linkedevents' ),
        __NAMESPACE__ . '\\render_wp_title_rewrite_enabled_field',
        $page,
        $group
    );
}

/**
  * Render
  */
function render_settings_form(): void
{
    echo '<form id="linked-events-settings" class="settings" action="options.php" method="post">';

    settings_fields( plugin_settings_group() );

    do_settings_sections( apply_filters( 'linked_events_settings_page_slug', '' ) );

    submit_button();

    echo '</form>';
}

function render_tprek_id_field(): void {
	printf(
        '<input type="text" name="%s" value="%s">',
        esc_attr( setting_tprek_id() ),
        esc_attr( linked_events_tprek_id() )
    );
}

function render_wp_title_rewrite_enabled_field(): void {
	printf(
        '<input type="checkbox" name="%s" value="true" %s>',
        esc_attr( setting_wp_title_rewrite_enabled() ),
        checked( true, is_wp_title_rewrite_enabled(), false )
    );
}
