# LBMap

This repository contains:

- a pipeline for generating OSM tiles for bike lanes (PostGIS + osmium + custom filters + osm2pgsql + Martin)

Below is a “from scratch” guide focused on running all commands with Docker.

## Prerequisites

- Docker and Docker Compose installed.
- Enough disk space (the planet file is very large).

## 0) Configure `.env`

Docker Compose automatically reads `.env` next to `docker-compose.yml`.

1. Copy `.env.example` to `.env`.
2. Set the folder to mount with the PBF files:

```
DATA_DIR=./data
```

You can use an absolute path, e.g. `DATA_DIR=/mnt/d/osm`.

## 1) Prepare the PBF folder

Put `planet-latest.osm.pbf` into the folder you specified in `DATA_DIR`.

Example:

```
./data/planet-latest.osm.pbf
```

All intermediate and final files will be created in the same folder.

## 2) Filtering and data preparation pipeline

We first select all roads, then filter and tag them for later import into PostGIS and tile generation.
Command to run the entire pipeline:

```bash
docker compose --profile prepare run --rm pbf-pipeline
```

### Continue after a failure

The script looks at the last existing pipeline file and starts from that step, so you can delete all previous files. It will continue from the previous step.

## 3) Import into PostGIS and start services

### 3.1 Bring up the database and tile server

```bash
docker compose up -d
```

### 3.2 Import `planet-filtered.osm.pbf`

```bash
docker compose --profile import run --rm osm2pgsql
```

### 3.3 Create the tiles view

```bash
docker compose exec -T postgres psql -U lbmap -d lbmap -f /sql/02_lbroads_tiles.sql
```

## 4) Where to see the result

- Page: `http://localhost/`
- Debug page, where you can see why a road is marked as unknown: `http://localhost/debug`

## Reference

- osm2pgsql mapping: `tileproduction/osm2pgsql/flex.lua`
- SQL view/index: `tileproduction/sql/02_lbroads_tiles.sql`
