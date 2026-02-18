This repository now contains a basic Laravel skeleton. Existing helper
classes have been moved under `app/Services` so they can be autoloaded
via PSR-4. Legacy PHP scripts have been converted to Artisan commands
and controllers so the project follows typical Laravel conventions.

OSM vector tiles pipeline (PostGIS + osm2pgsql + Martin) is also included
under `tileproduction`. It imports `highway=lbroad` lines into PostGIS
with full tags (hstore), creates a light-weight tiles view, and serves
MVT tiles through Martin.

The Laravel router now serves the original HTML page at `/`. Tile
requests for overlays and rendered maps are handled by `MapController`:
`/lb_overlay/{z}/{x}/{y}.png` and `/lb_map/{z}/{x}/{y}.png`.

To get started you will need to install dependencies using Composer and
configure your environment variables based on `.env.example`.

OSM pipeline quickstart:

1. Set the PBF path for your machine:
   `export PBF_PATH=/mnt/d/downloads/planet-filtered.pbf`
2. Start PostGIS and Martin:
   `docker compose up -d postgres martin`
3. Run import (one-off, can take hours for large files):
   `docker compose --profile import run --rm osm2pgsql`
4. Create the tiles view and indexes:
   `docker compose exec -T postgres psql -U lbmap -d lbmap -f /sql/02_lbroads_tiles.sql`
5. Fetch tiles:
   `http://localhost:3000/tiles/lbroads_tiles/{z}/{x}/{y}.pbf`

Notes:

- Mapping: `tileproduction/osm2pgsql/flex.lua`
- SQL view/index: `tileproduction/sql/02_lbroads_tiles.sql`
- Full tags are stored in `osm_lbroads.tags`
