#Converting full .osm.pbf to only highways .o5m

## keep only highways with osmium(79gb planet to 25gb highways)
```osmium tags-filter --progress -v -t planet-latest.osm.pbf w/highway -o planet-highways.osm.pbf```

## filters all without "cycle" "foot" "pedestrian" and "track" (25gb pbf to 22gb o5m)
```osmconvert planet-highways.osm.pbf --drop-version|./filter_osm|osmconvert - -o=planet-highways.o5m```

## keep only highways with needed (29gb to 6gb)
```osmfilter planet-highways.o5m \
    --keep="highway=" \
    --drop-ways="surface=ground or surface=grass or surface=gravel or surface=dirt or surface=unpaved or smoothness=bad or access=no or access=destination or foot=private or foot=permissive or foot=permit or bicycle=permissive or bicycle=private or bicycle=permit or tracktype=grade2 or tracktype=grade3 or tracktype=grade4 or tracktype=grade5" \
    --drop-tags="*=no *=unknown name*= motorcycle*= sidewalk= cycleway=opposite bicycle=yes foot=yes surface=sett surface=paved surface=compacted smoothness=intermediate class:bicycle*= oneway:bicycle= bicycle=dismount bicycle:backwards= note*= check_date*= ramp*= fixme*= designation=public_footpath" \
    --out-o5m > planet-highways2.o5m```

## filter highways by filter.php(removing ways, change tags etc) (6gb to 5gb)
```osmconvert planet-highways2.o5m --drop-version|php filter.php|osmconvert - -o=planet-filtered.o5m```

## remove unused nodes. we have resulting file)(5gb to 2gb)
```osmfilter planet-filtered.o5m --keep="highway=" --out-o5m >planet-highways.o5m```


## make "black" file
```osmfilter planet-highways.o5m --keep="highway" --drop="lbroads="  --out-o5m > planet-black.o5m```

#merge some files
```osmconvert planet-highways.o5m planet-highways.o5m -o=planet-merged.o5m```
