<?php 

global $path;
$device = $_GET['node'];

?>

<style>

.box {
    padding:20px;
    background-color:#f6f6f6;
    border: 1px solid #ddd;
}

.saved { color:#888 };

</style>

<div style="height:20px"></div>

<div class="box">
    <h2 id="devicename"></h2>
    <div id="controls"></div>
    <p class="saved hide">Saved</p>
    <button id="save" class="btn hide">Save</button>
</div>

<script>

var path = "<?php echo $path; ?>";
var device = "<?php echo $device; ?>";
$("#devicename").html(jsUcfirst(device));

var controls = {};

$.ajax({ url: path+"device/gettemplate.json?device="+device, dataType: 'json', async: true, success: function(template) { 
    controls = template.control;
    draw_controls();
    update();
    
    $("input[type=text]").keyup(function(){ $("#save").show(); $(".saved").hide(); });
    $("input[type=checkbox]").change(function(){ $("#save").show(); $(".saved").hide(); });

}});

// -------------------------------------------------------------------------


$("#save").click(function(){

    var tosave = {};
    for (var property in controls) {
        if (controls[property].type=="text") 
            tosave[property] = $("input[name='"+property+"']").val();
        if (controls[property].type=="checkbox") 
            tosave[property] = 1*$("input[name='"+property+"']")[0].checked;
    }
    save(tosave);
    $("#save").hide();
});

// -------------------------------------------------------------------------

function draw_controls() {
    var out = "";
    for (var property in controls) {
        if (controls[property].type=="text")
            out += "<p>"+jsUcfirst(property)+":<br><input type='text' name='"+property+"' value='"+controls[property].default+"' /></p>";
        if (controls[property].type=="checkbox")
            out += "<p>"+jsUcfirst(property)+": <input type='checkbox' name='"+property+"' value='"+controls[property].default+"' /></p>";
    }
    $("#controls").html(out);
}

function update() {
    $.ajax({ url: path+"input/get/"+device, dataType: 'json', async: true, success: function(data) {
        inputs = data;
        for (var property in controls) {
            if (controls[property].type=="text" && inputs[property]!=undefined) 
                $("input[name='"+property+"']").val(inputs[property].value);
            if (controls[property].type=="checkbox" && inputs[property]!=undefined) 
                $("input[name='"+property+"']")[0].checked = inputs[property].value;
        }
    }});
}

function save(data) {
    $.ajax({ url: path+"input/post/"+device+"?data="+JSON.stringify(data)+"&mqttpub=1", dataType: 'text', async: true, success: function(result) {
        if (result=="ok") $(".saved").show();
    }});
}

function jsUcfirst(string) {return string.charAt(0).toUpperCase() + string.slice(1);}

</script>
