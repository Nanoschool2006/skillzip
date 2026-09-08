( function( $ ) {
    // Wait for the DOM to be ready
    $(document).ready(() => {
        // Import necessary modules
        const CategoriesCollection = require('./collections/categories');
        const SearchView = require('./views/search');
        const FilterView = require('./views/filter');
        const {
            getButtonHtml,
            redirectToUri,
            updateButtonClassWhileInstalling,
            updateButtonClassIfInstallationFailed,
            updateButtonClassAfterActivated,
            updateButtonClassAfterInstallation,
            handleError,
            getGetStartedButtonText,
            getActivateButtonText,
            getInstallNowButtonText
        } = require('./helperFunctions');

        // Create a new instance of CategoriesCollection
        const categoriesCollection = new CategoriesCollection();

        // Define the GrowthTools view
        const GrowthTools = Backbone.View.extend({
            el: '.growth-tools-list',
            events: {
                'click .tve-action-btn-learn-more': 'redirectToUri',
                'click .tve-action-btn-get-started': 'redirectToDashboard',
                'click .tve-action-btn-install-now': 'initInstallation',
                'click .tve-action-btn-activate': 'activatePlugin',
                'click .tool-name': 'redirectToUri'
            },
            initialize: function() {
                // Show loader while fetching data
                TVE_Dash.showLoader(true);
                const self = this;
                categoriesCollection.url = TVD_AM_CONST.baseUrl;

                categoriesCollection.fetch({
                    success: function( collection ) {
                        if ( collection.length === 0 ) {
                            // Handle case where no data is returned
                            self.$el.empty();
                            self.$el.append( '<div class="growth-tools-not-found"><span>No tools found</span><svg class="td-icon"><use xlink:href="#icon-no-tools-found"></use></svg></div>' );
                        } else {
                            // Set fetched data to view properties
                            self.categories = collection;
                            self.filteredTools = collection;
                            // Render the view after setting the collection
                            self.render();
                        }
                    },
                    error: function( collection, response ) {
                        // Error handling
                        TVE_Dash.err( 'Error fetching data:', response, 3000, null, 'top' );
                    },
                    complete: function() {
                        // Hide loader after rendering
                        TVE_Dash.hideLoader();
                    }
                });
            },
            filterTools: function() {
                // Get selected category and search text
                const selectedOption = $( '#tools-category-select option:selected' ).text();
                const category = encodeURIComponent( selectedOption );
                const search = encodeURIComponent( $( '#tvd-search-growth-tools' ).val().trim().toLowerCase() );
                // Show loader while fetching filtered data
                TVE_Dash.showLoader( true );
                const self = this;
                categoriesCollection.url = TVD_AM_CONST.baseUrl + '?category=' + category + '&query=' + search;

                categoriesCollection.fetch({
                    success: function( collection ) {
                        if ( collection.length === 0 ) {
                            // Handle case where no data is returned
                            self.$el.empty();
                            self.$el.append( '<div class="growth-tools-not-found"><span>No tools found</span><svg class="td-icon"><use xlink:href="#icon-no-tools-found"></use></svg></div>' );
                        } else {
                            // Set fetched data to view properties
                            self.categories = collection;
                            self.filteredTools = collection;
                            // Render the view after setting the collection
                            self.render();
                        }
                    },
                    error: function( collection, response ) {
                        // Error handling
                        TVE_Dash.err( 'Error fetching data:', response, 3000, null, 'top' );
                    },
                    complete: function() {
                        // Hide loader after rendering
                        TVE_Dash.hideLoader();
                    }
                });
            },
            // Handle redirection to external URL
            redirectToUri: function( event ) {
                redirectToUri( event, '_blank' );
            },
            // Handle redirection to dashboard
            redirectToDashboard: function( event ) {
                redirectToUri( event );
            },
            // Initialize plugin installation process
            initInstallation: function( event ) {
                event.preventDefault();
                const button = $( event.currentTarget );
                const pluginSlug = button.data( 'plugin-slug' );
                updateButtonClassWhileInstalling( button );
                // Disable the button
                button.prop( 'disabled', true ).text( 'Installing...' );

                // Ensure plugin slug is available
                if ( !pluginSlug ) {
                    TVE_Dash.err( 'Plugin slug is missing.', 3000, null, 'top' );
                    button.prop( 'disabled', false ).html( getInstallNowButtonText() );
                    return;
                }

                const apiUrl = TVD_AM_CONST.baseUrl;
                const data = {
                    plugin_slug: pluginSlug,
                    action: 'install'
                };

                // Define request options
                const requestOptions = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify( data ),
                };

                let self = this;
                // Send the POST request using fetch
                fetch( apiUrl, requestOptions )
                    .then( response => {
                        // Check if request was successful
                        if ( !response.ok ) {
                            return handleError( response );
                        }
                        return response.json(); // Parse response JSON
                    })
                    .then( response_data => {
                        // If response contains download link, install plugin
                        if ( response_data && response_data?.dl ) {
                            self.installPlugin( event );
                        } else {
                            // Otherwise, activate the plugin
                            TVE_Dash.success( response_data?.success, 3000, null, 'top' );
                            self.activatePlugin( event );
                        }
                    })
                    .catch( error => {
                        updateButtonClassIfInstallationFailed( button );
                        // Handle errors
                        TVE_Dash.err( 'Error: ' + error.message, 3000, null, 'top' );
                        button.prop( 'disabled', false ).html( getInstallNowButtonText() );
                    });
            },
            // Install plugin from remote URL
            installPlugin: function( event ) {
                const button = $( event.currentTarget );
                const pluginSlug = button.data( 'plugin-slug' );
                updateButtonClassWhileInstalling( button );
                // Disable the button
                button.prop( 'disabled', true ).text( 'Installing...' );

                const apiUrl = TVD_AM_CONST.baseUrl;
                const data = {
                    plugin_slug: pluginSlug,
                    action: 'install'
                };

                // Define request options
                const requestOptions = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify( data ),
                };
                let self = this;
                // Send the POST request using fetch
                fetch( apiUrl, requestOptions )
                    .then( response => {
                        // Check if request was successful
                        if ( !response.ok ) {
                            return handleError( response );
                        }
                        return response.json();
                    })
                    .then( response_data => {
                        TVE_Dash.success( response_data?.success, 3000, null, 'top' );
                        updateButtonClassAfterInstallation( button );
                        button.html( getActivateButtonText() );
                        self.activatePlugin( event );
                    })
                    .catch( error => {
                        updateButtonClassIfInstallationFailed( button );
                        // Handle errors
                        TVE_Dash.err( 'Error: ' + error.message, 3000, null, 'top' );
                        button.prop( 'disabled', false ).html( getInstallNowButtonText() );
                    });
            },
            // Activate plugin
            activatePlugin: function( event ) {
                event.preventDefault();
                const button = $( event.currentTarget );
                const pluginSlug = button.data( 'plugin-slug' );
                button.prop( 'disabled', true ).text( 'Activating' );

                // Ensure plugin slug is available
                if ( !pluginSlug ) {
                    TVE_Dash.err( 'Plugin slug is missing.', 3000, null, 'top' );
                    button.prop( 'disabled', false ).html( getActivateButtonText() );
                    return;
                }

                const apiUrl = TVD_AM_CONST.baseUrl;
                const data = {
                    plugin_slug: pluginSlug,
                    action: 'activate'
                };

                // Define request options
                const requestOptions = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify( data ),
                };

                // Send the POST request using fetch
                fetch( apiUrl, requestOptions )
                    .then( response => {
                        if ( !response.ok ) {
                            return handleError( response );
                        }
                        return response.json(); // Parse response JSON
                    })
                    .then( data => {
                        // Handle successful response
                        TVE_Dash.success( data?.success, 3000, null, 'top' );
                        updateButtonClassAfterActivated( button );
                        button.prop( 'disabled', false ).html( getGetStartedButtonText() ); // Enable the button
                    })
                    .catch(error => {
                        // Handle errors
                        TVE_Dash.err(  'Error: ' + error.message, 3000, null, 'top' );
                        button.prop( 'disabled', false ).html( getActivateButtonText() );
                    });
            },
            render: function() {
                // Empty the element
                this.$el.empty();
                // Check if categories collection exists
                if ( !this.filteredTools || this.filteredTools.length === 0 ) {
                    return this;
                }

                const self = this;

                // Loop through each category
                this.filteredTools.each( function( category ) {
                    const $categoryItem = $( '<div class="growth-tools-category-item"></div>' );
                    const $categoryTitle = $( '<span class="category-title">' + category.get( 'name' ) + '</span>' );
                    $categoryItem.append( $categoryTitle );

                    // Check if category has tools
                    const tools = category.get( 'tools' );
                    if ( tools && tools.length > 0 ) {
                        const $toolsContainer = $( '<div class="growth-tools-card"></div>' );

                        // Loop through each tool in the category
                        tools.forEach( function( tool ) {
                            const $toolItem = $( '<div class="growth-tools-card-item"></div>' );
                            const $toolLogo = $( '<div class="growth-tool-logo"><svg class="td-icon"><use xlink:href="#' + tool.get( 'icon' ) + '"></use></svg></div>' );
                            const $toolContent = $( '<div class="growth-tool-content"><span class="tool-name" data-url="' + encodeURIComponent( tool.get( 'landing_url' ) ) + '">' + tool.get( 'name' ) + '</span><br/><span class="tool-summary">' + tool.get( 'summary' ) + '</span></div>' );
                            const $toolAction = $( getButtonHtml( tool ) );

                            $toolItem.append( $toolLogo, $toolContent, $toolAction );
                            $toolsContainer.append( $toolItem );
                        });

                        $categoryItem.append( $toolsContainer );
                    }

                    self.$el.append( $categoryItem );
                });
                return this;
            }
        });

        // Instantiate GrowthTools view and related views
        const growthToolsView = new GrowthTools();
        const filterView = new FilterView({
            growthToolsView: growthToolsView
        });
        const searchView = new SearchView({
            growthToolsView: growthToolsView
        });

        // Render filter and search views
        filterView.render();
        searchView.render();
        // Render GrowthTools view
        growthToolsView.render();
    });
} )( jQuery );
