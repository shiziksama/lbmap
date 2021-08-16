<?php

include(__DIR__.'/vendor/autoload.php');
function base_path($str){
	return __DIR__.'/'.$str;
}
MapRenderer::handle(1,0,0);
MapRenderer::handle(1,0,1);
MapRenderer::handle(1,1,0);
MapRenderer::handle(1,1,1);
