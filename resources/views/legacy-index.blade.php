<?php

$zoom = 15;
$x = 17155;
$y = 11191;

$items_count = pow(2, $zoom);
$lng_deg_per_item = 360 / $items_count;
$lng_from = -180 + $x * $lng_deg_per_item;
$lng_to = -180 + ($x + 1) * $lng_deg_per_item;

$lat_deg_per_item = (85.0511 * 2) / $items_count;
$lat_to = rad2deg(atan(sinh(pi() * (1 - 2 * $y / $items_count))));
$lat_from = rad2deg(atan(sinh(pi() * (1 - 2 * ($y + 1) / $items_count))));

$bbox = $lng_from.','.$lat_from.','.$lng_to.','.$lat_to;
// var_dump($bbox);
// die();
?>
<!DOCTYPE html>
<html>
    <head>
    <title>Карта велодоріжок</title>
	
	<meta name="viewport" content="width=device-width" />
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js" integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
  <style>
  #mapid { 
  position:fixed;
  left:0;
  right:0;
  bottom:0;
  top:0;
 
  }
  </style>
    </head>
    <body  lang="ru">
	 <div id="mapid"></div>
<script>
let clat = localStorage.getItem('lat');
let clng = localStorage.getItem('lng');
let cz = localStorage.getItem('zoom');
if(!clat){
	clat=50.456;
}
if(!clng){
	clng=30.481516;
}
if(!cz){
	cz=6;
}

var mymap = L.map('mapid', {
    center: [clat, clng],
    zoom: cz
});




L.tileLayer('https://maps.geoapify.com/v1/tile/positron/{z}/{x}/{y}.png?apiKey=500c2912a9584dbfb20018405f772c3d', {
  attribution: 'Powered by <a href="https://www.geoapify.com/" target="_blank">Geoapify</a> | <a href="https://openmaptiles.org/" target="_blank">© OpenMapTiles</a> <a href="https://www.openstreetmap.org/copyright" target="_blank">© OpenStreetMap</a> contributors | <a href="https://lamastravels.in.ua/" target="_blank">Authors</a>',
  maxZoom: 20, id: 'osm-bright'
}).addTo(mymap);


const lbmap=L.tileLayer('/lb_overlay/{z}/{x}/{y}.png', {
	maxZoom: 18,
    tileSize: 256,
    zoomOffset: 0,
	opacity:0.9,
}).addTo(mymap);
const layerControl = L.control.layers({},{"Карта велодоріжок":lbmap}).addTo(mymap);




mymap.addEventListener('moveend',function(ev){
	localStorage.setItem('lat',mymap.getCenter().lat);
	localStorage.setItem('lng',mymap.getCenter().lng);
	//console.log(mymap.getCenter());
});

mymap.addEventListener('zoomend',function(ev){
	localStorage.setItem('zoom',mymap.getZoom());
});
	
	
	
var locationMarker;

function onLocationFound(e) {
	if(!locationMarker){
		locationMarker = L.marker(e.latlng).addTo(mymap);
	}else{
		locationMarker.setLatLng(e.latlng);
	}
}

mymap.on('locationfound', onLocationFound);
mymap.locate({watch: true});


 var legend = L.control({position: 'topleft'});
legend.onAdd=function(map){
	var div=L.DomUtil.create('div','legend');
	/*
	'great'=>'125,0,125','bicycle_undefined'=>'255,0,0','bikelane'=>'0,0,255','undefined'=>'0,0,0','foot'=>'40,252,3','greatfoot'=>'19,130,0'
	*/
	div.innerHTML=
		'<div class="legend_row"><i style="background:rgb(125,0,125)"></i> <div class="text">Велодоріжки з якісним покриттям</div></div>'+
		'<div class="legend_row"><i style="background:rgb(255,0,0)"></i> <div class="text">Велодоріжки з невідомим покриттям</div></div>'+
		'<div class="legend_row"><i style="background:rgb(0,0,255)"></i> <div class="text">Велосмуги</div></div>'+
		'<div class="legend_row"><i style="background:rgb(19,130,0)"></i> <div class="text">Тротуар або вірогідний проїзд (асфальт)</div></div>'+
		'<div class="legend_row"><i style="background:rgb(40,252,3)"></i> <div class="text">Тротуар або вірогідний проїзд</div></div>'		
	;
	return div;
}
legend.addTo(mymap);

</script>
<style>
.legend{
	background:white;
	min-width:30px;
	padding:5px 0;
	border-radius:2px;
	border:2px solid rgba(0,0,0,0.2)	
	
}
.legend_row{
	display:flex;
    align-items: center;
    padding-left: 8px;
}
.legend .text{
	max-width:0px;
	margin-left:0px;
	overflow: hidden;
    white-space: nowrap;
	transition:all 0.2s linear;
    margin-right: 0px;
}

.legend i{
	margin:2px;
	width:10px;
	height:10px;
	display:inline-block;
	border-radius:50%;
	
}
.legend:hover .text{
	max-width:250px;
	margin-left:7px;
	margin-right: 10px;
}
</style>
	     </body>
</html>