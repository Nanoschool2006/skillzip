( function( $ ) {
    /**
     * Backbone view for the growth tools search.
     */
    module.exports = Backbone.View.extend( {
        // Template for the search input
        template: _.template( '<input type="search" id="tvd-search-growth-tools" name="tvd-search" placeholder="Search"><button type="button" class="tvd-tools-search-icon" aria-label="Search"><svg class="td-icon"><use xlink:href="#icon-tvd-search"></use></svg></button><button type="button" class="tvd-clear-search-icon" aria-label="Clear" style="display: none;"><svg class="td-icon"><use xlink:href="#icon-tvd-cross"></use></svg></button>' ),

        // Element where the view will be rendered
        el: '.tvd-search-elem',

        // Events handled by the view
        events: {
            'click .tvd-clear-search-icon': 'clearSearch',
            'keyup #tvd-search-growth-tools': 'handleSearchKeyPress',
            'input #tvd-search-growth-tools': 'toggleClearIcon'
        },

        /**
         * Initialize the view.
         *
         * @param {Object} options Options for the view.
         */
        initialize(options) {
            this.growthToolsView = options.growthToolsView;
        },

        /**
         * Handle search button click.
         */
        searchTools: function() {
            this.growthToolsView.filterTools();
        },

        /**
         * Handle clear search button click.
         */
        clearSearch: function() {
            // Clear the search input
            $( '#tvd-search-growth-tools' ).val('');
            // Call the API to fetch data
            this.growthToolsView.filterTools();
            // Hide the clear icon
            $( '.tvd-clear-search-icon' ).hide();
            // Show the search icon
            $( '.tvd-tools-search-icon' ).show();
        },
        /**
         * Handle key press event for search input.
         *
         * @param {Event} event The keyup event.
         */
        handleSearchKeyPress: function(event) {
            if ( event.keyCode === 13 ) { // Enter key code
                this.growthToolsView.filterTools();
            }
        },

        /**
         * Toggle the visibility of the clear icon based on input value.
         */
        toggleClearIcon: function() {
            let inputVal = $( '#tvd-search-growth-tools' ).val();
            if ( inputVal.trim().length > 0 ) {
                // Hide the search icon
                $( '.tvd-tools-search-icon' ).hide();
                // Show the clear icon
                $( '.tvd-clear-search-icon' ).show();
            } else {
                // Hide the clear icon
                $( '.tvd-clear-search-icon' ).hide();
                // Show the search icon
                $( '.tvd-tools-search-icon' ).show();
            }
        },

        /**
         * Render the view.
         *
         * @returns {Object} The rendered view element.
         */
        render() {
            this.$el.html( this.template() );
            return this.$el;
        },
    } );
} )( jQuery );
