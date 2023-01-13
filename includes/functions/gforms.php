<?php namespace Evermade\HyperIn;


add_action('gform_after_submission', '\Evermade\HyperIn\post_to_hyperin', 10, 2);
function post_to_hyperin($entry, $form) {
 
    $hyperIn = new \Evermade\HyperIn\HyperIn();
    $stores = $hyperIn->sendFeedback($entry[3] . ' ' . $entry[5], $entry[1] . ' ' . $entry[2], $entry[4]);
    
}