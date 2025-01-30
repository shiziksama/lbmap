<?php

ini_set('memory_limit','25G');
include(__DIR__.'/vendor/autoload.php');
function base_path($str){
	return __DIR__.'/'.$str;
}
function render_childs($zoom,$x,$y){
	OverlayRenderer::handle($zoom+1,$x*2,$y*2);
	OverlayRenderer::handle($zoom+1,$x*2,$y*2+1);
	OverlayRenderer::handle($zoom+1,$x*2+1,$y*2);
	OverlayRenderer::handle($zoom+1,$x*2+1,$y*2+1);
}
//OverlayRenderer::handle(4,8,5);//голубой банан
//OverlayRenderer::handle(3,4,2);//главная вырезка голубого банана//2.2.1//1.1.0

//OverlayRenderer::handle(1,0,0);
//OverlayRenderer::handle(1,0,1);
//OverlayRenderer::handle(1,1,0);
//OverlayRenderer::handle(1,1,1);

for($i=6;$i<11;$i++){
	$files=glob(__DIR__.'/lb_json/l_'.$i.'*.packed');
	$files=array_filter($files,function($item){
		if(filesize($item)>9000000)return true;
		return false;
	});
	foreach($files as $file){
		preg_match('~/lb_json/l_(?<zoom>(\d+)).(?<x>(\d+)).(?<y>(\d+)).packed~',$file,$matches);
		render_childs($matches['zoom'],$matches['x'],$matches['y']);
	}
}
