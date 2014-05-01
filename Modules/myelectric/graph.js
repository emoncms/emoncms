var graph = {

    element: false,
    ctx: false,
    
    // Pixel width and height of graph
    width: 200,
    height: 200,
    
    
    draw: function(element,series) {
    
        // Initialise the canvas get context
        if (!ctx) 
        {
            this.element = element;
            var c = document.getElementById(element);  
            this.ctx = c.getContext("2d");
        }
        
        var ctx = this.ctx;
        
        // Clear canvas
        ctx.clearRect(0,0,this.width,this.height);
        
        // OEM Blue
        ctx.strokeStyle = "#0699fa";
        ctx.fillStyle = "#0699fa";
        
        // Axes
        ctx.moveTo(0,0);
        ctx.lineTo(0,this.height);
        ctx.lineTo(this.width,this.height);
        ctx.stroke();
        
        // Axes label
        ctx.textAlign    = "left";
        ctx.font = "16px arial";
        ctx.fillText('kWh',10,15);
        
        // find out max and min values of data
        
        var xmin = undefined;
        var xmax = undefined;
        var ymin = undefined;
        var ymax = undefined;
        
        for (s in series)
        {
            var data = series[s];
            for (z in data)
            {
                if (xmin==undefined) xmin = data[z][0];
                if (xmax==undefined) xmax = data[z][0];
                if (ymin==undefined) ymin = data[z][1];
                if (ymax==undefined) ymax = data[z][1];
                            
                if (data[z][1]>ymax) ymax = data[z][1];
                if (data[z][1]<ymin) ymin = data[z][1];
                if (data[z][0]>xmax) xmax = data[z][0];
                if (data[z][0]<xmin) xmin = data[z][0];               
            }
        }
        var r = (ymax - ymin);
        ymin = (ymin + (r / 2)) - (r/1.5);
        ymin = 0;
        
        ymax = (ymax - (r / 2)) + (r/1.5);
        
        xmin -= 3600000*14;
        xmax += 3600000*14;
        
        var scale = 1;
        
        for (s in series)
        {
            var data = series[s]; 
            for (z in data)
            {
            
                var x = ((data[z][0] - xmin) / (xmax - xmin)) * this.width;
                var y = this.height - (((data[z][1] - ymin) / (ymax - ymin)) * this.height);
                  
                //if (z==0) ctx.moveTo(x,y); else ctx.lineTo(x,y);   
                  
                var barwidth = ((3600000*20) / (xmax - xmin)) * this.width;
                
                ctx.fillStyle = "#0699fa";
                ctx.fillRect(x-(barwidth/2),y-7,barwidth,this.height-y);
                  
                              // Text is too small if less than 2kWh
                if ((this.height-y)>25) {
                    ctx.textAlign    = "center";
                    ctx.fillStyle = "#ccccff";
                    ctx.fillText((data[z][1]*scale).toFixed(0),x,y+20-7);
                }
            }
            ctx.stroke();
        }
        
        
        /*
        var data = series[0];
        
        ctx.beginPath();
        for (var z=0; z<data.length; z++) {
          var x = ((data[z][0] - xmin) / (xmax - xmin)) * this.width;
          var y = this.height - (((data[z][1] - ymin) / (ymax - ymin)) * this.height);
          if (z==0) ctx.moveTo(x,y); else ctx.lineTo(x,y);   
        }
        
        var data = series[1];
        for (var z=data.length-1; z>=0; z--) {
          var x = ((data[z][0] - xmin) / (xmax - xmin)) * this.width;
          var y = this.height - (((data[z][1] - ymin) / (ymax - ymin)) * this.height);
          ctx.lineTo(x,y);   
        }
        ctx.closePath();
        ctx.strokeStyle = "rgba(6,153,250,1)";
        ctx.stroke();
        ctx.fillStyle = "rgba(6,153,250,0.5)";
        ctx.fill();
        
        ctx.beginPath();
        var data = series[1];
        for (var z=data.length-1; z>=0; z--) {
          var x = ((data[z][0] - xmin) / (xmax - xmin)) * this.width;
          var y = this.height - (((data[z][1] - ymin) / (ymax - ymin)) * this.height);
          ctx.lineTo(x,y);   
        }
        ctx.lineTo(0,this.height);
        ctx.lineTo(this.width,this.height);
        
        
        ctx.closePath();
        ctx.strokeStyle = "rgba(6,153,250,0.2)";
        ctx.stroke();
        ctx.fillStyle = "rgba(6,153,250,0.3)";
        ctx.fill();
        */
    
    }

};
