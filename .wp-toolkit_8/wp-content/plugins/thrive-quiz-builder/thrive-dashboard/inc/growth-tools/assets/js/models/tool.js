/**
 * Backbone model for representing a tool.
 */
module.exports = Backbone.Model.extend({
    // Default attributes for the tool
    defaults: {
        name: '',     // Name of the tool
        path: '',     // Path of the tool
        icon: '',     // Icon representing the tool
        status: '',   // Status of the tool
        summary: '',   // Summary or description of the tool
        landing_url: '' //url of landing page of the tool
    }
});
