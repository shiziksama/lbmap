<?php
/*
Route::get('/lb_overlay/{z}/{x}/{y}.png',[MapRendererController::class,'longboard_overlay']);
Route::get('/lb_map/{z}/{x}/{y}.png',[MapRendererController::class,'longboard_map']);

	public function elements_to_lines($elements){
		$lines=[];
		$points=[];
		$pre_lines=[];
		foreach($elements as $el){
			if($el['type']=='way'){
				$pre_lines[]=$el['nodes'];
			}elseif($el['type']=='node'){
				$points[$el['id']]=['lat'=>$el['lat'],'lng'=>$el['lon']];
			}
		}
		foreach($pre_lines as $pre_line){
			$line=[];
			foreach($pre_line as $pt){
				$line[]=$points[$pt];
			}
			$lines[]=$line;
		}
		return $lines;
		var_dump($lines);
		
	}
		public function computeOutCode($point,$lat_from,$lat_to,$lng_from,$lng_to){
		$result=0;
		if($point[0]<$lat_from){
			$result = $result |1;
		}
		if($point[0]>$lat_to){
			$result = $result |2;
		}
		if($point[1]<$lng_from){
			$result = $result |4;
		}
		if($point[1]>$lng_to){
			$result = $result |8;
		}
+		return $result;
	}

*/
include(__DIR__.'/vendor/autoload.php');
function base_path($str){
	return __DIR__.'/'.$str;

}
preg_match('~/lb_overlay/(?<zoom>(\d+))/(?<x>(\d+))/(?<y>(\d+)).png~',$_SERVER['REQUEST_URI'],$matches);
$zoom=$matches['zoom'];
$x=$matches['x'];
$y=$matches['y'];

if($zoom>6){
	OverlayRenderer::handle($zoom,$x,$y);
	header ('Content-Type: image/png');
	echo file_get_contents(base_path('lb_overlay/'.$zoom.'/'.$x.'/'.$y.'.png'));
}else{
	file_put_contents(__DIR__.'/queue/'.$zoom.'.'.$x.'.'.$y,'');
}

