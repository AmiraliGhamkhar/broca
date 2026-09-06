import os
import re

views_dir = "/root/broca/resources/views"

blade_files = []
for root, _, files in os.walk(views_dir):
    for f in files:
        if f.endswith(".blade.php"):
            blade_files.append(os.path.join(root, f))

blade_files.sort()

print("=== HTML TAG AUDIT ===\n")
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    for i, line in enumerate(lines, 1):
        # Check for broken HTML tags
        if "<<" in line or ">>" in line:
            print(f"BROKEN HTML -> {rel}:{i}: {line.strip()}")
        
        # Check for unclosed tags
        if "<" in line and ">" not in line:
            # Check if it's a self-closing tag
            if not re.search(r'<[^>]+/>$', line.strip()):
                print(f"UNCLOSED TAG -> {rel}:{i}: {line.strip()}")
