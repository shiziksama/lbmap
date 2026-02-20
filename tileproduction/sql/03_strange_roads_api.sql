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
