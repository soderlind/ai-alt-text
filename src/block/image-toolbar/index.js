/**
 * AI Alt Text - Image Block Sidebar Extension
 *
 * Adds an "AI Alt Text" button in the image block sidebar.
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment, useState } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Higher-order component that adds AI Alt Text button to image block sidebar.
 */
const withAIAltTextSidebar = createHigherOrderComponent( ( BlockEdit ) => {
	return function AIAltTextBlockEdit( props ) {
		const [ isGenerating, setIsGenerating ] = useState( false );
		
		// Only modify image blocks
		if ( props.name !== 'core/image' ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes, setAttributes } = props;
		const { id, url } = attributes;

		/**
		 * Generate alt text via REST API.
		 */
		const generateAltText = async () => {
			if ( ! url && ! id ) {
				return;
			}

			setIsGenerating( true );

			try {
				const response = await apiFetch( {
					path: '/ai-alt-text/v1/generate',
					method: 'POST',
					data: {
						attachment_id: id || null,
						image_url: id ? null : url,
						overwrite: true,
					},
				} );

				if ( response.success && response.alt_text ) {
					setAttributes( { alt: response.alt_text } );
				} else {
					throw new Error( response.message || __( 'Failed to generate', 'ai-alt-text' ) );
				}
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( 'AI Alt Text Error:', error );
				// eslint-disable-next-line no-alert
				alert( __( 'Error', 'ai-alt-text' ) + ': ' + error.message );
			} finally {
				setIsGenerating( false );
			}
		};

		const buttonStyle = {
			width: '100%',
			justifyContent: 'center',
		};

		const spinnerStyle = {
			marginRight: '8px',
		};

		return (
			<Fragment>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody title={ __( 'AI Alt Text', 'ai-alt-text' ) } initialOpen={ true }>
						<Button
							variant="secondary"
							onClick={ generateAltText }
							disabled={ isGenerating || ( ! url && ! id ) }
							style={ buttonStyle }
						>
							{ isGenerating ? (
								<Fragment>
									<Spinner style={ spinnerStyle } />
									{ __( 'Generating...', 'ai-alt-text' ) }
								</Fragment>
							) : (
								__( 'Generate Alt Text with AI', 'ai-alt-text' )
							) }
						</Button>
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'withAIAltTextSidebar' );

addFilter(
	'editor.BlockEdit',
	'ai-alt-text/image-sidebar',
	withAIAltTextSidebar,
	20
);
