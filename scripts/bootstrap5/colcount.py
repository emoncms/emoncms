# colcount.py: hex literals and !important in hand written CSS and <style> blocks, working tree and HEAD.
# Same file set as cssbytes.sh. Use it for the goal metrics, as grep counts differ by pattern.
import os, re, subprocess, sys
E=os.path.realpath(os.path.join(os.path.dirname(__file__), '..', '..')); skip=re.compile(r'bootstrap\.min\.css|bootstrap2-icons\.css|svg-icons\.css|montserrat\.css')
HEX=re.compile(r'#[0-9a-fA-F]{3,8}\b'); IMP='!important'
def files(ext):
    out=[]
    for root,dirs,fs in os.walk(E, followlinks=True):
        dirs[:]=[d for d in dirs if d not in ('node_modules','vendor','.git')]
        for f in fs:
            if f.endswith(ext): out.append(os.path.join(root,f))
    return out
def head(p):
    d=os.path.dirname(os.path.realpath(p))
    try:
        top=subprocess.check_output(['git','-C',d,'rev-parse','--show-toplevel'],text=True,stderr=subprocess.DEVNULL).strip()
        rel=os.path.relpath(os.path.realpath(p),top)
        return subprocess.check_output(['git','-C',top,'show','HEAD:'+rel],text=True,stderr=subprocess.DEVNULL)
    except Exception: return ''
def style(t): return '\n'.join(re.findall(r'<style[^>]*>(.*?)</style>', t, re.S))
tot={'now':[0,0,0,0],'head':[0,0,0,0]}
for p in files('.css'):
    if skip.search(p): continue
    theme = p.endswith('bootstrap5-theme.css')
    for k,t in (('now',open(p).read()),('head',head(p))):
        tot[k][0]+=len(HEX.findall(t)); tot[k][1]+=t.count(IMP)
        if not theme: tot[k][2]+=len(HEX.findall(t))
for p in files('.php')+files('.html'):
    if re.search(r'/scripts/|/tools/',p): continue
    for k,t in (('now',open(p,errors='ignore').read()),('head',head(p))):
        s=style(t); tot[k][3]+=len(HEX.findall(s)); tot[k][1]+=s.count(IMP)
for k,v in tot.items(): print(k,'hex css',v[0],'hex outside theme',v[2],'hex style blocks',v[3],'!important',v[1])
