
import pandas as pd
import json
import re

df = pd.read_json("holzarten_raw.json")

def clean(value):
    if pd.isna(value):
        return None
    return re.sub(r"\s+", " ", str(value)).strip()

def split_values(value):
    value = clean(value)
    if not value:
        return []

    return [
        item.strip()
        for item in value.split(",")
        if item.strip()
    ]

holzarten = []

for _, row in df.iterrows():

    code_raw = clean(row["Kurzzeichen"])
    botanical_raw = clean(row["Gattung / Art"])
    german_raw = clean(row["Deutscher Gattungs- / Artname"])
    standard_raw = clean(row["Deutscher Standard-Holzname"])

    item = {
        "id": code_raw.split(",")[0].strip(),

        "codes": split_values(code_raw),

        "wood_type": clean(row["Laub / Nadel"]),

        "botanical_raw": botanical_raw,
        "botanical_names": split_values(botanical_raw),

        "german_raw": german_raw,
        "german_names": split_values(german_raw),

        "standard_raw": standard_raw,
        "standard_names": split_values(standard_raw),

        "origin": clean(row["Herkunft"]),
        "protection": clean(row["Schutzstatus"]),

        "images": [],
        "description": "",

        "source": "EN 13556 / Wikipedia"
    }

    holzarten.append(item)

with open(
    "holzarten.json",
    "w",
    encoding="utf-8"
) as f:
    json.dump(
        holzarten,
        f,
        ensure_ascii=False,
        indent=2
    )

print("Normalisiert:", len(holzarten))

print("Mit mehreren botanischen Namen:",
      sum(len(x["botanical_names"]) > 1 for x in holzarten))

print("Mit deutschem Namen:",
      sum(bool(x["german_names"]) for x in holzarten))

print("JSON erzeugt: holzarten.json")
