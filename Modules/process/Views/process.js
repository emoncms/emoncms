
var process = {

    'list':function()
    {
        var result = {};
        $.ajax({ url: path+"process/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
    }

}