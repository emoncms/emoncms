
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
    props: ['property','entry','column'],
    computed: {
        title: function(){
            return '#' +
            this.entry.id +
            ' ' +
            (this.column.label || this.property).toUpperCase() +
            '\n' +
            (this.column.title || '')
        }
    },
    methods: {
        input: function(event){
            this.$root.$emit('event:handler',event,this.entry,this.property,event.target.value);
        }
    }
})

/**
 * GRID -> ROW 
 */
Vue.component("grid-row", {
    template: "#grid-row",
    props: ['entry','columns']
})


/**
 * GRID 
 */
Vue.component("grid-data", {
    template: "#grid-data",
    props: ['gridData','columns','filterKey','caption'],
    data: function() {
        var sortOrders = {};
        Object.keys(this.columns).forEach(function(key) {
            sortOrders[key] = 1;
        });
        return {
            sortKey: "",
            sortOrders: sortOrders
        };
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
        // @todo: add utility functions Handler() and Set_field() function from #app view instance to here... if possible
    }
});
