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

bool is_bicycledesignated(const TagMap& tags) {
    static const std::unordered_set<std::string> types = {
        "track",
        "separate",
        "opposite_track",
        "use_sidepath",
    };

    bool designated = false;
    auto get = [&](const std::string& k) -> std::string {
        auto it = tags.find(k);
        return it == tags.end() ? std::string() : it->second;
    };

    designated = designated || (get("bicycle") == "designated" || get("bicycle") == "use_sidepath");
    designated = designated || (get("bicycle_road") == "yes");
    designated = designated || (get("cyclestreet") == "yes");
    designated = designated || (types.find(get("cycleway")) != types.end());
    designated = designated || (types.find(get("cycleway:left")) != types.end());
    designated = designated || (types.find(get("cycleway:right")) != types.end());
    designated = designated || (types.find(get("cycleway:both")) != types.end());
    designated = designated || (get("highway") == "cycleway");
    return designated;
}

bool is_footdesignated(const TagMap& tags) {
    bool designated = false;
    auto get = [&](const std::string& k) -> std::string {
        auto it = tags.find(k);
        return it == tags.end() ? std::string() : it->second;
    };

    designated = designated || (get("foot") == "designated" || get("foot") == "use_sidepath");
    designated = designated || (get("highway") == "footway");
    designated = designated || (get("footway") == "sidewalk");
    return designated;
}

bool is_surface_great(const TagMap& tags) {
    auto get = [&](const std::string& k) -> std::string {
        auto it = tags.find(k);
        return it == tags.end() ? std::string() : it->second;
    };

    const std::string surface = get("surface");
    if (surface == "asphalt" || surface == "paving_stones" || surface == "concrete") {
        return true;
    }
    const std::string smoothness = get("smoothness");
    return (smoothness == "good" || smoothness == "excellent");
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

bool test_great(const TagMap& tags) {
    return is_bicycledesignated(tags) && is_surface_great(tags);
}

bool test_bicycleundefined(const TagMap& tags) {
    return is_bicycledesignated(tags) && no_surface_information(tags);
}

bool test_bikelane(const TagMap& tags) {
    static const std::unordered_set<std::string> lanes = {
        "lane",
        "line",
        "shared_lane",
        "share_busway",
        "opposite_lane",
    };

    auto get = [&](const std::string& k) -> std::string {
        auto it = tags.find(k);
        return it == tags.end() ? std::string() : it->second;
    };

    return lanes.find(get("cycleway")) != lanes.end() ||
           lanes.find(get("cycleway:left")) != lanes.end() ||
           lanes.find(get("cycleway:right")) != lanes.end() ||
           lanes.find(get("cycleway:both")) != lanes.end();
}

bool test_greatother(const TagMap& tags) {
    std::string all_tags;
    all_tags.reserve(tags.size() * 8);
    for (const auto& kv : tags) {
        all_tags += kv.first;
        all_tags += kv.second;
    }
    if (all_tags.find("cycle") != std::string::npos) {
        return false;
    }
    if (is_footdesignated(tags) && is_surface_great(tags)) {
        return true;
    }
    auto it = tags.find("highway");
    if (it != tags.end() && it->second == "track" && is_surface_great(tags)) {
        return true;
    }
    return false;
}

bool test_foot(const TagMap& tags) {
    std::string all_tags;
    all_tags.reserve(tags.size() * 8);
    for (const auto& kv : tags) {
        all_tags += kv.first;
        all_tags += kv.second;
    }
    if (all_tags.find("cycle") != std::string::npos) {
        return false;
    }
    return is_footdesignated(tags) && no_surface_information(tags);
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

std::string test_element(const TagMap& tags) {
    if (tags.empty()) {
        return "no";
    }
    auto it = tags.find("lbroads");
    if (it != tags.end()) {
        return it->second;
    }
    if (test_no(tags)) {
        return "no";
    }
    if (test_great(tags)) {
        return "great";
    }
    if (test_bicycleundefined(tags)) {
        return "bicycle_undefined";
    }
    if (test_bikelane(tags)) {
        return "bikelane";
    }
    if (test_greatother(tags)) {
        return "greatfoot";
    }
    if (test_foot(tags)) {
        return "foot";
    }
    return "undefined";
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
        TagMap tags = taglist_to_map(w.tags());
        if (tags.find("highway") == tags.end()) {
            maybe_log();
            return;
        }
        if (!has_keyword(tags)) {
            maybe_log();
            return;
        }
        if (drop_way(tags)) {
            maybe_log();
            return;
        }

        tags = drop_tags(tags);
        tags = modify_tags(std::move(tags));
        const std::string result = test_element(tags);
        if (result == "no") {
            maybe_log();
            return;
        }
        if (kSpecialResults.find(result) != kSpecialResults.end()) {
            tags.clear();
            tags["lbroads"] = result;
            tags["highway"] = "lbroad";
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
                for (const auto& kv : tags) {
                    tlb.add_tag(kv.first.c_str(), kv.second.c_str());
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
