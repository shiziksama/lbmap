<?php
ini_set('memory_limit','5G');
//var_dump(ini_get('memory_limit'));
//die();
include(__DIR__.'/vendor/autoload.php');

use App\Services\OverlayRenderer;

function base_path($str){
	return __DIR__.'/'.$str;
}
while(true){
	$files=glob(__DIR__.'/queue/*');
	foreach($files as $file){
		$basename=pathinfo($file,PATHINFO_BASENAME);
		list($zoom,$x,$y)=explode('.',$basename);
		OverlayRenderer::handle($zoom,$x,$y);
		unlink($file);
	}
	var_dump('end queue');
	sleep(1);
}
