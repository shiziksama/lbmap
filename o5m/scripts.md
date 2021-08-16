#Converting full .osm.pbf to only highways .o5m
## just convert to o5m for osmfilter use (625mb osm.pbf to 1000mb o5m)
```osmconvert ukraine-latest.osm.pbf --drop-relations --drop-version -o=ukraine-filtered.o5m```
## left only highways (any kind of road or street)(1000mb to 227mb)
```osmfilter ukraine-filtered.o5m --keep="highway=" --out-o5m >ukraine-highways.o5m```
## filter highways by filter.php (227mb to 152mb)
```osmconvert ukraine-highways.o5m --drop-version|php filter.php|osmconvert - -o=ukraine-filtered.o5m```
## left only highways (any kind of road or street)(152mb to 10mb)
```osmfilter ukraine-filtered.o5m --keep="highway=" --out-o5m >ukraine-highways.o5m```


#merge some files
```osmconvert planet-highways.o5m ukraine-highways.o5m -o=planet-merged.o5m```


#maybe some useful, but dont know

osmconvert ukraine-highways.o5m --drop-nodes|php tags.php|sort|uniq -c|sort -nr|tee 1.txt

osmconvert ukraine-highways.o5m --drop-version|php filter_great.php|osmconvert - -o=ukraine-black_temp.o5m
osmfilter ukraine-black_temp.o5m --keep="highway=" --out-o5m >ukraine-black.o5m

osmconvert ukraine-black.o5m --drop-version|php filter_great.php|osmconvert - -o=ukraine-black_temp.o5m;osmfilter ukraine-black_temp.o5m --keep="highway=" --out-o5m >ukraine-black.o5m

//black фильтр
osmconvert ukraine-black.o5m --drop-nodes|php tags.php|sort|uniq -c|sort -nr|tee 1.txt


//жесткая ошибка
./osmfilter.exe ukraine-highways.o5m --drop="highway=bus_stop =steps =platform =construction =elevator" --out-o5m >ukraine-filtered.o5m
./osmfilter.exe ukraine-filtered.o5m --drop-node-tags="*"  --out-o5m >ukraine-highways.o5m
./osmfilter.exe ukraine-highways.o5m --drop-tags="name*"  --out-o5m >ukraine-filtered.o5m
./osmfilter.exe ukraine-filtered.o5m --drop-tags="tiger*"  --out-o5m >ukraine-highways.o5m


./osmfilter.exe ukraine-highways.o5m --out-count=surface>3.txt
./osmfilter.exe ukraine-highways.o5m --drop="surface=unpaved =ground	=gravel =dirt =grass =sand =earth =mud =soil" --out-o5m >ukraine-filtered.o5m
./osmfilter.exe ukraine-filtered.o5m --keep="highway=" --out-o5m >ukraine-highways.o5m


./osmconvert.exe ukraine-latest.osm.pbf --drop-relations --drop-version -o=ukraine-filtered.o5m
./osmfilter.exe ukraine-filtered.o5m --keep="highway=" --out-o5m >ukraine-highways.o5m
./osmfilter.exe ukraine-highways.o5m --drop-node-tags="*"  --out-o5m >ukraine-filtered.o5m
./osmfilter.exe ukraine-filtered.o5m --drop="surface=unpaved =ground	=gravel =dirt =grass =sand =earth =mud =soil" --out-o5m >ukraine-highways.o5m
./osmfilter.exe ukraine-highways.o5m --drop-tags="*name*"  --out-o5m >ukraine-filtered.o5m
./osmfilter.exe ukraine-filtered.o5m --drop-tags="*date*"  --out-o5m >ukraine-highways.o5m
./osmfilter.exe ukraine-highways.o5m --drop-tags="*weight*"  --out-o5m >ukraine-filtered.o5m
./osmfilter.exe ukraine-filtered.o5m --keep="highway=" --out-o5m >ukraine-highways.o5m



./osmfilter.exe ukraine-base.o5m --keep="highway=bus_stop" --out-o5m >ukraine-filtered.o5m


./osmfilter.exe ukraine-base.o5m --keep="highway=steps =platform =construction =elevator" --out-o5m >ukraine-platform.o5m



osmconvert ukraine-highways.3.4.2.o5m --drop-version|php filter.php|osmconvert - -o=ukraine-filtered.3.4.2.o5m
osmfilter ukraine-filtered.3.4.2.o5m --keep="highway=" --out-o5m >ukraine-highways_n.3.4.2.o5m