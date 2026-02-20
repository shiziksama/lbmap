import json
import os


def load_bad_tags(path=None):
    if path is None:
        path = os.path.join(os.path.dirname(__file__), "bad_tags.json")

    pairs_list = []
    pairs_set = set()

    try:
        with open(path, "r", encoding="utf-8") as f:
            data = json.load(f)
        for item in data.get("bad_pairs", []):
            if not isinstance(item, dict) or len(item) != 1:
                continue
            key, value = next(iter(item.items()))
            if not key or not value:
                continue
            pairs_list.append((key, value))
            pairs_set.add((key, value))
    except FileNotFoundError:
        pass

    return pairs_set, pairs_list


BAD_TAG_SET, BAD_TAG_PAIRS = load_bad_tags()
