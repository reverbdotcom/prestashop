<?php
// Get cURL resource
$curl = curl_init();
// Set some options - we are passing in a useragent too here
curl_setopt_array(
    $curl,
    array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'http://leshop-lyon.fr/modules/reverb/cron.php?code=orders-reconciliation',
        CURLOPT_USERAGENT => 'CRON OVH'
    )
);
// Send the request & save response to $resp
$resp = curl_exec($curl);
// Close request to clear up some resources
curl_close($curl);