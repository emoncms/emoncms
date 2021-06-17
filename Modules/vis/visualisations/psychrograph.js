//xmin and xmax are numbers
//ymin and ymax are functions
function Zone(xmin,xmax,ymin,ymax){
    this.xmin=xmin;
    this.xmax=xmax;
    this.ymin=ymin;
    this.ymax=ymax;
}

//outline method to draw the zone
Zone.prototype.outline = function(){
    var XY =[];
    var pas = 0.5;
    var x=this.xmin;var i=0;
    while (x<this.xmax){
        XY[i]=[];
        XY[i][0]=x;XY[i][1]=this.ymin(x);x+=pas;i+=1;
    }
    XY[i]=[];
    XY[i][0]=this.xmax;XY[i][1]=this.ymin(this.xmax);
    i+=1;
    while (x>=this.xmin){
        XY[i]=[];
        XY[i][0]=x;XY[i][1]=this.ymax(x);x-=pas;i+=1;
    }
    XY[i]=[];
    XY[i][0]=this.xmin;XY[i][1]=this.ymin(this.xmin);
    return XY;
};

//test if a point is inside the zone
Zone.prototype.includes = function(x,y){
    if (x<this.xmin) {return false;}
    if (x>this.xmax) {return false;}
    if (y<this.ymin(x)) {return false;}
    if (y>this.ymax(x)) {return false;}
    return true;
};
