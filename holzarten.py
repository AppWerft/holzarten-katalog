#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import csv
import json
import re
from datetime import datetime
from pathlib import Path

import requests
from bs4 import BeautifulSoup

URL = "https://de.wikipedia.org/wiki/Liste_der_Holzarten"
SOURCE = URL

OUT_DIR = Path("holzarten_export")
JSON_FILE = OUT_DIR / "holzarten.json"
CSV_FILE = OUT_DIR / "holzarten.csv"
REPORT_FILE = OUT_DIR / "pruefbericht.txt"

HEADERS = {
    "User-Agent": "Holzarten-WP-Plugin/1.0 (Datenimport)"
}


def clean_cell(cell, separator=" "):
    """Zelltext ohne Fußnotenmarker und überflüssige Leerzeichen."""
    if cell is None:
        return ""

    cell = BeautifulSoup(str(cell), "html.parser")
    for tag in cell.select("sup, .reference, .mw-ref"):
        tag.decompose()

    text = cell.get_text(separator, strip=True)
    text = re.sub(r"\s+", " ", text)
    text = re.sub(r"\s+([,;:])", r"\1", text)
    return text.strip()


def botanical_names(cell):
    """Botanische Namen anhand echter HTML-Zeilenumbrüche trennen."""
    if cell is None:
        return []

    cell = BeautifulSoup(str(cell), "html.parser")
    for tag in cell.select("sup, .reference, .mw-ref"):
        tag.decompose()

    # <br> ist in dieser Tabelle der relevante Trenner zwischen Namen.
    for br in cell.find_all("br"):
        br.replace_with("###BOTANICAL_BREAK###")

    raw = cell.get_text(" ", strip=True)
    parts = [
        re.sub(r"\s+", " ", p).strip(" ,;")
        for p in raw.split("###BOTANICAL_BREAK###")
    ]
    parts = [p for p in parts if p]

    # Korrektur eines redaktionellen Zusatzes in der Wikipedia-Zelle Ahorn.
    parts = [re.sub(r"\s+und seltener$", "", p, flags=re.I) for p in parts]

    return list(dict.fromkeys(parts))


def expand_table(table):
    """
    Erzeugt ein rechteckiges Zellraster und berücksichtigt rowspan/colspan.
    Jede Position enthält ein BeautifulSoup-Tag oder None.
    """
    rows = table.find_all("tr")
    grid = []
    active = {}  # Spaltenposition -> [Zelle, verbleibende Folgezeilen]

    for row in rows:
        current = {}
        next_active = {}

        for col, (cell, remaining) in active.items():
            current[col] = cell
            if remaining > 1:
                next_active[col] = [cell, remaining - 1]

        col = 0
        cells = row.find_all(["th", "td"], recursive=False)

        for cell in cells:
            while col in current:
                col += 1

            try:
                colspan = max(1, int(cell.get("colspan", 1)))
            except (TypeError, ValueError):
                colspan = 1

            try:
                rowspan = max(1, int(cell.get("rowspan", 1)))
            except (TypeError, ValueError):
                rowspan = 1

            for offset in range(colspan):
                position = col + offset
                current[position] = cell
                if rowspan > 1:
                    next_active[position] = [cell, rowspan - 1]

            col += colspan

        if current:
            width = max(current) + 1
            grid.append([current.get(i) for i in range(width)])
        else:
            grid.append([])

        active = next_active

    return grid


def find_column(headers, search):
    for index, header in enumerate(headers):
        if search in header.lower():
            return index
    return None


def value_at(cells, index):
    if index is None or index >= len(cells):
        return ""
    return clean_cell(cells[index])


def codes_in_order(value):
    """Vierstellige Codes in Quellreihenfolge, ohne Duplikate."""
    found = re.findall(r"(?<![A-Z])[A-Z]{4}(?![A-Z])", value or "")
    return list(dict.fromkeys(found))


def get_group(table):
    heading = table.find_previous(["h2", "h3", "h4"])
    if heading:
        heading_text = clean_cell(heading).lower()
        if "laub" in heading_text:
            return "Laubholz"
        if "nadel" in heading_text:
            return "Nadelholz"
    return ""


def download_page():
    print("Lade Wikipedia-Seite ...")
    response = requests.get(URL, headers=HEADERS, timeout=30)
    response.raise_for_status()
    response.encoding = response.apparent_encoding
    return BeautifulSoup(response.text, "html.parser")


def extract_wood_types(soup):
    result = {}
    problems = []

    tables = soup.find_all("table")
    print(f"Gefundene HTML-Tabellen: {len(tables)}")

    for table_number, table in enumerate(tables, start=1):
        grid = expand_table(table)

        header_index = None
        headers = []

        for row_index, cells in enumerate(grid):
            labels = [clean_cell(cell).lower() for cell in cells]
            joined = " ".join(labels)

            if (
                "holzart" in joined
                and "botanisch" in joined
                and "kurzzeichen" in joined
            ):
                header_index = row_index
                headers = labels
                break

        if header_index is None:
            continue

        print(f"Verarbeite Tabelle {table_number}")

        columns = {
            "name": find_column(headers, "holzart"),
            "botanical": find_column(headers, "botanisch"),
            "code": find_column(headers, "kurzzeichen"),
            "color": find_column(headers, "holzfar"),
            "properties": find_column(headers, "eigenschaften"),
            "hardness": find_column(headers, "härte"),
            "durability": find_column(headers, "dauerhaftigkeit"),
            "usage": find_column(headers, "verwendung"),
        }

        group = get_group(table)

        for row_number, cells in enumerate(grid[header_index + 1:], start=header_index + 2):
            name = value_at(cells, columns["name"])
            code_text = value_at(cells, columns["code"])
            botanical_cell = (
                cells[columns["botanical"]]
                if columns["botanical"] is not None
                and columns["botanical"] < len(cells)
                else None
            )

            codes = codes_in_order(code_text)
            names_latein = botanical_names(botanical_cell)

            if not name or not codes:
                if name or names_latein:
                    problems.append(
                        f"Tabelle {table_number}, Zeile {row_number}: "
                        f"Holzart ohne erkennbaren Kurzcode: {name!r}"
                    )
                continue

            # Mehrere Codes und gleich viele botanische Namen:
            # Zuordnung paarweise in der Reihenfolge der Wikipedia-Tabelle.
            if len(codes) > 1 and len(names_latein) == len(codes):
                botanical_by_code = dict(zip(codes, names_latein))
            else:
                botanical_by_code = {code: names_latein for code in codes}
                if len(codes) > 1 and len(names_latein) > 1:
                    problems.append(
                        f"{name}: {len(codes)} Codes, aber "
                        f"{len(names_latein)} botanische Namen; "
                        "Zuordnung nicht automatisch aufteilbar."
                    )

            for code in codes:
                entry = {
                    "code": code,
                    "name_de": name,
                    "name_latein": (
                        [botanical_by_code[code]]
                        if isinstance(botanical_by_code[code], str)
                        else botanical_by_code[code]
                    ),
                    "gruppe": group,
                    "holzfarbe": value_at(cells, columns["color"]),
                    "eigenschaften": value_at(cells, columns["properties"]),
                    "haerte": value_at(cells, columns["hardness"]),
                    "dauerhaftigkeit": value_at(cells, columns["durability"]),
                    "verwendung": value_at(cells, columns["usage"]),
                    "bild": f"bilder/{code}.jpg",

                    # Bildnachweis: nach manueller Auswahl ergänzen.
                    "bildquelle": "",
                    "bildquelle_url": "",
                    "bildautor": "",
                    "bildlizenz": "",
                    "bildlizenz_url": "",

                    "datenquelle": SOURCE,
                }

                if code in result:
                    problems.append(
                        f"Doppelter Code {code}: "
                        f"{result[code]['name_de']} / {name}"
                    )
                    continue

                result[code] = entry

    return result, problems


def write_json(data):
    with JSON_FILE.open("w", encoding="utf-8") as file:
        json.dump(list(data.values()), file, ensure_ascii=False, indent=2)


def write_csv(data):
    fields = [
        "code", "name_de", "name_latein", "gruppe", "holzfarbe",
        "eigenschaften", "haerte", "dauerhaftigkeit", "verwendung",
        "bild", "bildquelle", "bildquelle_url", "bildautor",
        "bildlizenz", "bildlizenz_url", "datenquelle"
    ]

    with CSV_FILE.open("w", encoding="utf-8-sig", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=fields, delimiter=";")
        writer.writeheader()

        for entry in data.values():
            row = entry.copy()
            row["name_latein"] = " | ".join(row["name_latein"])
            writer.writerow(row)


def write_report(data, problems):
    with REPORT_FILE.open("w", encoding="utf-8") as file:
        file.write("HOLZARTEN – IMPORTBERICHT\n")
        file.write("=========================\n\n")
        file.write(f"Erstellungsdatum: {datetime.now():%d.%m.%Y %H:%M}\n")
        file.write(f"Anzahl Holzarten: {len(data)}\n\n")
        file.write("AUFFÄLLIGKEITEN\n")
        file.write("----------------\n")

        if problems:
            for problem in problems:
                file.write(problem + "\n")
        else:
            file.write("Keine Auffälligkeiten erkannt.\n")


def main():
    OUT_DIR.mkdir(exist_ok=True)
    soup = download_page()
    data, problems = extract_wood_types(soup)
    data = dict(sorted(data.items()))

    write_json(data)
    write_csv(data)
    write_report(data, problems)

    print("\nFERTIG")
    print("==============================")
    print(f"Holzarten:   {len(data)}")
    print(f"JSON:        {JSON_FILE}")
    print(f"CSV:         {CSV_FILE}")
    print(f"Prüfbericht: {REPORT_FILE}")
    print(f"Prüfhinweise: {len(problems)}")
    print("==============================")


if __name__ == "__main__":
    main()
