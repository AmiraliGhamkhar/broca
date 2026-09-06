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

issues = []

def add_sev(file_rel, line_num, severity, issue, fix):
    issues.append({
        "severity": severity,
        "file": f"{file_rel}:{line_num}",
        "issue": issue,
        "fix": fix
    })

# 1. Unescaped {!! !!}
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    for i, line in enumerate(lines, 1):
        if "{!!" in line and "!!}" in line:
            # Skip known-safe patterns
            stripped = line.strip()
            if "json_encode" in stripped or "nl2br(e(" in stripped or "htmlspecialchars" in stripped:
                continue
            add_sev(rel, i, "critical", "Unescaped output {!! !!} - potential XSS", "Use {{ }} for escaped output.")

# 2. Forms missing CSRF
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    in_form = False
    form_line = 0
    method = 'GET'
    has_method_directive = False
    has_csrf = False
    for i, line in enumerate(lines, 1):
        if re.search(r'<form\b', line, re.IGNORECASE):
            in_form = True
            form_line = i
            method = 'GET'
            has_method_directive = False
            has_csrf = False
            m = re.search(r'method\s*=\s*["\'](get|post)["\']', line, re.IGNORECASE)
            if m: method = m.group(1).upper()
        if in_form:
            if '@method' in line: has_method_directive = True
            if '@csrf' in line: has_csrf = True
            if '</form>' in line.lower():
                in_form = False
                needs_csrf = method == 'POST' or has_method_directive
                if needs_csrf and not has_csrf:
                    add_sev(rel, form_line, "critical", f"Form (method={method}) missing CSRF token", "Add @csrf inside the <form>.")

# 3. Broken Blade @if/@endif
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    stack = []
    with open(path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    for i, line in enumerate(lines, 1):
        opens = re.findall(r'@(if|unless|isset|empty|auth|guest|hasSection|push|slot|forelse|foreach|while)\b(?!\()', line)
        closes = re.findall(r'@end(if|unless|isset|empty|auth|guest|hasSection|push|slot|forelse|foreach|while)\b', line)
        for d in opens:
            stack.append((d, i))
        for d in closes:
            if stack and stack[-1][0] == d:
                stack.pop()
            else:
                add_sev(rel, i, "high", f"Orphaned @end{d}", f"Fix or remove this @end{d}.")
    for d, ln in stack:
        add_sev(rel, 1, "high", f"Unclosed Blade directive: @{d} (opened at line {ln})", f"Add @end{d} to close it.")

# 4. Image attributes
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f, 1):
            if '<img' in line.lower():
                if 'alt=' not in line.lower():
                    add_sev(rel, i, "medium", "Missing alt attribute on <img>", "Add alt=\"...\" for accessibility.")
                if 'loading=' not in line.lower():
                    add_sev(rel, i, "low", "Missing loading=\"lazy\" on <img>", "Add loading=\"lazy\".")
                if 'width=' not in line.lower() or 'height=' not in line.lower():
                    add_sev(rel, i, "low", "Missing width/height attributes on <img>", "Add explicit width and height.")

# 5. Button aria-label
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f, 1):
            if '<button' in line.lower() and 'aria-label=' not in line.lower():
                inner = re.sub(r'<[^>]+>', '', line)
                if not inner.strip() or inner.strip() == '<button':
                    add_sev(rel, i, "medium", "Button without text or aria-label", "Add aria-label or visible text content.")

# 6. Broken tags: <<< or >>>
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f, 1):
            if '<<<' in line or '>>>' in line:
                add_sev(rel, i, "high", "Broken HTML tags (<<< or >>>)", "Fix HTML tag nesting.")

# 7. Emoji glyphs
emoji_re = re.compile(r'[\U0001F300-\U0001FAFF\U00002600-\U000027BF]+', re.UNICODE)
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f, 1):
            found = emoji_re.findall(line)
            if found:
                for em in found:
                    add_sev(rel, i, "low", f"Raw emoji glyph: {repr(em)}", "Replace with proper SVG icon component.")

# 8. Inconsistent button classes
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    has_primary = 'button-primary' in content
    has_cta = 'btn-cta-primary' in content
    if has_primary and has_cta:
        add_sev(rel, 1, "medium", "Mixed button-primary and btn-cta-primary classes", "Normalize to single button class convention.")

# 9. Alpine.js issues
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f, 1):
            if 'x-data' in line and '=' not in line:
                add_sev(rel, i, "medium", "x-data without value", "Provide x-data=\"...\" expression.")

# 10. Save JSON output
print(json.dumps(issues, indent=2))
