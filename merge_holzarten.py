
import json
from pathlib import Path
from copy import deepcopy
from collections import Counter

BASE = Path(__file__).parent

EN_FILE = BASE / "holzarten.json"
PROPERTIES_FILE = BASE / "holzarten_eigenschaften.json"
OUTPUT_FILE = BASE / "holzarten_gesamt.json"
REPORT_FILE = BASE / "zusammenfuehrung_bericht.txt"


def load_json(filename):
    with filename.open(encoding="utf-8") as f:
        return json.load(f)


def get_rows(data):
    """Unterstützt sowohl JSON-Arrays als auch umschließende Objekte."""
    if isinstance(data, list):
        return data

    for key in ("holzarten", "data", "items"):
        if isinstance(data.get(key), list):
            return data[key]

    raise ValueError("Unbekannte JSON-Struktur")


def get_codes(row):
    codes = row.get("codes", [])

    if isinstance(codes, str):
        codes = [codes]

    if not codes and row.get("id"):
        codes = [row["id"]]

    return [str(c).strip() for c in codes if str(c).strip()]


def normalize_botanical(name):
    """Nur für den Vergleich, nicht zur Veränderung der Quelldaten."""
    return (
        str(name)
        .casefold()
        .replace("×", "x")
        .replace(" ", "")
        .replace(".", "")
    )


# --------------------------------------------------
# 1. Quelldateien laden
# --------------------------------------------------

en_data = get_rows(load_json(EN_FILE))
property_data = get_rows(load_json(PROPERTIES_FILE))

print(f"EN 13556:       {len(en_data)} Datensätze")
print(f"Eigenschaften:  {len(property_data)} Datensätze")


# --------------------------------------------------
# 2. Eigenschaftsdaten nach Code indizieren
# --------------------------------------------------

property_map = {}

for row in property_data:
    code = row.get("code")

    if not code:
        raise ValueError("Eigenschaftsdatensatz ohne Code gefunden")

    if code in property_map:
        raise ValueError(f"Doppelter Eigenschaftscode: {code}")

    property_map[code] = row


# --------------------------------------------------
# 3. EN-Daten auf einzelne Codes aufteilen
# --------------------------------------------------

result = []
source_groups = {}
used_codes = []

for original in en_data:

    codes = get_codes(original)

    if not codes:
        raise ValueError(
            f"EN-Datensatz ohne Code: {original}"
        )

    source_group = "_".join(codes)

    for code in codes:

        if code in used_codes:
            raise ValueError(f"Doppelter EN-Code: {code}")

        used_codes.append(code)

        # Originaldatensatz kopieren
        wood = deepcopy(original)

        # Jeder Code erhält einen eigenen Datensatz
        wood["id"] = code
        wood["codes"] = [code]

        # Gemeinsame Herkunft bleibt nachvollziehbar
        wood["source_group"] = source_group
        wood["source_codes"] = codes

        # Zusätzliche Eigenschaften zuordnen
        prop = property_map.get(code)

        if prop:

            wood["properties"] = {
                "name_de": prop.get("name_de"),
                "botanical_names_source": prop.get("name_latein", []),
                "wood_group": prop.get("gruppe"),
                "color": prop.get("holzfarbe"),
                "characteristics": prop.get("eigenschaften"),
                "hardness": prop.get("haerte"),
                "durability": prop.get("dauerhaftigkeit"),
                "uses": prop.get("verwendung"),

                # Ursprüngliche Bildangabe nur aufbewahren
                "legacy_image_path": prop.get("bild"),

                # Bildrechte und Herkunft
                "image_source": prop.get("bildquelle"),
                "image_source_url": prop.get("bildquelle_url"),
                "image_author": prop.get("bildautor"),
                "image_license": prop.get("bildlizenz"),
                "image_license_url": prop.get("bildlizenz_url"),

                "data_source": prop.get("datenquelle")
            }

        else:
            wood["properties"] = None

        result.append(wood)


# --------------------------------------------------
# 4. Plausibilitätsprüfungen
# --------------------------------------------------

result_codes = [row["id"] for row in result]

assert len(result_codes) == len(set(result_codes))
assert len(result) == len(result_codes)

missing = sorted(set(property_map) - set(result_codes))
unmatched = sorted(set(result_codes) - set(property_map))

if missing:
    raise ValueError(
        f"Eigenschaftscodes ohne EN-Datensatz: {missing}"
    )


# --------------------------------------------------
# 5. Botanische Namensabweichungen ermitteln
# --------------------------------------------------

differences = []

for wood in result:

    prop = wood.get("properties")

    if not prop:
        continue

    en_names = wood.get("botanical_names", [])
    extra_names = prop.get("botanical_names_source", [])

    en_normalized = {
        normalize_botanical(n) for n in en_names
    }

    extra_normalized = {
        normalize_botanical(n) for n in extra_names
    }

    if (
        en_normalized
        and extra_normalized
        and en_normalized != extra_normalized
    ):
        differences.append({
            "code": wood["id"],
            "en_names": en_names,
            "property_names": extra_names
        })


# --------------------------------------------------
# 6. Ergebnisse speichern
# --------------------------------------------------

with OUTPUT_FILE.open("w", encoding="utf-8") as f:
    json.dump(
        result,
        f,
        ensure_ascii=False,
        indent=2
    )


# --------------------------------------------------
# 7. Prüfbericht erstellen
# --------------------------------------------------

property_count = sum(
    1 for row in result
    if row["properties"] is not None
)

lines = [
    "ZUSAMMENFUEHRUNG HOLZARTEN",
    "==========================",
    "",
    f"EN-Datensätze:              {len(en_data)}",
    f"Eigenschaftsdatensätze:     {len(property_data)}",
    f"Erzeugte Holzarten:         {len(result)}",
    f"Mit Eigenschaften:         {property_count}",
    f"Ohne Eigenschaften:       {len(result) - property_count}",
    f"Botanische Abweichungen:   {len(differences)}",
    "",
    "BOTANISCHE NAMENSABWEICHUNGEN",
    "-----------------------------"
]

for item in differences:
    lines.extend([
        "",
        item["code"],
        f"  EN 13556:     {item['en_names']}",
        f"  Eigenschaften: {item['property_names']}"
    ])

lines.extend([
    "",
    "CODES OHNE EIGENSCHAFTEN",
    "------------------------"
])

for code in unmatched:
    lines.append(code)

REPORT_FILE.write_text(
    "\n".join(lines),
    encoding="utf-8"
)

print()
print("Zusammenführung abgeschlossen.")
print(f"Erzeugte Holzarten:       {len(result)}")
print(f"Mit Eigenschaften:       {property_count}")
print(f"Ohne Eigenschaften:     {len(result) - property_count}")
print(f"Namensabweichungen:      {len(differences)}")
print()
print(f"Ergebnis: {OUTPUT_FILE.name}")
print(f"Bericht:  {REPORT_FILE.name}")
