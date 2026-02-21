Drop VIEW IF EXISTS lbroads_tiles;
CREATE OR REPLACE VIEW lbroads_tiles AS
SELECT
    osm_id,
    lbroads,
    geometry
FROM osm_lbroads;

Drop VIEW IF EXISTS strange_roads;

CREATE OR REPLACE VIEW strange_roads AS
SELECT
    osm_id,
    hstore_to_json(tags) AS tags,
    ST_AsGeoJSON(geometry)::json AS geometry
FROM osm_lbroads where lbroads = 'undefined';


CREATE INDEX IF NOT EXISTS osm_lbroads_geometry_gist
    ON osm_lbroads
    USING GIST (geometry);

ANALYZE osm_lbroads;


CREATE OR REPLACE FUNCTION strange_roads_bbox(
    minx double precision,
    miny double precision,
    maxx double precision,
    maxy double precision
)
RETURNS TABLE (
    osm_id bigint,
    tags json,
    geometry json
)
AS $$
    SELECT
        osm_id,
        hstore_to_json(tags) AS tags,
        ST_AsGeoJSON(ST_Transform(geometry, 4326))::json AS geometry
    FROM osm_lbroads
    WHERE lbroads = 'undefined'
      AND geometry && ST_Transform(
          ST_SetSRID(ST_MakeEnvelope(minx, miny, maxx, maxy), 4326),
          ST_SRID(geometry)
      );
$$ LANGUAGE sql STABLE;
