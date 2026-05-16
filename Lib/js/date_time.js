(function(global) {
    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function formatYmdHms(date) {
        if (!(date instanceof Date) || isNaN(date.getTime())) return '';

        return [
            date.getFullYear(),
            pad2(date.getMonth() + 1),
            pad2(date.getDate())
        ].join('-') + ' ' + [
            pad2(date.getHours()),
            pad2(date.getMinutes()),
            pad2(date.getSeconds())
        ].join(':');
    }

    function parseYmdHms(value) {
        var trimmed = String(value || '').trim();
        var match = trimmed.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})$/);
        if (!match) return null;

        var date = new Date(
            parseInt(match[1], 10),
            parseInt(match[2], 10) - 1,
            parseInt(match[3], 10),
            parseInt(match[4], 10),
            parseInt(match[5], 10),
            parseInt(match[6], 10)
        );

        if (isNaN(date.getTime())) return null;
        return date;
    }

    function toUnixSeconds(value) {
        var date = value instanceof Date ? value : parseYmdHms(value);
        if (!date) return false;
        return Math.floor(date.getTime() / 1000);
    }

    global.ecDateTime = global.ecDateTime || {};
    global.ecDateTime.pad2 = pad2;
    global.ecDateTime.formatYmdHms = formatYmdHms;
    global.ecDateTime.parseYmdHms = parseYmdHms;
    global.ecDateTime.toUnixSeconds = toUnixSeconds;
})(window);
