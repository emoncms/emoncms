function autocomplete(input, arr) {
    // the autocomplete function takes two arguments,
    var currentFocus;
    // execute a function when someone writes in the text field:
    input.addEventListener("input", function(e) {
        var a, b, i, val = this.value;
        // close any already open lists of autocompleted values
        closeAllLists();
        if (!val) { return false;}
        currentFocus = -1;
        // create a DIV element that will contain the items (values):
        a = document.createElement("DIV");
        a.setAttribute("id", this.id + "_autocomplete-list");
        a.setAttribute("class", "autocomplete-items");
        // append the DIV element as a child of the autocomplete container:
        this.parentNode.appendChild(a);
        // for each item in the array...
        for (i = 0; i < arr.length; i++) {
            // check if the item starts with the same letters as the text field value:
            if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
                // create a DIV element for each matching element:
                b = document.createElement("DIV");
                // make the matching letters bold:
                b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
                b.innerHTML += arr[i].substr(val.length);
                // insert a input field that will hold the current array item's value:
                b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
                // execute a function when someone clicks on the item value (DIV element):
                b.addEventListener("click", function(e) {
                    // insert the value for the autocomplete text field:
                    input.value = this.getElementsByTagName("input")[0].value;
                    //close the list of autocompleted values,
                    closeAllLists();
                });
                a.appendChild(b);
            }
        }
    });
    input.addEventListener("keydown", function(e) {
        var x = document.getElementById(this.id + "autocomplete-list");
        if (x) x = x.getElementsByTagName("div");
        if (e.keyCode == 40) {
            currentFocus++;
            addActive(x);
        } else if (e.keyCode == 38) { //up
            currentFocus--;
            addActive(x);
        } else if (e.keyCode == 13) {
            // If the ENTER key is pressed, prevent the form from being submitted,
            e.preventDefault();
            if (currentFocus > -1) {
                // and simulate a click on the "active" item:
                if (x) x[currentFocus].click();
            }
        }
    });
    function addActive(x) {
        // add class "active"
        if (!x) return false;
        // remove the "active" class on all items:
        removeActive(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (x.length - 1);
        // add class "autocomplete-active":
        x[currentFocus].classList.add("autocomplete-active");
    }
    function removeActive(x) {
        // a function to remove the "active" class from all autocomplete items:
        for (var i = 0; i < x.length; i++) {
            x[i].classList.remove("autocomplete-active");
        }
    }
    function closeAllLists(elem) {
        // close all autocomplete lists in the document,
        var x = document.getElementsByClassName("autocomplete-items");
        for (var i = 0; i < x.length; i++) {
            if (elem != x[i] && elem != input) {
                x[i].parentNode.removeChild(x[i]);
            }
        }
    }
    //execute a function when someone clicks in the document
    document.addEventListener("click", function (e) {
        closeAllLists(e.target);
    });
}