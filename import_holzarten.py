
import pandas as pd
import requests
from io import StringIO

url = "https://de.wikipedia.org/wiki/EN_13556"

headers = {
    "User-Agent": "HolzartenImporter/1.0 (personal research project)"
}

response = requests.get(url, headers=headers, timeout=30)
response.raise_for_status()

print("HTTP:", response.status_code)

html = response.text

# HTML lokal sichern, damit wir es bei Bedarf untersuchen können
with open("en13556.html", "w", encoding="utf-8") as f:
    f.write(html)

tables = pd.read_html(StringIO(html))

print("Gefundene Tabellen:", len(tables))

for i, table in enumerate(tables):
    print(f"\nTabelle {i}: {len(table)} Zeilen")
    print(table.columns.tolist())

# Größte Tabelle zunächst als Kandidat
df = max(tables, key=len)

df.to_json(
    "holzarten_raw.json",
    orient="records",
    force_ascii=False,
    indent=2
)

print("\nExportiert:", len(df), "Zeilen")
