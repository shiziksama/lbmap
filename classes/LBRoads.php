<?php 
class LBRoads{
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
	public function get_converted($zoom,$x,$y){
		$filename=$this->create_o5m($zoom,$x,$y);
		$items_count=pow(2,$zoom);
		$lng_deg_per_item=360/$items_count;
		$lng_from=-180+$x*$lng_deg_per_item;
		$lng_to=-180+($x+1)*$lng_deg_per_item;
		
		$lat_deg_per_item=(85.0511*2)/$items_count;
		$lat_to=rad2deg(atan(sinh(pi() * (1 - 2 * $y / $items_count))));
		$lat_from=rad2deg(atan(sinh(pi() * (1 - 2 * ($y+1) / $items_count))));
		
		$bbox=$lng_from.','.$lat_from.','.$lng_to.','.$lat_to;
		return $this->get_elements($filename,$bbox);
	}
	public function get_elements($filename,$bbox){
		$shell='osmconvert '.base_path('o5m/'.$filename).' --complete-ways --drop-author -b='.$bbox;
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
		return $elements;
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
		
		$elements=$this->get_converted($zoom,$x,$y);
		
		$pre_lines=[];
		foreach($elements as $el){
			if($el['type']=='way'){
				$pre_lines[]=$el;
			}elseif($el['type']=='node'){
				$points[$el['id']]=['lat'=>$el['lat'],'lng'=>$el['lon']];
			}
		}
		$lines=[];
		foreach($pre_lines as $pre_line){
			foreach($pre_line['nodes'] as $pt){
				if(empty($points[$pt])){
					
					var_dump($pre_line);
					var_dump($pt);
					continue;//STRONGLY TODO
				}
				$pre_line['points'][]=$points[$pt];
			}
			unset($pre_line['nodes']);
			$lines[]=$pre_line;
		}
		if(php_sapi_name()=='cli'){var_dump('filtered_lines|time:'.time());}
		file_put_contents($path,json_encode($lines));
		return $lines;	
	}
}