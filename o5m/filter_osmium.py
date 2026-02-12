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
from bad_tags import BAD_TAG_SET

KEYWORDS = ("cycle", "foot", "track", "pedestrian")

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
        if (k, v) in BAD_TAG_SET:
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
        self.start = time.perf_counter()
        self.last_log = self.start
        self.nodes_in = 0
        self.nodes_out = 0
        self.ways_in = 0
        self.ways_out = 0
        self.node_log_stride = 5_000_000

    @staticmethod
    def _format_count(value):
        digits = str(value)
        out = []
        for i, ch in enumerate(digits):
            if i > 0 and (len(digits) - i) % 3 == 0:
                out.append(" ")
            out.append(ch)
        return "".join(out)

    def _maybe_log(self):
        now = time.perf_counter()
        if now - self.last_log < 5.0:
            return
        self.last_log = now
        elapsed = int(now - self.start)
        sys.stderr.write(
            "Processed: "
            f"nodes_in={self._format_count(self.nodes_in)} "
            f"nodes_out={self._format_count(self.nodes_out)} "
            f"ways_in={self._format_count(self.ways_in)} "
            f"ways_out={self._format_count(self.ways_out)} "
            f"elapsed={elapsed}s\n"
        )
        sys.stderr.flush()

    def node(self, n):
        # Keep nodes, but drop their tags like in filter.php
        self.nodes_in += 1
        self.writer.add_node(n)
        self.nodes_out += 1
        if self.nodes_in % self.node_log_stride == 0:
            self._maybe_log()

    def way(self, w):
        self.ways_in += 1
        tags = dict(w.tags)
        if "highway" not in tags:
            self._maybe_log()
            return
        if not has_keyword(tags):
            self._maybe_log()
            return
        if drop_way(tags):
            self._maybe_log()
            return
        tags = drop_tags(tags)
        tags = modify_tags(tags)
        result = test_element(tags)
        if result == "no":
            self._maybe_log()
            return
        if result in SPECIAL_RESULTS:
            tags = {"lbroads": result, "highway": "lbroad"}

        mw = osm.osm.mutable.Way()
        mw.id = w.id
        mw.nodes = [osm.osm.NodeRef(n.location, n.ref) for n in w.nodes]
        mw.tags = tags
        self.writer.add_way(mw)
        self.ways_out += 1
        self._maybe_log()


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
        sys.stderr.write(
            "Processed: "
            f"nodes_in={FilterHandler._format_count(handler.nodes_in)} "
            f"nodes_out={FilterHandler._format_count(handler.nodes_out)} "
            f"ways_in={FilterHandler._format_count(handler.ways_in)} "
            f"ways_out={FilterHandler._format_count(handler.ways_out)} "
            f"elapsed={int(elapsed)}s\n"
        )
        sys.stderr.write(f"Elapsed: {minutes}m {seconds}s\n")


if __name__ == "__main__":
    main()
