(function(user, callback){
    if (!user) return;

    // rework to fit the momentjs naming scheme for the locale files
    momentjs_locales = {
        da_DK:'da',
        nl_BE:'nl-be',
        nl_NL:'nl',
        en_GB:'en-gb',
        et_EE:'et',
        fr_FR:'fr',
        de_DE:'de',
        it_IT:'it',
        es_ES:'es',
        cy_GB:'cy'
    }
    // match supported locales with momentjs file names
    user.locale = momentjs_locales.hasOwnProperty(user.lang) ? momentjs_locales[user.lang] : 'en-gb'
    // load the moment js locale file for the user's language
    var script = document.createElement('script');
    script.src = path + "Lib/momentjs-locales/%s.js".replace("%s", user.locale);
    script.id = '__user_loaded_locale';
    if(typeof callback == 'function') {
        script.addEventListener("load", callback);
    }
    document.getElementsByTagName("script")[0].parentNode.appendChild(script);

})(typeof user !== 'undefined' ? user: null, typeof _locale_loaded !== 'undefined' ? _locale_loaded: null);