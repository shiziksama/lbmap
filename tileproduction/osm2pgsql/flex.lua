local lbroads = osm2pgsql.define_table({
    name = "osm_lbroads",
    ids = { type = "way", id_column = "osm_id" },
    columns = {
        { column = "roadtype", type = "text" },
        { column = "tags", type = "hstore" },
        { column = "geometry", type = "linestring" }
    }
})

function osm2pgsql.process_way(object)
    if object.tags.highway ~= "lbroad" then
        return
    end

    lbroads:insert({
        roadtype = object.tags.highway,
        tags = object.tags,
        geometry = object:as_linestring()
    })
end
