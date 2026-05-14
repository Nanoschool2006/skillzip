const ToolsCollection = require('../collections/tools');

/**
 * Backbone model representing a tool.
 */
module.exports = Backbone.Model.extend({
    defaults: {
        name: '',           // Default name of the tool
        tools: new ToolsCollection()  // Collection of tools
    }
});
