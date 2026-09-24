# summ.sh <masterdir> <branchdir> <width> states...
M=$1; B=$2; W=$3; shift 3
for s in "$@"; do python3 - "$M/$s@$W.json" "$B/$s@$W.json" "$s" <<'PY'
import json,sys
A={e["path"]:e for e in json.load(open(sys.argv[1]))}; B=json.load(open(sys.argv[2]))
moved=0; maxd=0; tot=0
for e in B:
    a=A.get(e["path"])
    if not a: continue
    tot+=1; d=max(abs(x-y) for x,y in zip(a["rect"],e["rect"]))
    if d>1: moved+=1; maxd=max(maxd,d)
print(f"{sys.argv[3]:15} {moved:4}/{tot} moved >1px, max {maxd:6.1f}px")
PY
done
