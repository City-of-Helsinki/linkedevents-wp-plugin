<?php

namespace Evermade\Swiss\Logic;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

function getCurrentTimestamp(){
	return time();
}

function getTinyUrl($url) {

	$timeout = 5;

	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,'http://tinyurl.com/api-create.php?url='.$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	$data = curl_exec($ch);

	//if we have an error revert to the original url
	if(curl_error($ch)){
		$data = $url;
	}

	curl_close($ch);

	return $data;
}

function log_me($message) {
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}
