import pandas as pd
import sys

def inspect_excel(file_path):
    try:
        df = pd.read_excel(file_path, nrows=5)
        print(f"--- File: {file_path} ---")
        print(f"Headers: {df.columns.tolist()}")
        print("First 2 rows:")
        print(df.head(2).to_string())
        print("\n")
    except Exception as e:
        print(f"Error reading {file_path}: {e}")

if __name__ == "__main__":
    inspect_excel("/home/rennyboy/Downloads/projects/ieepis/antipoloesEquipement.xlsx")
    inspect_excel("/home/rennyboy/Downloads/projects/ieepis/equipment_import_template.xlsx")
