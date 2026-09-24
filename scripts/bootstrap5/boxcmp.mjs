import fs from "fs";
const [a,b,lim]=process.argv.slice(2); const A=JSON.parse(fs.readFileSync(a)),B=JSON.parse(fs.readFileSync(b)); const mb=new Map(B.map(e=>[e.path,e]));
const IGN=new Set(["width","height","box-sizing"]); const groups=new Map(); let moved=0;
for(const e of A){const f=mb.get(e.path); if(!f) continue;
  const sd=Object.keys(e.st).filter(k=>!IGN.has(k)&&e.st[k]!==f.st[k]);
  if(!sd.length) continue;
  const sig=sd.map(k=>`${k}: ${e.st[k]} -> ${f.st[k]}`).join(" | ");
  const tag=e.path.split(">").pop().replace(/\[\d+\]/,"")+(String(e.cls).trim()?"."+String(e.cls).trim().split(/\s+/).join("."):"");
  if(!groups.has(sig)) groups.set(sig,{n:0,ex:new Set()}); const g=groups.get(sig); g.n++; if(g.ex.size<4) g.ex.add(tag+(e.text?` "${e.text}"`:""));}
[...groups.entries()].sort((x,y)=>y[1].n-x[1].n).slice(0,+(lim||40)).forEach(([s,g])=>console.log(`${String(g.n).padStart(4)}  ${s}\n        e.g. ${[...g.ex].join(", ")}`));
