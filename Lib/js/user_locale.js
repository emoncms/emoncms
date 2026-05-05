(function(user, callback){
    if (!user) {
        if (typeof callback === 'function') callback();
        return;
    }

    var localeAliases = {
        da_DK: 'da-DK',
        nl_BE: 'nl-BE',
        nl_NL: 'nl-NL',
        en_GB: 'en-GB',
        et_EE: 'et-EE',
        fr_FR: 'fr-FR',
        de_DE: 'de-DE',
        it_IT: 'it-IT',
        es_ES: 'es-ES',
        cy_GB: 'cy-GB'
    };

    var locale = localeAliases[user.lang] || String(user.lang || 'en_GB').replace('_', '-');
    user.locale = locale;

    if (typeof document !== 'undefined' && document.documentElement) {
        document.documentElement.lang = locale;
    }

    if (typeof callback === 'function') callback();

})(typeof _user !== 'undefined' ? _user: null, typeof _locale_loaded !== 'undefined' ? _locale_loaded: null);