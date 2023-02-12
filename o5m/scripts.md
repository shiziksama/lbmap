#Converting full .osm.pbf to only highways .o5m

## just convert to o5m for osmfilter use (625mb osm.pbf to 1000mb o5m)
```osmconvert planet-latest.osm.pbf --drop-relation --drop-version -o=planet-latest.o5m```

## keep only mentioning bicycle or pedestrians
```osmfilter planet-latest.o5m --keep="highway= and ( *cycle* or *foot* or *pedestrian* or =*cycle* or =*foot* or="pedestian" or lbroads= or highway=track )" --drop-ways="surface=ground or surface=grass or surface=gravel or surface=dirt or surface=unpaved or smoothness=bad or access=no or access=destination or foot=private or foot=permissive or foot=permit or bicycle=permissive or bicycle=private or bicycle=permit or tracktype=grade2 or tracktype=grade3 or tracktype=grade4 or tracktype=grade5"```

## remove silly tags

```osmfilter planet-cnpw.o5m --drop-tags="*=no *=unknown name*= motorcycle*= sidewalk= cycleway=opposite bicycle=yes foot=yes surface=sett surface=paved surface=compacted smoothness=intermediate class:bicycle*= oneway:bicycle= bicycle=dismount bicycle:backwards= note*= check_date*= ramp*= fixme*=" --drop-node-tags="*=" --out-o5m >planet-notags.o5m```

## one more filter

```osmfilter planet-notags.o5m --keep="highway= and ( *cycle* or *foot* or *pedestrian* or =*cycle* or =*foot* or="pedestian" or lbroads= or highway=track )" --out-o5m > planet-highways.o5m```


## filter highways by filter.php (227mb to 152mb)
```osmconvert planet-highways.o5m --drop-version|php filter.php|osmconvert - -o=planet-filtered.o5m```
## left only highways (any kind of road or street)(152mb to 10mb)
```osmfilter planet-filtered.o5m --keep="highway=" --out-o5m >planet-highways.o5m```

## make "black" file
```osmfilter planet-highways.o5m --keep="highway" --drop="lbroads="  --out-o5m > planet-black.o5m```

#merge some files
```osmconvert planet-highways.o5m planet-highways.o5m -o=planet-merged.o5m```

