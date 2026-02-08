#!/usr/bin/env python3
import argparse
import os
import sys
import time

try:
    import osmium as osm
except ImportError as e:
    sys.stderr.write("Missing dependency: osmium (pyosmium). Install with: pip install osmium\n")
    raise

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
if SCRIPT_DIR not in sys.path:
    sys.path.insert(0, SCRIPT_DIR)

from osm_filter_rules import SPECIAL_RESULTS, modify_tags, test_element

KEYWORDS = ("cycle", "foot", "track", "pedestrian")

DROP_WAYS = {
    ("surface", "ground"),
    ("surface", "grass"),
    ("surface", "gravel"),
    ("surface", "dirt"),
    ("surface", "unpaved"),
    ("smoothness", "bad"),
    ("access", "no"),
    ("access", "destination"),
    ("foot", "private"),
    ("foot", "permissive"),
    ("foot", "permit"),
    ("bicycle", "permissive"),
    ("bicycle", "private"),
    ("bicycle", "permit"),
    ("tracktype", "grade2"),
    ("tracktype", "grade3"),
    ("tracktype", "grade4"),
    ("tracktype", "grade5"),
}

DROP_TAGS_SURFACE = {"sett", "paved", "compacted"}


def has_keyword(tags):
    for k, v in tags.items():
        kl = k.lower()
        vl = v.lower()
        for kw in KEYWORDS:
            if kw in kl or kw in vl:
                return True
    return False


def drop_way(tags):
    for k, v in tags.items():
        if (k, v) in DROP_WAYS:
            return True
    return False


def drop_tags(tags):
    out = {}
    for k, v in tags.items():
        if v in ("no", "unknown"):
            continue
        if k.startswith("name"):
            continue
        if k.startswith("motorcycle"):
            continue
        if k == "sidewalk":
            continue
        if k == "cycleway" and v == "opposite":
            continue
        if k == "bicycle" and v == "yes":
            continue
        if k == "foot" and v == "yes":
            continue
        if k == "surface" and v in DROP_TAGS_SURFACE:
            continue
        if k == "smoothness" and v == "intermediate":
            continue
        if k.startswith("class:bicycle"):
            continue
        if k == "oneway:bicycle":
            continue
        if k == "bicycle" and v == "dismount":
            continue
        if k == "bicycle:backwards":
            continue
        if k.startswith("note"):
            continue
        if k.startswith("check_date"):
            continue
        if k.startswith("ramp"):
            continue
        if k.startswith("fixme"):
            continue
        if k == "designation" and v == "public_footpath":
            continue
        out[k] = v
    return out


class FilterHandler(osm.SimpleHandler):
    def __init__(self, writer):
        super().__init__()
        self.writer = writer

    def node(self, n):
        # Keep nodes, but drop their tags like in filter.php
        mn = osm.osm.mutable.Node()
        mn.id = n.id
        mn.location = n.location
        self.writer.add_node(mn)

    def way(self, w):
        tags = dict(w.tags)
        if "highway" not in tags:
            return
        if not has_keyword(tags):
            return
        if drop_way(tags):
            return
        tags = drop_tags(tags)
        tags = modify_tags(tags)
        result = test_element(tags)
        if result == "no":
            return
        if result in SPECIAL_RESULTS:
            tags = {"lbroads": result, "highway": "lbroad"}

        mw = osm.osm.mutable.Way()
        mw.id = w.id
        mw.nodes = [osm.osm.mutable.NodeRef(ref=n.ref) for n in w.nodes]
        for k, v in tags.items():
            mw.tags[k] = v
        self.writer.add_way(mw)


def main():
    parser = argparse.ArgumentParser(
        description="Filter OSM data with osmium (replaces filter_osm + osmfilter + filter.php)."
    )
    parser.add_argument("--in", dest="infile", required=True, help="Input OSM file (.pbf/.o5m/.osm)")
    parser.add_argument("--out", dest="outfile", required=True, help="Output OSM file (.o5m/.pbf/.osm)")
    parser.add_argument(
        "--out-format",
        dest="out_format",
        default="",
        help="Force output format (e.g. o5m, pbf, osm). Required when --out is '-' (stdout).",
    )
    args = parser.parse_args()

    out_format = args.out_format
    if args.outfile == "-" and not out_format:
        out_format = "osm"

    if out_format:
        writer = osm.SimpleWriter(osm.io.File(args.outfile, out_format))
    else:
        writer = osm.SimpleWriter(args.outfile)
    start = time.perf_counter()
    try:
        handler = FilterHandler(writer)
        handler.apply_file(args.infile, locations=True)
    finally:
        writer.close()
        elapsed = time.perf_counter() - start
        minutes = int(elapsed // 60)
        seconds = int(elapsed % 60)
        sys.stderr.write(f"Elapsed: {minutes}m {seconds}s\n")


if __name__ == "__main__":
    main()
