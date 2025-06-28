<?php

/**
 *  An example CORS-compliant method.  It will allow any GET, POST, or OPTIONS requests from any
 *  origin.
 *
 *  In a production environment, you probably want to be more restrictive, but this gives you
 *  the general idea of what is involved.  For the nitty-gritty low-down, read:
 *
 *  - https://developer.mozilla.org/en/HTTP_access_control
 *  - https://fetch.spec.whatwg.org/#http-cors-protocol
 *
 */
function cors() {
    
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
    
    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
        exit(0);
    }
}
cors();
function base_path($str){
	return __DIR__.'/'.$str;

}
include(__DIR__.'/vendor/autoload.php');

use App\Services\LBRoads;
$lbroads=new LBRoads;
foreach(explode(";",$_POST['data']) as $line){
	preg_match_all('/\([\d.,-]+\)/',$line,$out);
	if(empty(array_filter($out)))continue;
	$bbox=$out[0][0];
	$bbox=substr($bbox,1,-1);
}
$filename='planet-black.o5m';
$bbox=explode(',',$bbox);
$bbox=$bbox[1].','.$bbox[0].','.$bbox[3].','.$bbox[2];
$elements=$lbroads->get_elements($filename,$bbox);

$result=['elements'=>$elements, 
		"version"=> 0.6,
		"generator"=>"Overpass API 0.7.56.8 7d656e78",
		"osm3s"=>[
			"timestamp_osm_base"=>"2021-02-05T12:58:03Z",
			"copyright"=>"The data included in this document is from www.openstreetmap.org. The data is made available under ODbL."
		  ]];
echo json_encode($result);