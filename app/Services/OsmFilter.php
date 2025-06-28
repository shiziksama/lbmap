<?php

namespace App\Services;

class OsmFilter{
	public static function modify_tags($tags){
		if(!empty($tags['cycleway:surface'])){
			$tags['surface']=$tags['cycleway:surface'];
			$tags['bicycle']='designated';
			unset($tags['cycleway:surface']);
		}
		return $tags;
	}
	public static function is_bicycledesignated($tags){
		$types=['track','separate','opposite_track','use_sidepath'];
		$designated=false;
		$designated=$designated||(!empty($tags['bicycle'])&&in_array($tags['bicycle'],['designated','use_sidepath']));
		$designated=$designated||(!empty($tags['bicycle_road'])&&in_array($tags['bicycle_road'],['yes']));
		$designated=$designated||(!empty($tags['cyclestreet'])&&in_array($tags['cyclestreet'],['yes']));
		$designated=$designated||(!empty($tags['cycleway'])&&in_array($tags['cycleway'],$types));
		$designated=$designated||(!empty($tags['cycleway:left'])&&in_array($tags['cycleway:left'],$types));
		$designated=$designated||(!empty($tags['cycleway:right'])&&in_array($tags['cycleway:right'],$types));
		$designated=$designated||(!empty($tags['cycleway:both'])&&in_array($tags['cycleway:both'],$types));
		$designated=$designated||(!empty($tags['highway'])&&$tags['highway']=='cycleway');
		$designated=$designated||(!empty($tags['highway'])&&$tags['highway']=='cycleway');
		return $designated;
	}
	public static function is_footdesignated($tags){
		$designated=false;
		$designated=$designated||(!empty($tags['foot'])&&in_array($tags['foot'],['designated','use_sidepath']));
		$designated=$designated||(!empty($tags['highway'])&&$tags['highway']=='footway');
		$designated=$designated||(!empty($tags['footway'])&&$tags['footway']=='sidewalk');
		return $designated;
	}
	public static function is_surface_great($tags){
		$surface=false;
		$surface=$surface||(!empty($tags['surface'])&&in_array($tags['surface'],['asphalt','paving_stones','concrete']));
		$surface=$surface||(!empty($tags['smoothness'])&&in_array($tags['smoothness'],['good','excellent']));
		//$surface=$surface||(!empty($tags['tracktype'])&&in_array($tags['tracktype'],['grade1']));
		return $surface;
	}
	public static function no_surface_information($tags){
		$all_tags=implode('',array_keys($tags)).implode('',array_values($tags));
		if(
			(strpos($all_tags,'surface')===false)&&
			(strpos($all_tags,'smoothness')===false)
			)return true;//есть упоминания сурфейса
		return false;
	}
	public static function test_great($tags){
		$designated=self::is_bicycledesignated($tags);
		$surface=self::is_surface_great($tags);
		if($designated&&$surface)return true;
		return false;
	}
	public static function test_bicycleundefined($tags){
		$designated=self::is_bicycledesignated($tags);
		$no_surface=self::no_surface_information($tags);
		if($designated&&$no_surface)return true;//для велосипедов, но нету вообще информации о качестве покрытия
		return false;
	}
	public static function test_bikelane($tags){
		$lanes=['lane','line','shared_lane','share_busway','opposite_lane'];
		if(!empty($tags['cycleway'])&&in_array($tags['cycleway'],$lanes))return true;
		if(!empty($tags['cycleway:left'])&&in_array($tags['cycleway:left'],$lanes))return true;
		if(!empty($tags['cycleway:right'])&&in_array($tags['cycleway:right'],$lanes))return true;
		if(!empty($tags['cycleway:both'])&&in_array($tags['cycleway:both'],$lanes))return true;
		return false;
	}
	public static function test_greatother($tags){
		$all_tags=implode('',array_keys($tags)).implode('',array_values($tags));
		if(
			(strpos($all_tags,'cycle')!==false)
			)return false;//есть упоминания велосипедов
			
		$designated=self::is_footdesignated($tags);
		$surface=self::is_surface_great($tags);
		if($designated&&$surface)return true;
		if(!empty($tags['highway'])&&$tags['highway']=='track'&&$surface)return true;
		
		return false;
	}
	public static function test_foot($tags){
		$all_tags=implode('',array_keys($tags)).implode('',array_values($tags));
		if(
			(strpos($all_tags,'cycle')!==false)
			)return false;//есть упоминания велосипедов
		$designated=self::is_footdesignated($tags);
		$no_surface=self::no_surface_information($tags);
		if($designated&&$no_surface)return true;//для велосипедов, но нету вообще информации о качестве покрытия
		return false;
	}
	public static function test_no($tags){
		$all_tags=implode('',array_keys($tags)).implode('',array_values($tags));
		$no_great_tags=(strpos($all_tags,'cycle')===false)&&(strpos($all_tags,'foot')===false)&&(strpos($all_tags,'pedestrian')===false);
		$no_surface=self::no_surface_information($tags);
		if($no_great_tags&&$tags['highway']!='track')return true;//никакого упоминания ни велосипедов, ни людей.
		if($no_surface&&$no_great_tags&&$tags['highway']=='track')return true;//нету покрытия и это трек без велосипедов и ходьбы
		$filters=[
			['highway'=>'construction'],
			['highway'=>'steps'],
			['highway'=>'proposed'],
			['highway'=>'platform'],
			['highway'=>'bus_stop'],
			['highway'=>'rest_area'],
			['highway'=>'bridleway'],
			['highway'=>'via_ferrata'],
			['highway'=>'planned'],
			['highway'=>'corridor'],
			['highway'=>'raceway'],
			['highway'=>'elevator'],
			['highway'=>'emergency_bay'],
			['highway'=>'services'],
			['amenity'=>'parking'],
			['amenity'=>'services'],//заправка
			['smoothness'=>'bad'],
			['designation'=>'public_bridleway'],
			['smoothness'=>'very_bad'],
			['smoothness'=>'very_horrible'],
			['smoothness'=>'horrible'],
			['smoothness'=>'impassable'],
			['smoothness'=>'medium'],
			['footway'=>'crossing'],
			['surface'=>'dirt'],
			['surface'=>'unpaved'],
			['surface'=>'gravel'],
			['surface'=>'grass'],
			['surface'=>'ground'],
			['surface'=>'sand'],
			['surface'=>'earth'],
			['surface'=>'pebblestone'],
			['surface'=>'fine_gravel'],
			['surface'=>'cobblestone'],
			['surface'=>'concrete:plates'],
			['surface'=>'concrete:lanes'],
			['surface'=>'wood'],
			['surface'=>'metal'],
			['surface'=>'stone'],
			['surface'=>'grass_paver'],
			['area'=>'yes'],
			['ice_road'=>'yes'],
			['winter_road'=>'yes'],
			['tracktype'=>'grade2'],
			['tracktype'=>'grade3'],
			['tracktype'=>'grade4'],
			['tracktype'=>'grade5'],
			['tracktype'=>'indeterminate'],//не совсем правильно, но пусть будет
			['access'=>'private'],
		];
		foreach($filters as $filter){
			foreach($filter as $k=>$v){
				if(!empty($tags[$k])&&$tags[$k]==$v)return true;
			}
		}	
		return false;
	}
	public static function test_element($element){
		if(empty($element['tags']))return 'no';
		if(!empty($element['tags']['lbroads'])) return $element['tags']['lbroads'];
		if(self::test_no($element['tags']))return 'no';
		if(self::test_great($element['tags']))return 'great';
		if(self::test_bicycleundefined($element['tags'])) return 'bicycle_undefined';
		if(self::test_bikelane($element['tags'])) return 'bikelane';
		if(self::test_greatother($element['tags'])) return 'greatfoot';
		if(self::test_foot($element['tags'])) return 'foot';
		return 'undefined';
		
	}
	
}