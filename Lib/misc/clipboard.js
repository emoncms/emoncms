function copyToClipboardCustomMsg(elem, msgElem, successMessage, errorMessage) {
    successMessage = successMessage || 'Copied to the clipboard.';
    errorMessage = errorMessage || 'Copy not supported.';
    // get the element - if id passed and not htmlElement
    if (msgElem =  typeof msgElem === "string" ? document.getElementById(msgElem) : msgElem) {
        // set the user feedback and hide after 2s
        msgElem.innerHTML = !copyToClipboard(elem) ? errorMessage : successMessage;
        setTimeout(function() {
            msgElem.innerHTML = "";
        }, 2000);
    }
}
function copyToClipboardMsg(elem, msgElem) {
    copyToClipboardCustomMsg(elem, msgElem, 'API key copied to the clipboard.', 'Copy not supported by this browser.');
}
function copyToClipboard(elem) {
    // create hidden text element, if it doesn't already exist
    var targetId = "_hiddenCopyText_";
    var isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
    var origSelectionStart, origSelectionEnd;
    if (isInput) {
        // can just use the original source element for the selection and copy
        target = elem;
        origSelectionStart = elem.selectionStart;
        origSelectionEnd = elem.selectionEnd;
    } else {
        // must use a temporary form element for the selection and copy
        target = document.getElementById(targetId);
        if (!target) {
            var target = document.createElement("textarea");
            target.style.position = "absolute";
            target.style.left = "-9999px";
            target.style.top = "0";
            target.id = targetId;
            document.body.appendChild(target);
        }
        target.textContent = elem.textContent;
    }
    // select the content
    var currentFocus = document.activeElement;
    target.focus();
    target.setSelectionRange(0, target.value.length);
    // copy the selection
    var succeed;
    try {
          succeed = document.execCommand("copy");
    } catch(e) {
        succeed = false;
    }
    // restore original focus
    if (currentFocus && typeof currentFocus.focus === "function") {
        currentFocus.focus();
    }

    if (isInput) {
        // restore prior selection
        elem.setSelectionRange(origSelectionStart, origSelectionEnd);
    } else {
        // clear temporary content
        target.textContent = "";
    }
    return succeed;
}
