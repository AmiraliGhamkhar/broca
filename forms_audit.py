import os
import re

views_dir = "/root/broca/resources/views"

blade_files = []
for root, _, files in os.walk(views_dir):
    for f in files:
        if f.endswith(".blade.php"):
            blade_files.append(os.path.join(root, f))

blade_files.sort()

print("=== FORMS & CSRF AUDIT ===\n")
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    # Track form state
    in_form = False
    form_line = 0
    form_method = 'GET'
    has_method_directive = False
    has_csrf = False
    
    for i, line in enumerate(lines, 1):
        # Detect form opening
        form_open = re.search(r'<form\b', line, re.IGNORECASE)
        if form_open:
            in_form = True
            form_line = i
            form_method = 'GET'
            has_method_directive = False
            has_csrf = False
            
            # Check method in same line
            method_match = re.search(r'method\s*=\s*["\'](get|post)["\']', line, re.IGNORECASE)
            if method_match:
                form_method = method_match.group(1).upper()
            
            # Check for @method directive in same line
            if '@method' in line:
                has_method_directive = True
            
            # Check for @csrf in same line
            if '@csrf' in line:
                has_csrf = True
        
        # While in form, check for directives
        if in_form:
            if '@method' in line and not has_method_directive:
                has_method_directive = True
            if '@csrf' in line:
                has_csrf = True
            
            # Check form closing
            if '</form>' in line:
                in_form = False
                # Determine if CSRF needed
                needs_csrf = form_method == 'POST' or has_method_directive
                if needs_csrf and not has_csrf:
                    print(f"MISSING CSRF -> {rel}:{form_line} (form at line {form_line}, closes at line {i}, method={form_method}, has_method_directive={has_method_directive})")
