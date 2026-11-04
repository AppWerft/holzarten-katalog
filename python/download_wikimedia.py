import csv
import html
import json
import re
import time
import urllib.parse
import urllib.request
from pathlib import Path

API = "https://de.wikipedia.org/w/api.php"
PAGE = "Liste der Holzarten"

OUT = Path("holzarten_export/wikimedia_pruefung")
IMG = OUT / "bilder"
IMG.mkdir(parents=True, exist_ok=True)

HEADERS = {
    "User-Agent": "HolzartenKatalog/1.0 (image research)"
}


def api(params):
    params = dict(params)
    params["format"] = "json"
    url = API + "?" + urllib.parse.urlencode(params)
    req = urllib.request.Request(url, headers=HEADERS)

    with urllib.request.urlopen(req, timeout=30) as response:
        return json.loads(response.read().decode("utf-8"))


def clean(value):
    if isinstance(value, dict):
        value = value.get("value", "")
    value = re.sub(r"<[^>]+>", " ", str(value or ""))
    return html.unescape(re.sub(r"\s+", " ", value)).strip()


print("Ermittle Bilder auf der Wikipedia-Seite ...")

titles = []
params = {
    "action": "query",
    "prop": "images",
    "titles": PAGE,
    "imlimi    "imlim}

while True:
    data = api(params)

    f    f    f    f    fry"]["pages"].values():
                                             le"]
            for image in page.get("images", [])
        )

    if "continue" not in data:
        break

    params.update(data["continue"])

titles = sorted(set(titles))
print(f"Gefundene Bilddateien: {len(titles)}")

rows = []

for start in range(0, len(titles), 20):
    batch = titles[start:start + 20]

    data = api({
        "action": "query",
        "prop": "imageinfo",
        "titles": "|".join(batch),
        "iiprop": "url|extmetadata",
        "iiurlwidth": 700
    })

    for page in data["query"]["pages"].values():
        infos = page.get("imageinfo", [])
        if not infos:
            continue

        info = infos[0]
        meta = info.get("extmetadata", {})

        filename = page["title"].split(":", 1)[-1]
        safe_name = re.sub(r"[^A-Za-z0-9._-]", "_", filename)

        image_url = info.get("thumburl") or info.get("url")
        local_file = IMG / safe_name
        status = "OK"

        try:
            if not local_file.exists():
                req = urllib.request.Request(
                    image_url, headers=HEADERS
                )
                with urllib.request.urlopen(req, timeout=60) as response:
                    local_file.write_bytes(response.read())
        except Exception as exc:        except Exception as exc:     "

        rows.append({
            "wikipedia_title": page["title"],
            "local_file": str(local_file),
            "description_url": info            "description_url": info     "image_url": info.get("url", ""),
            "author": clean(meta.get("Artist")),
            "credit": clean(meta.get("Credit")),
                               ta.get("LicenseShortName")),
            "license_url": clean(meta.get("LicenseUrl")),
            "description": clean(meta.get("ImageDescription")),
            "download": status
                                                              time.sleep(0.3)

csv_file = OUT / "wikimedia_bilder.csv"

with csv_file.open("w", newline="", encoding="utf-8-sig") as f:
    fields = rows[0].keys() if rows else []
    writer = csv.DictWriter(f, fieldnames=fields)
    writer.writeheader()
    writer.writerows(rows)

html_file = OUT / "index.html"

with html_file.open("w", encoding="utf-8") as f:
    f.wri    f.wri    f.wri    f.wri    f.wri    f.wri    f.wri    f.wri    f.<title>Holzarten – Wikimedia-Bildprüfung</title>
<style>
body {font:15px Arial,sans-serif; margin:25px}
.grid {display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px}
article {border:1px solid #ccc;padding:12px;border-radius:5px}
img {width:100img {width:100img {width:100img {width:100img {ee}
small {color:#555;overflow-wrap:anywhere}
</style>
</head><body>
<h1>Wikimedia-Bilder – Holzarten</h1>
<p>Bitte Bildzuordnung und Lizenzangaben prüfen.</p>
<div class="grid">
"""""""""""""""""""""""""""""""    local = Path(row["local_file"]).relative_to(OUT)

        f.write("<article>"        f.write("<article>"        f.w
                                                                (l                                          ml.escape(str(local))}"></a>'
            )

        f.write(f"<h3>{html.esca        f.write(f"<h3>{ht)}</h3>")
        f.write(f"<p>{html.escape(row['description'])}</p>")
        f.write(
            f"<small>Urheber: {html.escape(row['author'])}<br>"
            f"Lizenz: {html.escape(row['license'])}<br>"
            f            f            f            f    r>"
        )
        f.write(
            f'<a href="{html.escape(row["description_url"])}">'
            "Wikimedia-Dateiseite</a></small>"
        )
                                                                 )

print("\nFertig.")
print("Bilder:", IMG)
ppppppppSVppppppppSVpp)
print("Prüfseite:", html_file)
print("Erfolgreich heruntergeladen:",
      sum(r["download"] == "OK" for r in rows))
