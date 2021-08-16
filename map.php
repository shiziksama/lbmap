<?php

include(__DIR__.'/vendor/autoload.php');
function base_path($str){
	return __DIR__.'/'.$str;

}
var_dump('some');
preg_match('~/lb_map/(?<zoom>(\d+))/(?<x>(\d+))/(?<y>(\d+)).png~',$_SERVER['REQUEST_URI'],$matches);
$zoom=$matches['zoom'];
$x=$matches['x'];
$y=$matches['y'];

if(false){
	MapRenderer::handle($zoom,$x,$y);
}else{
	file_put_contents(__DIR__.'/queue/'.$zoom.'.'.$x.'.'.$y,'');
}

