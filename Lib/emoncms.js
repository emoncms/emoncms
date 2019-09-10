/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
*/
//
// THIS IS LOADED IN <head> - DO NOT ADD SCRIPTS THAT WOULD BLOCK THE "time to first print"
// ---------------------------------------------------------------------------------------
"use strict";

var _SETTINGS = {
    show_errors: true // false to allow errors to be handled by browser developer console
}

/**
 * POLYFILLS
 * document.currentScript
 *  - polyfill for IE browsers
 * works by getting the last <script> in the document
 * each <script> is loaded in order so this <script> would always be the last one in the parent <html>
 */
if(!document.currentScript) {
    document.currentScript = (function() {
        var scripts = document.getElementsByTagName('script');
        return scripts[scripts.length - 1];
    })();
}
/**
 * Set the passed path as a constant that cannot be changed by other scripts
 * reads the [data-path] param of the <script> tag loading this file.
 * works by current filename if no [data-path] given
 * 
 * ## "Self-Executing Anonymous Functions" will not add to the window variable scope
 * @return emoncms path
 * @todo change "var path = ..." to "const path = ..." once all other modules have been updated to use this new js file
 * this will prevent any future changes to `path` within any other modules.
 * @todo look at adding this into a global `_SETTINGS` object for all js settings
 */
var path = (function() {
    // if [data-path] not in initial <script> tag, this file is in the /Lib directory
    const filePath = 'Lib/emoncms.js'
    var _path = document.currentScript.dataset.path
    /**
     * remove the filePath from given url
     * @param {string} src url of current file
     * @returns url and path of emoncms system
     */
    function getPathFromScript(src) {
        var regex = new RegExp('(.*)' + filePath)
        var match = src.match(regex)
        return match[1]
    }
    // if path not set as [data-path] of <script> tag get the path from emoncms.js url
    // @todo: more testing ond different devices/browsers
    if (!_path) {
        _path = getPathFromScript(document.currentScript.src)
    }
    return _path
})();

// on JQuery Ready...
$(function(){
    // trigger jquery "window.resized" custom event after debounce delay
    var resizeTimeout = false
    window.addEventListener('resize', function(event) {
        clearTimeout(resizeTimeout)
        resizeTimeout = setTimeout(function() {
            $.event.trigger('window.resized')
        }, 200);
    })
})

// Display alert if js error encountered
window.onerror = function(msg, source, lineno, colno, error) {
    if (_SETTINGS && !_SETTINGS.show_errors) return false;
    if (msg.toLowerCase().indexOf("script error") > -1) {
        alert('Script Error: See Browser Console for Detail');
    }
    else {
        // REMOVE API KEY FROM ALERT
        // ----------------------------
        var maskedSource = source
        var pattern = /(([\?&])?apikey=)([\w]*)/
        // pattern match result examples:
        //  0 = ?apikey=abc123
        //  1 = ?apikey=
        //  2 = ?
        //  3 = abc123
        var match = source.match(pattern)
        if (match) {
            // if apikey first parameter replace with '?'
            // if apikey not first parameter replace with ''
            if(match[2]==='&') {
                maskedSource = source.replace(match[0], '')
            } else {
                maskedSource = source.replace(match[0], '?')
            }
        }
        var messages = [
            'EmonCMS Error',
            '-------------',
            'Message: ' + msg,
            'Route: ' + maskedSource.replace(path,''),
            'Line: ' + lineno,
            'Column: ' + colno
        ];
        if (Object.keys(error).length > 0) {
            messages.push('Error: ' + JSON.stringify(error));
        }
        alert(messages.join("\n"));
    }
    return true; // true == prevents the firing of the default event handler.
}
