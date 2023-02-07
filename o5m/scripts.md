#Converting full .osm.pbf to only highways .o5m

## just convert to o5m for osmfilter use (625mb osm.pbf to 1000mb o5m)
```osmconvert germany-latest.osm.pbf --drop-relations --drop-version -o=germany-filtered.o5m```

## keep only mentioning bicycle or pedestrians
```osmfilter germany-filtered.o5m --keep="highway= and ( *cycle* or *foot* or *pedestrian* or lbroads= or highway=track )" --drop-ways="surface=ground or surface=grass or surface=gravel or surface=dirt or surface=unpaved or smoothness=bad or access=no or access=destination or foot=private or foot=permissive or foot=permit or bicycle=permissive or bicycle=private or bicycle=permit or tracktype=grade2 or tracktype=grade3 or tracktype=grade4 or tracktype=grade5" --out-o5m > germany-highways.o5m```

## remove silly tags

```osmfilter germany-highways.o5m --drop-tags="*=no *=unknown name*= motorcycle= sidewalk= cycleway=opposite bicycle=yes foot=yes surface=sett surface=paved surface=compacted smoothness=intermediate class:bicycle*= oneway:bicycle= bicycle=dismount bicycle:backwards= note*= check_date*= ramp*=" --drop-node-tags="*=" --out-o5m >germany-filtered.o5m```

## one more filter

```osmfilter germany-filtered.o5m --keep="highway= and ( *cycle* or *foot* or *pedestrian* or lbroads= or highway=track )" --out-o5m > germany-highways.o5m```


## filter highways by filter.php (227mb to 152mb)
```osmconvert germany-highways.o5m --drop-version|php filter.php|osmconvert - -o=germany-filtered.o5m```
## left only highways (any kind of road or street)(152mb to 10mb)
```osmfilter germany-filtered.o5m --keep="highway=" --out-o5m >germany-highways.o5m```

## make "black" file
```osmfilter germany-highways.o5m --keep="highway" --drop="lbroads="  --out-o5m > germany-black.o5m```

#merge some files
```osmconvert planet-highways.o5m germany-highways.o5m -o=planet-merged.o5m```

