<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TranslationTest extends TestCase
{
    // The unit bootstrap defines a stub tr(), so run the actual lookup in its own process.
    private function runWithCore(string $code): array
    {
        $root = var_export(dirname(__DIR__, 2), true);
        $script = 'define("EMONCMS_EXEC", 1); chdir('.$root.'); require "core.php"; '.$code;
        $output = shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg($script));
        return json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);
    }

    #[Test]
    public function swedish_is_available_in_the_account_and_from_browser_preferences(): void
    {
        $actual = $this->runWithCore(<<<'PHP'
require 'locale.php';
$settings = ['interface' => ['default_language' => 'en_GB']];
$result = [in_array('sv_SE', get_available_languages(), true)];
foreach (['sv', 'sv-SE', 'fi-FI, sv-FI;q=0.8'] as $header) {
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $header;
    set_emoncms_lang('');
    $result[] = $GLOBALS['language'];
}
echo json_encode($result);
PHP);
        $this->assertSame([true, 'sv_SE', 'sv_SE', 'sv_SE'], $actual);
    }

    #[Test]
    public function module_lookups_restore_the_caller_even_when_there_is_no_catalogue(): void
    {
        $actual = $this->runWithCore(<<<'PHP'
$GLOBALS['language'] = 'sv_SE';
$GLOBALS['translations'] = ['Send Email' => 'caller'];
$lookup = static fn() => tr('Send Email');
$translated = with_module_translations('eventp', $lookup);
$afterTranslated = $lookup();
$fallback = with_module_translations('missing-module', $lookup);
echo json_encode([$translated, $afterTranslated, $fallback, $lookup()]);
PHP);
        $this->assertSame(['Skicka e-post', 'caller', 'Send Email', 'caller'], $actual);
    }
}
