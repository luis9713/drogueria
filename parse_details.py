import re

def parse_detailed(filename):
    print(f"=== {filename} ===")
    with open(filename, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
    
    # Check lines of SQL for CREATE DATABASE or USE
    for line in content.splitlines()[:150]:
        if 'create' in line.lower() or 'use' in line.lower():
            print("Line:", line.strip())

# Check sql_nequi.sql
print("=== sql_nequi.sql ===")
with open('sql_nequi.sql', 'r', encoding='utf-8', errors='ignore') as f:
    print(f.read())

print("=== sql_notas.sql ===")
with open('sql_notas.sql', 'r', encoding='utf-8', errors='ignore') as f:
    print(f.read())
