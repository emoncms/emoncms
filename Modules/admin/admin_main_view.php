<?php
    /**
     * View specific functions
     *
     */

    /**
     * Shutdown button
     */
    function ShutdownBtn(){
        return '<button id="haltPi" class="btn btn-danger btn-small">'._('Shutdown').'</button>';
    }
    /**
     * Reboot button
     */
    function RebootBtn(){
        return '<button id="rebootPi" class="btn btn-warning btn-small mr-1">'._('Reboot').'</button>';
    }

    /**
     * output a progress bar with the labels and summery below
     *
     * @param number $width
     * @param string $label
     * @param array $summary key/value pairs to show below the progress bar
     * @return string
     */
    function bar($width,$label,$summary) {
        $pattern = <<<eot
        <h5 class="m-0">%s</h5>
        <div class="progress progress-info mb-0">
            <div class="bar" style="width: %s%%"></div>
        </div>
eot;
        $markup = sprintf($pattern, $label, $width);
        $markup .= '<dl class="inline">';
        foreach($summary as $key=>$value) {
            $markup .= "<dt class=\"pl-0\">$key</dt><dd>$value</dd>";
        }
        $markup .= '</dl>';
        return $markup;
    }
    /**
     * return html for single admin page title/value row
     * @param string $title shown as the row title
     * @param string $value shown as the row value
     * @param string $title_css list of css classes to add to the title container
     * @param string $value_css list of css classes to add to the value container
     */
    function row($title, $value, $title_css = '', $value_css='') {
        return <<<listItem
        <dt class="col-sm-2 col-4 text-truncate {$title_css}">{$title}</dt>
        <dd class="col-sm-10 col-8 border-box px-1 {$value_css}">{$value}</dd>
listItem;
    }
?>
<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css?v=<?php echo $v ?>">


<h2><?php echo _('Administration'); ?></h2>

<div class="admin-container">
    <?php 
    // USERS 
    // -------------------
    ?>
    <section class="d-md-flex justify-content-between align-items-center pb-md-2 pb-md-0 text-right pb-2 px-1">
        <div class="text-left">
            <h3 class="mt-1 mb-0"><?php echo _('Users'); ?></h3>
            <p><?php echo _('See a list of registered users') ?></p>
        </div>
        <a href="<?php echo $path; ?>admin/users" class="btn btn-info"><?php echo _('Users'); ?></a>
    </section>

    <?php 
    // UPDATES 
    // -------------------
    ?>
    <?php if ($admin_show_update || $allow_emonpi_admin) { ?>
    <section class="d-md-flex justify-content-between align-items-center pb-md-2 border-top pb-md-0 text-right pb-2 px-1">
        <div class="text-left">
            <h3 class="mt-1 mb-0"><?php echo _('Updates'); ?></h3>
            <p><?php echo _('OS, Packages, EmonHub, Emoncms & Firmware (If new version)'); ?></p>
        </div>
        <div class="btn-group">
        <button class="update btn btn-info" title="<?php echo _('Update All'); ?> - <?php echo _('OS, Packages, EmonHub, Emoncms & Firmware (If new version)'); ?>">
            <?php echo _('Full Update'); ?>
        </button>
        <button class="btn dropdown-toggle btn-info" data-toggle="collapse" data-target="aside" title="<?php echo _(''); ?>">
            <span class="caret text-black"></span>
        </button>
        <!-- <button class="btn dropdown-toggle btn-info" data-toggle="dropdown">
            <span class="caret text-black"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
            <li><a href="#" title="<?php echo _('Emoncms, Emoncms Modules and Services'); ?>"><?php echo _('Update Emoncms Only'); ?></a></li>
            <li><a href="#" title=""><?php echo _('Update EmonHub Only'); ?></a></li>
            <li><a href="#" title="<?php echo _('Select your hardware type and firmware version'); ?>"><?php echo _('Update Firmware Only'); ?></a></li>
            <li><a href="#" title="<?php echo _('Run this after a manual emoncms update, after installing a new module or to check emoncms database status.'); ?>"><?php echo _('Update Database Only'); ?></a></li>
            <li class="divider"></li>
            <li><a href="#" class="update" title="<?php echo _('OS, Packages, EmonHub, Emoncms & Firmware (If new version)'); ?>"><strong><?php echo _('Update All'); ?></strong></a></li>
        </ul> -->
        </div>
    </section>
    
    <?php 
    // EMONCMS UPDATE
    // -------------------
    ?>
    <aside class="d-md-flex justify-content-between align-items-center pb-md-2 border-top pb-md-0 text-right pb-2 border-top px-1 collapse">
        <div class="text-left">
            <h4 class="text-info text-uppercase mb-2"><?php echo _('Update Emoncms Only'); ?></h4>
            <p><?php echo _('Emoncms, Emoncms Modules and Services'); ?></p>
            <p><b>Release info:</b> <a href="https://github.com/emoncms/emoncms/releases"> Emoncms</a></p>
        </div>
        <a class="update btn btn-info" type="emoncms"><?php echo _('Update Emoncms'); ?></a>
    </aside>

    <?php 
    // EMONHUB UPDATE
    // -------------------
    ?>
    <aside class="d-md-flex justify-content-between align-items-center pb-md-2 border-top pb-md-0 text-right pb-2 border-top px-1 collapse"">
        <div class="text-left">
            <h4 class="text-info text-uppercase mb-2"><?php echo _('Update EmonHub Only'); ?></h4>
            <p><b>Release info:</b> <a href="https://github.com/openenergymonitor/emonhub/releases"> EmonHub</a></p>
        </div>
        <a class="update btn btn-info" type="emonhub"><?php echo _('Update EmonHub'); ?></a>
    </aside>

    <?php 
    // EMONPI UPDATE
    // -------------------
    ?>
    <aside class="d-md-flex justify-content-between align-items-center pb-md-2 border-top pb-md-0 text-right pb-2 border-top px-1 collapse"">
        <div class="text-left">
            <h4 class="text-info text-uppercase mb-2"><?php echo _('Update Firmware Only'); ?></h4>
            <p><?php echo _('Select your hardware type and firmware version'); ?></p>
            <p><b>Release info:</b> <a href="https://github.com/openenergymonitor/emonpi/releases">emonPi</a> | <a href="https://github.com/openenergymonitor/RFM2Pi/releases">RFM69Pi</a></p>
        </div>
        <div class="input-append">
            <select id="selected_firmware">
                <option value="emonpi">EmonPi</option>
                <option value="rfm69pi">RFM69Pi</option>
                <option value="rfm12pi">RFM12Pi</option>
                <option value="custom">Custom</option>
            </select>
            <button class="update btn btn-info" type="firmware"><?php echo _('Update Firmware'); ?></button>
        </div>
    </aside>

    <?php 
    // DATABASE UPDATE
    // -------------------
    ?>
    <aside class="d-md-flex justify-content-between align-items-center pb-md-2 border-top pb-md-0 text-right pb-2 border-top px-1 collapse"">
        <div class="text-left span6 ml-0">
            <h4 class="text-info text-uppercase mb-2"><?php echo _('Update Database Only'); ?></h4>
            <p><?php echo _('Run this after a manual emoncms update, after installing a new module or to check emoncms database status.'); ?></p>
        </div>
        <a href="<?php echo $path; ?>admin/db" class="btn btn-info"><?php echo _('Update Database'); ?></a>
    </aside>

    <?php } ?>

    <?php
    // UPDATE LOG FILE VIEWER
    // -------------------
    if (is_file($update_log_filename)) { ?>
    <section class="d-md-flex justify-content-between align-items-center pb-md-2 text-right px-1 border-top">
        <div class="text-left">
            <h3 class="mt-1 mb-0"><?php echo _('Update Log'); ?></h3>
            <p><?php
            if(is_writable($update_log_filename)) {
                echo sprintf("%s <code>%s</code>",_('View last entries on the logfile:'), $update_log_filename);
            } else {
                echo '<div class="alert alert-warn">';
                echo sprintf('The log file has no write permissions or does not exists. To fix, log-on on shell and do: <pre style="height:3em;overflow:auto">touch %1$s<br>chmod 666 %1$s</pre>',$update_log_filename);
                echo "</div>";
            } ?></p>
        </div>
        <div>
            <?php if(is_writable($update_log_filename)) { ?>
                <button id="getupdatelog" type="button" class="btn btn-info mb-1" data-toggle="button" aria-pressed="false" autocomplete="off">
                    <?php echo _('Auto refresh'); ?>
                </button>
                <a href="<?php echo $path; ?>admin/emonpi/downloadupdatelog" class="btn btn-info mb-1"><?php echo _('Download Log'); ?></a>
                <button class="btn btn-info mb-1" id="copyupdatelogfile" type="button"><?php echo _('Copy Log to clipboard'); ?></button>
            <?php } ?>
        </div>
    </section>
    <pre id="update-log-bound"><div id="update-log"></div></pre>
    
    <?php } ?>

    <?php
    // LOG FILE VIEWER
    // -------------------
    if ($log_enabled) { ?>
    <section class="d-md-flex justify-content-between align-items-center pb-md-2 text-right px-1">
        <div class="text-left">
            <h3 class="mt-1 mb-0"><?php echo _('Emoncms Log'); ?></h3>
            <p><?php
            if(is_writable($log_filename)) {
                echo sprintf("%s <code>%s</code>",_('View last entries on the logfile:'),$log_filename);
            } else {
                echo '<div class="alert alert-warn">';
                echo "The log file has no write permissions or does not exists. To fix, log-on on shell and do:<br><pre>touch $log_filename<br>chmod 666 $log_filename</pre>";
                echo '<small></div>';
            } ?></p>
        </div>
        <div>
            <?php if(is_writable($log_filename)) { ?>
                <button id="getlog" type="button" class="btn btn-info mb-1" data-toggle="button" aria-pressed="false" autocomplete="off">
                    <?php echo _('Auto refresh'); ?>
                </button>
                <a href="<?php echo $path; ?>admin/downloadlog" class="btn btn-info mb-1"><?php echo _('Download Log'); ?></a>
                <button class="btn btn-info mb-1" id="copylogfile" type="button"><?php echo _('Copy Log to clipboard'); ?></button>
            <?php } ?>
        </div>
    </section>
    
    <section>
        <pre id="logreply-bound"><div id="logreply"></div></pre>
        <?php if(is_writable($path_to_config)) { ?>
        <div id="log-level" class="dropup btn-group">
            <a class="btn btn-small dropdown-toggle btn-<?php echo $log_level_css?> text-uppercase" data-toggle="dropdown" href="#" title="<?php echo _('Change the logging level') ?>">
            <span class="log-level-name"><?php echo sprintf('Log Level: %s', $log_level_label) ?></span>
            <span class="caret"></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <?php foreach ($log_levels as $key=>$value) {
                    $active = $key === $log_level ? ' active':'';
                    echo sprintf('<li><a href="#" data-key="%s" class="btn btn-%s %s">%s</a></li>', $key, $log_levels_css[$key], $active, $value);
                }?>
            </ul>
        </div>
        <?php } else { ?>
            <span id="log-level" class="btn-small dropdown-toggle btn-<?php echo $log_level_css?> text-uppercase">
                <?php echo sprintf('Log Level: %s', $log_level_label) ?>
            </span>
        <?php } ?>
    </section>
    <?php } ?>

    <?php 
    // SERVER INFO
    // -------------------
    ?>
    <div class="d-md-flex justify-content-between align-items-center pb-md-2 pb-md-0 pb-2 text-right px-1">
        <div class="text-left">
            <h3 class="mt-1 mb-0"><?php echo _('Server Information'); ?></h3>
        </div>
        <div>
            <button class="btn btn-info mr-1" id="copyserverinfo_md" type="button" title="<?php echo _('**Recommended** when pasting into forum')?>" data-success="<?php echo _('Server info copied to clipboard as Markdown [text/markdown]')?>"><?php echo _('Copy as Markdown'); ?></button>
            <button class="btn btn-info" id="copyserverinfo_txt" type="button" title="<?php echo _('Formatted as plain text')?>" data-success="<?php echo _('Server info copied to clipboard as Text [text/plain]')?>"><?php echo _('Copy as Text'); ?></button>
        </div>
    </div>

    <div id="serverinfo-container">
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('Services'); ?></h4>
        <dl class="row">
            <?php
            foreach ($services as $key=>$value):
                echo row(
                    sprintf('<span class="badge-%2$s badge"></span> %1$s', $key, $value['cssClass']),
                    sprintf('<strong>%s</strong> %s', $value['state'], $value['text'])
                );
            endforeach;
        ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('Emoncms'); ?></h4>
        <dl class="row">
            <?php echo row(_('Version'),$emoncms_version); ?>
            <?php echo row(_('Modules'), $emoncms_modules); ?>
            <?php
            $git_parts = array(
                row(_('URL'), $system['git_URL'],'','overflow-hidden'),
                row(_('Branch'), $system['git_branch']),
                row(_('Describe'), $system['git_describe'])
            );
            $git_details = sprintf('<dl class="row">%s</dl>',implode('', $git_parts));
        ?>
            <?php echo row(_('Git'), $git_details); ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('Server'); ?></h4>
        <dl class="row">
            <?php echo row(_('OS'), $system['system'] . ' ' . $system['kernel']); ?>
            <?php echo row(_('Host'), $system['host'] . ' | ' . $system['hostbyaddress'] . ' | (' . $system['ip'] . ')'); ?>
            <?php echo row(_('Date'), $system['date']); ?>
            <?php echo row(_('Uptime'), $system['uptime']); ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('Memory'); ?></h4>
        <dl class="row">
            <?php 
            echo row(_('RAM'), bar($ram_info['table'], sprintf(_('Used: %s%%'), $ram_info['percent']), array(
                'Total'=>$ram_info['total'],
                'Used'=>$ram_info['used'],
                'Free'=>$ram_info['free']
            )));
            if (!empty($ram_info['swap'])) {
                echo row(_('Swap'), bar($ram_info['swap']['table'], sprintf(_('Used: %s%%'), $ram_info['swap']['percent']), array(
                    'Total'=>$ram_info['swap']['total'],
                    'Used'=>$ram_info['swap']['used'],
                    'Free'=>$ram_info['swap']['free']
                )));
            }
            ?>
            
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('Disk'); ?></h4>
        <dl class="row">
            <?php 
            foreach($disk_info as $mount_info) {
                echo row($mount_info['mountpoint'], 
                    bar($mount_info['table'], sprintf(_('Used: %s%%'), $mount_info['percent']), array(
                        'Total'=>$mount_info['total'],
                        'Used'=>$mount_info['used'],
                        'Free'=>$mount_info['free']
                    ))
                );
            }
            ?>
        </dl>


        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('HTTP'); ?></h4>
        <dl class="row">
            <?php echo row(_('Server'), $system['http_server'] . " " . $system['http_proto'] . " " . $system['http_mode'] . " " . $system['http_port']); ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('MySQL'); ?></h4>
        <dl class="row">
            <?php echo row(_('Version'), $system['db_version']); ?>
            <?php echo row(_('Host'), $system['redis_server'] . ' (' . $system['redis_ip'] . ')'); ?>
            <?php echo row(_('Date'), $system['db_date']); ?>
            <?php echo row(_('Stats'), $system['db_stat']); ?>
        </dl>

        <?php if ($redis_enabled) : ?>
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('Redis'); ?></h4>
        <dl class="row">
            <?php echo row(_('Version'), $redis->info()['redis_version']); ?>
            <?php echo row(_('Host'), $system['redis_server']); ?>
            <?php 
            $redis_flush_btn = sprintf('<button id="redisflush" class="btn btn-info btn-small pull-right">%s</button>',_('Flush'));
            $redis_keys = sprintf('%s keys',$redis->dbSize());
            $redis_size = sprintf('(%s)',$redis->info()['used_memory_human']);
            echo row(sprintf('<span class="align-self-center">%s</span>',_('Size')), sprintf('<span id="redisused">%s %s</span>%s',$redis_keys,$redis_size,$redis_flush_btn),'d-flex','d-flex align-items-center justify-content-between'); ?>
            <?php echo row(_('Uptime'), sprintf(_("%s days"), $redis->info()['uptime_in_days'])); ?>
        </dl>
        <?php endif; ?>

        <?php if ($mqtt_enabled) : ?>
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('MQTT Server'); ?></h4>
        <dl class="row">
            <?php echo row(_('Version'), sprintf(_('Mosquitto %s'), $mqtt_version)) ?>
            <?php echo row(_('Host'), sprintf('%s:%s (%s)', $system['mqtt_server'], $system['mqtt_port'], $system['mqtt_ip'])); ?>
        </dl>
        <?php endif; ?>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('PHP'); ?></h4>
        <dl class="row">
        <?php echo row(_('Version'), $system['php'] . ' (' . "Zend Version" . ' ' . $system['zend'] . ')'); ?>
        <?php echo row(_('Modules'), implode(' | ', $php_modules), '', 'overflow-hidden'); ?>
        </dl>

        <?php if (!empty(implode('',$rpi_info))) : ?>
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('Pi'); ?></h4>
        <dl class="row">
            <?php echo row(sprintf('<span class="align-self-center">%s</span>',_('Model')), $rpi_info['model'].'<div>'.RebootBtn().ShutdownBtn().'</div>','d-flex','d-flex align-items-center justify-content-between') ?>
            <?php echo row(_('SoC'), $rpi_info['hw']) ?>
            <?php echo row(_('Serial num.'), strtoupper(ltrim($rpi_info['sn'], '0'))) ?>
            <?php echo row(_('Temperature'), sprintf('%s - %s', $rpi_info['cputemp'], $rpi_info['gputemp'])) ?>
            <?php echo row(_('emonpiRelease'), $rpi_info['emonpiRelease']) ?>
            <?php echo row(_('File-system'), $rpi_info['currentfs']) ?>
        </dl>
        <?php endif; ?>

    </div>

    <h3 class="mt-1 mb-0"><?php echo _('Client Information'); ?></h3>
    <div id="clientinfo-container">
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('HTTP'); ?></h4>
        <dl class="row">
            <?php echo row(_('Browser'), $_SERVER['HTTP_USER_AGENT']); ?>
            <?php echo row(_('Language'), $_SERVER['HTTP_ACCEPT_LANGUAGE']); ?>
        </dl>
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('Window'); ?></h4>
        <dl class="row">
            <?php echo row(_('Size'), '<span id="windowsize"><script>document.write($( window ).width() + " x " + $( window ).height())</script></span>'); ?>
        </dl>
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo _('Screen'); ?></h4>
        <dl class="row">
            <?php echo row(_('Resolution'), '<span id="screensize"><script>document.write(window.screen.width + " x " + window.screen.height);</script></span>'); ?>
        </dl>
    </div>

</div><!-- eof .admin-container -->

<div id="snackbar" class=""></div>


<script>
/**
 * return object of gettext translated strings
 *
 * @return object
 */
function getTranslations(){
    return {
        'Copied to clipboard': "<?php echo _('Copied to clipboard') ?>",
        'successful': "<?php echo _('successful') ?>",
        'unsuccessful': "<?php echo _('unsuccessful') ?>",
        'Copy to clipboard: Ctrl+C, Enter': "<?php echo _('Copy to clipboard: Ctrl+C, Enter') ?>",
        'Server Information': "<?php echo _('Server Information') ?>",
        'Client Information': "<?php echo _('Client Information') ?>",
        'Log level: %s': "<?php echo _('Log level: %s') ?>"
    }
}
</script>


<script>

function copyTextToClipboard(text, message) {
  var textArea = document.createElement("textarea");
  textArea.style.position = 'fixed';
  textArea.style.top = 0;
  textArea.style.left = 0;
  textArea.style.width = '2em';
  textArea.style.height = '2em';
  textArea.style.padding = 0;
  textArea.style.border = 'none';
  textArea.style.outline = 'none';
  textArea.style.boxShadow = 'none';
  textArea.style.background = 'transparent';
  textArea.value = text;
  document.body.appendChild(textArea);
  textArea.select();
  try {
    var successful = document.execCommand('copy');
    var msg = successful ? 'successful' : 'unsuccessful';
    console.log('Copying text command was ' + msg);
    snackbar(message || 'Copied to clipboard');
  } 
  catch(err) {
    window.prompt("<?php echo _('Copy to clipboard: Ctrl+C, Enter'); ?>", text);
  }
  document.body.removeChild(textArea);
}

/**
 * attempt to convert markdown (text/markdown) to a text/plain
 * @todo: look at a better library to do this
 */
function markdownStringify(md) {
    return md
    // indent values
    .replace(/\:-/g,':\t')
    // start titles on new lines
    .replace(/- \*\*/g,'\n\t')
    // remove bold
    .replace(/\*\*/g,'')
    // remove heading
    .replace(/##(.)/gm,'\n')
    // remove orphan new lines
    .replace(/\n /g,'')
    // replace unrequired whitespace
    .replace(/\n{2,}/g,'\n\n')
    .replace(/\s{4}/g,'\n\t')
    .replace(/\s{2,}\n/gm,'\n')
}
/**
 * attempt to convert <html> to markdown (text/markdown)
 *
 * uses `====` as temp tab placeholder
 * uses `~~~~` as temp newline placeholder
 * @todo: look at a better library to do this
 */
function markdownify(markup) {
    var newline = '~~~~';
    var indent = '====';

    var newlineRegex = new RegExp(newline,'g');
    var indentRegex = new RegExp(indent,'g');

    // add placeholder for nested <dl> <dd> <dl>
    let $temp = $('<div>');
    $temp.html(markup);

    // add correct indentation to nested lists
    $temp.find('dl').each(function(i, parent){
        let $parent = $(parent);
        console.log($parent.find('dl').length,$parent.find('dt').first().get())
        if(!$parent.is('.inline')) {
            $(parent).find('dl').each(function(i, child){
                let $list = $(child);
                let $firstTitle = $list.find('dt').first();
                $firstTitle.before(newline)
                $list.find('dt').each(function(j, title){
                    let $title = $(title)
                    $title.before(indent)
                })
            })
        }
    })

    // -----

    // use modified <html> source to replace patterns
    markup = $temp.html()

    return markup
    // remove indenting
    .replace(/^\s{2,}/gm," ")
    // remove buttons
    .replace(/<\/?button.[\s\S]*?button./g,'')
    // remove html space
    .replace(/&nbsp;/g,'')
    // remove comments
    .replace(/<!--[\S\s]*-->/g,'')
    // replace <h4> with markdown level two heading (##)
    .replace(/\s+<h4 *[^/]*?>/g, newline+"## ")
    .replace(/<\/h4>\s+/g,"\n")
    .replace(/<dd class="__inline__">/g,'    ')
    // remove <dl>
    .replace(/<dl *[^/]*?>/g," ")   
    .replace(/<\/dl>[ \n]/g,"\n" + newline)
    // remove <dt>
    .replace(/<dt *[^/]*?>/g,' - **')
    .replace(/<\/dt>[ \n]*/g,'**')
    // remove <dd>
    .replace(/<dd *[^/]*?>/g,' :- ')
    .replace(/<\/dd>/g,"\n")
    // remove all other <tags>
    .replace(/(<([^>]+)> *)/ig,'')
    // remove all indenting
    .replace(/^ {2,}/gm," ")
    // remove orphan new lines
    .replace(/\n{3,}/g,"\n\n")
    // remove all orphan lines with single space
    .replace(/^ (\S)/gm,"$1")
    // replace indent placeholder
    .replace(indentRegex,'    ')
    // remove orphan new lines
    .replace(/\n \n ?\n/g, '\n')
    // remove leading spaces from values
    .replace(/:-\s+/g,':- ')
    // rplace newline placeholder
    .replace(newlineRegex,"\n")

    .trim()
}
/**
 * Return the Server information section as text/markdown
 *
 * @return string
 */
function getServerInfoDetails() {
    var markup = $('#serverinfo-container').html()
    return markdownify(markup);
}
/**
 * Return the Client information section as text/markdown
 *
 * @return string
 */
function getClientInfoDetails() {
    var temp = $('<div>')
    temp.html($('#clientinfo-container').html())
    temp.find('#windowsize').html($( window ).width() + " x " + $( window ).height())
    temp.find('#screensize').html(window.screen.width + " x " + window.screen.height)
    return markdownify(temp.html());
}
// format <html> as tabbed text (text/plain) and copy to system clipboard
$("#copyserverinfo_txt").on('click', function(event) {
    copyTextToClipboard(
        'Server Information\n-----------------------\n' + 
        markdownStringify(getServerInfoDetails()) +
        '\n\nClient Information\n-----------------------\n' + 
        markdownStringify(getClientInfoDetails()),
        event.target.dataset.success
    );
});

// format <html> as markdown (text/markdown) and copy to system clipboard
$("#copyserverinfo_md").on('click', function(event) {
    copyTextToClipboard(
        '<details><summary>Server Information</summary>\n' + 
        '\n# Server Information\n' + 
        getServerInfoDetails().replace(/\n+/g, '\n') + 
        '\n</details>' + 
        '\n\n<details><summary>Client Information</summary>\n' + 
        '\n# Client Information\n' + getClientInfoDetails() +
        '\n</details>',
        this.dataset.success
    );
});

// copy value (and title) to clipboard when clicked
$("dd").on('click', function(event) {
    let $this = $(this),
        title = $this.prev('dt').text().trim(),
        markup = $this.clone().find('script').remove().end().html()
        value = markdownStringify(markdownify(markup));
        
    copyTextToClipboard(
        [title, value].join(': '),
        _('Copied to clipboard')
    );
});
// copy title (and value) to clipboard when clicked
$("dt").on('click', function(event) {
    let $this = $(this),
        title = $this.text().trim(),
        markup = $this.next('dd').clone().find('script').remove().end().html()
        value = markdownStringify(markdownify(markup));
        
    copyTextToClipboard(
        [title, value].join(': '),
        _('Copied to clipboard')
    );
});
/**
 * wrapper for gettext like string replace function
 */
function _(str) {
    return translate(str);
}
/**
 * emulate the php gettext function for replacing php strings in js
 */
function translate(property) {
    _strings = typeof translations === 'undefined' ? getTranslations() : translations;
    if (_strings.hasOwnProperty(property)) {
        return _strings[property];
    } else {
        return property;
    }
}

var logFileDetails;
$("#copylogfile").on('click', function(event) {
    logFileDetails = $("#logreply").text();
    if ( event.ctrlKey ) {
        copyTextToClipboard('LAST ENTRIES ON THE LOG FILE\n'+logFileDetails,
        event.target.dataset.success);
    } else {
        copyTextToClipboard('<details><summary>LAST ENTRIES ON THE LOG FILE</summary><br />\n'+ logFileDetails.replace(/\n/g,'<br />\n').replace(/API key '[\s\S]*?'/g,'API key \'xxxxxxxxx\'') + '</details><br />\n',
        event.target.dataset.success);
    }
} );

$("#copyupdatelogfile").on('click', function(event) {
    logFileDetails = $("#update-log").text();
    if ( event.ctrlKey ) {
        copyTextToClipboard('LAST ENTRIES ON THE UPDATE LOG FILE\n'+logFileDetails,
        event.target.dataset.success);
    } else {
        copyTextToClipboard('<details><summary>LAST ENTRIES ON THE LOG FILE</summary><br />\n'+ logFileDetails.replace(/\n/g,'<br />\n').replace(/API key '[\s\S]*?'/g,'API key \'xxxxxxxxx\'') + '</details><br />\n',
        event.target.dataset.success);
    }
} );

$(window).resize(function() {
  $("#windowsize").html( $(window).width() + " x " + $(window).height() );
});
var logrunning = false;
var updatelogrunning = false;
<?php if ($feed_settings['redisbuffer']['enabled']) { ?>
  getBufferSize();
<?php } ?>
function getBufferSize() {
  $.ajax({ url: path+"feed/buffersize.json", async: true, dataType: "json", success: function(result)
    {
      var data = JSON.parse(result);
      $("#bufferused").html( data + " feed points pending write");
    }
  });
}

// setInterval() markers
var updates_log_interval;
var emoncms_log_interval;

// stop updates if interval == 0
function refresherStart(func, interval){
    if (interval > 0) return setInterval(func, interval);
}

// push value to updates logfile viewer
function refresh_updateLog(result){
    output_logfile(result, $("#update-log"));
}
// push value to emoncms logfile viewer
function refresh_log(result){
    output_logfile(result, $("#logreply"));
}
// display content in container and scroll to the bottom
function output_logfile(result, $container){
    $container.html(result);
    scrollable = $container.parent('pre')[0];
    if(scrollable) scrollable.scrollTop = scrollable.scrollHeight;
}

getLog();
// use the api to get the latest value from the logfile
function getLog() {
  $.ajax({ url: path+"admin/getlog", async: true, dataType: "text", success: refresh_log });
}

// auto refresh the updates logfile
$("#getupdatelog").click(function() {
    $this = $(this)
    if ($this.is('.active')) {
        clearInterval(updates_log_interval);
    } else {
        updates_log_interval = refresherStart(getUpdateLog, 500); 
    }
});
// auto refresh the updates logfile
$("#getlog").click(function() {
    $this = $(this)
    if ($this.is('.active')) {
        clearInterval(emoncms_log_interval);
    } else {
        emoncms_log_interval = refresherStart(getLog, 500); 
    }
});

// update all button clicked
$(".update").click(function() {
  var type = $(this).attr("type");
  var firmware = $("#selected_firmware").val();
  $.ajax({ type: "POST", url: path+"admin/emonpi/update", data: "type="+type+"&firmware="+firmware, async: true, success: function(result)
    {
      // update with latest value
      refresh_updateLog(result);
      // autoupdate every 1s
      updates_log_interval = refresherStart(getUpdateLog, 1000)
    }
  });
});

$("#rfm69piupdate").click(function() {
  $.ajax({ type: "POST", url: path+"admin/emonpi/update", data: "argument=rfm69pi", async: true, success: function(result)
    {
      // update with latest value
      refresh_updateLog(result);
      // autoupdate every 1s
      updates_log_interval = refresherStart(getUpdateLog, 1000)
    }
  });
});
// shrink log file viewers
$('[data-dismiss="log"]').click(function(event){
    event.preventDefault();
    $(this).parents('pre').first().addClass('small');
})
getUpdateLog();
function getUpdateLog() {
  $.ajax({ url: path+"admin/emonpi/getupdatelog", async: true, dataType: "text", success: function(result)
    {
      refresh_updateLog(result);
      if (result.indexOf("emonPi update done")!=-1) {
          clearInterval(updates_log_interval);
      }
    }
  });
}

$("#redisflush").click(function() {
  $.ajax({ url: path+"admin/redisflush.json", async: true, dataType: "text", success: function(result)
    {
      var data = JSON.parse(result);
      $("#redisused").html(data.dbsize+" keys ("+data.used+")");
    }
  });
});

$("#haltPi").click(function() {
  if(confirm('Please confirm you wish to shutdown your Pi, please wait 30 secs before disconnecting the power...')) {
    $.post( location.href, { shutdownPi: "halt" } );
  }
});

$("#rebootPi").click(function() {
  if(confirm('Please confirm you wish to reboot your Pi, this will take approximately 30 secs to complete...')) {
    $.post( location.href, { shutdownPi: "reboot" } );
  }
});

$("#noshut").click(function() {
  alert('Please modify /etc/sudoers to allow your webserver to run the shutdown command.')
});

// $("#fs-rw").click(function() {
//   if(confirm('Setting file-system to Read-Write, remember to restore Read-Only when your done..')) {
//     $.ajax({ type: "POST", url: path+"admin/emonpi/fs", data: "argument=rw", async: true, success: function(result)
//       {
//         // console.log(data);
//       }
//     });
//   }
// });

// $("#fs-ro").click(function() {
//   if(confirm('Settings filesystem back to Read Only')) {
//     $.ajax({ type: "POST", url: path+"admin/emonpi/fs", data: "argument=ro", async: true, success: function(result)
//       {
//       // console.log(data);
//       }
//     });
//   }
// });

$('#log-level ul li a').click(function(event){
    event.preventDefault();
    var $btn = $(this);
    var $toggle = $btn.parents('ul').prev('.btn');
    var key = $btn.data('key');
    var data = {level:key};
    $.post( path+"admin/loglevel.json",data)
    .done(function(response) {
        // make the dropdown toggle show the new setting
        if(response.hasOwnProperty('success') && response.success!==false) {
            $toggle.removeClass('btn-warning btn-danger btn-inverse').addClass('btn-'+response['css-class']);
            $toggle.find('.log-level-name').text(_('log level: %s').replace('%s',response['log-level-name']));
            // highlight the current dropdown element as active
            $btn.addClass('active').siblings().removeClass('active');
            notify(_('Log level set to: %s').replace('%s',response['log-level-name']),'success');
        } else {
            notify(_('Log level not set'), 'error', response.hasOwnProperty('message') ? response.message: '');
        }
    });
})

function snackbar(text) {
    var snackbar = document.getElementById("snackbar");
    snackbar.innerHTML = text;
    snackbar.className = "show";
    setTimeout(function () {
        snackbar.className = snackbar.className.replace("show", "");
    }, 3000);
}

function notify(message, css_class, more_info) {
    // @todo: show more information in the user notifications
    snackbar(message);
}
</script>
