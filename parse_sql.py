import re
import sys

def analyze_file(filename):
    print(f"=== Analyzing {filename} ===")
    with open(filename, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()

    # Search for CREATE DATABASE or USE
    create_db = re.findall(r'(?i)CREATE\s+DATABASE\s+[^;]+', content)
    use_db = re.findall(r'(?i)\bUSE\s+[`"\'\w]+', content)
    print("Database directives:")
    for db in create_db:
        print(f"  {db.strip()}")
    for use in use_db:
        print(f"  {use.strip()}")

    # Find comments at the beginning (first 500 chars) for export metadata
    print("Beginning metadata comments (first few non-empty lines starting with -- or /*):")
    lines = content.splitlines()[:50]
    meta_count = 0
    for line in lines:
        l = line.strip()
        if l.startswith('--') or l.startswith('/*') or l.startswith('*'):
            print("  ", l)
            meta_count += 1
            if meta_count > 15:
                break

    # Look for table structures (CREATE TABLE)
    # Let's extract each CREATE TABLE and its block
    # Simple regex for matching CREATE TABLE `table` ( ... ) ENGINE=... OR up to next empty line/terminator
    tables = {}
    
    # We can parse table creation and insert count
    # Let's find CREATE TABLE statements
    create_table_regex = re.compile(r'(?i)CREATE\s+TABLE\s+[`"\'\w]+\s*\(', re.MULTILINE)
    
    # Alternatively, let's use a more robust custom parser
    pos = 0
    n = len(content)
    
    # Let's also count insert statement rows
    # INSERT INTO `table` VALUES (val1, val2), (val3, val4);
    # Parsing INSERT INTO is also straightforward but we need to count tuples.
    # To count tuples in an insert statement, we can count the number of unescaped parentheses '),' outside of strings, 
    # or write a small function to count CSV/SQL list items.
    
    insert_counts = {}
    # Let's find all INSERT INTO matches
    # Sometimes table name is `table` or table
    # Pattern: INSERT INTO table_name VALUES (...);
    insert_pattern = re.compile(r'(?i)INSERT\s+INTO\s+[`"\'\w\-\.]+\s*VALUES\s*(.*?\s*);', re.DOTALL)
    
    insert_regex_all = re.finditer(r'(?i)INSERT\s+INTO\s+[`"\'\w]+', content)
    
    # Let's trace INSERT INTO manually
    import sqlparse
    try:
        parsed = sqlparse.parse(content)
        print(f"Parsed {len(parsed)} statements with sqlparse.")
    except Exception as e:
        print(f"sqlparse error: {e}")

if __name__ == '__main__':
    for fn in ['farmaciasistema (1).sql', 'farmaciasistema (2).sql', 'farmaciasistema.sql', 'sql_nequi.sql', 'sql_notas.sql']:
        analyze_file(fn)
