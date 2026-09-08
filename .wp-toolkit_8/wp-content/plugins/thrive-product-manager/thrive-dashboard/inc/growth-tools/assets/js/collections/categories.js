const ToolsCollection = require('./tools');
const CategoryModel = require('../models/category');

/**
 * Backbone collection for representing a collection of categories.
 */
module.exports = Backbone.Collection.extend( {
    model: CategoryModel, // Reference to the CategoryModel

    /**
     * Parse the response data to create CategoryModel instances.
     *
     * @param {Object} response The response data.
     * @returns {Array} An array of CategoryModel instances.
     */
    parse: function( response ) {
        // Iterate over each category in the response
        return _.map( response, function( tools, categoryName ) {
            // Create a CategoryModel for each category
            return new CategoryModel( {
                name: categoryName,
                tools: new ToolsCollection(tools)
            } );
        } );
    }
} );
