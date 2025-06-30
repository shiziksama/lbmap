<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class LBRoads{
       private function runOsmconvert(string $filename, string $params, bool $returnOutput = true)
       {
               $command = 'osmconvert ' . $filename . ' ' . $params;

               $lockName = 'osmconvert:' . md5($filename);

               $result = Cache::lock($lockName, 600)->block(0, function () use ($command) {
                       return Process::run($command);
               });

               return $returnOutput ? $result->output() : '';
       }

        public function create_o5m($zoom,$x,$y){
		while($zoom>4){
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
		
		$lat_to=rad2deg(atan(sinh(pi() * (1 - 2 * $y / $items_count))));
		$lat_from=rad2deg(atan(sinh(pi() * (1 - 2 * ($y+1) / $items_count))));
		
               $osmFile = base_path('o5m/planet-highways.o5m');
               $params = '--complete-ways --drop-author -b='.$lng_from.','.$lat_from.','.$lng_to.','.$lat_to.' -o='.base_path('o5m/'.$filename);
               var_dump('osmconvert '.$osmFile.' '.$params);
               var_dump(time());
               $this->runOsmconvert($osmFile, $params, false);
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
                $osmFile = base_path('o5m/'.$filename);
                $params = '--complete-ways --drop-author -b=' . $bbox;
//              if(php_sapi_name()=='cli'){var_dump('start_convert|time:'.time().'|osmconvert '.$osmFile.' '.$params);}
                $s = $this->runOsmconvert($osmFile, $params);
		//if(php_sapi_name()=='cli'){var_dump('get_xml|time:'.time());}
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
				//var_dump((string)$z->getAttribute('id'));
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
                $osm=null;
				$z->endElement();
			}
		}
//		if(php_sapi_name()=='cli'){var_dump('after_xml|time:'.time());}
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
               $file = 'l_'.$zoom.'.'.$x.'.'.$y.'.packed';
               Storage::disk('data_cache')->put($file, $this->lines2file($lines));
               return $lines;
	}
	public function file2lines($content){
		$lbroads = $this->getLbroadsMapping();
		$encoded = gzuncompress($content);
		$pointlines = array_values(unpack('i*', $encoded));
		$lines = [];
		while(!empty($pointlines)){
			$lines[] = $this->parseLine($pointlines, $lbroads);
		}
		return $lines;
	}

	private function getLbroadsMapping(){
		return [
			1 => 'great',
			2 => 'bicycle_undefined',
			3 => 'bikelane',
			4 => 'greatfoot',
			5 => 'foot',
			6 => 'undefined'
		];
	}

	private function parseLine(&$pointlines, $lbroads){
		$line = [];
		$line['tags']['lbroads'] = $lbroads[array_shift($pointlines)];
		$count = round(array_shift($pointlines) / 2);
		$line['points'] = $this->parsePoints($pointlines, $count);
		return $line;
	}

	private function parsePoints(&$pointlines, $count){
		$points = [];
		while($count > 0){
			$points[] = [
				'lat' => array_shift($pointlines) / 10000000,
				'lng' => array_shift($pointlines) / 10000000
			];
			$count--;
		}
		return $points;
	}
	public function lines2file($lines){
		//return json_encode($lines);
		//$lines=array_slice($lines,0,2);
		$lbroads=['great'=>1,'bicycle_undefined'=>2,'bikelane'=>3,'greatfoot'=>4,'foot'=>5,'undefined'=>6];
		$packed='';
		foreach($lines as $line){
			$pointline=[];
			$type=$lbroads[$line['tags']['lbroads']??'undefined'];
			foreach($line['points'] as $point){
				$pointline[]=$point['lat']*10000000;
				$pointline[]=$point['lng']*10000000;
				//$pointline[]=(180.1234567*10000000);
				
			}
			$pointlines[]=$pointline;
			$packed.=pack('i*',$type,count($pointline),...$pointline);
		}
		//$coord=(int)(180.1234567*10000000);
		//var_dump(PHP_INT_MAX);
		//var_dump(PHP_INT_MIN);
		//var_dump($coord);
		$compressed=gzcompress($packed,9);
		return $compressed;
	}
       public function get_lines($zoom,$x,$y){
               $file = 'l_'.$zoom.'.'.$x.'.'.$y.'.packed';
               if(Storage::disk('data_cache')->exists($file)){
                       return $this->file2lines(Storage::disk('data_cache')->get($file));
               }
               if($zoom>7){
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
				//Іноді точка відсутня, того що вона не входить в bbox. але присутній шлях
				if(!empty($points[$pt])){
					$pre_line['points'][]=$points[$pt];
				}
			}
			unset($pre_line['nodes']);
			if(!empty($pre_line['points'])){
				$lines[]=$pre_line;
			}
		}
//		if(php_sapi_name()=='cli'){var_dump('filtered_lines|time:'.time());}
               Storage::disk('data_cache')->put($file,$this->lines2file($lines));
               return $lines;
       }
}