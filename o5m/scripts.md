#Converting full .osm.pbf to only highways .o5m

## keep only highways with osmium(79gb planet to 25gb highways)
```osmium tags-filter --progress -v -t planet-latest.osm.pbf w/highway -o planet-highways.osm.pbf```

## filter ways + tags + lbroads with osmium (replaces filter_osm + osmfilter + filter.php)
```python3 o5m/filter_osmium.py --in planet-highways.osm.pbf --out planet-filtered.o5m```

## optional: run directly on full planet
```python3 o5m/filter_osmium.py --in planet-latest.osm.pbf --out planet-filtered.o5m --only-highway```

## remove unused nodes. we have resulting file)(5gb to 2gb)
```osmfilter planet-filtered.o5m --keep="highway=" --out-o5m >planet-highways.o5m```


## make "black" file
```osmfilter planet-highways.o5m --keep="highway" --drop="lbroads="  --out-o5m > planet-black.o5m```

#merge some files
```osmconvert planet-highways.o5m planet-highways.o5m -o=planet-merged.o5m```
