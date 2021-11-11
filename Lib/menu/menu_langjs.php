<?php bindtextdomain("lib_messages",__DIR__ . "/../../Lib/locale"); ?>

<script type="text/javascript">
// Create a Javascript associative array who contain sentences from menu
var LANG_JS_MENU = new Array();
LANG_JS_MENU["Expand sidebar"] = '<?php echo addslashes(dgettext('lib_messages','Expand sidebar')); ?>';
LANG_JS_MENU["Minimise sidebar"] = '<?php echo addslashes(dgettext('lib_messages','Minimise sidebar')); ?>';
function _Tr_Menu(key)
{
<?php // will return the default value if LANG_JS[key] is not defined. ?>
    return LANG_JS_MENU[key] || key;
}
</script>