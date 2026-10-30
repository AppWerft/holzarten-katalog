import json
from pathlib import Path
from collections import Counter

def load_json(filename):
    with open(filename, encoding="utf-8") as f:
        return json.load(f)

def get_rows(data):
    if isinstance(data, list):
        return data
    for key in ("holzarten", "data", "items"):
        if key in data and isinstance(data[key], list):
            return data[key]
    raise ValueError("Unbekannte JSON-Struktur")

# Dateien laden
en_data = get_rows(load_json("holzarten.json"))
eig_data = get_rows(load_json("holzarten_eigenschaften.json"))

# EN-Codes extrahieren
def en_codes(row):
    codes = row.get("codes", [])
    if isinstance(codes, str):
        codes = [codes]
    if not codes and row.get("id"):
        codes = [row["id"]]
    return codes

en_map = {}
for row in en_data:
    for code in en_codes(row):
        en_map.setdefault(code, []).append(row)

eig_map = {}
for row in eig_data:
    code = row.get("code")
    if code:
        eig_map.setdefault(code, []).append(row)

# Auswertung
print("\n--- DATEIINHALTE ---")
print(f"EN 13556:          {len(en_data)} Datensätze")
print(f"Eigenschaften:     {len(eig_data)} Datensätze")

print("\n--- DOPPELTE CODES ---")
for code, rows in en_map.items():
    if len(rows) > 1:
        print("EN 13556:", code, len(rows), "Vorkommen")

for code, rows in eig_map.items():
    if len(rows) > 1:
        print("Eigenschaften:", code, len(rows), "Vorkommen")

en_codes_set = set(en_map)
eig_codes_set = set(eig_map)

print("\n--- CODE-VERGLEICH ---")
print("In beiden Dateien:", len(en_codes_set & eig_codes_set))
print("Nur EN 13556:", len(en_codes_set - eig_codes_set))
print("Nur Eigenschaften:", len(eig_codes_set - en_codes_set))

print("\n--- NUR IN EN 13556 ---")
for code in sorted(en_codes_set - eig_codes_set):
    print(code)

print("\n--- NUR IN EIGENSCHAFTEN ---")
for code in sorted(eig_codes_set - en_codes_set):
    print(code)

print("\n--- BOTANISCHE NAMEN ---")
for code in sorted(en_codes_set & eig_codes_set):
    en_row = en_map[code][0]
    eig_row = eig_map[code][0]

    en_names = en_row.get("botanical_names", [])
    eig_names = eig_row.get("name_latein", [])

    if en_names and eig_names and set(en_names) != set(eig_names):
        print(f"\n{code}")
        print("  EN 13556:", en_names)
        print("  Eigenschaften:", eig_names)