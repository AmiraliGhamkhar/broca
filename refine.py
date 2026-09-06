import os, re, json

views_dir="/root/broca/resources/views"
blade_files=[]
for r,_,fs in os.walk(views_dir):
    for f in fs:
        if f.endswith(".blade.php"):
            blade_files.append(os.path.join(r,f))
blade_files.sort()

issues=[]

def add(s,f,iss,fix):
    issues.append({"severity":s,"file":f,"issue":iss,"fix":fix})

# ---- Proper Blade directive check (forelse/empty handled) ----
for path in blade_files:
    rel=os.path.relpath(path,"/root/broca")
    with open(path,encoding="utf-8") as fh:
        lines=fh.readlines()
    # Use stack for blocks: if/unless/auth/guest/isset etc use endif/endX, forelse uses empty+endforelse, foreach uses endforeach
    stack=[]
    for i,line in enumerate(lines,1):
        # Find directives - need to avoid matching inside strings, but simplest: find all @word
        # Forelse
        for m in re.finditer(r'@forelse\b', line):
            stack.append(("forelse",i))
        for m in re.finditer(r'@empty\b', line):
            # @empty inside forelse is separator, not standalone
            if stack and stack[-1][0]=="forelse":
                pass # separator, don't push
            else:
                stack.append(("empty",i))
        for m in re.finditer(r'@endforelse\b', line):
            # should close forelse
            found=False
            # find last forelse
            for idx in range(len(stack)-1,-1,-1):
                if stack[idx][0]=="forelse":
                    # remove it
                    stack.pop(idx)
                    found=True
                    break
            if not found:
                add("high",f"{rel}:{i}","Orphaned @endforelse","Remove or add matching @forelse")
        for m in re.finditer(r'@endempty\b', line):
            if stack and stack[-1][0]=="empty":
                stack.pop()
            else:
                # might be orphan or inside forelse handling odd
                pass

        # if/unless/isset
        for m in re.finditer(r'@if\b', line):
            stack.append(("if",i))
        for m in re.finditer(r'@unless\b', line):
            stack.append(("unless",i))
        for m in re.finditer(r'@isset\b', line):
            stack.append(("isset",i))
        # @endif can close if/unless/isset
        for m in re.finditer(r'@endif\b', line):
            # find last matching
            found=False
            for idx in range(len(stack)-1,-1,-1):
                if stack[idx][0] in ("if","unless","isset"):
                    stack.pop(idx)
                    found=True
                    break
            if not found:
                add("high",f"{rel}:{i}","Orphaned @endif","Remove or add matching @if/@unless/@isset")
        for m in re.finditer(r'@endunless\b', line):
            found=False
            for idx in range(len(stack)-1,-1,-1):
                if stack[idx][0]=="unless":
                    stack.pop(idx);found=True;break
            if not found:
                add("high",f"{rel}:{i}","Orphaned @endunless","Remove or add matching @unless")

        for m in re.finditer(r'@foreach\b', line):
            stack.append(("foreach",i))
        for m in re.finditer(r'@endforeach\b', line):
            found=False
            for idx in range(len(stack)-1,-1,-1):
                if stack[idx][0]=="foreach":
                    stack.pop(idx);found=True;break
            if not found:
                add("high",f"{rel}:{i}","Orphaned @endforeach","Remove or add matching @foreach")

        for m in re.finditer(r'@auth\b', line):
            stack.append(("auth",i))
        for m in re.finditer(r'@endauth\b', line):
            found=False
            for idx in range(len(stack)-1,-1,-1):
                if stack[idx][0]=="auth":
                    stack.pop(idx);found=True;break
            if not found:
                add("high",f"{rel}:{i}","Orphaned @endauth","Remove or add matching @auth")
        for m in re.finditer(r'@guest\b', line):
            stack.append(("guest",i))
        for m in re.finditer(r'@endguest\b', line):
            found=False
            for idx in range(len(stack)-1,-1,-1):
                if stack[idx][0]=="guest":
                    stack.pop(idx);found=True;break
            if not found:
                add("high",f"{rel}:{i}","Orphaned @endguest","Remove or add matching @guest")

        for m in re.finditer(r'@push\b', line):
            stack.append(("push",i))
        for m in re.finditer(r'@endpush\b', line):
            found=False
            for idx in range(len(stack)-1,-1,-1):
                if stack[idx][0]=="push":
                    stack.pop(idx);found=True;break
            if not found:
                add("high",f"{rel}:{i}","Orphaned @endpush","Remove or add matching @push")

        for m in re.finditer(r'@section\b', line):
            stack.append(("section",i))
        for m in re.finditer(r'@endsection\b', line):
            found=False
            for idx in range(len(stack)-1,-1,-1):
                if stack[idx][0]=="section":
                    stack.pop(idx);found=True;break
            if idx>=0 and not found:
                pass

    for typ,ln in stack:
        add("high",f"{rel}:1",f"Unclosed @{typ} opened at line {ln}","Add matching closing directive")

print(f"Blade directive issues: {len(issues)}")
for x in issues:
    print(json.dumps(x,ensure_ascii=False))

