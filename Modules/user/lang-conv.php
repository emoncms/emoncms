<?php
/* convert old gettext locale dir to new languages dir

example:
./locale/nl_NL/LC_MESSAGES/messages.po --> ./languages/messages-nl_NL.po
./locale/nl_NL/LC_MESSAGES/messages.mo --> ./languages/messages-nl_NL.mo
./locale/de_DE/LC_MESSAGES/messages.po --> ./languages/messages-de_DE.po
./locale/de_DE/LC_MESSAGES/messages.mo --> ./languages/messages-de_DE.mo
./locale/et_EE/LC_MESSAGES/messages.po --> ./languages/messages-et_EE.po
./locale/et_EE/LC_MESSAGES/messages.mo --> ./languages/messages-et_EE.mo
./locale/nl_BE/LC_MESSAGES/messages.po --> ./languages/messages-nl_BE.po
./locale/nl_BE/LC_MESSAGES/messages.mo --> ./languages/messages-nl_BE.mo
./locale/it_IT/LC_MESSAGES/messages.po --> ./languages/messages-it_IT.po
./locale/it_IT/LC_MESSAGES/messages.mo --> ./languages/messages-it_IT.mo
./locale/en_EN/LC_MESSAGES/messages.po --> ./languages/messages-en_EN.po
./locale/en_EN/LC_MESSAGES/messages.mo --> ./languages/messages-en_EN.mo
./locale/cy_GB/LC_MESSAGES/messages.po --> ./languages/messages-cy_GB.po
./locale/cy_GB/LC_MESSAGES/messages.mo --> ./languages/messages-cy_GB.mo
./locale/es_ES/LC_MESSAGES/messages.po --> ./languages/messages-es_ES.po
./locale/es_ES/LC_MESSAGES/messages.mo --> ./languages/messages-es_ES.mo
./locale/fr_FR/LC_MESSAGES/messages.po --> ./languages/messages-fr_FR.po
./locale/fr_FR/LC_MESSAGES/messages.mo --> ./languages/messages-fr_FR.mo
./locale/da_DK/LC_MESSAGES/messages.po --> ./languages/messages-da_DK.po
./locale/da_DK/LC_MESSAGES/messages.mo --> ./languages/messages-da_DK.mo


usage from console with pi user do: php lang-conv.conf
*/
$dir = './locale/';
$newdir = './languages/';
$domain = 'messages';
$newdomain = 'user';

if(!is_dir($newdir)) mkdir($newdir);
if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if($file == '.' || $file == '..') continue;
            if(filetype($dir . $file) != 'dir' ) continue;
            echo "Language found : $file " . "\n";
            $filepo = $dir .$file.'/LC_MESSAGES/'.$domain.'.po';
            $newfilepo = $newdir.$newdomain.'-'.$file.'.po';
            $filemo = $dir .$file.'/LC_MESSAGES/'.$domain.'.mo';
            $newfilemo = $newdir.$newdomain.'-'.$file.'.mo';
            if(file_exists( $filepo)){
                echo  $filepo.' --> '.$newfilepo. "\n";
                copy($filepo, $newfilepo);
            }
            if(file_exists($filemo)){
                echo  $filemo.' --> '.$newfilemo. "\n";
                copy($filemo, $newfilemo);
            }
        }
        closedir($dh);
    }
}
