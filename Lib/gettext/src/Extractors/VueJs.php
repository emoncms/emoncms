<?php

namespace Gettext\Extractors;

use DOMAttr;
use DOMDocument;
use DOMElement;
use Gettext\Translations;
use Gettext\Utils\JsFunctionsScanner;

/**
 * Class to get gettext strings from VueJS template files.
 */
class VueJs extends JsCode implements ExtractorInterface
{
    /**
     * @inheritdoc
     * @throws \Exception
     */
    public static function fromString($string, Translations $translations, array $options = [])
    {
        $options += self::$options;
        $options += [
            // HTML attribute prefixes we parse as JS which could contain translations (are JS expressions)
            'attributePrefixes' => [
                ':',
                'v-bind:',
                'v-on:',
            ],
        ];

        // Ok, this is the weirdest hack, but let me explain:
        // On Linux (Mac is fine), when converting HTML to DOM, new lines get trimmed after the first tag.
        // So if there are new lines between <template> and next element, they are lost
        // So we insert a "." which is a text node, and it will prevent that newlines are stripped between elements.
        // Same thing happens between template and script tag.
        $string = str_replace('<template>', '<template>.', $string);
        $string = str_replace('</template>', '</template>.', $string);

        // Normalize newlines
        $string = str_replace(["\r\n", "\n\r", "\r"], "\n", $string);

        // VueJS files are valid HTML files, we will operate with the DOM here
        $dom = self::convertHtmlToDom($string);

        // Parse the script part as a regular JS code
        $script = $dom->getElementsByTagName('script')->item(0);
        if ($script) {
            self::getScriptTranslationsFromString(
                $script->textContent,
                $translations,
                $options,
                $script->getLineNo() - 1
            );
        }

        // Template part is parsed separately, all variables will be extracted
        // and handled as a regular JS code
        $template = $dom->getElementsByTagName('template')->item(0);
        if ($template) {
            self::getTemplateTranslations(
                $template,
                $translations,
                $options,
                $template->getLineNo() - 1
            );
        }
    }

    /**
     * @param string $html
     * @return DOMDocument
     */
    private static function convertHtmlToDom($html)
    {
        $dom = new DOMDocument;

        libxml_use_internal_errors(true);
        $dom->loadHTML($html);

        libxml_clear_errors();

        return $dom;
    }

    /**
     * Extract translations from script part
     *
     * @param string $scriptContents Only script tag contents, not the whole template
     * @param Translations $translations
     * @param array $options
     * @param int $lineOffset Number of lines the script is offset in the vue template file
     * @throws \Exception
     */
    private static function getScriptTranslationsFromString(
        $scriptContents,
        Translations $translations,
        array $options = [],
        $lineOffset = 0
    ) {
        $functions = new JsFunctionsScanner($scriptContents);
        $options['lineOffset'] = $lineOffset;
        $functions->saveGettextFunctions($translations, $options);
    }

    /**
     * Parse template to extract all translations (element content and dynamic element attributes)
     *
     * @param DOMElement $dom
     * @param Translations $translations
     * @param array $options
     * @param int $lineOffset Line number where the template part starts in the vue file
     * @throws \Exception
     */
    private static function getTemplateTranslations(
        DOMElement $dom,
        Translations $translations,
        array $options,
        $lineOffset = 0
    ) {
        // Build a JS string from all template attribute expressions
        $fakeAttributeJs = self::getTemplateAttributeFakeJs($options, $dom);

        // 1 line offset is necessary because parent template element was ignored when converting to DOM
        self::getScriptTranslationsFromString($fakeAttributeJs, $translations, $options, $lineOffset);

        // Build a JS string from template element content expressions
        $fakeTemplateJs = self::getTemplateFakeJs($dom);
        self::getScriptTranslationsFromString($fakeTemplateJs, $translations, $options, $lineOffset);
    }

    /**
     * Extract JS expressions from element attribute bindings (excluding text within elements)
     * For example: <span :title="__('extract this')"> skip element content </span>
     *
     * @param array $options
     * @param DOMElement $dom
     * @return string JS code
     */
    private static function getTemplateAttributeFakeJs(array $options, DOMElement $dom)
    {
        $expressionsByLine = self::getVueAttributeExpressions($options['attributePrefixes'], $dom);

        if (empty($expressionsByLine)) {
            return '';
        }

        $maxLines = max(array_keys($expressionsByLine));
        $fakeJs = '';

        for ($line = 1; $line <= $maxLines; $line++) {
            if (isset($expressionsByLine[$line])) {
                $fakeJs .= implode("; ", $expressionsByLine[$line]);
            }
            $fakeJs .= "\n";
        }

        return $fakeJs;
    }

    /**
     * Loop DOM element recursively and parse out all dynamic vue attributes which are basically JS expressions
     *
     * @param array $attributePrefixes List of attribute prefixes we parse as JS (may contain translations)
     * @param DOMElement $dom
     * @param array $expressionByLine [lineNumber => [jsExpression, ..], ..]
     * @return array [lineNumber => [jsExpression, ..], ..]
     */
    private static function getVueAttributeExpressions(
        array $attributePrefixes,
        DOMElement $dom,
        array &$expressionByLine = []
    ) {
        $children = $dom->childNodes;

        for ($i = 0; $i < $children->length; $i++) {
            $node = $children->item($i);

            if (!($node instanceof DOMElement)) {
                continue;
            }

            $attrList = $node->attributes;

            for ($j = 0; $j < $attrList->length; $j++) {
                /** @var DOMAttr $domAttr */
                $domAttr = $attrList->item($j);

                // Check if this is a dynamic vue attribute
                if (self::isAttributeMatching($domAttr->name, $attributePrefixes)) {
                    $line = $domAttr->getLineNo();
                    $expressionByLine += [$line => []];
                    $expressionByLine[$line][] = $domAttr->value;
                }
            }

            if ($node->hasChildNodes()) {
                $expressionByLine = self::getVueAttributeExpressions($attributePrefixes, $node, $expressionByLine);
            }
        }

        return $expressionByLine;
    }

    /**
     * Check if this attribute name should be parsed for translations
     *
     * @param string $attributeName
     * @param string[] $attributePrefixes
     * @return bool
     */
    private static function isAttributeMatching($attributeName, $attributePrefixes)
    {
        foreach ($attributePrefixes as $prefix) {
            if (strpos($attributeName, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract JS expressions from within template elements (excluding attributes)
     * For example: <span :title="skip attributes"> {{__("extract element content")}} </span>
     *
     * @param DOMElement $dom
     * @return string JS code
     */
    private static function getTemplateFakeJs(DOMElement $dom)
    {
        $fakeJs = '';
        $lines = explode("\n", $dom->textContent);

        // Build a fake JS file from template by extracting JS expressions within each template line
        foreach ($lines as $line) {
            $expressionMatched = self::parseOneTemplateLine($line);

            $fakeJs .= implode("; ", $expressionMatched) . "\n";
        }

        return $fakeJs;
    }

    /**
     * Match JS expressions in a template line
     *
     * @param string $line
     * @return string[]
     */
    private static function parseOneTemplateLine($line)
    {
        $line = trim($line);

        if (!$line) {
            return [];
        }

        $regex = '#\{\{(.*?)\}\}#';

        preg_match_all($regex, $line, $matches);

        $matched = array_map(function ($v) {
            return trim($v, '\'"{}');
        }, $matches[1]);

        return $matched;
    }
}
