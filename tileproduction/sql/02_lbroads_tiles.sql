CREATE OR REPLACE VIEW lbroads_tiles AS
SELECT
    osm_id,
    roadtype,
    geometry
FROM osm_lbroads;

CREATE INDEX IF NOT EXISTS osm_lbroads_geometry_gist
    ON osm_lbroads
    USING GIST (geometry);

ANALYZE osm_lbroads;
