
<!DOCTYPE html>
<html>
    <head>
    <title>тречки</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css"
   integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
   crossorigin=""/>
   <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js"
   integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew=="
   crossorigin=""></script>
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
/*
L.tileLayer('https://tile.thunderforest.com/cycle/{z}/{x}/{y}@2x.png?apikey=cdeea879c575479fbf645def237f4afa', {
    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
    maxZoom: 18,
    //id: 'mapbox/outdoors-v11',
    tileSize: 512,
    zoomOffset: -1,
    //accessToken: 'pk.eyJ1Ijoic2hpemlrc2FtYSIsImEiOiJja2I2bWNsbm0wMDJlMnFvYmRwanVma3ZnIn0.-2IBbm2m-ZnEv-EjvH7WAA'
}).addTo(mymap);
*/
//L.tileLayer('https://tracks.lamastravels.in.ua/map_overlay/1/{z}/{x}/{y}.png', {
/*
L.tileLayer('http://lbmap.lamastravels.in.ua/lb_map/{z}/{x}/{y}.png', {
	maxZoom: 18,
    tileSize: 256,
    zoomOffset: 0,
}).addTo(mymap);
	*/

L.tileLayer('http://localhost:8000/lb_map/{z}/{x}/{y}.png', {
	maxZoom: 18,
    tileSize: 256,
    zoomOffset: 0,
}).addTo(mymap);


mymap.addEventListener('moveend',function(ev){
	localStorage.setItem('lat',mymap.getCenter().lat);
	localStorage.setItem('lng',mymap.getCenter().lng);
	//console.log(mymap.getCenter());
});

mymap.addEventListener('zoomend',function(ev){
	localStorage.setItem('zoom',mymap.getZoom());
});
	

</script>
	     </body>
</html>