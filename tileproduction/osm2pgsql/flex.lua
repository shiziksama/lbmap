local lbroads = osm2pgsql.define_table({
    name = "osm_lbroads",
    ids = { type = "way", id_column = "osm_id" },
    columns = {
        { column = "lbroads", type = "text" },
        { column = "tags", type = "hstore" },
        { column = "geometry", type = "linestring" }
    }
})

function osm2pgsql.process_way(object)
    lbroads:insert({
        lbroads = object.tags.highway == "lbroad" and object.tags.lbroads or "undefined",
        tags = object.tags,
        geometry = object:as_linestring()
    })
end
