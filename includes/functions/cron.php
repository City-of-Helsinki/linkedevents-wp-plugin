<?php namespace Evermade\LinkedEvents;

// Schedule hourly event to update stores list.
if (!wp_next_scheduled('linkedevents_cron_hook')) {
    wp_schedule_event(time(), 'hourly', 'linkedevents_cron_hook');
}
add_action('linkedevents_cron_hook', '\Evermade\LinkedEvents\updateStores');



/**
 * Update stores.
 *
 * @return void
 */
function updateStores() {

    $hyperIn = new \Evermade\LinkedEvents\LinkedEvents();
    $stores = $hyperIn->updateStores();

}
