<?php

namespace Evermade\LinkedEvents;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

add_filter( 'linked_events_cron_hook_name', __NAMESPACE__ . '\\cron_hook_name' );
function cron_hook_name(): string {
	return 'linkedevents_cron_hook';
}

// Schedule hourly event to update stores list.
if ( ! wp_next_scheduled( cron_hook_name() ) ) {
    wp_schedule_event( time(), 'hourly', cron_hook_name() );
}
