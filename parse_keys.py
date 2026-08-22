import re

def parse_keys_and_names(fn):
    print(f"\n==================== {fn} ====================")
    with open(fn, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()

    # Look for table structures (CREATE TABLE)
    import parse_sql_advanced
    tables = parse_sql_advanced.parse_tables(content)
    
    for tbl in sorted(tables.keys()):
        info = tables[tbl]
        # Let's inspect primary keys or indexes
        pks = info['primary_keys']
        # If no PK found, it could be that PK is specified on the column definition itself as "PRIMARY KEY"
        if not pks:
            # Look inside columns
            for col, col_info in info['columns'].items():
                if 'PRIMARY KEY' in col_info['options'].upper():
                    pks = [col]
                    break
        
        # Let's also check if there are AUTO_INCREMENT attributes, types, etc.
        print(f"Table: {tbl}")
        print(f"  Primary Keys: {pks}")
        print(f"  Columns counts: {len(info['columns'])}")
        # Print columns and their definitions
        for col, col_info in sorted(info['columns'].items()):
             print(f"    - {col}: {col_info['type']} {col_info['options']}")
        if info['indexes']:
             print("  Indexes:")
             for idx in info['indexes']:
                 print(f"    - {idx.strip()}")
        if info['uniques']:
             print("  Uniques:")
             for u in info['uniques']:
                 print(f"    - {u.strip()}")
        if info['fks']:
             print("  Foreign Keys:")
             for fk in info['fks']:
                 print(f"    - {fk.strip()}")

parse_keys_and_names('farmaciasistema (2).sql')
