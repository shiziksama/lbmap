<?php
include __DIR__.'/../vendor/autoload.php';
$lbroads=new LBRoads;

$z = new WeblamasXMLReader();
$z->open("php://stdin");
echo "<?xml version='1.0' encoding='UTF-8'?>".PHP_EOL;
while($z->read()) {
	if($z->name=='node'&&$z->nodeType==\XmlReader::ELEMENT){
		echo '<node id="'.$z->getAttribute('id').'" lat="'.$z->getAttribute('lat').'" lon="'.$z->getAttribute('lon').'"/>'.PHP_EOL;
		$z->endElement();
		continue;
		//break;
	}elseif($z->name=='way'&&$z->nodeType==\XmlReader::ELEMENT){		
		$node=simplexml_load_string($z->readOuterXml());
		$nodes=[];
		$tags=[];
		foreach($node as $key=>$child){
			if($key=='nd'){
				$nodes[]=''.$child->attributes()->ref;
			}elseif($key=='tag'){
				$tags[''.$child->attributes()->k]=''.$child->attributes()->v;
			}
		}
		if(empty($tags['highway'])){
			$z->endElement();
			continue;
		};//Только дороги
		$tags=OsmFilter::modify_tags($tags);
		$result=OsmFilter::test_element(['tags'=>$tags]);
		if($result=='no'){
			//$tags=['lbroads'=>'no','highway'=>'lbroad'];
			$z->endElement();
			continue;
		}elseif(in_array($result,['great','bicycle_undefined','bikelane','greatfoot','foot'])){
			$tags=['lbroads'=>$result,'highway'=>'lbroad'];
		}
		
		echo '<way id="'.$node->attributes()->id.'">'.PHP_EOL;
		foreach($nodes as $nd){
			echo '<nd ref="'.$nd.'"/>'.PHP_EOL;
		}
		foreach($tags as $k=>$v){
         echo '	<tag k="'.$k.'" v="'.$v.'"/>'.PHP_EOL;
		}
        echo '</way>'.PHP_EOL;
		$z->endElement();
	}else{
		if($z->nodeType==\XmlReader::ELEMENT){
			$attributes='';
			$name=$z->name;
			if($z->hasAttributes)  {
				while($z->moveToNextAttribute()) { 
					$attributes.=' '.$z->name. '="'. $z->value. '"'; 
				}
			}
			echo '<'.$name.$attributes.'>'.PHP_EOL;
		}elseif($z->nodeType==\XmlReader::END_ELEMENT){
			echo '</'.$z->name.'>'.PHP_EOL;
		}
		/*
		<osm version="0.6" generator="osmconvert 0.8.10" timestamp="2021-02-01T21:42:02Z">
        <bounds minlat="44.008624" minlon="22.132644" maxlat="52.386497" maxlon="40.238113"/>
		*/
	}
	//var_dump($z->nodeType);
	//die();
	//echo '';//TODO echo readed item
	//if(@!$z->read())break;//one more very bad solution
}
/*
for($i=0;$i<10;$i++){
$line = fgets(STDIN);
var_dump($line);
}
*/
echo '</xml>';