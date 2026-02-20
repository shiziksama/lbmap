Drop VIEW IF EXISTS lbroads_tiles;
CREATE OR REPLACE VIEW lbroads_tiles AS
SELECT
    osm_id,
    lbroads,
    geometry
FROM osm_lbroads WHERE lbroads != 'undefined';

Drop VIEW IF EXISTS strange_roads;

CREATE OR REPLACE VIEW strange_roads AS
SELECT
    osm_id,
    tags,
    geometry
FROM osm_lbroads where lbroads = 'undefined';


CREATE INDEX IF NOT EXISTS osm_lbroads_geometry_gist
    ON osm_lbroads
    USING GIST (geometry);

ANALYZE osm_lbroads;
