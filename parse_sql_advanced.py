import re
import sys
from collections import defaultdict

def extract_database_directives(content):
    res = []
    # Match CREATE DATABASE or USE
    for match in re.finditer(r'(?i)\b(CREATE\s+DATABASE|USE)\s+[`"\'\w\-]+', content):
        res.append(match.group(0).strip())
    return res

def parse_inserts(content):
    # This function parses INSERT INTO lines and counts rows.
    # phpMyAdmin format: INSERT INTO `table_name` (`col1`, `col2`, ...) VALUES
    # (val1, val2, ...),
    # (val1, val2, ...);
    # Let's search for INSERT INTO statements and then parse the value tuples.
    table_rows = defaultdict(int)
    
    # regex to find INSERT INTO `table` ... VALUES
    # we can use single character scanning for robust parentheses matching.
    pos = 0
    n = len(content)
    pattern = re.compile(r'(?i)INSERT\s+INTO\s+[`"\'\w\-]+\s*(?:\([^)]*\))?\s*VALUES', re.DOTALL)
    
    for match in re.finditer(r'(?i)INSERT\s+INTO\s+([`"\'\w\-]+)', content):
        table_name = match.group(1).replace('`', '').replace('"', '').replace("'", "")
        # Find where the statement start is and then search for VALUES
        # Let's search for the next VALUES keyword starting from the match end
        ins_start = match.start()
        # Find next semicolon to bound the statement, which is safe for regular PHPMyAdmin dumps
        semi_idx = content.find(';', ins_start)
        if semi_idx == -1:
            semi_idx = n
        
        stmt = content[ins_start:semi_idx+1]
        values_match = re.search(r'(?i)\bVALUES\b', stmt)
        if not values_match:
            continue
        
        values_part = stmt[values_match.end():]
        # Count the number of tuples in values_part.
        # A simple and robust tuple counter handles strings (escaping), comments, and brackets.
        # We want to count outer-level brackets (...)
        # Let's scan values_part from left to right.
        bracket_count = 0
        in_string = False
        string_char = None
        escaped = False
        tuple_count = 0
        
        i = 0
        vlen = len(values_part)
        while i < vlen:
            char = values_part[i]
            if escaped:
                escaped = False
                i += 1
                continue
            if char == '\\':
                escaped = True
                i += 1
                continue
            if in_string:
                if char == string_char:
                    in_string = False
                i += 1
                continue
            if char in ("'", '"', '`'):
                in_string = True
                string_char = char
                i += 1
                continue
            
            # Now we are outside strings
            if char == '(':
                if bracket_count == 0:
                    # Starting a tuple
                    pass
                bracket_count += 1
            elif char == ')':
                bracket_count -= 1
                if bracket_count == 0:
                    tuple_count += 1
            i += 1
            
        table_rows[table_name] += tuple_count
        
    return table_rows

def parse_tables(content):
    # Extract table schema definitions
    # Match CREATE TABLE `name` or name ( ... ) ENGINE=...
    tables = {}
    # Let's find each CREATE TABLE block
    # A block goes from CREATE TABLE `name` ( to the first ); that is not in a string or inner paren block.
    pos = 0
    while True:
        match = re.search(r'(?i)CREATE\s+TABLE\s+([`"\'\w\-]+)\s*\(', content[pos:])
        if not match:
            break
        
        tbl_name = match.group(1).replace('`', '').replace('"', '').replace("'", "")
        start_idx = pos + match.start()
        bracket_start = pos + match.end() - 1 # exact index of '('
        
        # Scan until we match the closing bracket for of CREATE TABLE (often followed by ENGINE=...;)
        bracket_level = 0
        in_string = False
        string_char = None
        escaped = False
        
        i = bracket_start
        n = len(content)
        while i < n:
            char = content[i]
            if escaped:
                escaped = False
                i += 1
                continue
            if char == '\\':
                escaped = True
                i += 1
                continue
            if in_string:
                if char == string_char:
                    in_string = False
                i += 1
                continue
            if char in ("'", '"', '`'):
                in_string = True
                string_char = char
                i += 1
                continue
            
            if char == '(':
                bracket_level += 1
            elif char == ')':
                bracket_level -= 1
                if bracket_level == 0:
                    # Found the end of CREATE TABLE body!
                    # Find semicolon
                    semi_idx = content.find(';', i)
                    if semi_idx == -1:
                        semi_idx = n
                    table_sql = content[start_idx : semi_idx+1]
                    tables[tbl_name] = {
                        'full_sql': table_sql,
                        'body': content[bracket_start+1 : i]
                    }
                    pos = semi_idx + 1
                    break
            i += 1
        else:
            # Reached end without matching parent
            break
            
    # Now parse columns and indexes inside each table body
    for tbl, info in tables.items():
        body = info['body']
        # Lines separated by commas, but commas can also be inside types like varchar(255) or decimal(10,2) or key lists.
        # Let's split elements by comma outside matching inner parentheses and strings.
        elements = []
        curr = []
        bracket_level = 0
        in_string = False
        string_char = None
        escaped = False
        
        for char in body:
            if escaped:
                curr.append(char)
                escaped = False
                continue
            if char == '\\':
                curr.append(char)
                escaped = True
                continue
            if in_string:
                curr.append(char)
                if char == string_char:
                    in_string = False
                continue
            if char in ("'", '"', '`'):
                curr.append(char)
                in_string = True
                string_char = char
                continue
            if char == '(':
                bracket_level += 1
            elif char == ')':
                bracket_level -= 1
            elif char == ',' and bracket_level == 0:
                elements.append("".join(curr).strip())
                curr = []
                continue
            curr.append(char)
        if curr:
            elements.append("".join(curr).strip())
            
        columns = {}
        primary_keys = []
        indexes = []
        uniques = []
        fks = []
        
        for elem in elements:
            if not elem:
                continue
            # Detect primary keys, unique keys, keys, foreign keys or columns
            elem_upper = elem.upper()
            if elem_upper.startswith('PRIMARY KEY'):
                # Extract columns
                cols = re.findall(r'`([^`]+)`', elem)
                if not cols:
                    cols = re.findall(r'(\w+)', elem[elem_upper.find('('):])
                primary_keys = cols
            elif elem_upper.startswith('UNIQUE KEY') or elem_upper.startswith('UNIQUE INDEX'):
                uniques.append(elem)
            elif elem_upper.startswith('KEY') or elem_upper.startswith('INDEX'):
                indexes.append(elem)
            elif elem_upper.startswith('CONSTRAINT') or elem_upper.startswith('FOREIGN KEY'):
                fks.append(elem)
            else:
                # Column parsing
                # Usually: `name` type [NOT NULL] [DEFAULT ...] [AUTO_INCREMENT] ...
                parts = elem.split()
                if not parts:
                    continue
                col_name = parts[0].replace('`', '').replace('"', '').replace("'", "")
                col_type = parts[1] if len(parts) > 1 else ""
                # Keep rest of definition (options)
                col_opts = " ".join(parts[2:])
                columns[col_name] = {
                    'type': col_type,
                    'options': col_opts,
                    'full': elem
                }
                
        info['columns'] = columns
        info['primary_keys'] = primary_keys
        info['indexes'] = indexes
        info['uniques'] = uniques
        info['fks'] = fks
        
    return tables

print("Done compiling parser functions.")

def analyze_all_files():
    files = ['farmaciasistema (1).sql', 'farmaciasistema (2).sql', 'farmaciasistema.sql', 'sql_nequi.sql', 'sql_notas.sql']
    
    results = {}
    for fn in files:
        print(f"\n========================================\nAnalyzing {fn}\n========================================")
        with open(fn, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
            
        print("Length of file:", len(content))
        db_directives = extract_database_directives(content)
        print("Database directives found:", db_directives)
        
        # Metadata / comments at beginning (first 10 comments or so)
        print("Metadata comments:")
        lines = content.splitlines()
        meta_count = 0
        for l in lines[:100]:
            ls = l.strip()
            if ls.startswith('--') or ls.startswith('/*') or ls.startswith('*'):
                print("  ", ls)
                meta_count += 1
                if meta_count >= 15:
                    break
                    
        # Parse tables
        tables = parse_tables(content)
        print("Tables created:")
        for tbl in sorted(tables.keys()):
            info = tables[tbl]
            print(f"  - {tbl}: {len(info['columns'])} columns, PK: {info['primary_keys']}")
            
        # Parse inserts
        inserts = parse_inserts(content)
        print("Row counts from inserts:")
        for tbl in sorted(inserts.keys()):
            print(f"  - {tbl}: {inserts[tbl]} rows")
            
        results[fn] = {
            'db_directives': db_directives,
            'tables': tables,
            'inserts': inserts
        }
    return results

if __name__ == '__main__':
    results = analyze_all_files()
    
    # Let's perform a direct comparative analysis between 'farmaciasistema (1).sql' and 'farmaciasistema (2).sql'
    f1 = 'farmaciasistema (1).sql'
    f2 = 'farmaciasistema (2).sql'
    f_base = 'farmaciasistema.sql'
    
    r1 = results[f1]
    r2 = results[f2]
    r_base = results[f_base] if f_base in results else None
    
    print("\n========================================\nCOMPARED SCHEMAS (1) vs (2):\n========================================")
    t1 = set(r1['tables'].keys())
    t2 = set(r2['tables'].keys())
    
    print("Tables unique to (1):", t1 - t2)
    print("Tables unique to (2):", t2 - t1)
    
    common_tables = t1 & t2
    print(f"Common tables ({len(common_tables)}):")
    
    differing_tables = []
    for tbl in sorted(common_tables):
        tbl1 = r1['tables'][tbl]
        tbl2 = r2['tables'][tbl]
        
        # Check columns
        cols1 = set(tbl1['columns'].keys())
        cols2 = set(tbl2['columns'].keys())
        
        col_diff = False
        desc_diffs = []
        if cols1 != cols2:
            col_diff = True
            desc_diffs.append(f"Diff columns: 1 unique {cols1 - cols2}, 2 unique {cols2 - cols1}")
        else:
            # check types
            for col in cols1:
                t1_type = tbl1['columns'][col]['type']
                t2_type = tbl2['columns'][col]['type']
                if t1_type.upper() != t2_type.upper():
                    col_diff = True
                    desc_diffs.append(f"Diff type for {col}: 1 is {t1_type}, 2 is {t2_type}")
                    
        # Check PK
        if tbl1['primary_keys'] != tbl2['primary_keys']:
            col_diff = True
            desc_diffs.append(f"Diff PK: 1 is {tbl1['primary_keys']}, 2 is {tbl2['primary_keys']}")
            
        if col_diff:
            differing_tables.append((tbl, desc_diffs))
            
    print("Differing schemas between common tables:")
    for tbl, diffs in differing_tables:
        print(f"  - {tbl}:")
        for d in diffs:
            print(f"    * {d}")
            
    print("\n========================================\nROW COUNT COMPARISON:\n========================================")
    all_all_tables = t1 | t2
    print(f"{'Table':<30} | {'farmaciasistema (1)':<20} | {'farmaciasistema (2)':<20} | {'farmaciasistema.sql':<20}")
    print("-" * 100)
    for tbl in sorted(all_all_tables):
        c1 = r1['inserts'].get(tbl, 0)
        c2 = r2['inserts'].get(tbl, 0)
        c_base = r_base['inserts'].get(tbl, 0) if r_base else 'N/A'
        print(f"{tbl:<30} | {c1:<20} | {c2:<20} | {c_base:<20}")

