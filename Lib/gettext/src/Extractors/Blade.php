<?php

namespace Gettext\Extractors;

use Gettext\Translations;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

/**
 * Class to get gettext strings from blade.php files returning arrays.
 */
class Blade extends Extractor implements ExtractorInterface
{
    /**
     * {@inheritdoc}
     */
    public static function fromString($string, Translations $translations, array $options = [])
    {
        $cachePath = empty($options['cachePath']) ? sys_get_temp_dir() : $options['cachePath'];
        $bladeCompiler = new BladeCompiler(new Filesystem(), $cachePath);
        $string = $bladeCompiler->compileString($string);

        PhpCode::fromString($string, $translations, $options);
    }
}
