
import pandas as pd

df = pd.read_json("holzarten_raw.json")

print("Datensätze:", len(df))
print("\nFehlende Werte:")
print(df.isna().sum())

print("\nErste 15 Datensätze:")
for i, row in df.head(15).iterrows():
    print(f"\n--- Datensatz {i+1} ---")
    for col, value in row.items():
        print(f"{col}: {value!r}")

print("\nZellen mit mehreren möglichen Einträgen:")
for col in df.columns:
    mask = df[col].astype(str).str.contains(
        r"[,;\n/]|<br", case=False, regex=True, na=False
    )
    if mask.any():
        print(f"\n{col}: {mask.sum()} Treffer")
        print(df.loc[mask, col].head(10).to_list())
