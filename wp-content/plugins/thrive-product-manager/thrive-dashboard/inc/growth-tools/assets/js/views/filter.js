/**
 * Backbone view for rendering and managing the tools category select dropdown.
 */
( function ( $ ) {
    module.exports = Backbone.View.extend( {
        // Template for the select dropdown
        template: _.template( '<select id="tools-category-select" class="tools-category-select"></select>' ),

        // Element to which the view is bound
        el: '.tvd-filter-tools',

        // DOM events handled by the view
        events: {
            'change #tools-category-select': 'filterTools' // Change event for category selection
        },

        /**
         * Initialize the view.
         * @param {object} options - Options for the view.
         * @param {object} options.growthToolsView - Reference to the growth tools view.
         */
        initialize: function ( options ) {
            this.toolsCategory = JSON.parse( TVD_AM_CONST.tools_category ); // Parse tools category JSON
            this.growthToolsView = options.growthToolsView; // Reference to the growth tools view
            this.render(); // Render the view
        },

        // Filter tools based on the selected category
        filterTools: function () {
            this.growthToolsView.filterTools(); // Call the filterTools method of the growth tools view
        },

        // Render the view
        render: function () {
            var $select = $( this.template() ); // Create a select element using the template
            $select.append( '<option value="">All Tools</option>' ); // Add default "All Tools" option
            _.each( this.toolsCategory, function ( category ) {
                $select.append( '<option value="' + category + '">' + category + '</option>' ); // Add options for each category
            } );
            this.$el.html( $select ); // Add the select element to the view's element
            this.$( '#tools-category-select' ).select2( {
                minimumResultsForSearch: Infinity
            } ); // Initialize select2 plugin for the dropdown
            return this; // Return the view instance for chaining
        }
    } );
} )( jQuery );
