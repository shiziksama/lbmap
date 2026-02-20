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
	<link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
	<script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
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

const mymap = new maplibregl.Map({
	container: 'mapid',
	center: [parseFloat(clng), parseFloat(clat)],
	zoom: parseFloat(cz),
	style: {
		version: 8,
		sources: {
			base: {
				type: 'raster',
				tiles: ['https://maps.geoapify.com/v1/tile/positron/{z}/{x}/{y}.png?apiKey=500c2912a9584dbfb20018405f772c3d'],
				tileSize: 256,
				attribution: 'Powered by <a href="https://www.geoapify.com/" target="_blank">Geoapify</a> | <a href="https://openmaptiles.org/" target="_blank">© OpenMapTiles</a> <a href="https://www.openstreetmap.org/copyright" target="_blank">© OpenStreetMap</a> contributors | <a href="https://lamastravels.in.ua/" target="_blank">Authors</a>'
			},
			lbroads: {
				type: 'vector',
				tiles: ['http://localhost:3000/osm_lbroads/{z}/{x}/{y}'],
				attribution: '© OpenStreetMap contributors'
			}
		},
		layers: [
			{
				id: 'base',
				type: 'raster',
				source: 'base',
				minzoom: 0,
				maxzoom: 20
			},
			{
				id: 'lb_lines',
				type: 'line',
				source: 'lbroads',
				'source-layer': 'osm_lbroads',
				minzoom: 0,
				maxzoom: 20,
				paint: {
					'line-opacity': 0.9,
					'line-width': 8,
					'line-color': [
						'match',
						['get', 'lbroads'],
						'great',
						'rgb(125,0,125)',
						'bicycle_undefined',
						'rgb(255,0,0)',
						'bikelane',
						'rgb(0,0,255)',
						'greatfoot',
						'rgb(19,130,0)',
						'foot',
						'rgb(40,252,3)',
						'undefined',
						'rgb(0,0,0)',
						'rgb(0,0,0)'
					]
				},
				layout: {
					'line-cap': 'butt',
					'line-join': 'round'
				}
			}
		]
	}
});

mymap.on('moveend', function() {
	const center = mymap.getCenter();
	localStorage.setItem('lat', center.lat);
	localStorage.setItem('lng', center.lng);
});

mymap.on('zoomend', function() {
	localStorage.setItem('zoom', mymap.getZoom());
});

let locationMarker;

function onLocationFound(position) {
	const lngLat = [position.coords.longitude, position.coords.latitude];
	if (!locationMarker) {
		locationMarker = new maplibregl.Marker().setLngLat(lngLat).addTo(mymap);
	} else {
		locationMarker.setLngLat(lngLat);
	}
}

if (navigator.geolocation) {
	navigator.geolocation.watchPosition(onLocationFound);
}

const legend = document.createElement('div');
legend.className = 'legend';
legend.innerHTML =
	'<div class="legend_row"><i style="background:rgb(125,0,125)"></i> <div class="text">Велодоріжки з якісним покриттям</div></div>' +
	'<div class="legend_row"><i style="background:rgb(255,0,0)"></i> <div class="text">Велодоріжки з невідомим покриттям</div></div>' +
	'<div class="legend_row"><i style="background:rgb(0,0,255)"></i> <div class="text">Велосмуги</div></div>' +
	'<div class="legend_row"><i style="background:rgb(19,130,0)"></i> <div class="text">Тротуар або вірогідний проїзд (асфальт)</div></div>' +
	'<div class="legend_row"><i style="background:rgb(40,252,3)"></i> <div class="text">Тротуар або вірогідний проїзд</div></div>';
mymap.getContainer().appendChild(legend);

</script>
<style>
.legend{
	background:white;
	min-width:30px;
	padding:5px 0;
	border-radius:2px;
	border:2px solid rgba(0,0,0,0.2);
	position:absolute;
	top:10px;
	left:10px;
	z-index:10;
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
