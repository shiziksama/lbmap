<?php
//var_dump($_SERVER["REQUEST_URI"]);
if (preg_match('/\.(?:png|jpg|jpeg|gif)$/', $_SERVER["REQUEST_URI"])&&file_exists(__DIR__.$_SERVER["REQUEST_URI"])) {
    return false;    // сервер возвращает файлы напрямую.
}

include(__DIR__.'/vendor/autoload.php');
function base_path($str){
	return __DIR__.'/'.$str;
}
preg_match('~/lb_map/(?<zoom>(\d+))/(?<x>(\d+))/(?<y>(\d+)).png~',$_SERVER['REQUEST_URI'],$matches);
$zoom=$matches['zoom'];
$x=$matches['x'];
$y=$matches['y'];

MapRenderer::handle($zoom,$x,$y);


/*
if(file_exists(base_path('lb_map/'.$zoom.'/'.$x.'/'.$y.'.png'))){
	return response(file_get_contents(base_path('lb_map/'.$zoom.'/'.$x.'/'.$y.'.png')))->header('Content-type','image/png');
}
if($zoom>10){
	\App\Jobs\RenderMap::dispatchNow($zoom,$x,$y);
}else{
	\App\Jobs\RenderMap::dispatch($zoom,$x,$y);
}
if(file_exists(base_path('lb_map/'.$zoom.'/'.$x.'/'.$y.'.png'))){
	return response(file_get_contents(base_path('lb_map/'.$zoom.'/'.$x.'/'.$y.'.png')))->header('Content-type','image/png');
}
var_dump($matches);*/