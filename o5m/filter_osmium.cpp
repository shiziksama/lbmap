#include <osmium/builder/attr.hpp>
#include <osmium/builder/osm_object_builder.hpp>
#include <osmium/handler.hpp>
#include <osmium/io/any_input.hpp>
#include <osmium/io/any_output.hpp>
#include <osmium/io/file.hpp>
#include <osmium/io/reader.hpp>
#include <osmium/io/writer.hpp>
#include <osmium/memory/buffer.hpp>
#include <osmium/osm.hpp>
#include <osmium/visitor.hpp>

#include <algorithm>
#include <cctype>
#include <chrono>
#include <cstdlib>
#include <iostream>
#include <string>
#include <unordered_map>
#include <unordered_set>
#include <utility>
#include <vector>

namespace {

using TagMap = std::unordered_map<std::string, std::string>;

const std::vector<std::string> kKeywords = {"cycle", "foot", "track", "pedestrian"};

struct PairHash {
    std::size_t operator()(const std::pair<std::string, std::string>& p) const noexcept {
        return std::hash<std::string>{}(p.first) ^ (std::hash<std::string>{}(p.second) << 1U);
    }
};

const std::unordered_set<std::pair<std::string, std::string>, PairHash> kDropWays = {
    {"surface", "ground"},
    {"surface", "grass"},
    {"surface", "gravel"},
    {"surface", "dirt"},
    {"surface", "unpaved"},
    {"smoothness", "bad"},
    {"access", "no"},
    {"access", "destination"},
    {"foot", "private"},
    {"foot", "permissive"},
    {"foot", "permit"},
    {"bicycle", "permissive"},
    {"bicycle", "private"},
    {"bicycle", "permit"},
    {"tracktype", "grade2"},
    {"tracktype", "grade3"},
    {"tracktype", "grade4"},
    {"tracktype", "grade5"},
};

const std::unordered_set<std::string> kDropTagsSurface = {"sett", "paved", "compacted"};

const std::unordered_set<std::string> kSpecialResults = {
    "great",
    "bicycle_undefined",
    "bikelane",
    "greatfoot",
    "foot",
};

std::string to_lower(std::string s) {
    std::transform(s.begin(), s.end(), s.begin(), [](unsigned char c) { return static_cast<char>(std::tolower(c)); });
    return s;
}

bool has_keyword(const TagMap& tags) {
    for (const auto& kv : tags) {
        const std::string key = to_lower(kv.first);
        const std::string val = to_lower(kv.second);
        for (const auto& kw : kKeywords) {
            if (key.find(kw) != std::string::npos || val.find(kw) != std::string::npos) {
                return true;
            }
        }
    }
    return false;
}

bool drop_way(const TagMap& tags) {
    for (const auto& kv : tags) {
        if (kDropWays.find(kv) != kDropWays.end()) {
            return true;
        }
    }
    return false;
}

bool starts_with(const std::string& s, const std::string& prefix) {
    return s.rfind(prefix, 0) == 0;
}

TagMap drop_tags(const TagMap& tags) {
    TagMap out;
    out.reserve(tags.size());

    for (const auto& kv : tags) {
        const std::string& k = kv.first;
        const std::string& v = kv.second;

        if (v == "no" || v == "unknown") {
            continue;
        }
        if (starts_with(k, "name")) {
            continue;
        }
        if (starts_with(k, "motorcycle")) {
            continue;
        }
        if (k == "sidewalk") {
            continue;
        }
        if (k == "cycleway" && v == "opposite") {
            continue;
        }
        if (k == "bicycle" && v == "yes") {
            continue;
        }
        if (k == "foot" && v == "yes") {
            continue;
        }
        if (k == "surface" && kDropTagsSurface.find(v) != kDropTagsSurface.end()) {
            continue;
        }
        if (k == "smoothness" && v == "intermediate") {
            continue;
        }
        if (starts_with(k, "class:bicycle")) {
            continue;
        }
        if (k == "oneway:bicycle") {
            continue;
        }
        if (k == "bicycle" && v == "dismount") {
            continue;
        }
        if (k == "bicycle:backwards") {
            continue;
        }
        if (starts_with(k, "note")) {
            continue;
        }
        if (starts_with(k, "check_date")) {
            continue;
        }
        if (starts_with(k, "ramp")) {
            continue;
        }
        if (starts_with(k, "fixme")) {
            continue;
        }
        if (k == "designation" && v == "public_footpath") {
            continue;
        }
        out.emplace(k, v);
    }
    return out;
}

TagMap modify_tags(TagMap tags) {
    auto it = tags.find("cycleway:surface");
    if (it != tags.end()) {
        tags["surface"] = it->second;
        tags["bicycle"] = "designated";
        tags.erase(it);
    }
    return tags;
}
bool no_surface_information(const TagMap& tags) {
    std::string all_tags;
    all_tags.reserve(tags.size() * 8);
    for (const auto& kv : tags) {
        all_tags += kv.first;
        all_tags += kv.second;
    }
    return (all_tags.find("surface") == std::string::npos) && (all_tags.find("smoothness") == std::string::npos);
}

bool test_no(const TagMap& tags) {
    std::string all_tags;
    all_tags.reserve(tags.size() * 8);
    for (const auto& kv : tags) {
        all_tags += kv.first;
        all_tags += kv.second;
    }

    const bool no_great_tags = (all_tags.find("cycle") == std::string::npos) &&
                               (all_tags.find("foot") == std::string::npos) &&
                               (all_tags.find("pedestrian") == std::string::npos);
    const bool no_surface = no_surface_information(tags);

    auto it_highway = tags.find("highway");
    const bool highway_is_track = (it_highway != tags.end() && it_highway->second == "track");

    if (no_great_tags && !highway_is_track) {
        return true;
    }
    if (no_surface && no_great_tags && highway_is_track) {
        return true;
    }

    static const std::vector<std::pair<std::string, std::string>> filters = {
        {"highway", "construction"},
        {"highway", "steps"},
        {"highway", "proposed"},
        {"highway", "platform"},
        {"highway", "bus_stop"},
        {"highway", "rest_area"},
        {"highway", "bridleway"},
        {"highway", "via_ferrata"},
        {"highway", "planned"},
        {"highway", "corridor"},
        {"highway", "raceway"},
        {"highway", "elevator"},
        {"highway", "emergency_bay"},
        {"highway", "services"},
        {"amenity", "parking"},
        {"amenity", "services"},
        {"smoothness", "bad"},
        {"designation", "public_bridleway"},
        {"smoothness", "very_bad"},
        {"smoothness", "very_horrible"},
        {"smoothness", "horrible"},
        {"smoothness", "impassable"},
        {"smoothness", "medium"},
        {"footway", "crossing"},
        {"surface", "dirt"},
        {"surface", "unpaved"},
        {"surface", "gravel"},
        {"surface", "grass"},
        {"surface", "ground"},
        {"surface", "sand"},
        {"surface", "earth"},
        {"surface", "pebblestone"},
        {"surface", "fine_gravel"},
        {"surface", "cobblestone"},
        {"surface", "concrete:plates"},
        {"surface", "concrete:lanes"},
        {"surface", "wood"},
        {"surface", "metal"},
        {"surface", "stone"},
        {"surface", "grass_paver"},
        {"area", "yes"},
        {"ice_road", "yes"},
        {"winter_road", "yes"},
        {"tracktype", "grade2"},
        {"tracktype", "grade3"},
        {"tracktype", "grade4"},
        {"tracktype", "grade5"},
        {"tracktype", "indeterminate"},
        {"access", "private"},
    };

    for (const auto& kv : filters) {
        auto it = tags.find(kv.first);
        if (it != tags.end() && it->second == kv.second) {
            return true;
        }
    }

    return false;
}

TagMap taglist_to_map(const osmium::TagList& tags) {
    TagMap out;
    out.reserve(tags.size());
    for (const auto& tag : tags) {
        out.emplace(tag.key(), tag.value());
    }
    return out;
}

struct FilterHandler : public osmium::handler::Handler {
    explicit FilterHandler(osmium::io::Writer& writer)
        : writer_(writer),
          start_(std::chrono::steady_clock::now()),
          last_log_(start_) {}

    void node(const osmium::Node& n) {
        ++nodes_in_;
        osmium::memory::Buffer buffer{1024, osmium::memory::Buffer::auto_grow::yes};
        {
            osmium::builder::NodeBuilder nb{buffer};
            nb.set_id(n.id());
            nb.set_location(n.location());
        }
        buffer.commit();
        writer_(std::move(buffer));
        ++nodes_out_;
        maybe_log();
    }

    void way(const osmium::Way& w) {
        ++ways_in_;
        const TagMap original_tags = taglist_to_map(w.tags());
        if (original_tags.find("highway") == original_tags.end()) {
            maybe_log();
            return;
        }
        if (!has_keyword(original_tags)) {
            maybe_log();
            return;
        }
        if (drop_way(original_tags)) {
            maybe_log();
            return;
        }

        TagMap tags = drop_tags(original_tags);
        if (test_no(tags)) {
            maybe_log();
            return;
        }

        osmium::memory::Buffer buffer{4096, osmium::memory::Buffer::auto_grow::yes};
        {
            osmium::builder::WayBuilder wb{buffer};
            wb.set_id(w.id());

            {
                osmium::builder::WayNodeListBuilder wnl{buffer, &wb};
                for (const auto& node_ref : w.nodes()) {
                    wnl.add_node_ref(node_ref.ref());
                }
            }

            {
                osmium::builder::TagListBuilder tlb{buffer, &wb};
                for (const auto& tag : w.tags()) {
                    tlb.add_tag(tag.key(), tag.value());
                }
            }
        }

        buffer.commit();
        writer_(std::move(buffer));
        ++ways_out_;
        maybe_log();
    }

  private:
    void maybe_log() {
        const auto now = std::chrono::steady_clock::now();
        if (now - last_log_ < std::chrono::seconds(5)) {
            return;
        }
        last_log_ = now;
        const auto elapsed = std::chrono::duration_cast<std::chrono::seconds>(now - start_);
        std::cerr << "Processed: "
                  << "nodes_in=" << nodes_in_
                  << " nodes_out=" << nodes_out_
                  << " ways_in=" << ways_in_
                  << " ways_out=" << ways_out_
                  << " elapsed=" << elapsed.count() << "s\n";
        std::cerr.flush();
    }

    osmium::io::Writer& writer_;
    std::chrono::steady_clock::time_point start_;
    std::chrono::steady_clock::time_point last_log_;
    std::uint64_t nodes_in_ = 0;
    std::uint64_t nodes_out_ = 0;
    std::uint64_t ways_in_ = 0;
    std::uint64_t ways_out_ = 0;
};

struct Args {
    std::string infile;
    std::string outfile;
    std::string out_format;
};

bool parse_args(int argc, char* argv[], Args& args) {
    for (int i = 1; i < argc; ++i) {
        const std::string a = argv[i];
        if (a == "--in" && i + 1 < argc) {
            args.infile = argv[++i];
            continue;
        }
        if (a == "--out" && i + 1 < argc) {
            args.outfile = argv[++i];
            continue;
        }
        if (a == "--out-format" && i + 1 < argc) {
            args.out_format = argv[++i];
            continue;
        }
        if (a == "-h" || a == "--help") {
            return false;
        }
        std::cerr << "Unknown or incomplete argument: " << a << "\n";
        return false;
    }

    if (args.infile.empty() || args.outfile.empty()) {
        return false;
    }
    return true;
}

void print_usage(const char* argv0) {
    std::cerr
        << "Usage: " << argv0 << " --in <input.osm.pbf|.o5m|.osm> --out <output> [--out-format <osm|o5m|pbf>]\n";
}

}  // namespace

int main(int argc, char* argv[]) {
    Args args;
    if (!parse_args(argc, argv, args)) {
        print_usage(argv[0]);
        return 2;
    }

    if (args.outfile == "-" && args.out_format.empty()) {
        args.out_format = "osm";
    }

    osmium::io::File input_file{args.infile};
    osmium::io::File output_file = args.out_format.empty()
        ? osmium::io::File{args.outfile}
        : osmium::io::File{args.outfile, args.out_format};

    osmium::io::Reader reader{
        input_file,
        osmium::osm_entity_bits::node | osmium::osm_entity_bits::way
    };
    osmium::io::Writer writer{output_file, osmium::io::overwrite::allow};

    const auto start = std::chrono::steady_clock::now();
    try {
        FilterHandler handler{writer};
        osmium::apply(reader, handler);
    } catch (const std::exception& e) {
        std::cerr << "Error: " << e.what() << "\n";
        writer.close();
        reader.close();
        return 1;
    }

    reader.close();
    writer.close();

    const auto elapsed = std::chrono::duration_cast<std::chrono::seconds>(
        std::chrono::steady_clock::now() - start);
    const int minutes = static_cast<int>(elapsed.count() / 60);
    const int seconds = static_cast<int>(elapsed.count() % 60);
    std::cerr << "Elapsed: " << minutes << "m " << seconds << "s\n";

    return 0;
}
