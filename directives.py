import os, re, json

views_dir="/root/broca/resources/views"
blade_files=[]
for r,_,fs in os.walk(views_dir):
    for f in fs:
        if f.endswith(".blade.php"):
            blade_files.append(os.path.join(r,f))
blade_files.sort()

issues=[]

# Pair semantics
# if -> endif ; unless -> endunless ; isset/empty? isset->endif, empty is forelse separator
# foreach -> endforeach ; forelse -> empty + endforelse ; auth -> endauth ; guest -> endguest
# push -> endpush ; section -> stop/endsection ; component -> endcomponent ; switch -> endswitch
# hasSection -> endif (uses endif); can/role/choice -> endcan etc
# For simplicity treat:
close_map = {
    'if':['endif'], 'unless':['endunless'], 'isset':['endif'], 'empty_brace':['endif'],
    'forelse':['endforelse'], 'foreach':['endforeach'], 'auth':['endauth'], 'guest':['endguest'],
    'push':['endpush'], 'section':['stop','endsection'], 'component':['endcomponent'],
    'hasSection':['endif'], 'can':['endcan'], 'role':['endcan'], 'choice':['endcan'],
    'switch':['endswitch'], 'error':['enderror'], 'env':['endenv'], 'production':['endproduction'],
    'once':['endonce'], 'php':[], 'while':['endwhile'], 'for':['endfor'],
}

def pop_match(stack, name):
    # find matching opener on stack, remove if present
    for idx in range(len(stack)-1,-1,-1):
        if stack[idx][0]==name:
            stack.pop(idx)
            return True
    return False

for path in blade_files:
    rel=os.path.relpath(path,"/root/broca")
    with open(path,encoding="utf-8") as fh:
        lines=fh.readlines()
    stack=[]
    for i,line in enumerate(lines,1):
        # Opens
        # @if( or @if (  -> if
        for m in re.finditer(r'@(if|unless|isset|empty|foreach|forelse|auth|guest|push|section|component|hasSection|can|choice|role|switch|env|production|while)\b', line):
            name=m.group(1)
            # @empty inside forelse = separator, skip pushing unless actually @empty(...)
            if name=='empty' and not re.search(r'@empty\(', line) and any(st[0]=='forelse' for st in stack):
                # separator - skip (forelse handled)
                # but track that empty separator seen inside forelse not needed
                continue
            stack.append((name,i))
        # Closes
        for m in re.finditer(r'@(stop|endsection|endif|endunless|endforeach|endforelse|endauth|endguest|endpush|endcomponent|endcan|endswitch|endenv|endproduction|endwhile|endfor|endempty)\b', line):
            c=m.group(1)
            # map close to opener names
            opener_candidates = {
                'stop':['section'], 'endsection':['section'],
                'endif':['if','isset','hasSection'], 'endunless':['unless'],
                'endforeach':['foreach'], 'endforelse':['forelse'],
                'endauth':['auth'], 'endguest':['guest'], 'endpush':['push'],
                'endcomponent':['component'], 'endcan':['can','role','choice'],
                'endswitch':['switch'], 'endenv':['env'], 'endproduction':['production'],
                'endwhile':['while'], 'endfor':['for'], 'endempty':['empty'],
            }[c]
            matched=False
            for oc in opener_candidates:
                if pop_match(stack, oc):
                    matched=True
                    break
            if not matched:
                issues.append({"severity":"high","file":f"{rel}:{i}","issue":f"Orphaned @{c}","fix":"Remove or add matching opening directive"})
    for name,ln in stack:
        issues.append({"severity":"high","file":f"{rel}:1","issue":f"Unclosed @{name} opened at line {ln}","fix":"Add matching closing directive"})

for x in issues:
    print(json.dumps(x,ensure_ascii=False))
print(f"\nTOTAL: {len(issues)}")
