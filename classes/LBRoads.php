<?php 
class LBRoads{
	public $roads='[out:json][timeout:600];
		(
		  way["highway"]({{bbox}});
		  -(
			);
		);
		out body;
		>;
		out skel qt;';
	public function drop_nodes($elements){
		$nodes=[];
		foreach($elements as $element){
			if(!empty($element['nodes'])){
				$nodes=array_merge($nodes,$element['nodes']);
			}
		}

		$nodes=array_unique($nodes);
		$elements=array_filter($elements,function($value)use($nodes){
			if($value['type']=='node'&&!in_array($value['id'],$nodes))return false;
				return true;
		});
		$elements=array_values($elements);
		return $elements;
	}
	public function overpass($data,$bbox='',$zoom=0,$x=0,$y=0){
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
		if(empty($result)){
			if(php_sapi_name()=='cli'){
				var_dump('overpass_download');
				
			}
			$result = json_decode(file_get_contents($url, false, $context),true);
			if(php_sapi_name()=='cli'){
				sleep(5);
			}
		}
		return $result;
	}
	public function create_o5m($zoom,$x,$y){
		while($zoom>3){
			$zoom-=1;
			$x=floor($x/2);
			$y=floor($y/2);
		}
		$filename='planet-highways.'.$zoom.'.'.$x.'.'.$y.'.o5m';
		if(file_exists(base_path('o5m/'.$filename))){
			return $filename;
		}
		$items_count=pow(2,$zoom);
		$lng_deg_per_item=360/$items_count;
		$lng_from=-180+$x*$lng_deg_per_item;
		$lng_to=-180+($x+1)*$lng_deg_per_item;
		
		$lat_deg_per_item=(85.0511*2)/$items_count;
		$lat_to=rad2deg(atan(sinh(pi() * (1 - 2 * $y / $items_count))));
		$lat_from=rad2deg(atan(sinh(pi() * (1 - 2 * ($y+1) / $items_count))));
		
		$shell='osmconvert '.base_path('o5m/planet-highways.o5m').' --complete-ways --drop-author -b='.$lng_from.','.$lat_from.','.$lng_to.','.$lat_to.' -o='.base_path('o5m/'.$filename);
		var_dump($shell);
		var_dump(time());
		$s=shell_exec($shell);
		var_dump(time());
		return $filename;
	}
	public function osmconvert($data,$bbox='',$zoom='',$x='',$y=''){
		$filename='planet-highways.o5m';
		foreach(explode(";",$data) as $line){
			preg_match_all('/\([\d.,-]+\)/',$line,$out);
			if(empty(array_filter($out)))continue;
			$bbox=$out[0][0];
			$bbox=substr($bbox,1,-1);
			$filename='planet-black.o5m';
		}
		
		
		if(!empty($zoom)){
			$filename=$this->create_o5m($zoom,$x,$y);
		}
		
		$bbox=explode(',',$bbox);
		$bbox=$bbox[1].','.$bbox[0].','.$bbox[3].','.$bbox[2];
		$shell='osmconvert '.base_path('o5m/'.$filename).' --complete-ways --drop-author -b='.$bbox;
		//var_dump($shell);
		if(php_sapi_name()=='cli'){var_dump('start_convert|time:'.time());}
		$s=shell_exec($shell);
		if(php_sapi_name()=='cli'){var_dump('get_xml|time:'.time());}
		$z = new WeblamasXMLReader();
		$z->xml($s);
		$elements=[];
		while($z->read()) {
			if($z->name=='node'&&$z->nodeType==\XmlReader::ELEMENT){
				//echo '<node id="'.$z->getAttribute('id').'" lat="'.$z->getAttribute('lat').'" lon="'.$z->getAttribute('lon').'"/>'.PHP_EOL;
				$elements[]=[
					'type'=>'node',
					'id'=>''.$z->getAttribute('id'),
					'lat'=>''.$z->getAttribute('lat'),
					'lon'=>''.$z->getAttribute('lon'),
				];
				$z->endElement();
				continue;
			}elseif($z->name=='way'&&$z->nodeType==\XmlReader::ELEMENT){
				$osm=simplexml_load_string($z->readOuterXml());
				$tags=[];
				$nodes=[];
				foreach($osm as $key=>$child){
					if($key=='nd'){
						$nodes[]=''.$child->attributes()->ref;
					}elseif($key=='tag'){
						$tags[''.$child->attributes()->k]=''.$child->attributes()->v;
					}
				}
				$elements[]=[
					'type'=>'way',
					'id'=>''.$osm->attributes()->id,
					'tags'=>$tags,
					'nodes'=>$nodes,
					
				];
				$z->endElement();
			}
		}
		if(php_sapi_name()=='cli'){var_dump('after_xml|time:'.time());}
		return ['elements'=>$elements, 
				"version"=> 0.6,
				"generator"=>"Overpass API 0.7.56.8 7d656e78",
				"osm3s"=>[
					"timestamp_osm_base"=>"2021-02-05T12:58:03Z",
					"copyright"=>"The data included in this document is from www.openstreetmap.org. The data is made available under ODbL."
				  ]];
	}
	public function computeOutCode($point,$lat_from,$lat_to,$lng_from,$lng_to){
		$result=0;
		if($point['lat']<$lat_from){
			$result = $result |1;
		}
		if($point['lat']>$lat_to){
			$result = $result |2;
		}
		if($point['lng']<$lng_from){
			$result = $result |4;
		}
		if($point['lng']>$lng_to){
			$result = $result |8;
		}
		return $result;
	}
	public function parse_parent_lines($zoom,$x,$y){
		$parent_lines=$this->get_lines($zoom-1,floor($x/2),floor($y/2));
		
		$items_count=pow(2,$zoom);
		$lng_deg_per_item=360/$items_count;
		$lng_from=-180+$x*$lng_deg_per_item;
		$lng_to=-180+($x+1)*$lng_deg_per_item;
		
		$lat_deg_per_item=(85.0511*2)/$items_count;
		$lat_to=rad2deg(atan(sinh(pi() * (1 - 2 * $y / $items_count))));
		$lat_from=rad2deg(atan(sinh(pi() * (1 - 2 * ($y+1) / $items_count))));
		$lines=[];
		foreach($parent_lines as $track){
			$points_numbers=[];
			foreach($track['points'] as $k=>$point){
				$points_numbers[$k]=$this->computeOutCode($point,$lat_from,$lat_to,$lng_from,$lng_to);
			}
			foreach($points_numbers as $k=>$number){
				if($k==0)continue;
				if(($number & $points_numbers[$k-1])==0||$number==0||$points_numbers[$k-1]==0){ //Значит эта линия пересекает.
					$lines[]=$track;
					break;
				}
			}
		}
		$path=base_path('lb_json/l_'.$zoom.'.'.$x.'.'.$y.'.json');
		file_put_contents($path,json_encode($lines));
		return $lines;
		var_dump(count($lines));
		var_dump(count($parent_lines));
		die();
	}
	public function get_lines($zoom,$x,$y){
		if(php_sapi_name()=='cli'){
			var_dump('get_lines|zoom:'.$zoom.' '.$x.' '.$y);
			if(php_sapi_name()=='cli'){var_dump('get_lines|time:'.time());}
		}
		$path=base_path('lb_json/l_'.$zoom.'.'.$x.'.'.$y.'.json');
		if(file_exists($path)){
			$result=json_decode(file_get_contents($path),true);
			return $result;
		}
		if($zoom>6){
			return $this->parse_parent_lines($zoom,$x,$y);
		}
		$items_count=pow(2,$zoom);
		$lng_deg_per_item=360/$items_count;
		$lng_from=-180+$x*$lng_deg_per_item;
		$lng_to=-180+($x+1)*$lng_deg_per_item;
		
		$lat_deg_per_item=(85.0511*2)/$items_count;
		$lat_to=rad2deg(atan(sinh(pi() * (1 - 2 * $y / $items_count))));
		$lat_from=rad2deg(atan(sinh(pi() * (1 - 2 * ($y+1) / $items_count))));
		//$lbroads=new \App\Lbroads();
		if(php_sapi_name()=='cli'){var_dump('before_overpass|time:'.time());}
		$result=$this->get_overpass($this->roads,$lat_from.','.$lng_from.','.$lat_to.','.$lng_to,$zoom,$x,$y);
		if(php_sapi_name()=='cli'){var_dump('after_overpass|time:'.time());}
		$pre_lines=[];
		foreach($result['elements'] as $el){
			//if($el['type']=='way'&&!empty($el['tags']['lbroad'])){
			if($el['type']=='way'){
				$pre_lines[]=$el;
			}elseif($el['type']=='node'){
				$points[$el['id']]=['lat'=>$el['lat'],'lng'=>$el['lon']];
			}
		}
		$lines=[];
		foreach($pre_lines as $pre_line){
			//$line=['id'=>$pre_line['id'],'points'=>[]];
			//var_dump($pre_line);
			foreach($pre_line['nodes'] as $pt){
				if(empty($points[$pt])){
					
					var_dump($pre_line);
					var_dump($pt);
					continue;//STRONGLY TODO
				}
				$pre_line['points'][]=$points[$pt];
			}
			unset($pre_line['nodes']);
			//unset($pre_line['type']);
			$lines[]=$pre_line;
		}
		if(php_sapi_name()=='cli'){var_dump('filtered_lines|time:'.time());}
		file_put_contents($path,json_encode($lines));
		return $lines;	
	}
		
	private function get_overpass_result($zoom,$x,$y){
		if(php_sapi_name()=='cli'){
			var_dump('zoom:'.$zoom.' '.$x.' '.$y);
			
		}
		
		
		
		if(php_sapi_name()=='cli'){var_dump('time:'.time());}
		if($zoom>13){
			//$lbroads=new \App\Lbroads();
			return $this->get_overpass_result($zoom-1,intdiv($x,2),intdiv($y,2));
			//return $lbroads->get_overpass($this->roads,$lat_from.','.$lng_from.','.$lat_to.','.$lng_to,$zoom,$x,$y);
		}
		if($zoom>8){
			$items_count=pow(2,$zoom);
			$lng_deg_per_item=360/$items_count;
			$lng_from=-180+$x*$lng_deg_per_item;
			$lng_to=-180+($x+1)*$lng_deg_per_item;
			
			$lat_deg_per_item=(85.0511*2)/$items_count;
			$lat_to=rad2deg(atan(sinh(pi() * (1 - 2 * $y / $items_count))));
			$lat_from=rad2deg(atan(sinh(pi() * (1 - 2 * ($y+1) / $items_count))));
			//$lbroads=new \App\Lbroads();
			
			return $this->get_overpass($this->roads,$lat_from.','.$lng_from.','.$lat_to.','.$lng_to,$zoom,$x,$y);
		}
		$params=[
			['x'=>2*$x,'y'=>2*$y],
			['x'=>2*$x,'y'=>2*$y+1],
			['x'=>2*$x+1,'y'=>2*$y],
			['x'=>2*$x+1,'y'=>2*$y+1],
		];
		$elements=[];
		foreach($params as $param){
			$result=$this->get_overpass_result($zoom+1,$param['x'],$param['y']);
			$elements=array_merge($elements,$result['elements']);
		}
		$result['elements']=$elements;
		return $result;
	}
	public function get_overpass($data,$bbox='',$zoom=0,$x=0,$y=0){
		if(!empty($zoom)){
			$path=base_path('lb_json/d_'.$zoom.'.'.$x.'.'.$y.'.json');
			if(file_exists($path)){
				return json_decode(file_get_contents($path),true);
			}
		}
		//var_dump('qq');
		if(empty($result)){
			if(php_sapi_name()=='cli'){var_dump('before osmconvert|time:'.time());}
			/* overpass 
			//$result=$this->overpass($data,$bbox,$zoom,$x,$y);
			//$elements=$result['elements'];
			//$elements=$this->filter_overpass($elements);
			//$elements=$this->drop_nodes($elements);
			*/
			$result=$this->osmconvert($data,$bbox,$zoom,$x,$y);
			if(php_sapi_name()=='cli'){var_dump('after osmconvert|time:'.time());}
		}
		if(!empty($zoom)){
			file_put_contents($path,json_encode($result));
		}
		return $result;
	}



	
}