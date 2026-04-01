<?php
/**
 * Addon update class
 *
 * @package FrmAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmAIFormXMLBuilder
 *
 * @since 2.0
 */
class FrmAIFormXMLBuilder {

	/**
	 * Store the decoded JSON as a property for later use.
	 * This is set in the constructor.
	 *
	 * @var array
	 */
	private $decoded_json;

	/**
	 * The DOMDocument used in get_fixed_output is used in other methods and called as a property.
	 *
	 * @var DOMDocument
	 */
	private $dom;

	/**
	 * Constructor.
	 *
	 * @param string $json The JSON output from our API containing AI generated form data.
	 */
	public function __construct( $json ) {
		$decoded_json       = json_decode( $json, true );
		$this->decoded_json = is_array( $decoded_json ) ? $decoded_json : array();
	}

	/**
	 * Output an XML string based on the data defined in the decoded JSON property.
	 *
	 * @return string XML
	 */
	public function get_fixed_output() {
		$this->dom = new DOMDocument();
		$success = $this->dom->loadXML( '<channel><form></form></channel>', LIBXML_COMPACT | LIBXML_PARSEHUGE );
		if ( ! $success ) {
			return '';
		}

		$imported_xml = simplexml_import_dom( $this->dom );
		if ( ! ( $imported_xml instanceof SimpleXMLElement ) ) {
			return '';
		}

		if ( isset( $imported_xml->form ) ) {
			$this->add_tags_to_form_tag( $imported_xml->form );
		}

		$xml = $imported_xml->asXML();
		if ( ! is_string( $xml ) ) {
			return '';
		}

		return $this->prepare_xml_output( $xml );
	}

	/**
	 * Add additional tags to form.
	 * To keep prompts more simple, ChatGPT does not know about these tags.
	 *
	 * @param SimpleXMLElement $form The target form XML element we are adding tags to.
	 * @return void
	 */
	private function add_tags_to_form_tag( $form ) {
		$settings = reset( $this->decoded_json['settings'] );

		$form->addChild( 'name', $settings['name'] );
		$form->addChild( 'description' );
		$form->addChild( 'options' );

		$field_order = 1;
		foreach ( $this->decoded_json['fields'] as $field ) {
			$field_tag = $form->addChild( 'field' );

			// Add the name tag.
			$node = dom_import_simplexml( $field_tag->addChild( 'name' ) );
			if ( $node instanceof DOMElement ) {
				$name_cdata = $this->dom->createCDATASection( $field['name'] );
				if ( false !== $name_cdata ) {
					$node->appendChild( $name_cdata );
				}
			}

			// Add the type tag.
			$node = dom_import_simplexml( $field_tag->addChild( 'type' ) );
			if ( $node instanceof DOMElement ) {
				$type_cdata = $this->dom->createCDATASection( $field['type'] );
				if ( false !== $type_cdata ) {
					$node->appendChild( $type_cdata );
				}
			}

			// Add the options tag.
			$field_options = array();
			if ( ! empty( $field['options'] ) ) {
				$options = $field_tag->addChild( 'options' );
				$node    = dom_import_simplexml( $options );
				if ( $node instanceof DOMElement ) {
					foreach ( $field['options'] as $option_key => $field_option ) {
						if ( 'Other' !== $field_option ) {
							continue;
						}
						unset( $field['options'][ $option_key ] );
						$field['options'][ 'other_' . count( $field['options'] ) ] = $field_option;
						$field_options['other']                                    = '1';
					}
					$json_encoded_options = json_encode( $field['options'] );
					if ( false === $json_encoded_options ) {
						$json_encoded_options = '[]';
					}
					$options_cdata = $this->dom->createCDATASection( $json_encoded_options );
					if ( false !== $options_cdata ) {
						$node->appendChild( $options_cdata );
					}
				}
			}

			// Add extra tags.
			$field_tag->addChild( 'description' );
			$field_tag->addChild( 'default_value' );
			$field_tag->addChild( 'required', ! empty( $field['required'] ) ? '1' : '0' );
			$field_tag->addChild( 'field_order', (string) $field_order++ );

			// Add field options tag.
			$json_encoded_field_options = $field_options ? json_encode( $field_options ) : '{}';
			if ( false === $json_encoded_field_options ) {
				$json_encoded_field_options = '{}';
			}
			$field_tag->addChild( 'field_options', $json_encoded_field_options );
		}

		// Add form timestamp tag.
		$form->addChild( 'created_at', $this->get_xml_timestamp() );

		// Add "published" status tag.
		$child = $form->addChild( 'status' );
		$node  = dom_import_simplexml( $child );

		if ( $node instanceof DOMElement ) {
			$published_cdata = $this->dom->createCDATASection( 'published' );
			if ( false !== $published_cdata ) {
				$node->appendChild( $published_cdata );
			}
		}

		$form->addChild( 'is_template', '0' );
	}

	/**
	 * Get the current timestamp in Y-m-d H:i:s (MySQL) format.
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	private function get_xml_timestamp() {
		return gmdate( 'Y-m-d H:i:s' );
	}

	/**
	 * Prepare the final XML string for output.
	 *
	 * @since 2.0
	 *
	 * @param string $xml XML data with our additional tags added.
	 * @return string
	 */
	private function prepare_xml_output( $xml ) {
		// Add encoding to the header tag.
		$xml = str_replace( '<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8" ?>', $xml );
		return $xml;
	}
}
