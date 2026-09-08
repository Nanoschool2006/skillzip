/**
 * Parse the Font Awesome icons and categorize them by style.
 */
const icons = require('./fontawesome.json' );

/**
 * Get the Font Awesome icons and categorize them by style.
 * @return {Object} Categorized Font Awesome icons
 */
function getFontawesomeIcons() {
	const fontawesomeIcons = {
		light: { prefix: 'fal', icons: [] },
		duotone: { prefix: 'fad', icons: [] },
		brands: { prefix: 'fab', icons: [] },
		solid: { prefix: 'fas', icons: [] },
		outlined: { prefix: 'far', icons: [] }, // "regular" is renamed as "outlined" here
	};

	// Categorize icons
	for ( const [ iconName, iconData ] of Object.entries( icons ) ) {
		const styles = iconData.styles;

		if ( styles.includes( 'brands' ) ) {
			fontawesomeIcons.brands.icons.push( iconName );
		}

		if ( styles.includes( 'light' ) ) {
			fontawesomeIcons.light.icons.push( iconName );
		}

		if ( styles.includes( 'duotone' ) ) {
			fontawesomeIcons.duotone.icons.push( iconName );
		}

		if ( styles.includes( 'solid' ) ) {
			fontawesomeIcons.solid.icons.push( iconName );
		}

		if ( styles.includes( 'regular' ) ) {
			fontawesomeIcons.outlined.icons.push( iconName );
		}
	}

	return fontawesomeIcons;
}

module.exports = {
	getFontawesomeIcons,
};
