import os
import re
import json

views_dir = "/root/broca/resources/views"
css_file = "/root/broca/resources/css/app.css"

with open(css_file, "r", encoding="utf-8") as f:
    css_content = f.read()
css_classes = set(re.findall(r'\.([a-zA-Z0-9_-]+)', css_content))

issues = []

def report(file_path, line_num, issue, fix, severity="high"):
    issues.append({
        "severity": severity,
        "file": f"{file_path}:{line_num}",
        "issue": issue,
        "fix": fix
    })

def scan_file(path):
    with open(path, "r", encoding="utf-8") as f:
        lines = f.readlines()
        
    rel_path = os.path.relpath(path, "/root/broca")
    
    in_form = False
    has_csrf = False
    
    auth_required_dirs = ["learner", "admin"]
    is_private = any(d in rel_path.split(os.sep) for d in auth_required_dirs)
    if is_private:
        content = "".join(lines)
        if not ("@auth" in content or "middleware" in path or "@extends('layouts.app')" in content): # rough check, might have controller auth
            pass

    if_stack = []

    for i, line in enumerate(lines):
        line_num = i + 1
        
        # 1. XSS {!! !!}
        if "{!!" in line and "!!}" in line:
            # allow some known safe variables if needed, but report all for manual review
            report(rel_path, line_num, "Unescaped output {!! !!}", "Use {{ }} for escaped output unless HTML is required and sanitized.", "critical")
            
        # 2. Missing CSRF
        if "<form" in line.lower() and "method=\"post\"" in line.lower():
            in_form = True
            has_csrf = False
        if in_form and ("@csrf" in line or "<input type=\"hidden\" name=\"_token\"" in line):
            has_csrf = True
        if in_form and "</form>" in line.lower():
            if not has_csrf:
                report(rel_path, line_num, "Form missing CSRF token", "Add @csrf inside the <form> tag.", "critical")
            in_form = False

        # 3. Broken HTML (basic checks)
        if "<<" in line or ">>" in line:
            report(rel_path, line_num, "Possible broken HTML tags (<< or >>)", "Fix HTML syntax.", "medium")

        # 4. Dead CSS classes (only custom classes, naive check)
        classes_found = re.findall(r'class="([^"]+)"', line)
        for cls_str in classes_found:
            classes = cls_str.split()
            # check if button classes are inconsistent (9)
            if "btn-cta-primary" in classes and "button-primary" in classes:
                report(rel_path, line_num, "Inconsistent button classes", "Use only one button primary class.", "medium")
            if "button-primary" in classes:
                # wait, rules say check for "Inconsistent button classes (button-primary vs btn-cta-primary)"
                pass

        # 5. Accessibility
        if "<img" in line.lower() and "alt=" not in line.lower():
            report(rel_path, line_num, "Missing alt attribute on image", "Add alt=\"...\" to <img>.", "medium")
        if "<button" in line.lower() and "aria-label=" not in line.lower() and ">" in line.lower():
            # naive, buttons might have text
            # if re.search(r'<button[^>]*>\s*</button>', line):
            pass

        # 7. Broken @if/@endif
        if "@if" in line and not "@if(" in line and not "@if (" in line:
             pass # Maybe other directive
        for token in re.findall(r'@(if|unless|isset|empty|auth|guest|hasSection|push|can)\b', line):
            if_stack.append(token)
        for token in re.findall(r'@end(if|unless|isset|empty|auth|guest|hasSection|push|can)\b', line):
            if if_stack:
                if_stack.pop()
            else:
                report(rel_path, line_num, f"Orphaned @end{token}", f"Remove or add matching @{token}.", "high")
                
        # 8. Dead emoji
        if re.search(r'[\U00010000-\U0010ffff]', line):
            report(rel_path, line_num, "Raw emoji found", "Replace with proper SVG icon.", "low")

        # 10. Missing loading="lazy"
        if "<img" in line.lower() and "loading=\"lazy\"" not in line.lower():
            report(rel_path, line_num, "Missing loading=\"lazy\" on image", "Add loading=\"lazy\" to <img>.", "low")

        # 11. Missing width/height on images
        if "<img" in line.lower() and ("width=" not in line.lower() or "height=" not in line.lower()):
            report(rel_path, line_num, "Missing width/height on image", "Add explicit width and height attributes.", "low")

        # 12. Broken Alpine.js
        if "x-data=" in line and "{" in line and "}" not in line:
            pass # multiline

    if if_stack:
        report(rel_path, 1, f"Unclosed directives: {if_stack}", "Close all opened Blade directives.", "high")

for root, _, files in os.walk(views_dir):
    for f in files:
        if f.endswith(".blade.php"):
            scan_file(os.path.join(root, f))

# Let's write a better grep for forms and csrf, loading lazy, components, etc.
