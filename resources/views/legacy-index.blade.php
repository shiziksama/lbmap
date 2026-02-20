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
				tiles: ['http://localhost:3000/lbroads_tiles/{z}/{x}/{y}'],
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
				id: 'lb_undefined',
				type: 'line',
				source: 'lbroads',
				'source-layer': 'lbroads_tiles',
				filter: ['==', ['get', 'lbroads'], 'undefined'],
				minzoom: 0,
				maxzoom: 20,
				paint: {
					'line-opacity': 0.9,
					'line-width': 8,
					'line-color': 'rgb(0,0,0)'
				},
				layout: {
					'line-cap': 'butt',
					'line-join': 'round'
				}
			},
			{
				id: 'lb_bikelane',
				type: 'line',
				source: 'lbroads',
				'source-layer': 'lbroads_tiles',
				filter: ['==', ['get', 'lbroads'], 'bikelane'],
				minzoom: 0,
				maxzoom: 20,
				paint: {
					'line-opacity': 0.9,
					'line-width': 8,
					'line-color': 'rgb(0,0,255)'
				},
				layout: {
					'line-cap': 'butt',
					'line-join': 'round'
				}
			},
			{
				id: 'lb_foot',
				type: 'line',
				source: 'lbroads',
				'source-layer': 'lbroads_tiles',
				filter: ['==', ['get', 'lbroads'], 'foot'],
				minzoom: 0,
				maxzoom: 20,
				paint: {
					'line-opacity': 0.9,
					'line-width': 8,
					'line-color': 'rgb(40,252,3)'
				},
				layout: {
					'line-cap': 'butt',
					'line-join': 'round'
				}
			},
			{
				id: 'lb_bicycle_undefined',
				type: 'line',
				source: 'lbroads',
				'source-layer': 'lbroads_tiles',
				filter: ['==', ['get', 'lbroads'], 'bicycle_undefined'],
				minzoom: 0,
				maxzoom: 20,
				paint: {
					'line-opacity': 0.9,
					'line-width': 8,
					'line-color': 'rgb(255,0,0)'
				},
				layout: {
					'line-cap': 'butt',
					'line-join': 'round'
				}
			},
			{
				id: 'lb_greatfoot',
				type: 'line',
				source: 'lbroads',
				'source-layer': 'lbroads_tiles',
				filter: ['==', ['get', 'lbroads'], 'greatfoot'],
				minzoom: 0,
				maxzoom: 20,
				paint: {
					'line-opacity': 0.9,
					'line-width': 8,
					'line-color': 'rgb(19,130,0)'
				},
				layout: {
					'line-cap': 'butt',
					'line-join': 'round'
				}
			},
			{
				id: 'lb_great',
				type: 'line',
				source: 'lbroads',
				'source-layer': 'lbroads_tiles',
				filter: ['==', ['get', 'lbroads'], 'great'],
				minzoom: 0,
				maxzoom: 20,
				paint: {
					'line-opacity': 0.9,
					'line-width': 8,
					'line-color': 'rgb(125,0,125)'
				},
				layout: {
					'line-cap': 'butt',
					'line-join': 'round'
				}
			},
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

const controls = document.createElement('div');
controls.className = 'map-controls';
controls.innerHTML =
	'<div class="legend">' +
		'<label class="legend_row"><input type="checkbox" data-layer="lb_great" checked><i style="background:rgb(125,0,125)"></i> <div class="text">Велодоріжки з якісним покриттям</div></label>' +
		'<label class="legend_row"><input type="checkbox" data-layer="lb_bicycle_undefined" checked><i style="background:rgb(255,0,0)"></i> <div class="text">Велодоріжки з невідомим покриттям</div></label>' +
		'<label class="legend_row"><input type="checkbox" data-layer="lb_bikelane" checked><i style="background:rgb(0,0,255)"></i> <div class="text">Велосмуги</div></label>' +
		'<label class="legend_row"><input type="checkbox" data-layer="lb_greatfoot" checked><i style="background:rgb(19,130,0)"></i> <div class="text">Тротуар або вірогідний проїзд (асфальт)</div></label>' +
		'<label class="legend_row"><input type="checkbox" data-layer="lb_foot" checked><i style="background:rgb(40,252,3)"></i> <div class="text">Тротуар або вірогідний проїзд</div></label>' +
		'<label class="legend_row"><input type="checkbox" data-layer="lb_undefined" checked><i style="background:rgb(0,0,0)"></i> <div class="text">Невідомі</div></label>' +
	'</div>';
mymap.getContainer().appendChild(controls);

function setLayerVisibility(layerId, visible) {
	mymap.setLayoutProperty(layerId, 'visibility', visible ? 'visible' : 'none');
}

controls.querySelectorAll('input[type="checkbox"][data-layer]').forEach(function (checkbox) {
	checkbox.addEventListener('change', function (e) {
		const layerId = e.target.getAttribute('data-layer');
		setLayerVisibility(layerId, e.target.checked);
	});
});

</script>
<style>
.map-controls{
	background:white;
	padding:8px 10px;
	border-radius:2px;
	border:2px solid rgba(0,0,0,0.2);
	position:absolute;
	top:10px;
	right:10px;
	z-index:10;
	display:flex;
	gap:12px;
	align-items:flex-start;
}
.legend{
	min-width:30px;
	display:flex;
	flex-direction:column;
	gap:4px;
}
.legend_row{
	display:flex;
    align-items: center;
    padding-left: 8px;
	gap:6px;
	cursor:pointer;
	font-family: Arial, sans-serif;
	font-size: 13px;
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

.legend input{
	cursor:pointer;
}
</style>
	     </body>
</html>
