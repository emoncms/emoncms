# python3 tags.py file.php : check div/span/... balance in the view before <script>
import sys,re
from html.parser import HTMLParser
T=('div','section','table','tbody','thead','tr','td','th','span','button','a','b','p','nav','ul','li','label','select')
for f in sys.argv[1:]:
    s=open(f).read()
    s=re.sub(r'<\?php.*?\?>','',s,flags=re.S)
    s=re.sub(r'<script.*?</script>','',s,flags=re.S)
    s=re.sub(r'<style.*?</style>','',s,flags=re.S)
    st=[]; bad=[]
    class P(HTMLParser):
        def handle_starttag(self,t,a):
            if t in T: st.append((t,self.getpos()[0]))
        def handle_endtag(self,t):
            if t in T:
                if not st: bad.append(('extra',t,self.getpos()[0])); return
                o=st.pop()
                if o[0]!=t: bad.append((o,t,self.getpos()[0]))
    P().feed(s); print(f, "ok" if not st and not bad else ("open",st[:5],"bad",bad[:5]))
