/**
 * GRIDJS
 * ---------------
 * Vue.js template code to build a datatable like interface
 * built as a series of <script> tags with specific [id]s
 * used by grid.js as templates
 * 
 * structure created by js:
 * ---------------
 * <grid-data>          <table> container for the data
 *   <grid-row>         <tr> repeated for each row
 *      <grid-column>   <td> text/html.repeated each column
 *         <grid-input> <input> text field
 * 
 * install on any page
 * --------------
 * link to the javascript (grid.js) and include the templates (grid.html) 
 * then paste this exmple into your view. see details on what can be edited below.
 * 
    <grid-data-container 
        :grid-data = "gridData"
        :columns = "gridColumns"
        :filter-key = "searchQuery"
        :caption = "status.title"
        :class-names = "classNames"
        :selected = "selected"
        @update:total = "updateTotal()"
    ></grid-data-container>

 * vuejs properties
 * --------------
 * vue instance must have the following [data/computed/methods] available
 *  - classNames - object {success,error,warning,fade,buttonActive,button,selectedRow} class names in stylesheet
 *  - selected - string id of selected row
 *  - searchQuery - string to filter data by
 *  - gridData - array of objects with properties that match the gridColumns
 *  - gridColumns - object with properties that match the gridData object
 *      use these properties to work with this script
 *       - icon - string id of icon
 *       - sort - bool allow column to be sorted by this field
 *       - input - bool display item as text field
 *       - values - array of objects [{name,value}] that display as a <select>
 *       - noHeader - bool hide the column heading
 *       - title - string tooltip title
 *       - label - string alternate label for column name
 *       - hideNarrow - bool hide column when viewed on narrow screen
 *       - handler - function to call when input has been acted on (click,keyup,select etc)
 *            these arguments are passed in this order to the handler function:
 *            - event - object Event
 *            - item - object Specific row interacted with
 *            - property - string column name interacted with
 *            - value - the new value once interacted with
 *            - success - function called once api responded
 *            - error - function called if api call unsucsessful
 *            - always - function called after success or error
 */

/**
 * GRID -> ROW -> COLUMN -> INPUT
 */
Vue.component("grid-input", {
    template: "#grid-input",
    props: ['value','property','title'],
    data: function(){
        return {
            inputVal: this.value
        }
    },
    methods: {
        input: function(event){
            this.$emit('field:input', event)
        },
        enterPressed: function(event) {
            event.target.blur();
        }
    }
})


/**
 * GRID -> ROW -> COLUMN 
 */
Vue.component("grid-column", {
    template: "#grid-column",
    props: ["property","entry","column","classNames","selected"],
    computed: {
        title: function(){
            // return '#' +
            // this.entry.id + ' ' +
            return (this.column.label || this.property).toUpperCase() + "\n" +
            (this.column.title || '')
        }
    },
    methods: {
        input: function(event) {
            let vm = this;
            let container = event.target.parentNode.parentNode;
            let feedback = event.target.parentNode.querySelector(".help-inline");
            let success, error, always;
            // if input has a .help-inline sibling show user feedback
            if (feedback) {
                // reset feedback
                container.classList.remove(this.classNames.error, this.classNames.warning, this.classNames.success);
                feedback.innerText = "";
                let value = event.target.value;
                let changed = this.entry[this.property] !== value;
                let timeoutKey = this.entry.id + "_" + this.property;
                let timeout = vm.$root.timeouts[timeoutKey];
                let timeout_reset = vm.$root.timeouts[timeoutKey + "_reset"];

                window.clearTimeout(timeout);
                success = function() {
                    feedback.classList.add(this.classNames.fade);
                    container.classList.add(this.classNames.success);
                    feedback.innerText = (vm.column.messages && vm.column.messages.success) ? vm.column.messages.success: "";
                }
                error = function(xhr, message) {
                    if(!changed) {
                        window.clearTimeout(timeout);
                    }
                }
                always = function() {
                    vm.$root.timeouts[timeoutKey] = window.setTimeout(function() {
                        window.clearTimeout(timeout_reset);
                        container.classList.remove(this.classNames.error, this.classNames.warning, vm.$root.$root.classes.success);
                        feedback.innerText = "";
                        feedback.classList.remove(this.classNames.fade);
                    }, vm.$root.wait * 2.3);
                }
            }
            
            // pass on success,error and always callbacks to gridColumn item handler() function
            vm.$root.$emit("event:handler",event,this.entry,this.property,event.target.value,success,error,always);
        }
    }
})

/**
 * GRID -> ROW 
 */
Vue.component("grid-row", {
    template: "#grid-row",
    props: ["entry","columns","classNames","selected"]
})


/**
 * GRID 
 */
Vue.component("grid-data", {
    template: "#grid-data",
    props: ["gridData","columns","filterKey","status","default-sort","classNames","selected"],
    data: function() {
        var sortOrders = {};
        Object.keys(this.columns).forEach(function(key) {
            sortOrders[key] = 1;
        });
        return {
            sortKey: this.defaultSort || "",
            sortOrders: sortOrders
        };
    },
    filters: {
        capitalize: function(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    },
    computed: {
        filteredColumns: function() {
            var sortKey = this.sortKey;
            var filterKey = this.filterKey && this.filterKey.toLowerCase();
            var order = this.sortOrders[sortKey] || 1;
            var data = this.gridData;
            if (filterKey) {
                data = data.filter(function(row) {
                    return Object.keys(row).some(function(key) {
                        return (
                            String(row[key])
                            .toLowerCase()
                            .indexOf(filterKey) > -1
                        );
                    });
                });
            }
            if (sortKey) {
                data = data.slice().sort(function(a, b) {
                    a = a[sortKey];
                    b = b[sortKey];
                    return (a === b ? 0 : a > b ? 1 : -1) * order;
                });
            }
            return data;
        }
    },
    watch: {
        filteredColumns: function(results){
            this.$emit("update:total", results.length);
        }
    },
    methods: {
        sortBy: function(key) {
            this.sortKey = key;
            this.sortOrders[key] = this.sortOrders[key] * -1;
        }
    }
});