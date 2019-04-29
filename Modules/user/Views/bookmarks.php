<h2><?php echo _("Bookmarks") ?></h2>
<p class="lead"><?php echo _("You can bookmark any page by clicking the star while on the page") ?></p>
<p><?php echo _("Bookmarked dashboards can be managed here") ?> 
<a href="<?php echo $path ?>dashboard/list" class="btn btn-default"><?php echo _('Dashboards') ?></a>
</p>
<?php if (!empty($bookmarks)) : ?>
<h4><?php echo _('Rename or remove your bookmarks') ?></h4>
<ul id="bookmarks" class="list-group" style="display: inline-block">
<?php foreach($bookmarks as $b): ?>
    <li href="#" class="list-group-item bookmark">
    <form class="form-inline mb-0" data-read>
        <div class="controls controls-row d-flex align-items-center">
            <input class="span3" data-mode-edit type="text" data-path="<?php echo $b['path'] ?>" value="<?php echo $b['text'] ?>">
            <button type="submit" data-mode-edit class="btn btn-primary ml-2"><?php echo _("Save") ?></button>
            <button type="button" data-cancel data-mode-edit class="btn btn-default ml-2"><?php echo _("Cancel") ?></button>

            <a class="span6 mb-0 ml-0" data-title title="<?php echo $path.$b['path'] ?>" href="<?php echo $path.$b['path'] ?>" data-mode-read><?php echo $b['text'] ?></a>
            <button type="button" data-delete data-mode-read class="btn btn-danger ml-2 pull-right" title="<?php echo _("Delete") ?>"><svg class="icon icon-bin"><use xlink:href="#icon-bin"></use></svg></button>
        </div>
    </form>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<style>
.list-group{
    display: -ms-flexbox;
    display: flex;
    -ms-flex-direction: column;
    flex-direction: column;
    padding-left: 0;
    margin-bottom: 0;
    margin-left: 0;
}
.list-group-item{
    position: relative;
    display: block;
    padding: .75rem 1.25rem;
    margin-bottom: -1px;
    background-color: #fff;
    border: 1px solid rgba(0,0,0,.125);
}
.list-group-item.active {
    z-index: 2;
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}
.list-group-item:first-child {
    border-top-left-radius: .25rem;
    border-top-right-radius: .25rem;
}
.list-group-item:last-child {
    margin-bottom: 0;
    border-bottom-right-radius: .25rem;
    border-bottom-left-radius: .25rem;
}

[data-read] [data-mode-edit] { 
    display: none!important;
}
[data-write] [data-mode-read] { 
    display: none!important;
}
</style>

<script>
var path = "<?php echo $path ?>";
$(function(){
    var path = "<?php echo $path ?>";
    // SHOW EDIT BOOKMARK FORM
    $(document).on('click', 'form[data-read] [data-title]', function(event) {
        event.preventDefault();
        editMode($(this).parents('form').first(), true);
    })
    // HIDE EDIT BOOKMARK FORM
    $(document).on('click', 'form[data-write] [data-cancel]', function(event) {
        editMode($(this).parents('form').first(), false);
    })
    // DELETE BOOKMARK
    $(document).on('click', 'form[data-read] [data-delete]', function(event) {
        var $form = $(this).parents('form').first();
        if(!confirm('Delete?')) return;
        var $input = $form.find('input');
        var bookmarkPath = $input.data('path');
        var bookmarkText = $input.val();
        var newBookmarks = [];

        $.get(path + 'user/preferences.json', {'preferences':'bookmarks'}, function(response){
            var bookmarks = [];
            // catch json parsing errors
            try{
                // url decimal decode database value
                var tmp = document.createElement('textarea');
                tmp.innerHTML = response;
                var decoded = tmp.value;
                // add user's prefs bookmarks to list
                bookmarks = JSON.parse(decoded);
            } catch(e) {
                console.error(e);
            }
            
            // remove the current bookmark (matched by path)
            for (b in bookmarks) {
                if (bookmarks[b]['path'] && bookmarks[b]['path'] != bookmarkPath) {
                    newBookmarks.push(bookmarks[b]);
                }
            }
            
            var data = {
                preferences : {
                    bookmarks: JSON.stringify(newBookmarks)
                }
            }
            // UPDATE USER PREFERENCES WITH NEW LIST OF BOOKMARKS
            $.post(path + 'user/preferences.json', data, function(response){
                if(response.success && response.success !== false) {
                    // IF RESPONDED WITH SUCCESSFUL MESSAGE
                    $form.parents('li').first().fadeOut(function(){
                        $(this).remove();
                    });
                    $('#sidebar_user_dropdown li a').each(function(n,elem){
                        if(elem.href===path+bookmarkPath) {
                            var $li = $(elem).parents('li').first();
                            $li.fadeOut(function(){
                                $(this).remove();
                                $('#set-bookmark, #remove-bookmark').parent().toggleClass('d-none');
                            });
                        }
                    })
                } else {
                    // @todo: show error
                }
            });
        });
    })

    // SAVE BOOKMARK EDITS
    $(document).on('submit', 'form[data-write]', function(event) {
        var $form = $(this);
        var $input = $form.find('input');
        var bookmarkPath = $input.data('path');
        var bookmarkText = $input.val();

        $.get(path + 'user/preferences.json', {'preferences':'bookmarks'}, function(response){
            var bookmarks = [];
            // catch json parsing errors
            try{
                // url decimal decode database value
                var tmp = document.createElement('textarea');
                tmp.innerHTML = JSON.stringify(response);
                var decoded = tmp.value;
                // add user's prefs bookmarks to list
                bookmarks = JSON.parse(decoded);
            } catch(e) {
                console.error(e);
            }
            // set the new title
            for (b in bookmarks) {
                if (bookmarks[b]['path'] && bookmarks[b]['path'] == bookmarkPath) {
                    bookmarks[b]['text'] = bookmarkText
                }
            }
            
            var data = {
                preferences : {
                    bookmarks: JSON.stringify(bookmarks)
                }
            }
            $.post(path + 'user/preferences.json', data, function(response){
                if(response.success && response.success !== false) {
                    $form.find('[data-title]').text(bookmarkText);
                    $('#sidebar_user_dropdown li a').each(function(n,elem){
                        if(elem.href===path+bookmarkPath) {
                            $(elem).fadeOut(function(){
                                $(this).text(bookmarkText).fadeIn();
                            })
                        }
                    })
                    editMode($form, false);
                } else {
                    // @todo: show error
                }
            });
        });
        event.preventDefault();
    })
})
function editMode($form, editMode) {
    if(editMode) {
        $form.removeAttr('data-read');
        $form.attr('data-write',true);
        $form.find('input[type="text"]').first().focus();
    }else{
        $form.removeAttr('data-write');
        $form.attr('data-read',true);
    }
}


</script>
