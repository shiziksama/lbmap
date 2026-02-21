#!/usr/bin/env bash
set -euo pipefail

PBF_IN="${PBF_IN:-/data/planet-latest.osm.pbf}"
PBF_HIGHWAYS="${PBF_HIGHWAYS:-/data/planet-highways.osm.pbf}"
PBF_CPP_OUT="${PBF_CPP_OUT:-/data/planet-filtered-cpp.osm.pbf}"
PBF_CPP_CLEAN="${PBF_CPP_CLEAN:-/data/planet-filtered-cpp-clean.osm.pbf}"
PBF_PY_OUT="${PBF_PY_OUT:-/data/planet-filtered-py.osm.pbf}"
PBF_OUT="${PBF_OUT:-/data/planet-filtered.osm.pbf}"
TMP_FILE="/data/tmp.pbf"

rm -f "$TMP_FILE"

start_step=1
if [ -s "$PBF_OUT" ]; then
    start_step=6
elif [ -s "$PBF_PY_OUT" ]; then
    start_step=5
elif [ -s "$PBF_CPP_CLEAN" ]; then
    start_step=4
elif [ -s "$PBF_CPP_OUT" ]; then
    start_step=3
elif [ -s "$PBF_HIGHWAYS" ]; then
    start_step=2
fi

if [ "$start_step" -le 1 ]; then
    echo "[1/5] osmium tags-filter (highways)"
    osmium tags-filter --progress -v -t "$PBF_IN" w/highway -o "$TMP_FILE"
    mv -f "$TMP_FILE" "$PBF_HIGHWAYS"
else
    echo "[1/5] skip: $PBF_HIGHWAYS exists"
fi

if [ "$start_step" -le 2 ]; then
    echo "[2/5] C++ filter"
    /usr/local/bin/filter_osmium_cpp --in "$PBF_HIGHWAYS" --out "$TMP_FILE"
    mv -f "$TMP_FILE" "$PBF_CPP_OUT"
else
    echo "[2/5] skip: $PBF_CPP_OUT exists"
fi

if [ "$start_step" -le 3 ]; then
    echo "[3/5] osmium tags-filter (cleanup after C++ filter)"
    osmium tags-filter --progress -v -t "$PBF_CPP_OUT" w/highway -o "$TMP_FILE"
    mv -f "$TMP_FILE" "$PBF_CPP_CLEAN"
else
    echo "[3/5] skip: $PBF_CPP_CLEAN exists"
fi

if [ "$start_step" -le 4 ]; then
    echo "[4/5] Python filter"
    python3 /o5m/filter_osmium.py --in "$PBF_CPP_CLEAN" --out "$TMP_FILE"
    mv -f "$TMP_FILE" "$PBF_PY_OUT"
else
    echo "[4/5] skip: $PBF_PY_OUT exists"
fi

if [ "$start_step" -le 5 ]; then
    echo "[5/5] osmium tags-filter (cleanup after Python filter)"
    osmium tags-filter --progress -v -t "$PBF_PY_OUT" w/highway -o "$TMP_FILE"
    mv -f "$TMP_FILE" "$PBF_OUT"
else
    echo "[5/5] skip: $PBF_OUT exists"
fi
