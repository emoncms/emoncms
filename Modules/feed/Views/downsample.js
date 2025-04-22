
/**
 * Downsample modal
 */
$(".feed-downsample").click(function(){
    $('#downsampleModal').modal('show');
    $("#downsample-confirm").show();
    $(".downsample-options").show();
    $("#downsample-finish").hide();
    $("#downsample-alert").hide();
    $(".downsample-new-interval").hide();

    // populate downsample-feeds table 
    var interval_max = 0;
    var out = "";
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid] == true && feeds[feedid].engine == 5) {
            out += "<tr>";
            out += "<td>"+feeds[feedid].tag+"</td>";
            out += "<td>"+feeds[feedid].name+"</td>";
            out += "<td>"+feeds[feedid].interval+"s</td>";
            out += "</tr>";

            if (feeds[feedid].interval > interval_max) {
                interval_max = feeds[feedid].interval;
            }
        }
    }
    $("#downsample-feeds").html(out);

    var first_selected = false;

    // Hide downsample-interval options that are less than the maximum interval
    $("#downsample-interval option").each(function() {
        var option_interval = parseInt($(this).val());
        if (parseInt($(this).val()) <= interval_max || option_interval % interval_max !== 0) {
            $(this).hide();
        } else {
            $(this).show();
            if (!first_selected) {
                $(this).prop('selected', true);
                first_selected = true;
            }
        }

        if (interval_max == 10) {
            $("#downsample-interval option[value='60']").prop('selected', true);
        }

        if (interval_max == 60) {
            $("#downsample-interval option[value='300']").prop('selected', true);
        }
    });
});

// downsample confirm button 
$("#downsample-confirm").click(function(){
    if( confirm("This action is irreversible. Are you sure you want to downsample?") == true) {
        var interval = $("#downsample-interval").val();

        // http://localhost/emoncms/postprocess/create
        // formdata: [{"feed":"2077","new_interval":"60","backup":false,"process_mode":"all","process":"downsample"}]


        var processes = [];
        for (var feedid in selected_feeds) {
            if (selected_feeds[feedid] == true && feeds[feedid].engine == 5) {
                processes.push({
                    feed: feedid,
                    new_interval: interval,
                    process_mode: "all",
                    process: "downsample"
                });
            }
        }

        if (processes.length == 0) {
            alert('No feeds selected');
            return;
        }

        $.ajax({
            url: path+"postprocess/create",
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(processes),
            success: function(response){
                if (!response.success) {
                    alert('Error: '+response.message);
                } else {
                    var count = response.count;
                    if (count) {

                        var out = "";
                        for (var feedid in selected_feeds) {
                            if (selected_feeds[feedid] == true && feeds[feedid].engine == 5) {
                                out += "<tr>";
                                out += "<td>"+feeds[feedid].tag+"</td>";
                                out += "<td>"+feeds[feedid].name+"</td>";
                                out += "<td>"+feeds[feedid].interval+"s</td>";
                                out += "<td>"+interval+"s</td>";
                                out += "</tr>";
                            }
                        }
                        $("#downsample-feeds").html(out);
                        $(".downsample-new-interval").show();


                        if (count == 1) {
                            $("#downsample-alert").html("1 feed added to downsample queue. This may take a few minutes to complete.");
                        } else {
                            $("#downsample-alert").html(count+" feeds added to downsample queue. This may take a few minutes to complete.");
                        }
                    } else {
                        $("#downsample-alert").html("No feeds downsampled");
                    }

                    $("#downsample-confirm").hide();
                    $(".downsample-options").hide();
                    $("#downsample-finish").show();
                    $("#downsample-alert").show();
                }
            }
        });

        // hide the modal
        // $('#downsampleModal').modal('hide');
    }
});
