/**
 * Get the button text based on the status of the tool.
 *
 * @param {string} status The status of the tool.
 * @returns {string} The button text.
 */
function getButtonText( status ) {
    let content;
    switch ( status ) {
        case "Activated":
            content = getGetStartedButtonText();
            break;
        case "Installed":
            content = getActivateButtonText();
            break;
        case "Not installed":
            content = getInstallNowButtonText();
            break;
        default:
            content = getLearnMoreButtonText();
    }
    return content;
}

function getLearnMoreButtonText() {
    return '<span>Learn More</span>';
}

function getInstallNowButtonText() {
    return '<span>Install Now</span>';
}

function getActivateButtonText() {
    return '<span>Activate</span>';
}

function getGetStartedButtonText() {
    return '<span>Get Started</span>';
}

/**
 * Get the action class for the button based on the status of the tool.
 *
 * @param {string} status The status of the tool.
 * @returns {string} The action class for the button.
 */
function getActionClass( status ) {
    let className;
    switch ( status ) {
        case "Activated":
            className = 'tve-action-btn-get-started';
            break;
        case "Installed":
            className = 'tve-action-btn-activate';
            break;
        case "Not installed":
            className = 'tve-action-btn-install-now';
            break;
        default:
            className = 'tve-action-btn-learn-more';
    }
    return className;
}

/**
 * Get the button class based on the status of the tool.
 *
 * @param {string} status The status of the tool.
 * @returns {string} The button class.
 */
function getButtonClass( status ) {
    let className;
    switch ( status ) {
        case "Activated":
            //Get Started
            className = 'tve-btn-get-started';
            break;
        case "Installed":
            //Activate
            className = 'tve-btn-action-activate';
            break;
        case "Not installed":
            //Activate
            className = 'tve-btn-install-now';
            break;
        default:
            //install Now or Learn More
            className = 'tve-btn-learn-more';
    }
    return className;
}

/**
 * Get the button URI based on the status of the tool.
 *
 * @param {string} status The status of the tool.
 * @param {object} tool The tool object.
 * @returns {string} The encoded URI for the button.
 */
function getButtonUri( status, tool ) {
    let url;
    switch ( status ) {
        case "Learn More":
            url = tool.get( 'landing_url' );
            break;
        default:
            url = tool.get( 'dashboard_uri' );
    }
    return encodeURIComponent( url );
}

/**
 * Get the HTML for the button based on the tool object.
 *
 * @param {object} tool The tool object.
 * @returns {string} The HTML for the button.
 */
function getButtonHtml( tool ) {
    let status = tool.get( 'status' );
    let pluginSlug = tool.get( 'plugin_slug' );
    const uriData = getButtonUri( status, tool )
    return '<div class="growth-tool-action"><button class="tve-btn ' + getButtonClass( status ) + ' ' + getActionClass( status ) + '" data-url="' + uriData + '" data-plugin-slug="' + pluginSlug + '">' + getButtonText( status ) + '</button></div>';
}

/**
 * Redirect to the URI specified in the event target's dataset.
 *
 * @param {Event} event The click event.
 * @param {string} target The target window or tab to open the URI.
 */
function redirectToUri( event, target = '_self' ) {
    const { target: eventTarget } = event || {};
    let element = eventTarget;

    while ( element ) {
        const { dataset } = element;
        if ( dataset && dataset.url ) {
            const path = decodeURIComponent( dataset.url );
            window.open( path, target );
            return;
        }
        // Move up to the parent element
        element = element.parentElement;
    }

    return false;
}

function updateButtonClassWhileInstalling ( button ) {
    // Remove previous classes
    if ( button.hasClass('tve-action-btn-install-now') ) {
        button.removeClass('tve-action-btn-install-now');
    }

    // Remove install now design
    if ( button.hasClass('tve-btn-info') ) {
        button.removeClass('tve-btn-info');
    }

    // Add new class
    if ( !button.hasClass('tve-btn-action-installing') ) {
        button.addClass('tve-btn-action-installing');
    }
}

function updateButtonClassIfInstallationFailed( button ) {
    // Remove previous classes
    if ( button.hasClass('tve-btn-action-installing') ) {
        button.removeClass('tve-btn-action-installing');
    }

    // Add new class
    if ( !button.hasClass('tve-btn-info') ) {
        button.addClass('tve-btn-info');
    }
    if ( !button.hasClass('tve-action-btn-install-now') ) {
        button.addClass('tve-action-btn-install-now');
    }
}

/**
 * Update the button class after installation.
 *
 * @param {jQuery} button The button element.
 */
function updateButtonClassAfterInstallation( button ) {
    // Remove previous classes
    if ( button.hasClass( 'tve-action-btn-install-now' ) ) {
        button.removeClass( 'tve-action-btn-install-now' );
    }

    if ( button.hasClass( 'tve-btn-action-installing' ) ) {
        button.removeClass( 'tve-btn-action-installing' );
    }

    // Add new class
    if ( !button.hasClass('tve-btn-action-activating') ) {
        button.addClass( 'tve-btn-action-activating' );
    }

    if ( !button.hasClass( 'tve-btn-action-activating' ) ) {
        button.addClass( 'tve-btn-action-activating' );
    }
}

/**
 * Update the button class after activation.
 *
 * @param {jQuery} button The button element.
 */
function updateButtonClassAfterActivated( button ) {
    // Remove previous classes
    if ( button.hasClass( 'tve-btn-info' ) ) {
        button.removeClass( 'tve-btn-info' );
    }

    if ( button.hasClass( 'tve-action-btn-activate' ) ) {
        button.removeClass( 'tve-action-btn-activate' );
    }

    // Add new classes
    if ( !button.hasClass( 'tve-btn-get-started' ) ) {
        button.addClass( 'tve-btn-get-started' );
    }

    if ( !button.hasClass( 'tve-action-btn-get-started' ) ) {
        button.addClass( 'tve-action-btn-get-started' );
    }
}

/**
 * Handles errors returned from a fetch response.
 * Parses JSON response and rejects promise with error message.
 *
 * @param {Response} response The fetch response object.
 * @returns {Promise} A promise rejected with the error message.
 */
function handleError( response ) {
    return response.json().then( errorData => {
        return Promise.reject( new Error( errorData.message ) );
    });
}

// Export the functions to make them accessible from other files
export {
    getButtonText,
    getActionClass,
    getButtonClass,
    getButtonHtml,
    redirectToUri,
    updateButtonClassWhileInstalling,
    updateButtonClassIfInstallationFailed,
    updateButtonClassAfterInstallation,
    updateButtonClassAfterActivated,
    handleError,
    getGetStartedButtonText,
    getActivateButtonText,
    getLearnMoreButtonText,
    getInstallNowButtonText
};
