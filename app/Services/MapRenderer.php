<?php

namespace App\Services;

class MapRenderer{
	public static function download_osm($zoom,$x,$y){
		$file_path=base_path('osm/'.$zoom.'/'.$x.'/'.$y.'.png');
		$options = array(
		  'http'=>array(
			'method'=>"GET",
			'header'=>"Accept-language: en\r\n" .
					  "Cookie: foo=bar\r\n" .  // check function.stream-context-create on php.net
					  "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad 
		  )
		);
		$context = stream_context_create($options);
		$imagefile=file_get_contents('https://a.tile.openstreetmap.org/'.$zoom.'/'.$x.'/'.$y.'.png',false,$context);
		$dirname=pathinfo($file_path,PATHINFO_DIRNAME);
		if(!is_dir($dirname)){
			mkdir($dirname,0755,true);
		}
		file_put_contents($file_path,$imagefile);
	}
	public static function getOverlay($zoom,$x,$y){
		if($zoom>5){
			$file_path=base_path('lb_overlay/'.$zoom.'/'.$x.'/'.$y.'.png');
			if(!file_exists($file_path)){
				OverlayRenderer::handle($zoom,$x,$y);
			}
			
			
			$overlay=file_get_contents($file_path);
			$overlayI=new \Imagick();
			$overlayI->readImageBlob($overlay);
			return $overlayI;
		}
		$params=[
			['x'=>2*$x,'y'=>2*$y],
			['x'=>2*$x,'y'=>2*$y+1],
			['x'=>2*$x+1,'y'=>2*$y],
			['x'=>2*$x+1,'y'=>2*$y+1],
		];
		foreach($params as $k=>$param){
			$overlays[$k]=self::getOverlay($zoom+1,$param['x'],$param['y']);
		}
		foreach($overlays as $overlay){
			$overlay->resizeImage(256,256,\imagick::FILTER_POINT,1);
		}

		$overlayI=new \Imagick();
		$overlayI->newImage(512, 512,new \ImagickPixel('transparent'));
		$overlayI->setImageFormat("png");
		$overlayI->compositeImage($overlays[0],\imagick::COMPOSITE_OVER,0,0);
		$overlayI->compositeImage($overlays[1],\imagick::COMPOSITE_OVER,0,256);
		$overlayI->compositeImage($overlays[2],\imagick::COMPOSITE_OVER,256,0);
		$overlayI->compositeImage($overlays[3],\imagick::COMPOSITE_OVER,256,256);
		return $overlayI;
	}
    public static function handle($zoom,$x,$y){
		$file_path=base_path('lb_map/'.$zoom.'/'.$x.'/'.$y.'.png');
		if(file_exists($file_path)){
			var_dump('exists');
			//return ;
		}
		if(php_sapi_name()=='cli'){
			var_dump('render_map|zoom:'.$zoom.' x:'.$x.' y: '.$y);
		}
		$file_path=base_path('osm/'.$zoom.'/'.$x.'/'.$y.'.png');
		if(!file_exists($file_path)){
			self::download_osm($zoom,$x,$y);
		}
		$osm=file_get_contents(base_path('osm/'.$zoom.'/'.$x.'/'.$y.'.png'));
		$overlayI=self::getOverlay($zoom,$x,$y);

		
		$osmI=new \Imagick();
		$osmI->readImageBlob($osm);
		$osmI->resizeImage(512,512,\imagick::FILTER_POINT,1);
		$osmI->compositeImage($overlayI,\imagick::COMPOSITE_DEFAULT,0,0);
		
		$draw = new \ImagickDraw();
		$pixel = new \ImagickPixel( 'gray' );
		$draw->setFillColor('black');
//		$draw->setFont('Bookman-DemiItalic');
		$draw->setFontSize( 15 );
		$osmI->annotateImage($draw, 5, 15, 0, $zoom);
		
		
		
		
		$imagefile=$osmI->getImageBlob();
		$file_path=base_path('lb_map/'.$zoom.'/'.$x.'/'.$y.'.png');
		$dirname=pathinfo($file_path,PATHINFO_DIRNAME);
		if(!is_dir($dirname)){
			mkdir($dirname,0755,true);
		}
		file_put_contents($file_path,$imagefile);
        //
    }
}
