#Converting full .osm.pbf to only highways .o5m

## keep only highways with osmium(85gb planet to 26gb highways) ~43m

`osmium tags-filter --progress -v -t planet-latest.osm.pbf w/highway -o planet-highways.osm.pbf`

## filter ways + tags + lbroads with cpp script (26gb to 12gb) ~16m

`./o5m/filter_osmium_cpp --in planet-highways.osm.pbf --out planet-filtered.osm.pbf`

## remove unused nodes. we have resulting file(12gb to 4gb)

`osmium tags-filter --progress -v -t /mnt/d/downloads/planet-filtered.osm.pbf w/highway -o /mnt/d/downloads/planet-filtered2.os
m.pbf`

## make "black" file

`osmfilter planet-highways.o5m --keep="highway" --drop="lbroads="  --out-o5m > planet-black.o5m`

#merge some files
`osmconvert planet-highways.o5m planet-highways.o5m -o=planet-merged.o5m`
