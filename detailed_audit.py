import os
import re
import json

views_dir = "/root/broca/resources/views"

blade_files = []
for root, _, files in os.walk(views_dir):
    for f in files:
        if f.endswith(".blade.php"):
            blade_files.append(os.path.join(root, f))

blade_files.sort()

print(f"Total blade files: {len(blade_files)}")

# 1. Unescaped output {!! !!}
print("\n=== 1. UNESCAPED OUTPUT {!! !!} ===")
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f, 1):
            if "{!!" in line:
                print(f"{rel}:{i}: {line.strip()}")

# 2. Forms and CSRF
print("\n=== 2. FORMS & CSRF ===")
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    # Find form blocks
    form_matches = list(re.finditer(r'<form\b[^>]*>(.*?)</form>', content, re.DOTALL | re.IGNORECASE))
    for m in form_matches:
        form_tag_and_body = m.group(0)
        # check method
        method_match = re.search(r'method=["\']?([^"\'\s>]+)', form_tag_and_body, re.IGNORECASE)
        method = method_match.group(1).upper() if method_match else 'GET'
        has_post_override = bool(re.search(r'@method\(["\'](POST|PUT|PATCH|DELETE)["\']\)', form_tag_and_body, re.IGNORECASE))
        has_csrf = bool(re.search(r'(@csrf|_token)', form_tag_and_body))
        
        line_no = content[:m.start()].count('\n') + 1
        if (method == 'POST' or has_post_override) and not has_csrf:
            print(f"MISSING CSRF -> {rel}:{line_no}: method={method} has_post_override={has_post_override}")
        else:
            # print(f"OK Form -> {rel}:{line_no}")
            pass

# Check forms without closing tag or multiline form tag
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    for i, line in enumerate(lines, 1):
        if "<form" in line.lower() and "</form>" not in line.lower():
            # Check lines following
            pass

