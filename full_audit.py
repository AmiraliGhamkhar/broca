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

# 1. XSS: unescaped {!! !!} that isn't json_encode/nl2br safe patterns
print("=== 1. XSS (unescaped {!! !!}) ===")
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    for i, line in enumerate(lines, 1):
        # Report all {!! !!} except known safe ones
        if "{!!" in line and "!!}" in line:
            # Filter safe patterns
            content_stripped = line.strip().lstrip(' ')
            if any(p in content_stripped for p in [
                '{!! json_encode(', '{!! nl2br(e(', '{!! htmlspecialchars(', 
            ]):
                continue
            # Also skip empty {!! !!}
            if re.search(r'\{\!\s*\}\!', content_stripped):
                continue
            add_sev(rel, i, "critical", "Unescaped output using {!! !!} - potential XSS", "Replace {!! ... !!} with {{ ... }} unless the content is explicitly sanitized HTML.")

# 2. Broken Blade @if/@endif pairs
print("=== 7. BROKEN BLADE DIRECTIVES ===")
directive_stack = []
directive_pairs = {
    'if': 'endif', 'unless': 'endunless', 'isset': 'endif', 'empty': 'endif',
    'auth': 'endauth', 'guest': 'endguest', 'hasSection': 'endif',
    'push': 'endpush', 'slot': 'endslot', 'forelse': 'endforelse',
    'foreach': 'endforeach', 'while': 'endwhile',
    'component': 'endcomponent', 'error': 'enderror',
    'section': 'endsource', 'production': 'endproduction',
    'switch': 'endswitch', 'break': 'case_break',
    'case': 'endswitch', 'default': 'endswitch',
}
reverse_pairs = {'endif': 'if', 'endunless': 'unless', 'endauth': 'auth', 
                 'endguest': 'guest', 'endforelse': 'forelse', 'endforeach': 'foreach',
                 'endwhile': 'while', 'endpush': 'push', 'endslot': 'slot',
                 'endforelse': 'forelse', 'enderror': 'error', 'endproduction': 'production',
                 'endswitch': 'switch'}

for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    stack = []
    with open(path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    for i, line in enumerate(lines, 1):
        # Match opening directives
        open_dirs = re.findall(r'@(if|unless|isset|empty|auth|guest|hasSection|push|slot|forelse|foreach|while)\b', line)
        close_dirs = re.findall(r'@end(if|unless|isset|empty|auth|guest|hasSection|push|slot|forelse|foreach|while)\b', line)
        
        for d in open_dirs:
            stack.append(('open', d, i))
        for d in close_dirs:
            if stack and stack[-1][1] == d:
                stack.pop()
            else:
                add_sev(rel, i, "high", f"Orphaned @{d}, no matching @{'@'.join(d[:-3])}", f"Add matching opening directive or remove @{d}")
    
    for opener_type, opener_line, _ in stack:
        add_sev(rel, 1, "high", f"Unclosed @{opener_type} opened after scan ended", f"Add closing @end{opener_type}")

# 3. Dead emoji glyphs
print("=== 8. DEAD EMOJI GLYPHS ===")
emoji_pattern = re.compile('[\U0001F300-\U0001FAFF\U00002600-\U000027BF\U0000FE00-\U0000FE0F⚠⛽✈-➿\U0001F900-\U0001F9FF]+')
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f, 1):
            matches = emoji_pattern.findall(line)
            if matches:
                # Filter out non-emoji unicode like CJK, Arabic etc
                emojis_found = []
                for m in matches:
                    try:
                        for c in m:
                            cp = ord(c)
                            if (0x1F300 <= cp <= 0x1FAFF) or \
                               (0x2600 <= cp <= 0x27BF) or \
                               (0xFE00 <= cp <= 0xFE0F) or \
                               cp in [0x26A0, 0x26FD, 0x2708, 0x2764]:
                                emojis_found.append(m)
                                break
                    except:
                        pass
                if emojis_found:
                    for em in emojis_found:
                        add_sev(rel, i, "low", f"Raw emoji found: {repr(em)}", "Replace with proper SVG icon via <x-icon> component.")

# 4. Missing loading="lazy" on <img
print("=== 10/11. IMAGE ATTRIBUTES ===")
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f, 1):
            if '<img' in line.lower():
                has_alt = 'alt=' in line.lower()
                has_lazy = 'loading=' in line.lower()
                has_width = 'width=' in line.lower()
                has_height = 'height=' in line.lower()
                
                if not has_alt:
                    add_sev(rel, i, "medium", "Missing alt attribute on <img>", "Add alt=\"...\" describing the image content.")
                if not has_lazy:
                    add_sev(rel, i, "low", "Missing loading=\"lazy\" on <img>", "Add loading=\"lazy\" for performance.")
                if not has_width or not has_height:
                    missing = []
                    if not has_width: missing.append("width")
                    if not has_height: missing.append("height")
                    add_sev(rel, i, "low", f"Missing {', '.join(missing)} attributes on <img>", f"Add explicit {', '.join(missing)} attributes.")

# 5. Accessibility: buttons without aria-label when they have only icon/content
print("=== 5. ACCESSIBILITY ===")
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f, 1):
            btn = re.search(r'<button[^>]*>(.*?)</button>', line, re.DOTALL | re.IGNORECASE)
            if btn:
                inner = btn.group(1).strip()
                has_aria_label = 'aria-label' in line.lower()
                has_text = bool(re.sub(r'<[^>]+>', '', inner).strip())
                if not has_text and not has_aria_label:
                    add_sev(rel, i, "medium", "Button with no text content and no aria-label", "Add aria-label=\"...\" to describe button action.")

# 6. Dead CSS classes
print("=== 4. POTENTIAL DEAD CSS CLASSES ===")
with open("/root/broca/resources/css/app.css", 'r', encoding='utf-8') as f:
    css_content = f.read()
css_classes_in_css = set(re.findall(r'\.([a-zA-Z0-9_-]+)', css_content))
# Also check tailwind prefix pattern (these are utility classes defined elsewhere)

for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    used_custom_classes = set()
    with open(path, 'r', encoding='utf-8') as f:
        for line in f:
            classes = re.findall(r'class=["\']([^"\']+)', line)
            for cls_set in classes:
                for c in cls_set.split():
                    # Skip tailwind classes
                    if re.match(r'^[a-z]+-\w+', c) and len(c) < 20:
                        continue
                    used_custom_classes.add(c)
    
    dead = used_custom_classes - css_classes_in_css
    if dead:
        # Filter tailwind-like false positives
        real_dead = [c for c in dead if not re.match(r'^[a-z]+-\S', c) or len(c) < 5]
        if real_dead:
            print(f"  {rel}: {real_dead}")

# 7. Broken tags (actual unclosed tags, not multiline)
print("=== 3. BROKEN TAGS ===")
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f, 1):
            # Check for <<< or >>>
            if '<<<' in line or '>>>' in line:
                add_sev(rel, i, "high", "Broken HTML tags (<<< or >>>)", "Fix HTML tag nesting.")
            
            # Check for self-closing tags missing /
            if '<img' in line.lower() and '/>' not in line and '>' not in line.replace('/>', ''):
                pass  # already handled by unmatched > below
            
            # Actual broken > (tag started but never closed on same line)
            opens = len(re.findall(r'<[^/>][^>]*$', line.rstrip()))
            closes = len(re.findall(r'</[^>]+>\s*$', line.lstrip()))
            # Very rough heuristic
            if "<input" in line.lower() and ">" not in line:
                add_sev(rel, i, "medium", "Input tag not closed (missing >", "Add > at end of input element.")
            elif "<a " in line.lower() and ">" not in line:
                add_sev(rel, i, "medium", "Anchor tag not closed (missing >", "Add > to anchor element.")

# 8. Alpine.js
print("=== 12. ALPINE.JS DIRECTIVES ===")
alpine_directives = ['x-data', 'x-show', 'x-model', 'x-bind', 'x-on', 'x-if', 'x-for', 'x-transition', 'x-html']
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        for i, line in enumerate(f, 1):
            for alpine in alpine_directives:
                if alpine in line:
                    if alpine == 'x-data':
                        if '=' not in line:
                            add_sev(rel, i, "medium", f"x-data without value", "Provide expression: x-data=\"{}\"")
                    elif alpine == 'x-show':
                        if '===' not in line and '==' not in line:
                            pass  # could be variable
                    elif alpine == 'x-for':
                        if 'as' not in line.lower():
                            add_sev(rel, i, "medium", f"x-for missing 'as' keyword", "Use format: x-for=\"item in items\"")

# 9. Inconsistent button classes
print("=== 9. BUTTON CLASS CONSISTENCY ===")
button_primary_re = re.compile(r'button-primary', re.IGNORECASE)
btn_cta_re = re.compile(r'btn-cta-primary', re.IGNORECASE)
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
        if button_primary_re.search(content) or btn_cta_re.search(content):
            # Check both patterns in same file
            if button_primary_re.search(content) and btn_cta_re.search(content):
                add_sev(rel, 1, "medium", "Inconsistent button primary classes (both button-primary and btn-cta-primary)", "Normalize to single button class convention.")

# 10. Missing @auth on private routes
print("=== 6. MISSING AUTH CHECKS ===")
private_dirs = {"learner/", "admin/"}
for path in blade_files:
    rel = os.path.relpath(path, "/root/broca")
    if any(rel.startswith(d) for d in private_dirs):
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
        has_auth = '@auth' in content or '@auth(' in content
        has_guard = '@guard' in content
        if not has_auth and not has_guard:
            # Could be checked in controller/middleware
            add_sev(rel, 1, "medium", "No @auth guard in template (private route area)", "Verify controller enforces auth; consider @auth block in template too.")

# Print issues
print(f"\n\nTOTAL ISSUES FOUND: {len(issues)}")
for iss in issues:
    print(json.dumps(iss))
