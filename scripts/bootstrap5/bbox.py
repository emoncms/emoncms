import json,sys
A={e["path"]:e for e in json.load(open(sys.argv[1]))}
seen={}
def px(v):
    try: return float(v.replace("px",""))
    except: return 0
for e in json.load(open(sys.argv[2])):
    m=A.get(e["path"])
    if not m: continue
    dw=round(e["rect"][2]-m["rect"][2],1); dh=round(e["rect"][3]-m["rect"][3],1)
    pad=sum(px(m["st"][k]) for k in ("padding-top","padding-bottom","padding-left","padding-right","border-top-width","border-bottom-width"))
    if (abs(dw)>0.5 or abs(dh)>0.5) and pad>0 and m["st"]["display"]!="inline" and m["st"]["box-sizing"]=="content-box":
        tag=e["path"].split(">")[-1].split("[")[0]; cls=".".join(str(m["cls"]).split())
        key=tag+"."+cls
        if key not in seen: seen[key]=(e["path"][14:][-60:],dw,dh,m["st"]["width"],m["st"]["height"])
for k,v in seen.items(): print(k[:45].ljust(45), v[1:], "|", v[0])
