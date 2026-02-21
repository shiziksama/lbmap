# LBMap

LBMap is a Docker-first pipeline and service stack for generating vector tiles for bike lane data from OpenStreetMap. It filters raw OSM PBFs, imports the result into PostGIS, and serves tiles via Martin, with optional APIs and legacy static pages.

## What’s inside

- End-to-end PBF filtering pipeline (osmium + custom C++/Python filters)
- PostGIS import via osm2pgsql (flex mapping)
- Tile server (Martin)
- Optional PostgREST and static legacy UI via Nginx

## Architecture overview

Raw OSM PBF → highway extraction → custom filters → cleaned PBF → PostGIS (osm2pgsql) → SQL views → tiles (Martin)

## Prerequisites

- Docker and Docker Compose
- Sufficient disk space for PBFs and intermediate files

## Quick start

1. Copy `.env.example` to `.env`.
2. Set `DATA_DIR` to the folder with your PBF files.
3. Put `planet-latest.osm.pbf` into `DATA_DIR`.
4. Run the preparation pipeline.
5. Start services and import the filtered PBF.

```bash
docker compose --profile prepare run --rm pbf-pipeline
docker compose up -d
docker compose --profile import run --rm osm2pgsql
docker compose exec -T postgres psql -U lbmap -d lbmap -f /sql/02_lbroads_tiles.sql
```

## Configuration

Docker Compose automatically reads `.env` next to `docker-compose.yml`.

Required:

- `DATA_DIR=./data` (folder with `planet-latest.osm.pbf` and all pipeline outputs)

Optional pipeline variables (useful for custom inputs/outputs):

- `PBF_IN` (default `/data/planet-latest.osm.pbf`)
- `PBF_HIGHWAYS` (default `/data/planet-highways.osm.pbf`)
- `PBF_CPP_OUT` (default `/data/planet-filtered-cpp.osm.pbf`)
- `PBF_CPP_CLEAN` (default `/data/planet-filtered-cpp-clean.osm.pbf`)
- `PBF_PY_OUT` (default `/data/planet-filtered-py.osm.pbf`)
- `PBF_OUT` (default `/data/planet-filtered.osm.pbf`)

Notes:

- `PBF_OUT` is also used by the `osm2pgsql` import in `docker-compose.yml`.
- Use absolute paths in `.env` if you want to store data outside the repo.

## Pipeline details

Entry point: `o5m/pbf_pipeline.sh` (executed in the `pbf-pipeline` container).

Steps:

1. `osmium tags-filter` extracts highways into `planet-highways.osm.pbf`.
2. `filter_osmium_cpp` applies the C++ filter.
3. `osmium tags-filter` cleans up the C++ output.
4. `filter_osmium.py` applies the Python filter rules.
5. `osmium tags-filter` cleans up the Python output into `planet-filtered.osm.pbf`.

Resume behavior:

- The script checks which output files already exist and resumes from the last completed step.
- To force a full re-run, remove existing output files in `DATA_DIR`.

## Import and services

### Bring up the core services

```bash
docker compose up -d
```

### Import the filtered PBF into PostGIS

```bash
docker compose --profile import run --rm osm2pgsql
```

### Create the tiles view

```bash
docker compose exec -T postgres psql -U lbmap -d lbmap -f /sql/02_lbroads_tiles.sql
```

## Endpoints

- UI: `http://localhost/`
- Debug UI: `http://localhost/debug`
- Martin tiles: `http://localhost:3000/`

## Project layout

- `docker-compose.yml` — service definitions and profiles
- `o5m/pbf_pipeline.sh` — pipeline runner
- `o5m/filter_osmium.py` — Python filter rules
- `o5m/filter_osmium.cpp` — C++ filter binary
- `tileproduction/osm2pgsql/flex.lua` — osm2pgsql flex mapping
- `tileproduction/sql/02_lbroads_tiles.sql` — tiles view/index definition
- `tileproduction/sql/03_strange_roads_api.sql` — API-related SQL (optional)
- `public/legacy` — Static UI
- `docker/nginx/legacy.conf` — Nginx config

## Troubleshooting

- The pipeline “skips” steps: remove the corresponding output files in `DATA_DIR` and re-run.
- `osm2pgsql` fails to connect: ensure `docker compose up -d` completed and `postgres` is healthy.
- `planet-filtered.osm.pbf` not found: check `PBF_OUT` and `DATA_DIR` in `.env`.
- Want to use an extract instead of planet: set `PBF_IN` to your extract path and run the pipeline.

## Reference

- osm2pgsql mapping: `tileproduction/osm2pgsql/flex.lua`
- SQL view/index: `tileproduction/sql/02_lbroads_tiles.sql`
