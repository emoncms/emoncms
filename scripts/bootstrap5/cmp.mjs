import { chromium } from "playwright"; import fs from "fs";
const [a,b]=process.argv.slice(2); const b64=f=>fs.readFileSync(f).toString("base64");
const br=await chromium.launch({executablePath:process.env.CHROME||"/usr/bin/google-chrome"}); const p=await br.newPage();
for (const f of fs.readdirSync(a).filter(f=>f.endsWith(".png"))) {
  if(!fs.existsSync(`${b}/${f}`)){console.log(f,"missing");continue}
  const r=await p.evaluate(async([x,y])=>{const L=s=>new Promise(r=>{const i=new Image();i.onload=()=>r(i);i.src="data:image/png;base64,"+s});
    const [i,j]=await Promise.all([L(x),L(y)]); if(i.width!=j.width||i.height!=j.height) return `size ${i.width}x${i.height} vs ${j.width}x${j.height}`;
    const c=new OffscreenCanvas(i.width,i.height),g=c.getContext("2d");g.drawImage(i,0,0);const d1=g.getImageData(0,0,i.width,i.height).data;
    g.clearRect(0,0,i.width,i.height);g.drawImage(j,0,0);const d2=g.getImageData(0,0,i.width,i.height).data;
    let n=0;for(let k=0;k<d1.length;k+=4) if(d1[k]!=d2[k]||d1[k+1]!=d2[k+1]||d1[k+2]!=d2[k+2]) n++; return n+" px differ";},[b64(`${a}/${f}`),b64(`${b}/${f}`)]);
  console.log(f.padEnd(28),r);
}
await br.close();
