<?php
defined('EMONCMS_EXEC') or die('Restricted access');
    /**
     * View specific functions
     *
     */

    /**
     * Shutdown button
     */
    function ShutdownBtn(){
        return '<button id="haltPi" class="btn btn-danger btn-small">'.tr('Shutdown').'</button>';
    }
    /**
     * Reboot button
     */
    function RebootBtn(){
        return '<button id="rebootPi" class="btn btn-warning btn-small mr-1">'.tr('Reboot').'</button>';
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
<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css?v=1">

<div class="admin-container">

<?php if (PHP_VERSION_ID<70300) { ?>
<div class="alert alert-error" style="text-align:left"><b>Important:</b> PHP version <?php echo PHP_VERSION; ?> detected. Please update to version 7.3 or newer to keep your installation secure.<br>This emoncms installation is running in compatibility mode and does not include all of the latest security improvements.<br>See guide on updating php on the emoncms github: <a href="https://github.com/emoncms/emoncms/issues/1726">Updating PHP.</a></div>
<?php } ?>

    <?php
    // SERVER INFO
    // -------------------
    ?>
    <div class="d-md-flex justify-content-between align-items-center pb-md-2 pb-md-0 pb-2 text-right px-1">
        <div class="text-left">
            <h3 class="mt-1 mb-0"><?php echo tr('System Information'); ?></h3>
        </div>
        <div>
            <button class="btn btn-info mr-1" id="copyserverinfo_md" type="button" title="<?php echo tr('**Recommended** when pasting into forum')?>" data-success="<?php echo tr('Server info copied to clipboard as Markdown [text/markdown]')?>"><?php echo tr('Copy as Markdown'); ?></button>
            <button class="btn btn-info" id="copyserverinfo_txt" type="button" title="<?php echo tr('Formatted as plain text')?>" data-success="<?php echo tr('Server info copied to clipboard as Text [text/plain]')?>"><?php echo tr('Copy as Text'); ?></button>
        </div>
    </div>



    <div id="serverinfo-container">
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('Services'); ?></h4>
        <dl class="row">
            <?php foreach ($services as $key=>$value) { ?>
                <dt class="col-sm-2 col-4 text-truncate"><span class="badge-<?php echo $value['cssClass']; ?> badge"></span> <?php echo $key; ?></dt>
                <dd class="col-sm-10 col-8 border-box px-1">
                  <?php if ($value['loadstate']=="Loaded") { ?>
                      <strong><?php echo $value['state']; ?></strong> <?php echo $value['text']; ?>
                      <div class="btn-group" role="group" style="float:right">

                      <?php if ($value["unitfilestate"]!="container") { ?>
                          <?php if ($value['unitfilestate']!="disabled" && $value['state']!="Active") { ?>
                          <button class="btn btn-small btn-success service-action" service_action="start" service_key="<?php echo $key; ?>">Start</button>
                          <?php } ?>

                          <?php if ($value['state']=="Active") { ?>
                          <button class="btn btn-small btn-danger service-action" service_action="stop" service_key="<?php echo $key; ?>">Stop</button>
                          <button class="btn btn-small btn-warning service-action" service_action="restart" service_key="<?php echo $key; ?>">Restart</button>
                          <?php } ?>

                          <?php if ($value['unitfilestate']=="disabled") { ?>
                          <button class="btn btn-small btn-primary service-action" service_action="enable" service_key="<?php echo $key; ?>">Enable</button>
                          <?php } elseif ($value['state']!="Active") { ?>
                          <button class="btn btn-small btn-inverse service-action" service_action="disable" service_key="<?php echo $key; ?>">Disable</button>
                          <?php } ?>
                      <?php } ?>

                      </div>
                  <?php } else { ?>
                      <?php echo $value['text']; ?>
                  <?php } ?>
                </dd>
            <?php } ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('Emoncms'); ?></h4>
        <dl class="row">
            <?php echo row(tr('Version'),$emoncms_version); ?>
            <?php
            $git_parts = array(
                row(tr('URL'), $system['git_URL'],'','overflow-hidden'),
                row(tr('Branch'), $system['git_branch']),
                row(tr('Describe'), $system['git_describe'])
            );
            $git_details = sprintf('<dl class="row">%s</dl>',implode('', $git_parts));
        ?>
            <?php echo row(tr('Git'), $git_details); ?>
            <?php echo row(tr('Components'), $component_summary); ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('Server'); ?></h4>
        <dl class="row">
            <?php if ($system['machine']) echo row(tr('Machine'),  $system['machine']); ?>
            <?php if ($system['cpu_info']) echo row(tr('CPU'), $system['cpu_info']); ?>
            <?php echo row(tr('OS'), $system['system'] . ' ' . $system['kernel']); ?>
            <?php echo row(tr('Host'), $system['host'] . ' | ' . $system['hostbyaddress'] . ' | (' . $system['ip'] . ')'); ?>
            <?php echo row(tr('Date'), $system['date']); ?>
            <?php echo row(tr('Uptime'), $system['uptime']); ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('Memory'); ?></h4>
        <dl class="row">
            <?php
            echo row(tr('RAM'), bar($ram_info['table'], sprintf(tr('Used: %s%%'), $ram_info['percent']), array(
                'Total'=>$ram_info['total'],
                'Used'=>$ram_info['used'],
                'Free'=>$ram_info['free']
            )));
            if (!empty($ram_info['swap'])) {
                echo row(tr('Swap'), bar($ram_info['swap']['table'], sprintf(tr('Used: %s%%'), $ram_info['swap']['percent']), array(
                    'Total'=>$ram_info['swap']['total'],
                    'Used'=>$ram_info['swap']['used'],
                    'Free'=>$ram_info['swap']['free']
                )));
            }
            ?>

        </dl>
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('Disk'); ?></h4>
        <dl class="row">
            <?php
            if ($redis_enabled) {
                $reset_write_load_btn = sprintf('<button id="resetdiskstats" class="btn btn-info btn-small pull-right">%s</button>',tr('Reset Disk Stats'));
                echo row('', sprintf('<span id="add-on"></span>%s',$reset_write_load_btn),'d-flex','d-flex align-items-center justify-content-between');
            }
            foreach($disk_info as $mount_info) {
                echo row($mount_info['mountpoint'],
                    bar($mount_info['table'], sprintf(tr('Used: %s%%'), $mount_info['percent']), array(
                        'Total'=>$mount_info['total'],
                        'Used'=>$mount_info['used'],
                        'Free'=>$mount_info['free'],
                        'Read Load'=>$mount_info['readload'],
                        'Write Load'=>$mount_info['writeload'],
                        'Load Time'=>$mount_info['statsloadtime']
                    ))
                );
            }
            ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('HTTP'); ?></h4>
        <dl class="row">
            <?php echo row(tr('Server'), $system['http_server'] . " " . $system['http_proto'] . " " . $system['http_mode'] . " " . $system['http_port']); ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('MySQL'); ?></h4>
        <dl class="row">
            <?php echo row(tr('Version'), $system['db_version']); ?>
            <?php echo row(tr('Host'), $system['db_server'] . ' (' . $system['db_ip'] . ')'); ?>
            <?php echo row(tr('Date'), $system['db_date']); ?>
            <?php echo row(tr('Stats'), $system['db_stat']); ?>
        </dl>

        <?php if ($redis_enabled) : ?>
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('Redis'); ?></h4>
        <dl class="row">
            <?php
            $redis_version_lines[] = row(tr('Redis Server'), $redis_info['redis_version']);
            if(!empty($redis_info['pipRedis'])) {
                $redis_version_lines[] = row(tr('Python Redis'), $redis_info['pipRedis']);
            }
            if(!empty($redis_info['phpRedis'])) {
                $redis_version_lines[] = row(tr('PHP Redis'), $redis_info['phpRedis']);
            }
            echo row(tr('Version'), sprintf('<dl class="row">%s</dl>',implode('', $redis_version_lines))); ?>
            <?php echo row(tr('Host'), $system['redis_server']); ?>
            <?php
            $redis_flush_btn = sprintf('<button id="redisflush" class="btn btn-info btn-small pull-right">%s</button>',tr('Flush'));
            $redis_keys = sprintf('%s keys',$redis_info['dbSize']);
            $redis_size = sprintf('(%s)',$redis_info['used_memory_human']);
            echo row(sprintf('<span class="align-self-center">%s</span>',tr('Size')), sprintf('<span id="redisused">%s %s</span>%s',$redis_keys,$redis_size,$redis_flush_btn),'d-flex','d-flex align-items-center justify-content-between');
            ?>
            <?php echo row(tr('Uptime'), sprintf(tr("%s days"), $redis_info['uptime_in_days'])); ?>
        </dl>
        <?php endif; ?>

        <?php if ($mqtt_enabled) : ?>
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('MQTT Server'); ?></h4>
        <dl class="row">
            <?php echo row(tr('Version'), sprintf(tr('Mosquitto %s'), $mqtt_version)) ?>
            <?php echo row(tr('Host'), sprintf('%s:%s (%s)', $system['mqtt_server'], $system['mqtt_port'], $system['mqtt_ip'])); ?>
        </dl>
        <?php endif; ?>

        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('PHP'); ?></h4>
        <dl class="row">
        <?php echo row(tr('Version'), $system['php'] . ' (' . "Zend Version" . ' ' . $system['zend'] . ')'); ?>
        <?php echo row(tr('Run user'), tr('User') . ": " . $system['run_user'] . " " . tr('Group') . ": " .  $system['run_group'] . " " . tr('Script Owner') . ": " . $system['script_owner'] ); ?>
        <?php echo row(tr('Modules'), "<ul id=\"php-modules\"><li>".str_replace("v".$system['php'],"", implode('</li><li>', $php_modules)).'</li></ul>', '', 'overflow-hidden'); ?>
        </dl>

        <?php if (!empty(implode('',$rpi_info))) : ?>
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('Pi'); ?></h4>
        <dl class="row">
            <?php echo row(sprintf('<span class="align-self-center">%s</span>',tr('Model')), $rpi_info['model'].'<div>'.RebootBtn().ShutdownBtn().'</div>','d-flex','d-flex align-items-center justify-content-between') ?>
            <?php echo row(tr('Serial num.'), strtoupper(ltrim($rpi_info['sn'], '0'))) ?>
            <?php echo row(tr('CPU Temperature'), $rpi_info['cputemp']) ?>
            <?php echo row(tr('GPU Temperature'), $rpi_info['gputemp']) ?>
            <?php echo row(tr('emonpiRelease'), $rpi_info['emonpiRelease']) ?>
            <?php echo row(tr('File-system'), $rpi_info['currentfs']) ?>
        </dl>
        <?php endif; ?>

    </div>

    <h3 class="mt-1 mb-0"><?php echo tr('Client Information'); ?></h3>
    <div id="clientinfo-container">
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('HTTP'); ?></h4>
        <dl class="row">
            <?php echo row(tr('Browser'), $_SERVER['HTTP_USER_AGENT']); ?>
            <?php echo row(tr('Language'), $_SERVER['HTTP_ACCEPT_LANGUAGE']); ?>
        </dl>
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('Window'); ?></h4>
        <dl class="row">
            <?php echo row(tr('Size'), '<span id="windowsize"><script>document.write($( window ).width() + " x " + $( window ).height())</script></span>'); ?>
        </dl>
        <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1"><?php echo tr('Screen'); ?></h4>
        <dl class="row">
            <?php echo row(tr('Resolution'), '<span id="screensize"><script>document.write(window.screen.width + " x " + window.screen.height);</script></span>'); ?>
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
        'Copied to clipboard': "<?php echo tr('Copied to clipboard') ?>",
        'successful': "<?php echo tr('successful') ?>",
        'unsuccessful': "<?php echo tr('unsuccessful') ?>",
        'Copy to clipboard: Ctrl+C, Enter': "<?php echo tr('Copy to clipboard: Ctrl+C, Enter') ?>",
        'Server Information': "<?php echo tr('Server Information') ?>",
        'Client Information': "<?php echo tr('Client Information') ?>",
        'Log level: %s': "<?php echo tr('Log level: %s') ?>"
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
    // console.log('Copying text command was ' + msg);
    snackbar(message || 'Copied to clipboard');
  }
  catch(err) {
    window.prompt("<?php echo tr('Copy to clipboard: Ctrl+C, Enter'); ?>", text);
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
    .replace(/ {4}- \*\*/g,'\n\t\t')
    .replace(/^- \*\*/mg,'\n\t')
    // remove bold
    .replace(/\*\*/g,'')
    // remove heading
    .replace(/##(.)/gm,'\n\n')
    // remove orphan new lines
    .replace(/\n /g,'')
    // replace unrequired whitespace
    .replace(/\n{2,}/g,'\n')
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
    .replace(/\s+<h4 *[^/]*?>/g, newline+newline+"## ")
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
    // mark bold content
    .replace(/<strong *[^/]*?>/g,'**')
    .replace(/<\/strong>[ \n]*/g,'** ')
    // remove all other <tags>
    .replace(/(<([^>]+)> *)/ig,'')
    // remove all indenting
    .replace(/^ {2,}/gm," ")
    // remove all single space indents
    .replace(/^ (\S)/gm,"$1")
    // replace indent placeholder
    .replace(indentRegex,'    ')
    // remove orphan new lines
    .replace(/\n \n ?\n/g, '\n')
    // remove leading spaces from values
    .replace(/:-\s+/g,':- ')
    // replace newline placeholder
    .replace(newlineRegex,"\n")
    // remove orphan new lines
    .replace(/\n{2,} /g,"\n\n")
    .replace(/\n{3,}/g,"\n\n")
    // add newline before level 2 title
    .replace(/\n?#{2}/g,'\n##')
    .replace(/([\w\(\)])\n*#{2}/g,"$1\n\n\r##")
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
$(".row dd").on('click', function(event) {
    if(event.target.tagName==='BUTTON') return
    let $this = $(this),
        title = $this.prev('dt').text().trim(),
        markup = $this.clone().find('script').remove().end().html()
        value = markdownStringify(markdownify(markup));

    copyTextToClipboard(
        [title, value].join(': '),
        tr('Copied to clipboard')
    );
});
// copy title (and value) to clipboard when clicked
$(".row dt").on('click', function(event) {
    let $this = $(this),
        title = $this.text().trim(),
        markup = $this.next('dd').clone().find('script').remove().end().html()
        value = markdownStringify(markdownify(markup));

    copyTextToClipboard(
        [title, value].join(': '),
        tr('Copied to clipboard')
    );
});
/**
 * wrapper for gettext like string replace function
 */
function tr(str) {
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

$(window).resize(function() {
  $("#windowsize").html( $(window).width() + " x " + $(window).height() );
});


<?php if ($feed_settings['redisbuffer']['enabled']) { ?>
  getBufferSize();
<?php } ?>
function getBufferSize() {
  $.ajax({ url: path+"feed/buffersize.json", async: true, dataType: "json", success: function(result)
    {
      if (result.reauth == true) { window.location.reload(true); }
      $("#bufferused").html( result + " feed points pending write");
    }
  });
}



$("#redisflush").click(function() {
  $.ajax({ url: path+"admin/redisflush", async: true, dataType: "json", success: function(result)
    {
      if (result.reauth == true) { window.location.reload(true); }
      $("#redisused").html(result.dbsize+" keys ("+result.used+")");
    }
  });
});

$("#resetdiskstats").click(function() {
  $.ajax({ url: path+"admin/resetdiskstats", async: true, dataType: "json", success: function(result)
    {
      window.location.reload();
    }
  });
});

$("#haltPi").click(function() {
  if(confirm('Please confirm you wish to shutdown your Pi, please wait 30 secs before disconnecting the power...')) {
    $.post( path+"admin/shutdown" );
  }
});

$("#rebootPi").click(function() {
  if(confirm('Please confirm you wish to reboot your Pi, this will take approximately 30 secs to complete...')) {
    $.post( path+"admin/reboot" );
  }
});

$("#noshut").click(function() {
  alert('Please modify /etc/sudoers to allow your webserver to run the shutdown command.')
});

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

$(".service-action").click(function() {

    var name = $(this).attr("service_key");
    var action = $(this).attr("service_action");
    console.log(action+" "+name)

    $.ajax({ url: path+"admin/service/"+action+"?name="+name, async: true, dataType: "json", success: function(result) {
        if (result.reauth == true) { window.location.reload(true); }
        setTimeout(function() {
            location.reload();
        },1000);
    }});
});

</script>

