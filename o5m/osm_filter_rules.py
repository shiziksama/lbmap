SPECIAL_RESULTS = {"great", "bicycle_undefined", "bikelane", "greatfoot", "foot"}


def modify_tags(tags):
    if "cycleway:surface" in tags:
        tags["surface"] = tags["cycleway:surface"]
        tags["bicycle"] = "designated"
        tags.pop("cycleway:surface", None)
    return tags


def is_bicycledesignated(tags):
    types = {"track", "separate", "opposite_track", "use_sidepath"}
    designated = False
    designated = designated or (tags.get("bicycle") in {"designated", "use_sidepath"})
    designated = designated or (tags.get("bicycle_road") == "yes")
    designated = designated or (tags.get("cyclestreet") == "yes")
    designated = designated or (tags.get("cycleway") in types)
    designated = designated or (tags.get("cycleway:left") in types)
    designated = designated or (tags.get("cycleway:right") in types)
    designated = designated or (tags.get("cycleway:both") in types)
    designated = designated or (tags.get("highway") == "cycleway")
    return designated


def is_footdesignated(tags):
    designated = False
    designated = designated or (tags.get("foot") in {"designated", "use_sidepath"})
    designated = designated or (tags.get("highway") == "footway")
    designated = designated or (tags.get("footway") == "sidewalk")
    return designated


def is_surface_great(tags):
    return (tags.get("surface") in {"asphalt", "paving_stones", "concrete"}) or (
        tags.get("smoothness") in {"good", "excellent"}
    )


def no_surface_information(tags):
    all_tags = "".join(tags.keys()) + "".join(tags.values())
    return ("surface" not in all_tags) and ("smoothness" not in all_tags)


def test_great(tags):
    return is_bicycledesignated(tags) and is_surface_great(tags)


def test_bicycleundefined(tags):
    return is_bicycledesignated(tags) and no_surface_information(tags)


def test_bikelane(tags):
    lanes = {"lane", "line", "shared_lane", "share_busway", "opposite_lane"}
    return (
        tags.get("cycleway") in lanes
        or tags.get("cycleway:left") in lanes
        or tags.get("cycleway:right") in lanes
        or tags.get("cycleway:both") in lanes
    )


def test_greatother(tags):
    all_tags = "".join(tags.keys()) + "".join(tags.values())
    if "cycle" in all_tags:
        return False
    if is_footdesignated(tags) and is_surface_great(tags):
        return True
    if tags.get("highway") == "track" and is_surface_great(tags):
        return True
    return False


def test_foot(tags):
    all_tags = "".join(tags.keys()) + "".join(tags.values())
    if "cycle" in all_tags:
        return False
    return is_footdesignated(tags) and no_surface_information(tags)


def test_no(tags):
    all_tags = "".join(tags.keys()) + "".join(tags.values())
    no_great_tags = ("cycle" not in all_tags) and ("foot" not in all_tags) and (
        "pedestrian" not in all_tags
    )
    no_surface = no_surface_information(tags)

    if no_great_tags and tags.get("highway") != "track":
        return True
    if no_surface and no_great_tags and tags.get("highway") == "track":
        return True

    filters = [
        ("highway", "construction"),
        ("highway", "steps"),
        ("highway", "proposed"),
        ("highway", "platform"),
        ("highway", "bus_stop"),
        ("highway", "rest_area"),
        ("highway", "bridleway"),
        ("highway", "via_ferrata"),
        ("highway", "planned"),
        ("highway", "corridor"),
        ("highway", "raceway"),
        ("highway", "elevator"),
        ("highway", "emergency_bay"),
        ("highway", "services"),
        ("amenity", "parking"),
        ("amenity", "services"),
        ("smoothness", "bad"),
        ("designation", "public_bridleway"),
        ("smoothness", "very_bad"),
        ("smoothness", "very_horrible"),
        ("smoothness", "horrible"),
        ("smoothness", "impassable"),
        ("smoothness", "medium"),
        ("footway", "crossing"),
        ("surface", "dirt"),
        ("surface", "unpaved"),
        ("surface", "gravel"),
        ("surface", "grass"),
        ("surface", "ground"),
        ("surface", "sand"),
        ("surface", "earth"),
        ("surface", "pebblestone"),
        ("surface", "fine_gravel"),
        ("surface", "cobblestone"),
        ("surface", "concrete:plates"),
        ("surface", "concrete:lanes"),
        ("surface", "wood"),
        ("surface", "metal"),
        ("surface", "stone"),
        ("surface", "grass_paver"),
        ("area", "yes"),
        ("ice_road", "yes"),
        ("winter_road", "yes"),
        ("tracktype", "grade2"),
        ("tracktype", "grade3"),
        ("tracktype", "grade4"),
        ("tracktype", "grade5"),
        ("tracktype", "indeterminate"),
        ("access", "private"),
    ]
    for k, v in filters:
        if tags.get(k) == v:
            return True
    return False


def test_element(tags):
    if not tags:
        return "no"
    if tags.get("lbroads"):
        return tags["lbroads"]
    if test_no(tags):
        return "no"
    if test_great(tags):
        return "great"
    if test_bicycleundefined(tags):
        return "bicycle_undefined"
    if test_bikelane(tags):
        return "bikelane"
    if test_greatother(tags):
        return "greatfoot"
    if test_foot(tags):
        return "foot"
    return "undefined"
