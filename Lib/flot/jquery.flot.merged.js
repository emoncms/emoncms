(function(b){b.color={};b.color.make=function(f,e,c,d){var h={};h.r=f||0;h.g=e||0;h.b=c||0;h.a=d!=null?d:1;h.add=function(k,j){for(var g=0;g<k.length;++g){h[k.charAt(g)]+=j}return h.normalize()};h.scale=function(k,j){for(var g=0;g<k.length;++g){h[k.charAt(g)]*=j}return h.normalize()};h.toString=function(){if(h.a>=1){return"rgb("+[h.r,h.g,h.b].join(",")+")"}else{return"rgba("+[h.r,h.g,h.b,h.a].join(",")+")"}};h.normalize=function(){function g(j,k,i){return k<j?j:k>i?i:k}h.r=g(0,parseInt(h.r),255);h.g=g(0,parseInt(h.g),255);h.b=g(0,parseInt(h.b),255);h.a=g(0,h.a,1);return h};h.clone=function(){return b.color.make(h.r,h.b,h.g,h.a)};return h.normalize()};b.color.extract=function(e,d){var f;do{f=e.css(d).toLowerCase();if(f!=""&&f!="transparent"){break}e=e.parent()}while(e.length&&!b.nodeName(e.get(0),"body"));if(f=="rgba(0, 0, 0, 0)"){f="transparent"}return b.color.parse(f)};b.color.parse=function(f){var e,c=b.color.make;if(e=/rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(f)){return c(parseInt(e[1],10),parseInt(e[2],10),parseInt(e[3],10))}if(e=/rgba\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]+(?:\.[0-9]+)?)\s*\)/.exec(f)){return c(parseInt(e[1],10),parseInt(e[2],10),parseInt(e[3],10),parseFloat(e[4]))}if(e=/rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(f)){return c(parseFloat(e[1])*2.55,parseFloat(e[2])*2.55,parseFloat(e[3])*2.55)}if(e=/rgba\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\s*\)/.exec(f)){return c(parseFloat(e[1])*2.55,parseFloat(e[2])*2.55,parseFloat(e[3])*2.55,parseFloat(e[4]))}if(e=/#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(f)){return c(parseInt(e[1],16),parseInt(e[2],16),parseInt(e[3],16))}if(e=/#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(f)){return c(parseInt(e[1]+e[1],16),parseInt(e[2]+e[2],16),parseInt(e[3]+e[3],16))}var d=b.trim(f).toLowerCase();if(d=="transparent"){return c(255,255,255,0)}else{e=a[d]||[0,0,0];return c(e[0],e[1],e[2])}};var a={aqua:[0,255,255],azure:[240,255,255],beige:[245,245,220],black:[0,0,0],blue:[0,0,255],brown:[165,42,42],cyan:[0,255,255],darkblue:[0,0,139],darkcyan:[0,139,139],darkgrey:[169,169,169],darkgreen:[0,100,0],darkkhaki:[189,183,107],darkmagenta:[139,0,139],darkolivegreen:[85,107,47],darkorange:[255,140,0],darkorchid:[153,50,204],darkred:[139,0,0],darksalmon:[233,150,122],darkviolet:[148,0,211],fuchsia:[255,0,255],gold:[255,215,0],green:[0,128,0],indigo:[75,0,130],khaki:[240,230,140],lightblue:[173,216,230],lightcyan:[224,255,255],lightgreen:[144,238,144],lightgrey:[211,211,211],lightpink:[255,182,193],lightyellow:[255,255,224],lime:[0,255,0],magenta:[255,0,255],maroon:[128,0,0],navy:[0,0,128],olive:[128,128,0],orange:[255,165,0],pink:[255,192,203],purple:[128,0,128],violet:[128,0,128],red:[255,0,0],silver:[192,192,192],white:[255,255,255],yellow:[255,255,0]}})(jQuery);(function(e){var d=Object.prototype.hasOwnProperty;if(!e.fn.detach){e.fn.detach=function(){return this.each(function(){if(this.parentNode){this.parentNode.removeChild(this)}})}}function a(h,g){var j=g.children("."+h)[0];if(j==null){j=document.createElement("canvas");j.className=h;e(j).css({direction:"ltr",position:"absolute",left:0,top:0}).appendTo(g);if(!j.getContext){if(window.G_vmlCanvasManager){j=window.G_vmlCanvasManager.initElement(j)}else{throw new Error("Canvas is not available. If you're using IE with a fall-back such as Excanvas, then there's either a mistake in your conditional include, or the page has no DOCTYPE and is rendering in Quirks Mode.")}}}this.element=j;var i=this.context=j.getContext("2d");var f=window.devicePixelRatio||1,k=i.webkitBackingStorePixelRatio||i.mozBackingStorePixelRatio||i.msBackingStorePixelRatio||i.oBackingStorePixelRatio||i.backingStorePixelRatio||1;this.pixelRatio=f/k;this.resize(g.width(),g.height());this.textContainer=null;this.text={};this._textCache={}}a.prototype.resize=function(i,f){if(i<=0||f<=0){throw new Error("Invalid dimensions for plot, width = "+i+", height = "+f)}var h=this.element,g=this.context,j=this.pixelRatio;if(this.width!=i){h.width=i*j;h.style.width=i+"px";this.width=i}if(this.height!=f){h.height=f*j;h.style.height=f+"px";this.height=f}g.restore();g.save();g.scale(j,j)};a.prototype.clear=function(){this.context.clearRect(0,0,this.width,this.height)};a.prototype.render=function(){var f=this._textCache;for(var o in f){if(d.call(f,o)){var n=this.getTextLayer(o),g=f[o];n.hide();for(var m in g){if(d.call(g,m)){var h=g[m];for(var p in h){if(d.call(h,p)){var k=h[p].positions;for(var j=0,l;l=k[j];j++){if(l.active){if(!l.rendered){n.append(l.element);l.rendered=true}}else{k.splice(j--,1);if(l.rendered){l.element.detach()}}}if(k.length==0){delete h[p]}}}}}n.show()}}};a.prototype.getTextLayer=function(g){var f=this.text[g];if(f==null){if(this.textContainer==null){this.textContainer=e("<div class='flot-text'></div>").css({position:"absolute",top:0,left:0,bottom:0,right:0,"font-size":"smaller",color:"#545454"}).insertAfter(this.element)}f=this.text[g]=e("<div></div>").addClass(g).css({position:"absolute",top:0,left:0,bottom:0,right:0}).appendTo(this.textContainer)}return f};a.prototype.getTextInfo=function(m,o,j,k,g){var n,f,i,h;o=""+o;if(typeof j==="object"){n=j.style+" "+j.variant+" "+j.weight+" "+j.size+"px/"+j.lineHeight+"px "+j.family}else{n=j}f=this._textCache[m];if(f==null){f=this._textCache[m]={}}i=f[n];if(i==null){i=f[n]={}}h=i[o];if(h==null){var l=e("<div></div>").html(o).css({position:"absolute","max-width":g,top:-9999}).appendTo(this.getTextLayer(m));if(typeof j==="object"){l.css({font:n,color:j.color})}else{if(typeof j==="string"){l.addClass(j)}}h=i[o]={width:l.outerWidth(true),height:l.outerHeight(true),element:l,positions:[]};l.detach()}return h};a.prototype.addText=function(o,r,p,s,h,j,f,n,q){var g=this.getTextInfo(o,s,h,j,f),l=g.positions;if(n=="center"){r-=g.width/2}else{if(n=="right"){r-=g.width}}if(q=="middle"){p-=g.height/2}else{if(q=="bottom"){p-=g.height}}for(var k=0,m;m=l[k];k++){if(m.x==r&&m.y==p){m.active=true;return}}m={active:true,rendered:false,element:l.length?g.element.clone():g.element,x:r,y:p};l.push(m);m.element.css({top:Math.round(p),left:Math.round(r),"text-align":n})};a.prototype.removeText=function(o,q,p,s,h,j){if(s==null){var f=this._textCache[o];if(f!=null){for(var n in f){if(d.call(f,n)){var g=f[n];for(var r in g){if(d.call(g,r)){var l=g[r].positions;for(var k=0,m;m=l[k];k++){m.active=false}}}}}}}else{var l=this.getTextInfo(o,s,h,j).positions;for(var k=0,m;m=l[k];k++){if(m.x==q&&m.y==p){m.active=false}}}};function c(Q,A,C,g){var t=[],L={colors:["#edc240","#afd8f8","#cb4b4b","#4da74d","#9440ed"],legend:{show:true,noColumns:1,labelFormatter:null,labelBoxBorderColor:"#ccc",container:null,position:"ne",margin:5,backgroundColor:null,backgroundOpacity:0.85,sorted:null},xaxis:{show:null,position:"bottom",mode:null,font:null,color:null,tickColor:null,transform:null,inverseTransform:null,min:null,max:null,autoscaleMargin:null,ticks:null,tickFormatter:null,labelWidth:null,labelHeight:null,reserveSpace:null,tickLength:null,alignTicksWithAxis:null,tickDecimals:null,tickSize:null,minTickSize:null},yaxis:{autoscaleMargin:0.02,position:"left"},xaxes:[],yaxes:[],series:{points:{show:false,radius:3,lineWidth:2,fill:true,fillColor:"#ffffff",symbol:"circle"},lines:{lineWidth:2,fill:false,fillColor:null,steps:false},bars:{show:false,lineWidth:2,barWidth:1,fill:true,fillColor:null,align:"left",horizontal:false,zero:true},shadowSize:3,highlightColor:null},grid:{show:true,aboveData:false,color:"#545454",backgroundColor:null,borderColor:null,tickColor:null,margin:0,labelMargin:5,axisMargin:8,borderWidth:2,minBorderMargin:null,markings:null,markingsColor:"#f4f4f4",markingsLineWidth:2,clickable:false,hoverable:false,autoHighlight:true,mouseActiveRadius:10},interaction:{redrawOverlayInterval:1000/60},hooks:{}},ac=null,al=null,am=null,D=null,aw=null,ao=[],W=[],J={left:0,right:0,top:0,bottom:0},k=0,ad=0,p={processOptions:[],processRawData:[],processDatapoints:[],processOffset:[],drawBackground:[],drawSeries:[],draw:[],bindEvents:[],drawOverlay:[],legendInserted:[],shutdown:[]},h=this;h.setData=K;h.setupGrid=O;h.draw=au;h.getPlaceholder=function(){return Q};h.getCanvas=function(){return ac.element};h.getPlotOffset=function(){return J};h.width=function(){return k};h.height=function(){return ad};h.offset=function(){var ay=am.offset();ay.left+=J.left;ay.top+=J.top;return ay};h.getData=function(){return t};h.getAxes=function(){var az={},ay;e.each(ao.concat(W),function(aA,aB){if(aB){az[aB.direction+(aB.n!=1?aB.n:"")+"axis"]=aB}});return az};h.getXAxes=function(){return ao};h.getYAxes=function(){return W};h.c2p=Y;h.p2c=R;h.getOptions=function(){return L};h.highlight=an;h.unhighlight=ah;h.triggerRedrawOverlay=X;h.pointOffset=function(ay){return{left:parseInt(ao[x(ay,"x")-1].p2c(+ay.x)+J.left,10),top:parseInt(W[x(ay,"y")-1].p2c(+ay.y)+J.top,10)}};h.shutdown=o;h.destroy=function(){o();Q.removeData("plot").empty();t=[];L=null;ac=null;al=null;am=null;D=null;aw=null;ao=[];W=[];p=null;ag=[];h=null};h.resize=function(){var az=Q.width(),ay=Q.height();ac.resize(az,ay);al.resize(az,ay)};h.hooks=p;H(h);aa(C);ax();K(A);O();au();ar();function F(aA,ay){ay=[h].concat(ay);for(var az=0;az<aA.length;++az){aA[az].apply(this,ay)}}function H(){var az={Canvas:a};for(var ay=0;ay<g.length;++ay){var aA=g[ay];aA.init(h,az);if(aA.options){e.extend(true,L,aA.options)}}}function aa(aA){e.extend(true,L,aA);if(aA&&aA.colors){L.colors=aA.colors}if(L.xaxis.color==null){L.xaxis.color=e.color.parse(L.grid.color).scale("a",0.22).toString()}if(L.yaxis.color==null){L.yaxis.color=e.color.parse(L.grid.color).scale("a",0.22).toString()}if(L.xaxis.tickColor==null){L.xaxis.tickColor=L.grid.tickColor||L.xaxis.color}if(L.yaxis.tickColor==null){L.yaxis.tickColor=L.grid.tickColor||L.yaxis.color}if(L.grid.borderColor==null){L.grid.borderColor=L.grid.color}if(L.grid.tickColor==null){L.grid.tickColor=e.color.parse(L.grid.color).scale("a",0.22).toString()}var ay,aF,aD,aC=Q.css("font-size"),aB=aC?+aC.replace("px",""):13,az={style:Q.css("font-style"),size:Math.round(0.8*aB),variant:Q.css("font-variant"),weight:Q.css("font-weight"),family:Q.css("font-family")};aD=L.xaxes.length||1;for(ay=0;ay<aD;++ay){aF=L.xaxes[ay];if(aF&&!aF.tickColor){aF.tickColor=aF.color}aF=e.extend(true,{},L.xaxis,aF);L.xaxes[ay]=aF;if(aF.font){aF.font=e.extend({},az,aF.font);if(!aF.font.color){aF.font.color=aF.color}if(!aF.font.lineHeight){aF.font.lineHeight=Math.round(aF.font.size*1.15)}}}aD=L.yaxes.length||1;for(ay=0;ay<aD;++ay){aF=L.yaxes[ay];if(aF&&!aF.tickColor){aF.tickColor=aF.color}aF=e.extend(true,{},L.yaxis,aF);L.yaxes[ay]=aF;if(aF.font){aF.font=e.extend({},az,aF.font);if(!aF.font.color){aF.font.color=aF.color}if(!aF.font.lineHeight){aF.font.lineHeight=Math.round(aF.font.size*1.15)}}}if(L.xaxis.noTicks&&L.xaxis.ticks==null){L.xaxis.ticks=L.xaxis.noTicks}if(L.yaxis.noTicks&&L.yaxis.ticks==null){L.yaxis.ticks=L.yaxis.noTicks}if(L.x2axis){L.xaxes[1]=e.extend(true,{},L.xaxis,L.x2axis);L.xaxes[1].position="top";if(L.x2axis.min==null){L.xaxes[1].min=null}if(L.x2axis.max==null){L.xaxes[1].max=null}}if(L.y2axis){L.yaxes[1]=e.extend(true,{},L.yaxis,L.y2axis);L.yaxes[1].position="right";if(L.y2axis.min==null){L.yaxes[1].min=null}if(L.y2axis.max==null){L.yaxes[1].max=null}}if(L.grid.coloredAreas){L.grid.markings=L.grid.coloredAreas}if(L.grid.coloredAreasColor){L.grid.markingsColor=L.grid.coloredAreasColor}if(L.lines){e.extend(true,L.series.lines,L.lines)}if(L.points){e.extend(true,L.series.points,L.points)}if(L.bars){e.extend(true,L.series.bars,L.bars)}if(L.shadowSize!=null){L.series.shadowSize=L.shadowSize}if(L.highlightColor!=null){L.series.highlightColor=L.highlightColor}for(ay=0;ay<L.xaxes.length;++ay){M(ao,ay+1).options=L.xaxes[ay]}for(ay=0;ay<L.yaxes.length;++ay){M(W,ay+1).options=L.yaxes[ay]}for(var aE in p){if(L.hooks[aE]&&L.hooks[aE].length){p[aE]=p[aE].concat(L.hooks[aE])}}F(p.processOptions,[L])}function K(ay){t=q(ay);y();S()}function q(aB){var az=[];for(var ay=0;ay<aB.length;++ay){var aA=e.extend(true,{},L.series);if(aB[ay].data!=null){aA.data=aB[ay].data;delete aB[ay].data;e.extend(true,aA,aB[ay]);aB[ay].data=aA.data}else{aA.data=aB[ay]}az.push(aA)}return az}function x(az,aA){var ay=az[aA+"axis"];if(typeof ay=="object"){ay=ay.n}if(typeof ay!="number"){ay=1}return ay}function j(){return e.grep(ao.concat(W),function(ay){return ay})}function Y(aB){var az={},ay,aA;for(ay=0;ay<ao.length;++ay){aA=ao[ay];if(aA&&aA.used){az["x"+aA.n]=aA.c2p(aB.left)}}for(ay=0;ay<W.length;++ay){aA=W[ay];if(aA&&aA.used){az["y"+aA.n]=aA.c2p(aB.top)}}if(az.x1!==undefined){az.x=az.x1}if(az.y1!==undefined){az.y=az.y1}return az}function R(aC){var aA={},az,aB,ay;for(az=0;az<ao.length;++az){aB=ao[az];if(aB&&aB.used){ay="x"+aB.n;if(aC[ay]==null&&aB.n==1){ay="x"}if(aC[ay]!=null){aA.left=aB.p2c(aC[ay]);break}}}for(az=0;az<W.length;++az){aB=W[az];if(aB&&aB.used){ay="y"+aB.n;if(aC[ay]==null&&aB.n==1){ay="y"}if(aC[ay]!=null){aA.top=aB.p2c(aC[ay]);break}}}return aA}function M(az,ay){if(!az[ay-1]){az[ay-1]={n:ay,direction:az==ao?"x":"y",options:e.extend(true,{},az==ao?L.xaxis:L.yaxis)}}return az[ay-1]}function y(){var aJ=t.length,aA=-1,aB;for(aB=0;aB<t.length;++aB){var aG=t[aB].color;if(aG!=null){aJ--;if(typeof aG=="number"&&aG>aA){aA=aG}}}if(aJ<=aA){aJ=aA+1}var aF,ay=[],aE=L.colors,aD=aE.length,az=0;for(aB=0;aB<aJ;aB++){aF=e.color.parse(aE[aB%aD]||"#666");if(aB%aD==0&&aB){if(az>=0){if(az<0.5){az=-az-0.2}else{az=0}}else{az=-az}}ay[aB]=aF.scale("rgb",1+az)}var aC=0,aK;for(aB=0;aB<t.length;++aB){aK=t[aB];if(aK.color==null){aK.color=ay[aC].toString();++aC}else{if(typeof aK.color=="number"){aK.color=ay[aK.color].toString()}}if(aK.lines.show==null){var aI,aH=true;for(aI in aK){if(aK[aI]&&aK[aI].show){aH=false;break}}if(aH){aK.lines.show=true}}if(aK.lines.zero==null){aK.lines.zero=!!aK.lines.fill}aK.xaxis=M(ao,x(aK,"x"));aK.yaxis=M(W,x(aK,"y"))}}function S(){var aM=Number.POSITIVE_INFINITY,aG=Number.NEGATIVE_INFINITY,ay=Number.MAX_VALUE,aT,aR,aQ,aL,aA,aH,aS,aN,aF,aE,az,aZ,aW,aJ,aY,aV;function aC(a2,a1,a0){if(a1<a2.datamin&&a1!=-ay){a2.datamin=a1}if(a0>a2.datamax&&a0!=ay){a2.datamax=a0}}e.each(j(),function(a0,a1){a1.datamin=aM;a1.datamax=aG;a1.used=false});for(aT=0;aT<t.length;++aT){aH=t[aT];aH.datapoints={points:[]};F(p.processRawData,[aH,aH.data,aH.datapoints])}for(aT=0;aT<t.length;++aT){aH=t[aT];aY=aH.data;aV=aH.datapoints.format;if(!aV){aV=[];aV.push({x:true,number:true,required:true});aV.push({y:true,number:true,required:true});if(aH.bars.show||(aH.lines.show&&aH.lines.fill)){var aO=!!((aH.bars.show&&aH.bars.zero)||(aH.lines.show&&aH.lines.zero));aV.push({y:true,number:true,required:false,defaultValue:0,autoscale:aO});if(aH.bars.horizontal){delete aV[aV.length-1].y;aV[aV.length-1].x=true}}aH.datapoints.format=aV}if(aH.datapoints.pointsize!=null){continue}aH.datapoints.pointsize=aV.length;aN=aH.datapoints.pointsize;aS=aH.datapoints.points;var aD=aH.lines.show&&aH.lines.steps;aH.xaxis.used=aH.yaxis.used=true;for(aR=aQ=0;aR<aY.length;++aR,aQ+=aN){aJ=aY[aR];var aB=aJ==null;if(!aB){for(aL=0;aL<aN;++aL){aZ=aJ[aL];aW=aV[aL];if(aW){if(aW.number&&aZ!=null){aZ=+aZ;if(isNaN(aZ)){aZ=null}else{if(aZ==Infinity){aZ=ay}else{if(aZ==-Infinity){aZ=-ay}}}}if(aZ==null){if(aW.required){aB=true}if(aW.defaultValue!=null){aZ=aW.defaultValue}}}aS[aQ+aL]=aZ}}if(aB){for(aL=0;aL<aN;++aL){aZ=aS[aQ+aL];if(aZ!=null){aW=aV[aL];if(aW.autoscale!==false){if(aW.x){aC(aH.xaxis,aZ,aZ)}if(aW.y){aC(aH.yaxis,aZ,aZ)}}}aS[aQ+aL]=null}}else{if(aD&&aQ>0&&aS[aQ-aN]!=null&&aS[aQ-aN]!=aS[aQ]&&aS[aQ-aN+1]!=aS[aQ+1]){for(aL=0;aL<aN;++aL){aS[aQ+aN+aL]=aS[aQ+aL]}aS[aQ+1]=aS[aQ-aN+1];aQ+=aN}}}}for(aT=0;aT<t.length;++aT){aH=t[aT];F(p.processDatapoints,[aH,aH.datapoints])}for(aT=0;aT<t.length;++aT){aH=t[aT];aS=aH.datapoints.points;aN=aH.datapoints.pointsize;aV=aH.datapoints.format;var aI=aM,aP=aM,aK=aG,aU=aG;for(aR=0;aR<aS.length;aR+=aN){if(aS[aR]==null){continue}for(aL=0;aL<aN;++aL){aZ=aS[aR+aL];aW=aV[aL];if(!aW||aW.autoscale===false||aZ==ay||aZ==-ay){continue}if(aW.x){if(aZ<aI){aI=aZ}if(aZ>aK){aK=aZ}}if(aW.y){if(aZ<aP){aP=aZ}if(aZ>aU){aU=aZ}}}}if(aH.bars.show){var aX;switch(aH.bars.align){case"left":aX=0;break;case"right":aX=-aH.bars.barWidth;break;default:aX=-aH.bars.barWidth/2}if(aH.bars.horizontal){aP+=aX;aU+=aX+aH.bars.barWidth}else{aI+=aX;aK+=aX+aH.bars.barWidth}}aC(aH.xaxis,aI,aK);aC(aH.yaxis,aP,aU)}e.each(j(),function(a0,a1){if(a1.datamin==aM){a1.datamin=null}if(a1.datamax==aG){a1.datamax=null}})}function ax(){Q.css("padding",0).children().filter(function(){return !e(this).hasClass("flot-overlay")&&!e(this).hasClass("flot-base")}).remove();if(Q.css("position")=="static"){Q.css("position","relative")}ac=new a("flot-base",Q);al=new a("flot-overlay",Q);D=ac.context;aw=al.context;am=e(al.element).unbind();var ay=Q.data("plot");if(ay){ay.shutdown();al.clear()}Q.data("plot",h)}function ar(){if(L.grid.hoverable){am.mousemove(f);am.bind("mouseleave",P)}if(L.grid.clickable){am.click(I)}F(p.bindEvents,[am])}function o(){if(l){clearTimeout(l)}am.unbind("mousemove",f);am.unbind("mouseleave",P);am.unbind("click",I);F(p.shutdown,[am])}function n(aD){function az(aE){return aE}var aC,ay,aA=aD.options.transform||az,aB=aD.options.inverseTransform;if(aD.direction=="x"){aC=aD.scale=k/Math.abs(aA(aD.max)-aA(aD.min));ay=Math.min(aA(aD.max),aA(aD.min))}else{aC=aD.scale=ad/Math.abs(aA(aD.max)-aA(aD.min));aC=-aC;ay=Math.max(aA(aD.max),aA(aD.min))}if(aA==az){aD.p2c=function(aE){return(aE-ay)*aC}}else{aD.p2c=function(aE){return(aA(aE)-ay)*aC}}if(!aB){aD.c2p=function(aE){return ay+aE/aC}}else{aD.c2p=function(aE){return aB(ay+aE/aC)}}}function Z(aB){var ay=aB.options,aH=aB.ticks||[],aG=ay.labelWidth||0,aC=ay.labelHeight||0,aI=aG||(aB.direction=="x"?Math.floor(ac.width/(aH.length||1)):null),aE=aB.direction+"Axis "+aB.direction+aB.n+"Axis",aF="flot-"+aB.direction+"-axis flot-"+aB.direction+aB.n+"-axis "+aE,aA=ay.font||"flot-tick-label tickLabel";for(var aD=0;aD<aH.length;++aD){var aJ=aH[aD];if(!aJ.label){continue}var az=ac.getTextInfo(aF,aJ.label,aA,null,aI);aG=Math.max(aG,az.width);aC=Math.max(aC,az.height)}aB.labelWidth=ay.labelWidth||aG;aB.labelHeight=ay.labelHeight||aC}function E(aA){var az=aA.labelWidth,aH=aA.labelHeight,aF=aA.options.position,ay=aA.direction==="x",aD=aA.options.tickLength,aE=L.grid.axisMargin,aG=L.grid.labelMargin,aJ=true,aC=true,aB=true,aI=false;e.each(ay?ao:W,function(aL,aK){if(aK&&(aK.show||aK.reserveSpace)){if(aK===aA){aI=true}else{if(aK.options.position===aF){if(aI){aC=false}else{aJ=false}}}if(!aI){aB=false}}});if(aC){aE=0}if(aD==null){aD=aB?"full":5}if(!isNaN(+aD)){aG+=+aD}if(ay){aH+=aG;if(aF=="bottom"){J.bottom+=aH+aE;aA.box={top:ac.height-J.bottom,height:aH}}else{aA.box={top:J.top+aE,height:aH};J.top+=aH+aE}}else{az+=aG;if(aF=="left"){aA.box={left:J.left+aE,width:az};J.left+=az+aE}else{J.right+=az+aE;aA.box={left:ac.width-J.right,width:az}}}aA.position=aF;aA.tickLength=aD;aA.box.padding=aG;aA.innermost=aJ}function ab(ay){if(ay.direction=="x"){ay.box.left=J.left-ay.labelWidth/2;ay.box.width=ac.width-J.left-J.right+ay.labelWidth}else{ay.box.top=J.top-ay.labelHeight/2;ay.box.height=ac.height-J.bottom-J.top+ay.labelHeight}}function B(){var aA=L.grid.minBorderMargin,az,ay;if(aA==null){aA=0;for(ay=0;ay<t.length;++ay){aA=Math.max(aA,2*(t[ay].points.radius+t[ay].points.lineWidth/2))}}var aB={left:aA,right:aA,top:aA,bottom:aA};e.each(j(),function(aC,aD){if(aD.reserveSpace&&aD.ticks&&aD.ticks.length){if(aD.direction==="x"){aB.left=Math.max(aB.left,aD.labelWidth/2);aB.right=Math.max(aB.right,aD.labelWidth/2)}else{aB.bottom=Math.max(aB.bottom,aD.labelHeight/2);aB.top=Math.max(aB.top,aD.labelHeight/2)}}});J.left=Math.ceil(Math.max(aB.left,J.left));J.right=Math.ceil(Math.max(aB.right,J.right));J.top=Math.ceil(Math.max(aB.top,J.top));J.bottom=Math.ceil(Math.max(aB.bottom,J.bottom))}function O(){var aA,aC=j(),aD=L.grid.show;for(var az in J){var aB=L.grid.margin||0;J[az]=typeof aB=="number"?aB:aB[az]||0}F(p.processOffset,[J]);for(var az in J){if(typeof(L.grid.borderWidth)=="object"){J[az]+=aD?L.grid.borderWidth[az]:0}else{J[az]+=aD?L.grid.borderWidth:0}}e.each(aC,function(aF,aG){var aE=aG.options;aG.show=aE.show==null?aG.used:aE.show;aG.reserveSpace=aE.reserveSpace==null?aG.show:aE.reserveSpace;m(aG)});if(aD){var ay=e.grep(aC,function(aE){return aE.show||aE.reserveSpace});e.each(ay,function(aE,aF){aq(aF);V(aF);u(aF,aF.ticks);Z(aF)});for(aA=ay.length-1;aA>=0;--aA){E(ay[aA])}B();e.each(ay,function(aE,aF){ab(aF)})}k=ac.width-J.left-J.right;ad=ac.height-J.bottom-J.top;e.each(aC,function(aE,aF){n(aF)});if(aD){at()}av()}function m(aB){var aC=aB.options,aA=+(aC.min!=null?aC.min:aB.datamin),ay=+(aC.max!=null?aC.max:aB.datamax),aE=ay-aA;if(aE==0){var az=ay==0?1:0.01;if(aC.min==null){aA-=az}if(aC.max==null||aC.min!=null){ay+=az}}else{var aD=aC.autoscaleMargin;if(aD!=null){if(aC.min==null){aA-=aE*aD;if(aA<0&&aB.datamin!=null&&aB.datamin>=0){aA=0}}if(aC.max==null){ay+=aE*aD;if(ay>0&&aB.datamax!=null&&aB.datamax<=0){ay=0}}}}aB.min=aA;aB.max=ay}function aq(aD){var az=aD.options;var aC;if(typeof az.ticks=="number"&&az.ticks>0){aC=az.ticks}else{aC=0.3*Math.sqrt(aD.direction=="x"?ac.width:ac.height)}var aI=(aD.max-aD.min)/aC,aE=-Math.floor(Math.log(aI)/Math.LN10),aB=az.tickDecimals;if(aB!=null&&aE>aB){aE=aB}var ay=Math.pow(10,-aE),aA=aI/ay,aK;if(aA<1.5){aK=1}else{if(aA<3){aK=2;if(aA>2.25&&(aB==null||aE+1<=aB)){aK=2.5;++aE}}else{if(aA<7.5){aK=5}else{aK=10}}}aK*=ay;if(az.minTickSize!=null&&aK<az.minTickSize){aK=az.minTickSize}aD.delta=aI;aD.tickDecimals=Math.max(0,aB!=null?aB:aE);aD.tickSize=az.tickSize||aK;if(az.mode=="time"&&!aD.tickGenerator){throw new Error("Time mode requires the flot.time plugin.")}if(!aD.tickGenerator){aD.tickGenerator=function(aN){var aP=[],aQ=b(aN.min,aN.tickSize),aM=0,aL=Number.NaN,aO;do{aO=aL;aL=aQ+aM*aN.tickSize;aP.push(aL);++aM}while(aL<aN.max&&aL!=aO);return aP};aD.tickFormatter=function(aQ,aO){var aN=aO.tickDecimals?Math.pow(10,aO.tickDecimals):1;var aP=""+Math.round(aQ*aN)/aN;if(aO.tickDecimals!=null){var aM=aP.indexOf(".");var aL=aM==-1?0:aP.length-aM-1;if(aL<aO.tickDecimals){return(aL?aP:aP+".")+(""+aN).substr(1,aO.tickDecimals-aL)}}return aP}}if(e.isFunction(az.tickFormatter)){aD.tickFormatter=function(aL,aM){return""+az.tickFormatter(aL,aM)}}if(az.alignTicksWithAxis!=null){var aF=(aD.direction=="x"?ao:W)[az.alignTicksWithAxis-1];if(aF&&aF.used&&aF!=aD){var aJ=aD.tickGenerator(aD);if(aJ.length>0){if(az.min==null){aD.min=Math.min(aD.min,aJ[0])}if(az.max==null&&aJ.length>1){aD.max=Math.max(aD.max,aJ[aJ.length-1])}}aD.tickGenerator=function(aN){var aO=[],aL,aM;for(aM=0;aM<aF.ticks.length;++aM){aL=(aF.ticks[aM].v-aF.min)/(aF.max-aF.min);aL=aN.min+aL*(aN.max-aN.min);aO.push(aL)}return aO};if(!aD.mode&&az.tickDecimals==null){var aH=Math.max(0,-Math.floor(Math.log(aD.delta)/Math.LN10)+1),aG=aD.tickGenerator(aD);if(!(aG.length>1&&/\..*0$/.test((aG[1]-aG[0]).toFixed(aH)))){aD.tickDecimals=aH}}}}}function V(aC){var aE=aC.options.ticks,aD=[];if(aE==null||(typeof aE=="number"&&aE>0)){aD=aC.tickGenerator(aC)}else{if(aE){if(e.isFunction(aE)){aD=aE(aC)}else{aD=aE}}}var aB,ay;aC.ticks=[];for(aB=0;aB<aD.length;++aB){var az=null;var aA=aD[aB];if(typeof aA=="object"){ay=+aA[0];if(aA.length>1){az=aA[1]}}else{ay=+aA}if(az==null){az=aC.tickFormatter(ay,aC)}if(!isNaN(ay)){aC.ticks.push({v:ay,label:az})}}}function u(ay,az){if(ay.options.autoscaleMargin&&az.length>0){if(ay.options.min==null){ay.min=Math.min(ay.min,az[0].v)}if(ay.options.max==null&&az.length>1){ay.max=Math.max(ay.max,az[az.length-1].v)}}}function au(){ac.clear();F(p.drawBackground,[D]);var az=L.grid;if(az.show&&az.backgroundColor){r()}if(az.show&&!az.aboveData){w()}for(var ay=0;ay<t.length;++ay){F(p.drawSeries,[D,t[ay]]);aj(t[ay])}F(p.draw,[D]);if(az.show&&az.aboveData){w()}ac.render();X()}function s(ay,aC){var az,aE,aF,aG,aD=j();for(var aB=0;aB<aD.length;++aB){az=aD[aB];if(az.direction==aC){aG=aC+az.n+"axis";if(!ay[aG]&&az.n==1){aG=aC+"axis"}if(ay[aG]){aE=ay[aG].from;aF=ay[aG].to;break}}}if(!ay[aG]){az=aC=="x"?ao[0]:W[0];aE=ay[aC+"1"];aF=ay[aC+"2"]}if(aE!=null&&aF!=null&&aE>aF){var aA=aE;aE=aF;aF=aA}return{from:aE,to:aF,axis:az}}function r(){D.save();D.translate(J.left,J.top);D.fillStyle=v(L.grid.backgroundColor,ad,0,"rgba(255, 255, 255, 0)");D.fillRect(0,0,k,ad);D.restore()}function w(){var aO,aN,aR,aA;D.save();D.translate(J.left,J.top);var aB=L.grid.markings;if(aB){if(e.isFunction(aB)){aN=h.getAxes();aN.xmin=aN.xaxis.min;aN.xmax=aN.xaxis.max;aN.ymin=aN.yaxis.min;aN.ymax=aN.yaxis.max;aB=aB(aN)}for(aO=0;aO<aB.length;++aO){var aL=aB[aO],aC=s(aL,"x"),aG=s(aL,"y");if(aC.from==null){aC.from=aC.axis.min}if(aC.to==null){aC.to=aC.axis.max}if(aG.from==null){aG.from=aG.axis.min}if(aG.to==null){aG.to=aG.axis.max}if(aC.to<aC.axis.min||aC.from>aC.axis.max||aG.to<aG.axis.min||aG.from>aG.axis.max){continue}aC.from=Math.max(aC.from,aC.axis.min);aC.to=Math.min(aC.to,aC.axis.max);aG.from=Math.max(aG.from,aG.axis.min);aG.to=Math.min(aG.to,aG.axis.max);var aD=aC.from===aC.to,aJ=aG.from===aG.to;if(aD&&aJ){continue}aC.from=Math.floor(aC.axis.p2c(aC.from));aC.to=Math.floor(aC.axis.p2c(aC.to));aG.from=Math.floor(aG.axis.p2c(aG.from));aG.to=Math.floor(aG.axis.p2c(aG.to));if(aD||aJ){var ay=aL.lineWidth||L.grid.markingsLineWidth,aP=ay%2?0.5:0;D.beginPath();D.strokeStyle=aL.color||L.grid.markingsColor;D.lineWidth=ay;if(aD){D.moveTo(aC.to+aP,aG.from);D.lineTo(aC.to+aP,aG.to)}else{D.moveTo(aC.from,aG.to+aP);D.lineTo(aC.to,aG.to+aP)}D.stroke()}else{D.fillStyle=aL.color||L.grid.markingsColor;D.fillRect(aC.from,aG.to,aC.to-aC.from,aG.from-aG.to)}}}aN=j();aR=L.grid.borderWidth;for(var aM=0;aM<aN.length;++aM){var az=aN[aM],aH=az.box,aK=az.tickLength,aF,aE,aQ,aS;if(!az.show||az.ticks.length==0){continue}D.lineWidth=1;if(az.direction=="x"){aF=0;if(aK=="full"){aE=(az.position=="top"?0:ad)}else{aE=aH.top-J.top+(az.position=="top"?aH.height:0)}}else{aE=0;if(aK=="full"){aF=(az.position=="left"?0:k)}else{aF=aH.left-J.left+(az.position=="left"?aH.width:0)}}if(!az.innermost){D.strokeStyle=az.options.color;D.beginPath();aQ=aS=0;if(az.direction=="x"){aQ=k+1}else{aS=ad+1}if(D.lineWidth==1){if(az.direction=="x"){aE=Math.floor(aE)+0.5}else{aF=Math.floor(aF)+0.5}}D.moveTo(aF,aE);D.lineTo(aF+aQ,aE+aS);D.stroke()}D.strokeStyle=az.options.tickColor;D.beginPath();for(aO=0;aO<az.ticks.length;++aO){var aI=az.ticks[aO].v;aQ=aS=0;if(isNaN(aI)||aI<az.min||aI>az.max||(aK=="full"&&((typeof aR=="object"&&aR[az.position]>0)||aR>0)&&(aI==az.min||aI==az.max))){continue}if(az.direction=="x"){aF=az.p2c(aI);aS=aK=="full"?-ad:aK;if(az.position=="top"){aS=-aS}}else{aE=az.p2c(aI);aQ=aK=="full"?-k:aK;if(az.position=="left"){aQ=-aQ}}if(D.lineWidth==1){if(az.direction=="x"){aF=Math.floor(aF)+0.5}else{aE=Math.floor(aE)+0.5}}D.moveTo(aF,aE);D.lineTo(aF+aQ,aE+aS)}D.stroke()}if(aR){aA=L.grid.borderColor;if(typeof aR=="object"||typeof aA=="object"){if(typeof aR!=="object"){aR={top:aR,right:aR,bottom:aR,left:aR}}if(typeof aA!=="object"){aA={top:aA,right:aA,bottom:aA,left:aA}}if(aR.top>0){D.strokeStyle=aA.top;D.lineWidth=aR.top;D.beginPath();D.moveTo(0-aR.left,0-aR.top/2);D.lineTo(k,0-aR.top/2);D.stroke()}if(aR.right>0){D.strokeStyle=aA.right;D.lineWidth=aR.right;D.beginPath();D.moveTo(k+aR.right/2,0-aR.top);D.lineTo(k+aR.right/2,ad);D.stroke()}if(aR.bottom>0){D.strokeStyle=aA.bottom;D.lineWidth=aR.bottom;D.beginPath();D.moveTo(k+aR.right,ad+aR.bottom/2);D.lineTo(0,ad+aR.bottom/2);D.stroke()}if(aR.left>0){D.strokeStyle=aA.left;D.lineWidth=aR.left;D.beginPath();D.moveTo(0-aR.left/2,ad+aR.bottom);D.lineTo(0-aR.left/2,0);D.stroke()}}else{D.lineWidth=aR;D.strokeStyle=L.grid.borderColor;D.strokeRect(-aR/2,-aR/2,k+aR,ad+aR)}}D.restore()}function at(){e.each(j(),function(aJ,az){var aC=az.box,aB=az.direction+"Axis "+az.direction+az.n+"Axis",aF="flot-"+az.direction+"-axis flot-"+az.direction+az.n+"-axis "+aB,ay=az.options.font||"flot-tick-label tickLabel",aD,aI,aG,aE,aH;ac.removeText(aF);if(!az.show||az.ticks.length==0){return}for(var aA=0;aA<az.ticks.length;++aA){aD=az.ticks[aA];if(!aD.label||aD.v<az.min||aD.v>az.max){continue}if(az.direction=="x"){aE="center";aI=J.left+az.p2c(aD.v);if(az.position=="bottom"){aG=aC.top+aC.padding}else{aG=aC.top+aC.height-aC.padding;aH="bottom"}}else{aH="middle";aG=J.top+az.p2c(aD.v);if(az.position=="left"){aI=aC.left+aC.width-aC.padding;aE="right"}else{aI=aC.left+aC.padding}}ac.addText(aF,aI,aG,aD.label,ay,null,null,aE,aH)}})}function aj(ay){if(ay.lines.show){G(ay)}if(ay.bars.show){T(ay)}if(ay.points.show){U(ay)}}function G(aB){function aA(aM,aN,aF,aR,aQ){var aS=aM.points,aG=aM.pointsize,aK=null,aJ=null;D.beginPath();for(var aL=aG;aL<aS.length;aL+=aG){var aI=aS[aL-aG],aP=aS[aL-aG+1],aH=aS[aL],aO=aS[aL+1];if(aI==null||aH==null){continue}if(aP<=aO&&aP<aQ.min){if(aO<aQ.min){continue}aI=(aQ.min-aP)/(aO-aP)*(aH-aI)+aI;aP=aQ.min}else{if(aO<=aP&&aO<aQ.min){if(aP<aQ.min){continue}aH=(aQ.min-aP)/(aO-aP)*(aH-aI)+aI;aO=aQ.min}}if(aP>=aO&&aP>aQ.max){if(aO>aQ.max){continue}aI=(aQ.max-aP)/(aO-aP)*(aH-aI)+aI;aP=aQ.max}else{if(aO>=aP&&aO>aQ.max){if(aP>aQ.max){continue}aH=(aQ.max-aP)/(aO-aP)*(aH-aI)+aI;aO=aQ.max}}if(aI<=aH&&aI<aR.min){if(aH<aR.min){continue}aP=(aR.min-aI)/(aH-aI)*(aO-aP)+aP;aI=aR.min}else{if(aH<=aI&&aH<aR.min){if(aI<aR.min){continue}aO=(aR.min-aI)/(aH-aI)*(aO-aP)+aP;aH=aR.min}}if(aI>=aH&&aI>aR.max){if(aH>aR.max){continue}aP=(aR.max-aI)/(aH-aI)*(aO-aP)+aP;aI=aR.max}else{if(aH>=aI&&aH>aR.max){if(aI>aR.max){continue}aO=(aR.max-aI)/(aH-aI)*(aO-aP)+aP;aH=aR.max}}if(aI!=aK||aP!=aJ){D.moveTo(aR.p2c(aI)+aN,aQ.p2c(aP)+aF)}aK=aH;aJ=aO;D.lineTo(aR.p2c(aH)+aN,aQ.p2c(aO)+aF)}D.stroke()}function aC(aF,aN,aM){var aT=aF.points,aS=aF.pointsize,aK=Math.min(Math.max(0,aM.min),aM.max),aU=0,aR,aQ=false,aJ=1,aI=0,aO=0;while(true){if(aS>0&&aU>aT.length+aS){break}aU+=aS;var aW=aT[aU-aS],aH=aT[aU-aS+aJ],aV=aT[aU],aG=aT[aU+aJ];if(aQ){if(aS>0&&aW!=null&&aV==null){aO=aU;aS=-aS;aJ=2;continue}if(aS<0&&aU==aI+aS){D.fill();aQ=false;aS=-aS;aJ=1;aU=aI=aO+aS;continue}}if(aW==null||aV==null){continue}if(aW<=aV&&aW<aN.min){if(aV<aN.min){continue}aH=(aN.min-aW)/(aV-aW)*(aG-aH)+aH;aW=aN.min}else{if(aV<=aW&&aV<aN.min){if(aW<aN.min){continue}aG=(aN.min-aW)/(aV-aW)*(aG-aH)+aH;aV=aN.min}}if(aW>=aV&&aW>aN.max){if(aV>aN.max){continue}aH=(aN.max-aW)/(aV-aW)*(aG-aH)+aH;aW=aN.max}else{if(aV>=aW&&aV>aN.max){if(aW>aN.max){continue}aG=(aN.max-aW)/(aV-aW)*(aG-aH)+aH;aV=aN.max}}if(!aQ){D.beginPath();D.moveTo(aN.p2c(aW),aM.p2c(aK));aQ=true}if(aH>=aM.max&&aG>=aM.max){D.lineTo(aN.p2c(aW),aM.p2c(aM.max));D.lineTo(aN.p2c(aV),aM.p2c(aM.max));continue}else{if(aH<=aM.min&&aG<=aM.min){D.lineTo(aN.p2c(aW),aM.p2c(aM.min));D.lineTo(aN.p2c(aV),aM.p2c(aM.min));continue}}var aL=aW,aP=aV;if(aH<=aG&&aH<aM.min&&aG>=aM.min){aW=(aM.min-aH)/(aG-aH)*(aV-aW)+aW;aH=aM.min}else{if(aG<=aH&&aG<aM.min&&aH>=aM.min){aV=(aM.min-aH)/(aG-aH)*(aV-aW)+aW;aG=aM.min}}if(aH>=aG&&aH>aM.max&&aG<=aM.max){aW=(aM.max-aH)/(aG-aH)*(aV-aW)+aW;aH=aM.max}else{if(aG>=aH&&aG>aM.max&&aH<=aM.max){aV=(aM.max-aH)/(aG-aH)*(aV-aW)+aW;aG=aM.max}}if(aW!=aL){D.lineTo(aN.p2c(aL),aM.p2c(aH))}D.lineTo(aN.p2c(aW),aM.p2c(aH));D.lineTo(aN.p2c(aV),aM.p2c(aG));if(aV!=aP){D.lineTo(aN.p2c(aV),aM.p2c(aG));D.lineTo(aN.p2c(aP),aM.p2c(aG))}}}D.save();D.translate(J.left,J.top);D.lineJoin="round";var aD=aB.lines.lineWidth,ay=aB.shadowSize;if(aD>0&&ay>0){D.lineWidth=ay;D.strokeStyle="rgba(0,0,0,0.1)";var aE=Math.PI/18;aA(aB.datapoints,Math.sin(aE)*(aD/2+ay/2),Math.cos(aE)*(aD/2+ay/2),aB.xaxis,aB.yaxis);D.lineWidth=ay/2;aA(aB.datapoints,Math.sin(aE)*(aD/2+ay/4),Math.cos(aE)*(aD/2+ay/4),aB.xaxis,aB.yaxis)}D.lineWidth=aD;D.strokeStyle=aB.color;var az=z(aB.lines,aB.color,0,ad);if(az){D.fillStyle=az;aC(aB.datapoints,aB.xaxis,aB.yaxis)}if(aD>0){aA(aB.datapoints,0,0,aB.xaxis,aB.yaxis)}D.restore()}function U(aB){function aE(aK,aJ,aR,aH,aP,aQ,aN,aG){var aO=aK.points,aF=aK.pointsize;for(var aI=0;aI<aO.length;aI+=aF){var aM=aO[aI],aL=aO[aI+1];if(aM==null||aM<aQ.min||aM>aQ.max||aL<aN.min||aL>aN.max){continue}D.beginPath();aM=aQ.p2c(aM);aL=aN.p2c(aL)+aH;if(aG=="circle"){D.arc(aM,aL,aJ,0,aP?Math.PI:Math.PI*2,false)}else{aG(D,aM,aL,aJ,aP)}D.closePath();if(aR){D.fillStyle=aR;D.fill()}D.stroke()}}D.save();D.translate(J.left,J.top);var aD=aB.points.lineWidth,az=aB.shadowSize,ay=aB.points.radius,aC=aB.points.symbol;if(aD==0){aD=0.0001}if(aD>0&&az>0){var aA=az/2;D.lineWidth=aA;D.strokeStyle="rgba(0,0,0,0.1)";aE(aB.datapoints,ay,null,aA+aA/2,true,aB.xaxis,aB.yaxis,aC);D.strokeStyle="rgba(0,0,0,0.2)";aE(aB.datapoints,ay,null,aA/2,true,aB.xaxis,aB.yaxis,aC)}D.lineWidth=aD;D.strokeStyle=aB.color;aE(aB.datapoints,ay,z(aB.points,aB.color),0,false,aB.xaxis,aB.yaxis,aC);D.restore()}function ak(aJ,aI,aR,aE,aM,aA,aH,aG,aQ,aN,az){var aB,aP,aF,aL,aC,ay,aK,aD,aO;if(aN){aD=ay=aK=true;aC=false;aB=aR;aP=aJ;aL=aI+aE;aF=aI+aM;if(aP<aB){aO=aP;aP=aB;aB=aO;aC=true;ay=false}}else{aC=ay=aK=true;aD=false;aB=aJ+aE;aP=aJ+aM;aF=aR;aL=aI;if(aL<aF){aO=aL;aL=aF;aF=aO;aD=true;aK=false}}if(aP<aH.min||aB>aH.max||aL<aG.min||aF>aG.max){return}if(aB<aH.min){aB=aH.min;aC=false}if(aP>aH.max){aP=aH.max;ay=false}if(aF<aG.min){aF=aG.min;aD=false}if(aL>aG.max){aL=aG.max;aK=false}aB=aH.p2c(aB);aF=aG.p2c(aF);aP=aH.p2c(aP);aL=aG.p2c(aL);if(aA){aQ.fillStyle=aA(aF,aL);aQ.fillRect(aB,aL,aP-aB,aF-aL)}if(az>0&&(aC||ay||aK||aD)){aQ.beginPath();aQ.moveTo(aB,aF);if(aC){aQ.lineTo(aB,aL)}else{aQ.moveTo(aB,aL)}if(aK){aQ.lineTo(aP,aL)}else{aQ.moveTo(aP,aL)}if(ay){aQ.lineTo(aP,aF)}else{aQ.moveTo(aP,aF)}if(aD){aQ.lineTo(aB,aF)}else{aQ.moveTo(aB,aF)}aQ.stroke()}}function T(aA){function az(aF,aE,aH,aG,aJ,aI){var aK=aF.points,aC=aF.pointsize;for(var aD=0;aD<aK.length;aD+=aC){if(aK[aD]==null){continue}ak(aK[aD],aK[aD+1],aK[aD+2],aE,aH,aG,aJ,aI,D,aA.bars.horizontal,aA.bars.lineWidth)}}D.save();D.translate(J.left,J.top);D.lineWidth=aA.bars.lineWidth;D.strokeStyle=aA.color;var ay;switch(aA.bars.align){case"left":ay=0;break;case"right":ay=-aA.bars.barWidth;break;default:ay=-aA.bars.barWidth/2}var aB=aA.bars.fill?function(aC,aD){return z(aA.bars,aA.color,aC,aD)}:null;az(aA.datapoints,ay,ay+aA.bars.barWidth,aB,aA.xaxis,aA.yaxis);D.restore()}function z(aA,ay,az,aC){var aB=aA.fill;if(!aB){return null}if(aA.fillColor){return v(aA.fillColor,az,aC,ay)}var aD=e.color.parse(ay);aD.a=typeof aB=="number"?aB:0.4;aD.normalize();return aD.toString()}function av(){if(L.legend.container!=null){e(L.legend.container).html("")}else{Q.find(".legend").remove()}if(!L.legend.show){return}var aG=[],aD=[],aE=false,aN=L.legend.labelFormatter,aM,aI;for(var aC=0;aC<t.length;++aC){aM=t[aC];if(aM.label){aI=aN?aN(aM.label,aM):aM.label;if(aI){aD.push({label:aI,color:aM.color})}}}if(L.legend.sorted){if(e.isFunction(L.legend.sorted)){aD.sort(L.legend.sorted)}else{if(L.legend.sorted=="reverse"){aD.reverse()}else{var aB=L.legend.sorted!="descending";aD.sort(function(aP,aO){return aP.label==aO.label?0:((aP.label<aO.label)!=aB?1:-1)})}}}for(var aC=0;aC<aD.length;++aC){var aK=aD[aC];if(aC%L.legend.noColumns==0){if(aE){aG.push("</tr>")}aG.push("<tr>");aE=true}aG.push('<td class="legendColorBox"><div style="border:1px solid '+L.legend.labelBoxBorderColor+';padding:1px"><div style="width:4px;height:0;border:5px solid '+aK.color+';overflow:hidden"></div></div></td><td class="legendLabel">'+aK.label+"</td>")}if(aE){aG.push("</tr>")}if(aG.length==0){return}var aL='<table style="font-size:smaller;color:'+L.grid.color+'">'+aG.join("")+"</table>";if(L.legend.container!=null){var aJ=e(L.legend.container).html(aL)}else{var aH="",az=L.legend.position,aA=L.legend.margin;if(aA[0]==null){aA=[aA,aA]}if(az.charAt(0)=="n"){aH+="top:"+(aA[1]+J.top)+"px;"}else{if(az.charAt(0)=="s"){aH+="bottom:"+(aA[1]+J.bottom)+"px;"}}if(az.charAt(1)=="e"){aH+="right:"+(aA[0]+J.right)+"px;"}else{if(az.charAt(1)=="w"){aH+="left:"+(aA[0]+J.left)+"px;"}}var aJ=e('<div class="legend">'+aL.replace('style="','style="position:absolute;'+aH+";")+"</div>").appendTo(Q);if(L.legend.backgroundOpacity!=0){var aF=L.legend.backgroundColor;if(aF==null){aF=L.grid.backgroundColor;if(aF&&typeof aF=="string"){aF=e.color.parse(aF)}else{aF=e.color.extract(aJ,"background-color")}aF.a=1;aF=aF.toString()}var ay=aJ.children();e('<div style="position:absolute;width:'+ay.width()+"px;height:"+ay.height()+"px;"+aH+"background-color:"+aF+';"> </div>').prependTo(aJ).css("opacity",L.legend.backgroundOpacity)}}F(p.legendInserted,[aJ])}var ag=[],l=null;function ap(aF,aD,aA){var aL=L.grid.mouseActiveRadius,aX=aL*aL+1,aV=null,aO=false,aT,aR,aQ;for(aT=t.length-1;aT>=0;--aT){if(!aA(t[aT])){continue}var aM=t[aT],aE=aM.xaxis,aC=aM.yaxis,aS=aM.datapoints.points,aN=aE.c2p(aF),aK=aC.c2p(aD),az=aL/aE.scale,ay=aL/aC.scale;aQ=aM.datapoints.pointsize;if(aE.options.inverseTransform){az=Number.MAX_VALUE}if(aC.options.inverseTransform){ay=Number.MAX_VALUE}if(aM.lines.show||aM.points.show){for(aR=0;aR<aS.length;aR+=aQ){var aH=aS[aR],aG=aS[aR+1];if(aH==null){continue}if(aH-aN>az||aH-aN<-az||aG-aK>ay||aG-aK<-ay){continue}var aJ=Math.abs(aE.p2c(aH)-aF),aI=Math.abs(aC.p2c(aG)-aD),aP=aJ*aJ+aI*aI;if(aP<aX){aX=aP;aV=[aT,aR/aQ]}}}if(aM.bars.show&&!aV){var aB,aU;switch(aM.bars.align){case"left":aB=0;break;case"right":aB=-aM.bars.barWidth;break;default:aB=-aM.bars.barWidth/2}aU=aB+aM.bars.barWidth;for(aR=0;aR<aS.length;aR+=aQ){var aH=aS[aR],aG=aS[aR+1],aW=aS[aR+2];if(aH==null){continue}if(t[aT].bars.horizontal?(aN<=Math.max(aW,aH)&&aN>=Math.min(aW,aH)&&aK>=aG+aB&&aK<=aG+aU):(aN>=aH+aB&&aN<=aH+aU&&aK>=Math.min(aW,aG)&&aK<=Math.max(aW,aG))){aV=[aT,aR/aQ]}}}}if(aV){aT=aV[0];aR=aV[1];aQ=t[aT].datapoints.pointsize;return{datapoint:t[aT].datapoints.points.slice(aR*aQ,(aR+1)*aQ),dataIndex:aR,series:t[aT],seriesIndex:aT}}return null}function f(ay){if(L.grid.hoverable){i("plothover",ay,function(az){return az.hoverable!=false})}}function P(ay){if(L.grid.hoverable){i("plothover",ay,function(az){return false})}}function I(ay){i("plotclick",ay,function(az){return az.clickable!=false})}function i(az,ay,aA){var aB=am.offset(),aE=ay.pageX-aB.left-J.left,aC=ay.pageY-aB.top-J.top,aG=Y({left:aE,top:aC});aG.pageX=ay.pageX;aG.pageY=ay.pageY;var aH=ap(aE,aC,aA);if(aH){aH.pageX=parseInt(aH.series.xaxis.p2c(aH.datapoint[0])+aB.left+J.left,10);aH.pageY=parseInt(aH.series.yaxis.p2c(aH.datapoint[1])+aB.top+J.top,10)}if(L.grid.autoHighlight){for(var aD=0;aD<ag.length;++aD){var aF=ag[aD];if(aF.auto==az&&!(aH&&aF.series==aH.series&&aF.point[0]==aH.datapoint[0]&&aF.point[1]==aH.datapoint[1])){ah(aF.series,aF.point)}}if(aH){an(aH.series,aH.datapoint,az)}}Q.trigger(az,[aG,aH])}function X(){var ay=L.interaction.redrawOverlayInterval;if(ay==-1){af();return}if(!l){l=setTimeout(af,ay)}}function af(){l=null;aw.save();al.clear();aw.translate(J.left,J.top);var az,ay;for(az=0;az<ag.length;++az){ay=ag[az];if(ay.series.bars.show){ai(ay.series,ay.point)}else{ae(ay.series,ay.point)}}aw.restore();F(p.drawOverlay,[aw])}function an(aA,ay,aC){if(typeof aA=="number"){aA=t[aA]}if(typeof ay=="number"){var aB=aA.datapoints.pointsize;ay=aA.datapoints.points.slice(aB*ay,aB*(ay+1))}var az=N(aA,ay);if(az==-1){ag.push({series:aA,point:ay,auto:aC});X()}else{if(!aC){ag[az].auto=false}}}function ah(aA,ay){if(aA==null&&ay==null){ag=[];X();return}if(typeof aA=="number"){aA=t[aA]}if(typeof ay=="number"){var aB=aA.datapoints.pointsize;ay=aA.datapoints.points.slice(aB*ay,aB*(ay+1))}var az=N(aA,ay);if(az!=-1){ag.splice(az,1);X()}}function N(aA,aB){for(var ay=0;ay<ag.length;++ay){var az=ag[ay];if(az.series==aA&&az.point[0]==aB[0]&&az.point[1]==aB[1]){return ay}}return -1}function ae(ay,aE){var aC=aE[0],aA=aE[1],aF=ay.xaxis,aD=ay.yaxis,aG=(typeof ay.highlightColor==="string")?ay.highlightColor:e.color.parse(ay.color).scale("a",0.5).toString();if(aC<aF.min||aC>aF.max||aA<aD.min||aA>aD.max){return}var aB=ay.points.radius+ay.points.lineWidth/2;aw.lineWidth=aB;aw.strokeStyle=aG;var az=1.5*aB;aC=aF.p2c(aC);aA=aD.p2c(aA);aw.beginPath();if(ay.points.symbol=="circle"){aw.arc(aC,aA,az,0,2*Math.PI,false)}else{ay.points.symbol(aw,aC,aA,az,false)}aw.closePath();aw.stroke()}function ai(aB,ay){var aC=(typeof aB.highlightColor==="string")?aB.highlightColor:e.color.parse(aB.color).scale("a",0.5).toString(),aA=aC,az;switch(aB.bars.align){case"left":az=0;break;case"right":az=-aB.bars.barWidth;break;default:az=-aB.bars.barWidth/2}aw.lineWidth=aB.bars.lineWidth;aw.strokeStyle=aC;ak(ay[0],ay[1],ay[2]||0,az,az+aB.bars.barWidth,function(){return aA},aB.xaxis,aB.yaxis,aw,aB.bars.horizontal,aB.bars.lineWidth)}function v(aG,ay,aE,az){if(typeof aG=="string"){return aG}else{var aF=D.createLinearGradient(0,aE,0,ay);for(var aB=0,aA=aG.colors.length;aB<aA;++aB){var aC=aG.colors[aB];if(typeof aC!="string"){var aD=e.color.parse(az);if(aC.brightness!=null){aD=aD.scale("rgb",aC.brightness)}if(aC.opacity!=null){aD.a*=aC.opacity}aC=aD.toString()}aF.addColorStop(aB/(aA-1),aC)}return aF}}}e.plot=function(i,g,f){var h=new c(e(i),g,f,e.plot.plugins);return h};e.plot.version="0.8.3";e.plot.plugins=[];e.fn.plot=function(g,f){return this.each(function(){e.plot(this,g,f)})};function b(g,f){return f*Math.floor(g/f)}})(jQuery);/* Javascript plotting library for jQuery, version 0.8.3.

Copyright (c) 2007-2014 IOLA and Ole Laursen.
Licensed under the MIT license.

*/
(function($){function init(plot){var selection={first:{x:-1,y:-1},second:{x:-1,y:-1},show:false,active:false};var savedhandlers={};var mouseUpHandler=null;function onMouseMove(e){if(selection.active){updateSelection(e);plot.getPlaceholder().trigger("plotselecting",[getSelection()])}}function onMouseDown(e){if(e.which!=1)return;document.body.focus();if(document.onselectstart!==undefined&&savedhandlers.onselectstart==null){savedhandlers.onselectstart=document.onselectstart;document.onselectstart=function(){return false}}if(document.ondrag!==undefined&&savedhandlers.ondrag==null){savedhandlers.ondrag=document.ondrag;document.ondrag=function(){return false}}setSelectionPos(selection.first,e);selection.active=true;mouseUpHandler=function(e){onMouseUp(e)};$(document).one("mouseup",mouseUpHandler)}function onMouseUp(e){mouseUpHandler=null;if(document.onselectstart!==undefined)document.onselectstart=savedhandlers.onselectstart;if(document.ondrag!==undefined)document.ondrag=savedhandlers.ondrag;selection.active=false;updateSelection(e);if(selectionIsSane())triggerSelectedEvent();else{plot.getPlaceholder().trigger("plotunselected",[]);plot.getPlaceholder().trigger("plotselecting",[null])}return false}function getSelection(){if(!selectionIsSane())return null;if(!selection.show)return null;var r={},c1=selection.first,c2=selection.second;$.each(plot.getAxes(),function(name,axis){if(axis.used){var p1=axis.c2p(c1[axis.direction]),p2=axis.c2p(c2[axis.direction]);r[name]={from:Math.min(p1,p2),to:Math.max(p1,p2)}}});return r}function triggerSelectedEvent(){var r=getSelection();plot.getPlaceholder().trigger("plotselected",[r]);if(r.xaxis&&r.yaxis)plot.getPlaceholder().trigger("selected",[{x1:r.xaxis.from,y1:r.yaxis.from,x2:r.xaxis.to,y2:r.yaxis.to}])}function clamp(min,value,max){return value<min?min:value>max?max:value}function setSelectionPos(pos,e){var o=plot.getOptions();var offset=plot.getPlaceholder().offset();var plotOffset=plot.getPlotOffset();pos.x=clamp(0,e.pageX-offset.left-plotOffset.left,plot.width());pos.y=clamp(0,e.pageY-offset.top-plotOffset.top,plot.height());if(o.selection.mode=="y")pos.x=pos==selection.first?0:plot.width();if(o.selection.mode=="x")pos.y=pos==selection.first?0:plot.height()}function updateSelection(pos){if(pos.pageX==null)return;setSelectionPos(selection.second,pos);if(selectionIsSane()){selection.show=true;plot.triggerRedrawOverlay()}else clearSelection(true)}function clearSelection(preventEvent){if(selection.show){selection.show=false;plot.triggerRedrawOverlay();if(!preventEvent)plot.getPlaceholder().trigger("plotunselected",[])}}function extractRange(ranges,coord){var axis,from,to,key,axes=plot.getAxes();for(var k in axes){axis=axes[k];if(axis.direction==coord){key=coord+axis.n+"axis";if(!ranges[key]&&axis.n==1)key=coord+"axis";if(ranges[key]){from=ranges[key].from;to=ranges[key].to;break}}}if(!ranges[key]){axis=coord=="x"?plot.getXAxes()[0]:plot.getYAxes()[0];from=ranges[coord+"1"];to=ranges[coord+"2"]}if(from!=null&&to!=null&&from>to){var tmp=from;from=to;to=tmp}return{from:from,to:to,axis:axis}}function setSelection(ranges,preventEvent){var axis,range,o=plot.getOptions();if(o.selection.mode=="y"){selection.first.x=0;selection.second.x=plot.width()}else{range=extractRange(ranges,"x");selection.first.x=range.axis.p2c(range.from);selection.second.x=range.axis.p2c(range.to)}if(o.selection.mode=="x"){selection.first.y=0;selection.second.y=plot.height()}else{range=extractRange(ranges,"y");selection.first.y=range.axis.p2c(range.from);selection.second.y=range.axis.p2c(range.to)}selection.show=true;plot.triggerRedrawOverlay();if(!preventEvent&&selectionIsSane())triggerSelectedEvent()}function selectionIsSane(){var minSize=plot.getOptions().selection.minSize;return Math.abs(selection.second.x-selection.first.x)>=minSize&&Math.abs(selection.second.y-selection.first.y)>=minSize}plot.clearSelection=clearSelection;plot.setSelection=setSelection;plot.getSelection=getSelection;plot.hooks.bindEvents.push(function(plot,eventHolder){var o=plot.getOptions();if(o.selection.mode!=null){eventHolder.mousemove(onMouseMove);eventHolder.mousedown(onMouseDown)}});plot.hooks.drawOverlay.push(function(plot,ctx){if(selection.show&&selectionIsSane()){var plotOffset=plot.getPlotOffset();var o=plot.getOptions();ctx.save();ctx.translate(plotOffset.left,plotOffset.top);var c=$.color.parse(o.selection.color);ctx.strokeStyle=c.scale("a",.8).toString();ctx.lineWidth=1;ctx.lineJoin=o.selection.shape;ctx.fillStyle=c.scale("a",.4).toString();var x=Math.min(selection.first.x,selection.second.x)+.5,y=Math.min(selection.first.y,selection.second.y)+.5,w=Math.abs(selection.second.x-selection.first.x)-1,h=Math.abs(selection.second.y-selection.first.y)-1;ctx.fillRect(x,y,w,h);ctx.strokeRect(x,y,w,h);ctx.restore()}});plot.hooks.shutdown.push(function(plot,eventHolder){eventHolder.unbind("mousemove",onMouseMove);eventHolder.unbind("mousedown",onMouseDown);if(mouseUpHandler)$(document).unbind("mouseup",mouseUpHandler)})}$.plot.plugins.push({init:init,options:{selection:{mode:null,color:"#e8cfac",shape:"round",minSize:5}},name:"selection",version:"1.1"})})(jQuery);/* jquery.flot.touch 3
Plugin for Flot version 0.8.3.
Allows to use touch for pan / zoom and simulate tap, double tap as mouse clicks so other plugins can work as usual with a touch device.

https://github.com/chaveiro/flot.touch

Copyright (c) 2015 Chaveiro - Licensed under the MIT license.

*/
(function($){function init(plot){$.support.touch="ontouchend" in document;if(!$.support.touch){return}var isPanning=false;var isZooming=false;var lastTouchPosition={x:-1,y:-1};var startTouchPosition=lastTouchPosition;var lastTouchDistance=0;var relativeOffset={x:0,y:0};var relativeScale=1;var scaleOrigin={x:50,y:50};var lastRedraw=new Date().getTime();var eventdelayTouchEnded;var tapNum=0;var tapTimer,tapTimestamp;function pan(delta){var placeholder=plot.getPlaceholder();var options=plot.getOptions();relativeOffset.x-=delta.x;relativeOffset.y-=delta.y;if(!options.touch.css){return}switch(options.touch.pan.toLowerCase()){case"x":placeholder.css("transform","translateX("+relativeOffset.x+"px)");break;case"y":placeholder.css("transform","translateY("+relativeOffset.y+"px)");break;default:placeholder.css("transform","translate("+relativeOffset.x+"px,"+relativeOffset.y+"px)");break}}function scale(delta){var placeholder=plot.getPlaceholder();var options=plot.getOptions();relativeScale*=1+(delta/100);if(!options.touch.css){return}switch(options.touch.scale.toLowerCase()){case"x":placeholder.css("transform","scaleX("+relativeScale+")");break;case"y":placeholder.css("transform","scaleY("+relativeScale+")");break;default:placeholder.css("transform","scale("+relativeScale+")");break}}function processOptions(plot,options){var placeholder=plot.getPlaceholder();var options=plot.getOptions();if(options.touch.autoWidth){placeholder.css("width","100%")}if(options.touch.autoHeight){var placeholderParent=placeholder.parent();var height=0;placeholderParent.siblings().each(function(){height-=$(this).outerHeight()});height-=parseInt(placeholderParent.css("padding-top"),10);height-=parseInt(placeholderParent.css("padding-bottom"),10);height+=window.innerHeight;placeholder.css("height",(height<=0)?100:height+"px")}}function getTimestamp(){return new Date().getTime()}function bindEvents(plot,eventHolder){var placeholder=plot.getPlaceholder();var options=plot.getOptions();if(options.touch.css){placeholder.parent("div").css({overflow:"hidden"})}placeholder.bind("touchstart",function(evt){clearTimeout(eventdelayTouchEnded);var touches=evt.originalEvent.touches;var placeholder=plot.getPlaceholder();var options=plot.getOptions();$.each(plot.getAxes(),function(index,axis){if(axis.direction===options.touch.scale.toLowerCase()||options.touch.scale.toLowerCase()=="xy"){axis.touch={min:axis.min,max:axis.max}}});tapTimestamp=getTimestamp();if(touches.length===1){isPanning=true;lastTouchPosition={x:touches[0].pageX,y:touches[0].pageY};lastTouchDistance=0;tapNum++}else{if(touches.length===2){isZooming=true;lastTouchPosition={x:(touches[0].pageX+touches[1].pageX)/2,y:(touches[0].pageY+touches[1].pageY)/2};lastTouchDistance=Math.sqrt(Math.pow(touches[1].pageX-touches[0].pageX,2)+Math.pow(touches[1].pageY-touches[0].pageY,2))}}var offset=placeholder.offset();var rect={x:offset.left,y:offset.top,width:placeholder.width(),height:placeholder.height()};startTouchPosition={x:lastTouchPosition.x,y:lastTouchPosition.y};if(startTouchPosition.x<rect.x){startTouchPosition.x=rect.x}else{if(startTouchPosition.x>rect.x+rect.width){startTouchPosition.x=rect.x+rect.width}}if(startTouchPosition.y<rect.y){startTouchPosition.y=rect.y}else{if(startTouchPosition.y>rect.y+rect.height){startTouchPosition.y=rect.y+rect.height}}scaleOrigin={x:Math.round((startTouchPosition.x/rect.width)*100),y:Math.round((startTouchPosition.y/rect.height)*100)};if(options.touch.css){placeholder.css("transform-origin",scaleOrigin.x+"% "+scaleOrigin.y+"%")}placeholder.trigger("touchstarted",[startTouchPosition]);return false});placeholder.bind("touchmove",function(evt){var options=plot.getOptions();var touches=evt.originalEvent.touches;var position,distance,delta;if(isPanning&&touches.length===1){position={x:touches[0].pageX,y:touches[0].pageY};delta={x:lastTouchPosition.x-position.x,y:lastTouchPosition.y-position.y};pan(delta);lastTouchPosition=position;lastTouchDistance=0}else{if(isZooming&&touches.length===2){distance=Math.sqrt(Math.pow(touches[1].pageX-touches[0].pageX,2)+Math.pow(touches[1].pageY-touches[0].pageY,2));position={x:(touches[0].pageX+touches[1].pageX)/2,y:(touches[0].pageY+touches[1].pageY)/2};delta=distance-lastTouchDistance;scale(delta);lastTouchPosition=position;lastTouchDistance=distance}}if(!options.touch.css){var now=new Date().getTime(),framedelay=now-lastRedraw;if(framedelay>50){lastRedraw=now;window.requestAnimationFrame(redraw)}}});placeholder.bind("touchend",function(evt){var placeholder=plot.getPlaceholder();var options=plot.getOptions();var touches=evt.originalEvent.changedTouches;tapTimer=setTimeout(function(){tapNum=0},options.touch.dbltapThreshold);if(isPanning&&touches.length===1&&(tapTimestamp+options.touch.tapThreshold)-getTimestamp()>=0&&startTouchPosition.x>=lastTouchPosition.x-options.touch.tapPrecision&&startTouchPosition.x<=lastTouchPosition.x+options.touch.tapPrecision&&startTouchPosition.y>=lastTouchPosition.y-options.touch.tapPrecision&&startTouchPosition.y<=lastTouchPosition.y+options.touch.tapPrecision){if(tapNum===2){placeholder.trigger("dbltap",[lastTouchPosition])}else{placeholder.trigger("tap",[lastTouchPosition])}if(options.touch.simulClick){var simulatedEvent=new MouseEvent("click",{bubbles:true,cancelable:true,view:window,detail:tapNum,screenX:touches[0].screenX,screenY:touches[0].screenY,clientX:touches[0].clientX,clientY:touches[0].clientY,button:0});touches[0].target.dispatchEvent(simulatedEvent)}}else{var r={};c1={x:0,y:0};c2={x:plot.width(),y:plot.height()};$.each(plot.getAxes(),function(name,axis){if(axis.used){var p1=axis.c2p(c1[axis.direction]),p2=axis.c2p(c2[axis.direction]);r[name]={from:Math.min(p1,p2),to:Math.max(p1,p2)}}});eventdelayTouchEnded=setTimeout(function(){placeholder.trigger("touchended",[r])},options.touch.delayTouchEnded)}isPanning=false;isZooming=false;lastTouchPosition={x:-1,y:-1};startTouchPosition=lastTouchPosition;lastTouchDistance=0;relativeOffset={x:0,y:0};relativeScale=1;scaleOrigin={x:50,y:50};if(options.touch.css){placeholder.css({transform:"translate("+relativeOffset.x+"px,"+relativeOffset.y+"px) scale("+relativeScale+")","transform-origin":scaleOrigin.x+"% "+scaleOrigin.y+"%"})}})}function redraw(){var options=plot.getOptions();updateAxesMinMax();if(typeof options.touch.callback=="function"){options.touch.callback()}else{plot.setupGrid();plot.draw()}}function updateAxesMinMax(){var options=plot.getOptions();if(relativeOffset.x!==0||relativeOffset.y!==0){$.each(plot.getAxes(),function(index,axis){if(axis.direction===options.touch.pan.toLowerCase()||options.touch.pan.toLowerCase()=="xy"){var min=axis.c2p(axis.p2c(axis.touch.min)-relativeOffset[axis.direction]);var max=axis.c2p(axis.p2c(axis.touch.max)-relativeOffset[axis.direction]);axis.options.min=min;axis.options.max=max}})}if(relativeScale!==1){var width=plot.width();var height=plot.height();var scaleOriginPixel={x:Math.round((scaleOrigin.x/100)*width),y:Math.round((scaleOrigin.y/100)*height)};var range={x:{min:scaleOriginPixel.x-(scaleOrigin.x/100)*width/relativeScale,max:scaleOriginPixel.x+(1-(scaleOrigin.x/100))*width/relativeScale},y:{min:scaleOriginPixel.y-(scaleOrigin.y/100)*height/relativeScale,max:scaleOriginPixel.y+(1-(scaleOrigin.y/100))*height/relativeScale}};$.each(plot.getAxes(),function(index,axis){if(axis.direction===options.touch.scale.toLowerCase()||options.touch.scale.toLowerCase()=="xy"){var min=axis.c2p(range[axis.direction].min);var max=axis.c2p(range[axis.direction].max);if(min>max){var temp=min;min=max;max=temp}axis.options.min=min;axis.options.max=max}})}}function processDatapoints(plot,series,datapoints){if(window.devicePixelRatio){var placeholder=plot.getPlaceholder();placeholder.children("canvas").each(function(index,canvas){var context=canvas.getContext("2d");var width=$(canvas).attr("width");var height=$(canvas).attr("height");$(canvas).attr("width",width*window.devicePixelRatio);$(canvas).attr("height",height*window.devicePixelRatio);$(canvas).css("width",width+"px");$(canvas).css("height",height+"px");context.scale(window.devicePixelRatio,window.devicePixelRatio)})}}function shutdown(plot,eventHolder){var placeholder=plot.getPlaceholder();placeholder.unbind("touchstart").unbind("touchmove").unbind("touchend")}plot.hooks.processOptions.push(processOptions);plot.hooks.bindEvents.push(bindEvents);plot.hooks.shutdown.push(shutdown)}$.plot.plugins.push({init:init,options:{touch:{pan:"xy",scale:"xy",css:false,autoWidth:false,autoHeight:false,delayTouchEnded:500,callback:null,simulClick:true,tapThreshold:150,dbltapThreshold:200,tapPrecision:60/2}},name:"touch",version:"3.0"})})(jQuery);/* Javascript plotting library for jQuery, version 0.8.3.

Copyright (c) 2007-2014 IOLA and Ole Laursen.
Licensed under the MIT license.

*/
(function($){var options={xaxis:{timezone:null,timeformat:null,twelveHourClock:false,monthNames:null}};function floorInBase(n,base){return base*Math.floor(n/base);}function formatDate(d,fmt,monthNames,dayNames){if(typeof d.strftime=="function"){return d.strftime(fmt);}var leftPad=function(n,pad){n=""+n;pad=""+(pad==null?"0":pad);return n.length==1?pad+n:n;};var r=[];var escape=false;var hours=d.getHours();var isAM=hours<12;if(monthNames==null){monthNames=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];}if(dayNames==null){dayNames=["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];}var hours12;if(hours>12){hours12=hours-12;}else if(hours==0){hours12=12;}else{hours12=hours;}for(var i=0;i<fmt.length;++i){var c=fmt.charAt(i);if(escape){switch(c){case'a':c=""+dayNames[d.getDay()];break;case'b':c=""+monthNames[d.getMonth()];break;case'd':c=leftPad(d.getDate());break;case'e':c=leftPad(d.getDate()," ");break;case'h':case'H':c=leftPad(hours);break;case'I':c=leftPad(hours12);break;case'l':c=leftPad(hours12," ");break;case'm':c=leftPad(d.getMonth()+1);break;case'M':c=leftPad(d.getMinutes());break;case'q':c=""+(Math.floor(d.getMonth()/3)+1);break;case'S':c=leftPad(d.getSeconds());break;case'y':c=leftPad(d.getFullYear()%100);break;case'Y':c=""+d.getFullYear();break;case'p':c=(isAM)?(""+"am"):(""+"pm");break;case'P':c=(isAM)?(""+"AM"):(""+"PM");break;case'w':c=""+d.getDay();break;}r.push(c);escape=false;}else{if(c=="%"){escape=true;}else{r.push(c);}}}return r.join("");}function makeUtcWrapper(d){function addProxyMethod(sourceObj,sourceMethod,targetObj,targetMethod){sourceObj[sourceMethod]=function(){return targetObj[targetMethod].apply(targetObj,arguments);};};var utc={date:d};if(d.strftime!=undefined){addProxyMethod(utc,"strftime",d,"strftime");}addProxyMethod(utc,"getTime",d,"getTime");addProxyMethod(utc,"setTime",d,"setTime");var props=["Date","Day","FullYear","Hours","Milliseconds","Minutes","Month","Seconds"];for(var p=0;p<props.length;p++){addProxyMethod(utc,"get"+props[p],d,"getUTC"+props[p]);addProxyMethod(utc,"set"+props[p],d,"setUTC"+props[p]);}return utc;};function dateGenerator(ts,opts){if(opts.timezone=="browser"){return new Date(ts);}else if(!opts.timezone||opts.timezone=="utc"){return makeUtcWrapper(new Date(ts));}else if(typeof timezoneJS!="undefined"&&typeof timezoneJS.Date!="undefined"){var d=new timezoneJS.Date();d.setTimezone(opts.timezone);d.setTime(ts);return d;}else{return makeUtcWrapper(new Date(ts));}}var timeUnitSize={"second":1000,"minute":60*1000,"hour":60*60*1000,"day":24*60*60*1000,"month":30*24*60*60*1000,"quarter":3*30*24*60*60*1000,"year":365.2425*24*60*60*1000};var baseSpec=[[1,"second"],[2,"second"],[5,"second"],[10,"second"],[30,"second"],[1,"minute"],[2,"minute"],[5,"minute"],[10,"minute"],[30,"minute"],[1,"hour"],[2,"hour"],[4,"hour"],[8,"hour"],[12,"hour"],[1,"day"],[2,"day"],[3,"day"],[0.25,"month"],[0.5,"month"],[1,"month"],[2,"month"]];var specMonths=baseSpec.concat([[3,"month"],[6,"month"],[1,"year"]]);var specQuarters=baseSpec.concat([[1,"quarter"],[2,"quarter"],[1,"year"]]);function init(plot){plot.hooks.processOptions.push(function(plot,options){$.each(plot.getAxes(),function(axisName,axis){var opts=axis.options;if(opts.mode=="time"){axis.tickGenerator=function(axis){var ticks=[];var d=makeUtcWrapper(new Date(axis.min));var minSize=0;var spec=(opts.tickSize&&opts.tickSize[1]==="quarter")||(opts.minTickSize&&opts.minTickSize[1]==="quarter")?specQuarters:specMonths;if(opts.minTickSize!=null){if(typeof opts.tickSize=="number"){minSize=opts.tickSize;}else{minSize=opts.minTickSize[0]*timeUnitSize[opts.minTickSize[1]];}}for(var i=0;i<spec.length-1;++i){if(axis.delta<(spec[i][0]*timeUnitSize[spec[i][1]]+spec[i+1][0]*timeUnitSize[spec[i+1][1]])/2&&spec[i][0]*timeUnitSize[spec[i][1]]>=minSize){break;}}var size=spec[i][0];var unit=spec[i][1];if(unit=="year"){if(opts.minTickSize!=null&&opts.minTickSize[1]=="year"){size=Math.floor(opts.minTickSize[0]);}else{var magn=Math.pow(10,Math.floor(Math.log(axis.delta/timeUnitSize.year)/Math.LN10));var norm=(axis.delta/timeUnitSize.year)/magn;if(norm<1.5){size=1;}else if(norm<3){size=2;}else if(norm<7.5){size=5;}else{size=10;}size*=magn;}if(size<1){size=1;}}axis.tickSize=opts.tickSize||[size,unit];var tickSize=axis.tickSize[0];unit=axis.tickSize[1];var step=tickSize*timeUnitSize[unit];if(unit=="second"){d.setSeconds(floorInBase(d.getSeconds(),tickSize));}else if(unit=="minute"){d.setMinutes(floorInBase(d.getMinutes(),tickSize));}else if(unit=="hour"){d.setHours(floorInBase(d.getHours(),tickSize));}else if(unit=="month"){d.setMonth(floorInBase(d.getMonth(),tickSize));}else if(unit=="quarter"){d.setMonth(3*floorInBase(d.getMonth()/3,tickSize));}else if(unit=="year"){d.setFullYear(floorInBase(d.getFullYear(),tickSize));}d.setMilliseconds(0);if(step>=timeUnitSize.minute){d.setSeconds(0);}if(step>=timeUnitSize.hour){d.setMinutes(0);}if(step>=timeUnitSize.day){d.setHours(0);}if(step>=timeUnitSize.day*4){d.setDate(1);}if(step>=timeUnitSize.month*2){d.setMonth(floorInBase(d.getMonth(),3));}if(step>=timeUnitSize.quarter*2){d.setMonth(floorInBase(d.getMonth(),6));}if(step>=timeUnitSize.year){d.setMonth(0);}var carry=0;var v=Number.NaN;var prev;do{prev=v;v=d.getTime();ticks.push(v);if(unit=="month"||unit=="quarter"){if(tickSize<1){d.setDate(1);var start=d.getTime();d.setMonth(d.getMonth()+(unit=="quarter"?3:1));var end=d.getTime();d.setTime(v+carry*timeUnitSize.hour+(end-start)*tickSize);carry=d.getHours();d.setHours(0);}else{d.setMonth(d.getMonth()+tickSize*(unit=="quarter"?3:1));}}else if(unit=="year"){d.setFullYear(d.getFullYear()+tickSize);}else{d.setTime(v+step);}}while(v<axis.max&&v!=prev);return ticks;};axis.tickFormatter=function(v,axis){var d=dateGenerator(v,axis.options);if(opts.timeformat!=null){return formatDate(d,opts.timeformat,opts.monthNames,opts.dayNames);}var useQuarters=(axis.options.tickSize&&axis.options.tickSize[1]=="quarter")||(axis.options.minTickSize&&axis.options.minTickSize[1]=="quarter");var t=axis.tickSize[0]*timeUnitSize[axis.tickSize[1]];var span=axis.max-axis.min;var suffix=(opts.twelveHourClock)?" %p":"";var hourCode=(opts.twelveHourClock)?"%I":"%H";var fmt;if(t<timeUnitSize.minute){fmt=hourCode+":%M:%S"+suffix;}else if(t<timeUnitSize.day){if(span<2*timeUnitSize.day){fmt=hourCode+":%M"+suffix;}else{fmt="%b %d "+hourCode+":%M"+suffix;}}else if(t<timeUnitSize.month){fmt="%b %d";}else if((useQuarters&&t<timeUnitSize.quarter)||(!useQuarters&&t<timeUnitSize.year)){if(span<timeUnitSize.year){fmt="%b";}else{fmt="%b %Y";}}else if(useQuarters&&t<timeUnitSize.year){if(span<timeUnitSize.year){fmt="Q%q";}else{fmt="Q%q %Y";}}else{fmt="%Y";}var rt=formatDate(d,fmt,opts.monthNames,opts.dayNames);return rt;};}});});}$.plot.plugins.push({init:init,options:options,name:'time',version:'1.0'});$.plot.formatDate=formatDate;$.plot.dateGenerator=dateGenerator;})(jQuery);var dateFormat=function(){var t=/d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,e=/\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,a=/[^-+\dA-Z]/g,m=function(t,e){for(t=String(t),e=e||2;t.length<e;)t="0"+t;return t};return function(d,n,r){var y=dateFormat;if(1!=arguments.length||"[object String]"!=Object.prototype.toString.call(d)||/\d/.test(d)||(n=d,d=void 0),d=d?new Date(d):new Date,isNaN(d))throw SyntaxError("invalid date");n=String(y.masks[n]||n||y.masks["default"]),"UTC:"==n.slice(0,4)&&(n=n.slice(4),r=!0);var s=r?"getUTC":"get",i=d[s+"Date"](),o=d[s+"Day"](),u=d[s+"Month"](),M=d[s+"FullYear"](),l=d[s+"Hours"](),T=d[s+"Minutes"](),h=d[s+"Seconds"](),c=d[s+"Milliseconds"](),g=r?0:d.getTimezoneOffset(),S={d:i,dd:m(i),ddd:y.i18n.dayNames[o],dddd:y.i18n.dayNames[o+7],m:u+1,mm:m(u+1),mmm:y.i18n.monthNames[u],mmmm:y.i18n.monthNames[u+12],yy:String(M).slice(2),yyyy:M,h:l%12||12,hh:m(l%12||12),H:l,HH:m(l),M:T,MM:m(T),s:h,ss:m(h),l:m(c,3),L:m(c>99?Math.round(c/10):c),t:12>l?"a":"p",tt:12>l?"am":"pm",T:12>l?"A":"P",TT:12>l?"AM":"PM",Z:r?"UTC":(String(d).match(e)||[""]).pop().replace(a,""),o:(g>0?"-":"+")+m(100*Math.floor(Math.abs(g)/60)+Math.abs(g)%60,4),S:["th","st","nd","rd"][i%10>3?0:(i%100-i%10!=10)*i%10]};return n.replace(t,function(t){return t in S?S[t]:t.slice(1,t.length-1)})}}();dateFormat.masks={"default":"ddd mmm dd yyyy HH:MM:ss",shortDate:"m/d/yy",mediumDate:"mmm d, yyyy",longDate:"mmmm d, yyyy",fullDate:"dddd, mmmm d, yyyy",shortTime:"h:MM TT",mediumTime:"h:MM:ss TT",longTime:"h:MM:ss TT Z",isoDate:"yyyy-mm-dd",isoTime:"HH:MM:ss",isoDateTime:"yyyy-mm-dd'T'HH:MM:ss",isoUtcDateTime:"UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"},dateFormat.i18n={dayNames:["Sun","Mon","Tue","Wed","Thu","Fri","Sat","Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],monthNames:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec","January","February","March","April","May","June","July","August","September","October","November","December"]},Date.prototype.format=function(t,e){return dateFormat(this,t,e)};/* Flot plugin for drawing all elements of a plot on the canvas.

Copyright (c) 2007-2014 IOLA and Ole Laursen.
Licensed under the MIT license.

Flot normally produces certain elements, like axis labels and the legend, using
HTML elements. This permits greater interactivity and customization, and often
looks better, due to cross-browser canvas text inconsistencies and limitations.

It can also be desirable to render the plot entirely in canvas, particularly
if the goal is to save it as an image, or if Flot is being used in a context
where the HTML DOM does not exist, as is the case within Node.js. This plugin
switches out Flot's standard drawing operations for canvas-only replacements.

Currently the plugin supports only axis labels, but it will eventually allow
every element of the plot to be rendered directly to canvas.

The plugin supports these options:

{
    canvas: boolean
}

The "canvas" option controls whether full canvas drawing is enabled, making it
possible to toggle on and off. This is useful when a plot uses HTML text in the
browser, but needs to redraw with canvas text when exporting as an image.

*/

(function($) {

	var options = {
		canvas: true
	};

	var render, getTextInfo, addText;

	// Cache the prototype hasOwnProperty for faster access

	var hasOwnProperty = Object.prototype.hasOwnProperty;

	function init(plot, classes) {

		var Canvas = classes.Canvas;

		// We only want to replace the functions once; the second time around
		// we would just get our new function back.  This whole replacing of
		// prototype functions is a disaster, and needs to be changed ASAP.

		if (render == null) {
			getTextInfo = Canvas.prototype.getTextInfo,
			addText = Canvas.prototype.addText,
			render = Canvas.prototype.render;
		}

		// Finishes rendering the canvas, including overlaid text

		Canvas.prototype.render = function() {

			if (!plot.getOptions().canvas) {
				return render.call(this);
			}

			var context = this.context,
				cache = this._textCache;

			// For each text layer, render elements marked as active

			context.save();
			context.textBaseline = "middle";

			for (var layerKey in cache) {
				if (hasOwnProperty.call(cache, layerKey)) {
					var layerCache = cache[layerKey];
					for (var styleKey in layerCache) {
						if (hasOwnProperty.call(layerCache, styleKey)) {
							var styleCache = layerCache[styleKey],
								updateStyles = true;
							for (var key in styleCache) {
								if (hasOwnProperty.call(styleCache, key)) {

									var info = styleCache[key],
										positions = info.positions,
										lines = info.lines;

									// Since every element at this level of the cache have the
									// same font and fill styles, we can just change them once
									// using the values from the first element.

									if (updateStyles) {
										context.fillStyle = info.font.color;
										context.font = info.font.definition;
										updateStyles = false;
									}

									for (var i = 0, position; position = positions[i]; i++) {
										if (position.active) {
											for (var j = 0, line; line = position.lines[j]; j++) {
												context.fillText(lines[j].text, line[0], line[1]);
											}
										} else {
											positions.splice(i--, 1);
										}
									}

									if (positions.length == 0) {
										delete styleCache[key];
									}
								}
							}
						}
					}
				}
			}

			context.restore();
		};

		// Creates (if necessary) and returns a text info object.
		//
		// When the canvas option is set, the object looks like this:
		//
		// {
		//     width: Width of the text's bounding box.
		//     height: Height of the text's bounding box.
		//     positions: Array of positions at which this text is drawn.
		//     lines: [{
		//         height: Height of this line.
		//         widths: Width of this line.
		//         text: Text on this line.
		//     }],
		//     font: {
		//         definition: Canvas font property string.
		//         color: Color of the text.
		//     },
		// }
		//
		// The positions array contains objects that look like this:
		//
		// {
		//     active: Flag indicating whether the text should be visible.
		//     lines: Array of [x, y] coordinates at which to draw the line.
		//     x: X coordinate at which to draw the text.
		//     y: Y coordinate at which to draw the text.
		// }

		Canvas.prototype.getTextInfo = function(layer, text, font, angle, width) {

			if (!plot.getOptions().canvas) {
				return getTextInfo.call(this, layer, text, font, angle, width);
			}

			var textStyle, layerCache, styleCache, info;

			// Cast the value to a string, in case we were given a number

			text = "" + text;

			// If the font is a font-spec object, generate a CSS definition

			if (typeof font === "object") {
				textStyle = font.style + " " + font.variant + " " + font.weight + " " + font.size + "px " + font.family;
			} else {
				textStyle = font;
			}

			// Retrieve (or create) the cache for the text's layer and styles

			layerCache = this._textCache[layer];

			if (layerCache == null) {
				layerCache = this._textCache[layer] = {};
			}

			styleCache = layerCache[textStyle];

			if (styleCache == null) {
				styleCache = layerCache[textStyle] = {};
			}

			info = styleCache[text];

			if (info == null) {

				var context = this.context;

				// If the font was provided as CSS, create a div with those
				// classes and examine it to generate a canvas font spec.

				if (typeof font !== "object") {

					var element = $("<div>&nbsp;</div>")
						.css("position", "absolute")
						.addClass(typeof font === "string" ? font : null)
						.appendTo(this.getTextLayer(layer));

					font = {
						lineHeight: element.height(),
						style: element.css("font-style"),
						variant: element.css("font-variant"),
						weight: element.css("font-weight"),
						family: element.css("font-family"),
						color: element.css("color")
					};

					// Setting line-height to 1, without units, sets it equal
					// to the font-size, even if the font-size is abstract,
					// like 'smaller'.  This enables us to read the real size
					// via the element's height, working around browsers that
					// return the literal 'smaller' value.

					font.size = element.css("line-height", 1).height();

					element.remove();
				}

				textStyle = font.style + " " + font.variant + " " + font.weight + " " + font.size + "px " + font.family;

				// Create a new info object, initializing the dimensions to
				// zero so we can count them up line-by-line.

				info = styleCache[text] = {
					width: 0,
					height: 0,
					positions: [],
					lines: [],
					font: {
						definition: textStyle,
						color: font.color
					}
				};

				context.save();
				context.font = textStyle;

				// Canvas can't handle multi-line strings; break on various
				// newlines, including HTML brs, to build a list of lines.
				// Note that we could split directly on regexps, but IE < 9 is
				// broken; revisit when we drop IE 7/8 support.

				var lines = (text + "").replace(/<br ?\/?>|\r\n|\r/g, "\n").split("\n");

				for (var i = 0; i < lines.length; ++i) {

					var lineText = lines[i],
						measured = context.measureText(lineText);

					info.width = Math.max(measured.width, info.width);
					info.height += font.lineHeight;

					info.lines.push({
						text: lineText,
						width: measured.width,
						height: font.lineHeight
					});
				}

				context.restore();
			}

			return info;
		};

		// Adds a text string to the canvas text overlay.

		Canvas.prototype.addText = function(layer, x, y, text, font, angle, width, halign, valign) {

			if (!plot.getOptions().canvas) {
				return addText.call(this, layer, x, y, text, font, angle, width, halign, valign);
			}

			var info = this.getTextInfo(layer, text, font, angle, width),
				positions = info.positions,
				lines = info.lines;

			// Text is drawn with baseline 'middle', which we need to account
			// for by adding half a line's height to the y position.

			y += info.height / lines.length / 2;

			// Tweak the initial y-position to match vertical alignment

			if (valign == "middle") {
				y = Math.round(y - info.height / 2);
			} else if (valign == "bottom") {
				y = Math.round(y - info.height);
			} else {
				y = Math.round(y);
			}

			// FIXME: LEGACY BROWSER FIX
			// AFFECTS: Opera < 12.00

			// Offset the y coordinate, since Opera is off pretty
			// consistently compared to the other browsers.

			if (!!(window.opera && window.opera.version().split(".")[0] < 12)) {
				y -= 2;
			}

			// Determine whether this text already exists at this position.
			// If so, mark it for inclusion in the next render pass.

			for (var i = 0, position; position = positions[i]; i++) {
				if (position.x == x && position.y == y) {
					position.active = true;
					return;
				}
			}

			// If the text doesn't exist at this position, create a new entry

			position = {
				active: true,
				lines: [],
				x: x,
				y: y
			};

			positions.push(position);

			// Fill in the x & y positions of each line, adjusting them
			// individually for horizontal alignment.

			for (var i = 0, line; line = lines[i]; i++) {
				if (halign == "center") {
					position.lines.push([Math.round(x - line.width / 2), y]);
				} else if (halign == "right") {
					position.lines.push([Math.round(x - line.width), y]);
				} else {
					position.lines.push([Math.round(x), y]);
				}
				y += line.height;
			}
		};
	}

	$.plot.plugins.push({
		init: init,
		options: options,
		name: "canvas",
		version: "1.0"
	});

})(jQuery);
﻿/* Copyright (C) 1999 Masanao Izumo <iz@onicos.co.jp>
* Version: 1.0
* LastModified: Dec 25 1999
* This library is free.  You can redistribute it and/or modify it.
*/

/*
* Interfaces:
* b64 = base64encode(data);
* data = base64decode(b64);
*/

(function () {

    var base64EncodeChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
    var base64DecodeChars = new Array(
    -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
    -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
    -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, 62, -1, -1, -1, 63,
    52, 53, 54, 55, 56, 57, 58, 59, 60, 61, -1, -1, -1, -1, -1, -1,
    -1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14,
    15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, -1, -1, -1, -1, -1,
    -1, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
    41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, -1, -1, -1, -1, -1);

    function base64encode(str) {
        var out, i, len;
        var c1, c2, c3;

        len = str.length;
        i = 0;
        out = "";
        while (i < len) {
            c1 = str.charCodeAt(i++) & 0xff;
            if (i == len) {
                out += base64EncodeChars.charAt(c1 >> 2);
                out += base64EncodeChars.charAt((c1 & 0x3) << 4);
                out += "==";
                break;
            }
            c2 = str.charCodeAt(i++);
            if (i == len) {
                out += base64EncodeChars.charAt(c1 >> 2);
                out += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
                out += base64EncodeChars.charAt((c2 & 0xF) << 2);
                out += "=";
                break;
            }
            c3 = str.charCodeAt(i++);
            out += base64EncodeChars.charAt(c1 >> 2);
            out += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
            out += base64EncodeChars.charAt(((c2 & 0xF) << 2) | ((c3 & 0xC0) >> 6));
            out += base64EncodeChars.charAt(c3 & 0x3F);
        }
        return out;
    }

    function base64decode(str) {
        var c1, c2, c3, c4;
        var i, len, out;

        len = str.length;
        i = 0;
        out = "";
        while (i < len) {
            /* c1 */
            do {
                c1 = base64DecodeChars[str.charCodeAt(i++) & 0xff];
            } while (i < len && c1 == -1);
            if (c1 == -1)
                break;

            /* c2 */
            do {
                c2 = base64DecodeChars[str.charCodeAt(i++) & 0xff];
            } while (i < len && c2 == -1);
            if (c2 == -1)
                break;

            out += String.fromCharCode((c1 << 2) | ((c2 & 0x30) >> 4));

            /* c3 */
            do {
                c3 = str.charCodeAt(i++) & 0xff;
                if (c3 == 61)
                    return out;
                c3 = base64DecodeChars[c3];
            } while (i < len && c3 == -1);
            if (c3 == -1)
                break;

            out += String.fromCharCode(((c2 & 0XF) << 4) | ((c3 & 0x3C) >> 2));

            /* c4 */
            do {
                c4 = str.charCodeAt(i++) & 0xff;
                if (c4 == 61)
                    return out;
                c4 = base64DecodeChars[c4];
            } while (i < len && c4 == -1);
            if (c4 == -1)
                break;
            out += String.fromCharCode(((c3 & 0x03) << 6) | c4);
        }
        return out;
    }

    if (!window.btoa) window.btoa = base64encode;
    if (!window.atob) window.atob = base64decode;

})();﻿/*
* Canvas2Image v0.1
* Copyright (c) 2008 Jacob Seidelin, jseidelin@nihilogic.dk
* MIT License [http://www.opensource.org/licenses/mit-license.php]
*/

var Canvas2Image = (function () {

    // check if we have canvas support
    var bHasCanvas = false;
    var oCanvas = document.createElement("canvas");
    if (!!oCanvas.getContext && !!oCanvas.getContext("2d")) {
        bHasCanvas = true;
    }

    // no canvas, bail out.
    if (!bHasCanvas) {
        return {
            saveAsBMP: function () { },
            saveAsPNG: function () { },
            saveAsJPEG: function () { }
        }
    }

    var bHasImageData = !!oCanvas.getContext && !!oCanvas.getContext("2d").getImageData;
    var bHasDataURL = !!(oCanvas.toDataURL);
    var bHasBase64 = !!(window.btoa);

    var strDownloadMime = "image/octet-stream";

    // ok, we're good
    var readCanvasData = function (oCanvas) {
        var iWidth = parseInt(oCanvas.width);
        var iHeight = parseInt(oCanvas.height);
        return oCanvas.getContext("2d").getImageData(0, 0, iWidth, iHeight);
    }

    // base64 encodes either a string or an array of charcodes
    var encodeData = function (data) {
        var strData = "";
        if (typeof data == "string") {
            strData = data;
        } else {
            var aData = data;
            for (var i = 0; i < aData.length; i++) {
                strData += String.fromCharCode(aData[i]);
            }
        }
        return btoa(strData);
    }

    // creates a base64 encoded string containing BMP data
    // takes an imagedata object as argument
    var createBMP = function (oData) {
        var aHeader = [];

        var iWidth = oData.width;
        var iHeight = oData.height;

        aHeader.push(0x42); // magic 1
        aHeader.push(0x4D);

        var iFileSize = iWidth * iHeight * 3 + 54; // total header size = 54 bytes
        aHeader.push(iFileSize % 256); iFileSize = Math.floor(iFileSize / 256);
        aHeader.push(iFileSize % 256); iFileSize = Math.floor(iFileSize / 256);
        aHeader.push(iFileSize % 256); iFileSize = Math.floor(iFileSize / 256);
        aHeader.push(iFileSize % 256);

        aHeader.push(0); // reserved
        aHeader.push(0);
        aHeader.push(0); // reserved
        aHeader.push(0);

        aHeader.push(54); // dataoffset
        aHeader.push(0);
        aHeader.push(0);
        aHeader.push(0);

        var aInfoHeader = [];
        aInfoHeader.push(40); // info header size
        aInfoHeader.push(0);
        aInfoHeader.push(0);
        aInfoHeader.push(0);

        var iImageWidth = iWidth;
        aInfoHeader.push(iImageWidth % 256); iImageWidth = Math.floor(iImageWidth / 256);
        aInfoHeader.push(iImageWidth % 256); iImageWidth = Math.floor(iImageWidth / 256);
        aInfoHeader.push(iImageWidth % 256); iImageWidth = Math.floor(iImageWidth / 256);
        aInfoHeader.push(iImageWidth % 256);

        var iImageHeight = iHeight;
        aInfoHeader.push(iImageHeight % 256); iImageHeight = Math.floor(iImageHeight / 256);
        aInfoHeader.push(iImageHeight % 256); iImageHeight = Math.floor(iImageHeight / 256);
        aInfoHeader.push(iImageHeight % 256); iImageHeight = Math.floor(iImageHeight / 256);
        aInfoHeader.push(iImageHeight % 256);

        aInfoHeader.push(1); // num of planes
        aInfoHeader.push(0);

        aInfoHeader.push(24); // num of bits per pixel
        aInfoHeader.push(0);

        aInfoHeader.push(0); // compression = none
        aInfoHeader.push(0);
        aInfoHeader.push(0);
        aInfoHeader.push(0);

        var iDataSize = iWidth * iHeight * 3;
        aInfoHeader.push(iDataSize % 256); iDataSize = Math.floor(iDataSize / 256);
        aInfoHeader.push(iDataSize % 256); iDataSize = Math.floor(iDataSize / 256);
        aInfoHeader.push(iDataSize % 256); iDataSize = Math.floor(iDataSize / 256);
        aInfoHeader.push(iDataSize % 256);

        for (var i = 0; i < 16; i++) {
            aInfoHeader.push(0); // these bytes not used
        }

        var iPadding = (4 - ((iWidth * 3) % 4)) % 4;

        var aImgData = oData.data;

        var strPixelData = "";
        var y = iHeight;
        do {
            var iOffsetY = iWidth * (y - 1) * 4;
            var strPixelRow = "";
            for (var x = 0; x < iWidth; x++) {
                var iOffsetX = 4 * x;

                strPixelRow += String.fromCharCode(aImgData[iOffsetY + iOffsetX + 2]);
                strPixelRow += String.fromCharCode(aImgData[iOffsetY + iOffsetX + 1]);
                strPixelRow += String.fromCharCode(aImgData[iOffsetY + iOffsetX]);
            }
            for (var c = 0; c < iPadding; c++) {
                strPixelRow += String.fromCharCode(0);
            }
            strPixelData += strPixelRow;
        } while (--y);

        var strEncoded = encodeData(aHeader.concat(aInfoHeader)) + encodeData(strPixelData);

        return strEncoded;
    }


    // sends the generated file to the client
    var saveFile = function (strData) {
        document.location.href = strData;
    }

    var makeDataURI = function (strData, strMime) {
        return "data:" + strMime + ";base64," + strData;
    }

    // generates a <img> object containing the imagedata
    var makeImageObject = function (strSource) {
        var oImgElement = document.createElement("img");
        oImgElement.src = strSource;
        return oImgElement;
    }

    var scaleCanvas = function (oCanvas, iWidth, iHeight) {
        if (iWidth && iHeight) {
            var oSaveCanvas = document.createElement("canvas");
            oSaveCanvas.width = iWidth;
            oSaveCanvas.height = iHeight;
            oSaveCanvas.style.width = iWidth + "px";
            oSaveCanvas.style.height = iHeight + "px";

            var oSaveCtx = oSaveCanvas.getContext("2d");

            oSaveCtx.drawImage(oCanvas, 0, 0, oCanvas.width, oCanvas.height, 0, 0, iWidth, iHeight);
            return oSaveCanvas;
        }
        return oCanvas;
    }

    return {

        saveAsPNG: function (oCanvas, bReturnImg, iWidth, iHeight) {
            if (!bHasDataURL) {
                return false;
            }
            var oScaledCanvas = scaleCanvas(oCanvas, iWidth, iHeight);
            var strData = oScaledCanvas.toDataURL("image/png");
            if (bReturnImg) {
                return makeImageObject(strData);
            } else {
                saveFile(strData.replace("image/png", strDownloadMime));
            }
            return true;
        },

        saveAsJPEG: function (oCanvas, bReturnImg, iWidth, iHeight) {
            if (!bHasDataURL) {
                return false;
            }

            var oScaledCanvas = scaleCanvas(oCanvas, iWidth, iHeight);
            var strMime = "image/jpeg";
            var strData = oScaledCanvas.toDataURL(strMime);

            // check if browser actually supports jpeg by looking for the mime type in the data uri.
            // if not, return false
            if (strData.indexOf(strMime) != 5) {
                return false;
            }

            if (bReturnImg) {
                return makeImageObject(strData);
            } else {
                saveFile(strData.replace(strMime, strDownloadMime));
            }
            return true;
        },

        saveAsBMP: function (oCanvas, bReturnImg, iWidth, iHeight) {
            if (!(bHasImageData && bHasBase64)) {
                return false;
            }

            var oScaledCanvas = scaleCanvas(oCanvas, iWidth, iHeight);

            var oData = readCanvasData(oScaledCanvas);
            var strImgData = createBMP(oData);
            if (bReturnImg) {
                return makeImageObject(makeDataURI(strImgData, "image/bmp"));
            } else {
                saveFile(makeDataURI(strImgData, strDownloadMime));
            }
            return true;
        }
    };

})();/* Flot plugin that adds a function to allow user save the current graph as an image
    by right clicking on the graph and then choose "Save image as ..." to local disk.

Copyright (c) 2013 http://zizhujy.com.
Licensed under the MIT license.

Usage:
    Inside the <head></head> area of your html page, add the following lines:
    
    <script type="text/javascript" src="http://zizhujy.com/Scripts/base64.js"></script>
    <script type="text/javascript" src="http://zizhujy.com/Scripts/drawing/canvas2image.js"></script>
    <script type="text/javascript" src="http://zizhujy.com/Scripts/flot/jquery.flot.saveAsImage.js"></script>

    Now you are all set. Right click on your flot canvas, you will see the "Save image as ..." option.

Online examples:
    http://zizhujy.com/FunctionGrapher is using it, you can try right clicking on the function graphs and
    you will see you can save the image to local disk.

Dependencies:
    This plugin references the base64.js and canvas2image.js.

Customizations:
    The default behavior of this plugin is dynamically creating an image from the flot canvas, and then puts the 
    image above the flot canvas. If you want to add some css effects on to the dynamically created image, you can
    apply whatever css styles on to it, only remember to make sure the css class name is set correspondingly by 
    the options object of this plugin. You can also customize the image format through this options object:

    options: {
        imageClassName: "canvas-image",
        imageFormat: "png"
    }

*/

; (function ($, Canvas2Image) {
    var imageCreated = null;
    var mergedCanvas = null;
    var theClasses = null;

    function init(plot, classes) {
        theClasses = classes;
        plot.hooks.bindEvents.push(bindEvents);
        plot.hooks.shutdown.push(shutdown);

        function bindEvents(plot, eventHolder) {
            eventHolder.mousedown(onMouseDown);
        }

        function shutdown(plot, eventHolder) {
            eventHolder.unbind("mousedown", onMouseDown);
        }

        function onMouseDown(e) {
            if (e.button == 2) {
                // Open an API in Canvas2Image, in case you would need to call
                // it to delete the dynamically created image.
                //Canvas2Image.deleteStaleCanvasImage = deleteStaleCanvasImage;
                deleteStaleCanvasImage(plot, mergedCanvas);
                mergedCanvas = mergeCanvases(plot);
                createImageFromCanvas(mergedCanvas, plot, plot.getOptions().imageFormat);
                // For ubuntu chrome:
                setTimeout(function () { deleteStaleCanvasImage(plot, mergedCanvas); }, 500);
            }
        }
    }

    function onMouseUp(plot) {
        setTimeout(function () { deleteStaleCanvasImage(plot, mergedCanvas); }, 100);
    }

    function deleteStaleCanvasImage(plot, mergedCanvas) {
        //$(plot.getCanvas()).parent().find("img." + plot.getOptions().imageClassName).unbind("mouseup", onMouseUp).remove();
        $(imageCreated).unbind("mouseup", onMouseUp).remove();
        if (!!mergedCanvas) {
            $(mergedCanvas).remove();
        }
        $(".mergedCanvas").remove();
    }
    
    function mergeCanvases(plot) {
        
        var theMergedCanvas = plot.getCanvas();

        if (!!theClasses) {
            theMergedCanvas = new theClasses.Canvas("mergedCanvas", plot.getPlaceholder());
            var mergedContext = theMergedCanvas.context;
            var plotCanvas = plot.getCanvas();
            
            theMergedCanvas.element.height = plotCanvas.height;
            theMergedCanvas.element.width = plotCanvas.width;
            
            mergedContext.restore();

            $(theMergedCanvas).css({
                "visibility": "hidden",
                "z-index": "-100",
                "position": "absolute"
            });

            var $canvases = $(plot.getPlaceholder()).find("canvas").not('.mergedCanvas');
            $canvases.each(function(index, canvas) {
                mergedContext.drawImage(canvas, 0, 0);
            });

            return theMergedCanvas.element;
        }

        return theMergedCanvas;
    }

    function createImageFromCanvas(canvas, plot, format) {
        if (!canvas) {
            canvas = plot.getCanvas();
        }
        
        var img = null;
        switch (format.toLowerCase()) {
            case "png":
                img = Canvas2Image.saveAsPNG(canvas, format);
                break;
            case "bmp":
                img = Canvas2Image.saveAsBMP(canvas, format);
                break;
            case "jpeg":
                img = Canvas2Image.saveAsJPEG(canvas, format);
                break;
            default:
                break;
        }

        if (!img) {
            img = Canvas2Image.saveAsPNG(canvas, "png");
        }

        if (!img) {
            img = Canvas2Image.saveAsPNG(canvas, "bmp");
        }

        if (!img) {
            img = Canvas2Image.saveAsJPEG(canvas, "jpeg");
        }

        if (!img) {
            alert(plot.getOptions().notSupportMessage || "Oh Sorry, but this browser is not capable of creating image files, please use PRINT SCREEN key instead!");
            return false;
        }

        $(img).attr("class", plot.getOptions().imageClassName);
        $(img).css({ "border": $(canvas).css("border"), "z-index": "9999", "position": "absolute" });
        $(img).insertBefore($(canvas));
        $(img).mouseup(plot, onMouseUp);

        imageCreated = img;
    }

    var options = {
        imageClassName: "canvas-image",
        imageFormat: "png"
    };

    $.plot.plugins.push({
        init: init,
        options: options,
        name: 'saveAsImage',
        version: '1.6'
    });

})(jQuery, Canvas2Image);
