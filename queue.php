<?php
include(__DIR__.'/vendor/autoload.php');

function base_path($str){
	return __DIR__.'/'.$str;
}
while(true){
	$files=glob(__DIR__.'/queue/*');
	foreach($files as $file){
		$basename=pathinfo($file,PATHINFO_BASENAME);
		list($zoom,$x,$y)=explode('.',$basename);
		MapRenderer::handle($zoom,$x,$y);
		unlink($file);
	}
	var_dump('end queue');
	sleep(1);
}
