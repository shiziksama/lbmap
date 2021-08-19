<?php
/*
Route::any('/interpreter',[MapRendererController::class,'interpreter']);
Route::get('/lb_overlay/{z}/{x}/{y}.png',[MapRendererController::class,'longboard_overlay']);
Route::get('/lb_map/{z}/{x}/{y}.png',[MapRendererController::class,'longboard_map']);

	public function interpreter(){
		header('Access-Control-Allow-Origin', '*');
		header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
		//return '[]';
		//var_dump($_POST);
		$lbroads=new \App\LBRoads;
		$result=$lbroads->get_overpass($_POST['data']);
		$result['elements']=array_map([$lbroads,'add_lbroads_tags'],$result['elements']);
		//file_put_contents(base_path('1.txt'),json_encode($result,JSON_PRETTY_PRINT));
		//var_dump($result);
		//die();
		
		$result['elements']=array_filter($result['elements'],function($element){
			return empty($element['tags']['lbroad']);
		});
		$result['elements']=$lbroads->drop_nodes($result['elements']);
		$result['elements']=array_values($result['elements']);
		return response()->json($result)->header('Access-Control-Allow-Origin', '*')->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');;
	}
		public function get_overpass($data,$bbox){
		$url = 'http://overpass-api.de/api/interpreter';
		$data=str_replace('{{bbox}}',$bbox,$data);
		//var_dump($data);
		$data=['data'=>$data];

		// use key 'http' even if you send the request to https://...
		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query($data)
			)
		);
		$context  = stream_context_create($options);
		$result = json_decode(file_get_contents($url, false, $context),true);
		return $result;
	}
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

