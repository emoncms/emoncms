import { chromium } from "playwright"; import fs from "fs";
const [o,x,y,w,h,...files]=process.argv.slice(2); const br=await chromium.launch({executablePath:process.env.CHROME||"/usr/bin/google-chrome"}); const p=await br.newPage();
const png=await p.evaluate(async([fs_,x,y,w,h])=>{const L=s=>new Promise(r=>{const i=new Image();i.onload=()=>r(i);i.src="data:image/png;base64,"+s});
 const c=new OffscreenCanvas(w*fs_.length+10*(fs_.length-1),h),g=c.getContext("2d");g.fillStyle="#f0f";g.fillRect(0,0,c.width,h);
 for(let n=0;n<fs_.length;n++){const i=await L(fs_[n]);g.drawImage(i,x,y,w,h,n*(w+10),0,w,h)}
 const b=new Uint8Array(await (await c.convertToBlob()).arrayBuffer());let s="";for(const v of b)s+=String.fromCharCode(v);return btoa(s)},
 [files.map(f=>fs.readFileSync(f).toString("base64")),+x,+y,+w,+h]);
fs.writeFileSync(o,Buffer.from(png,"base64")); await br.close();
