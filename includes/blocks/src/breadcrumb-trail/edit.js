/**
 * useBlockProps is a React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-block-editor/#useBlockProps
 *
 * RichText is a component that allows developers to render a contenteditable input,
 * providing users with the option to format block content to make it bold, italics,
 * linked, or use other formatting.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/richtext/
 */
import { useSelect } from '@wordpress/data';
import { sprintf, __ } from '@wordpress/i18n';
import { Disabled, PanelBody, ToggleControl, TextControl, SelectControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit ( { attributes, setAttributes } ) {

	const toggleAttribute = ( attributeName ) => ( newValue ) =>
		setAttributes( { [ attributeName ]: newValue } );
	const queryArgs = '';
	return (
		<div {...useBlockProps()}>
			<InspectorControls>
				<PanelBody
					title={__( 'Breadcrumb Trail Settings', 'breadcrumb-navxt' )}
				>
					<p className="description">{__( 'Adjust the breadcrumb trail.', 'breadcrumb-navxt' )}</p>
					<TextControl
						label={__( 'Text to show before the trail', 'breadcrumb-navxt' )}
						value={attributes.pretext}
						onChange={toggleAttribute( 'pretext' )}
					/>
					<SelectControl
						label={__( 'Output trail format', 'breadcrumb-navxt' )}
						value={attributes.format}
						options={ [
							{value: 'list', label: __('Ordered list elements', 'breadcrumb-navxt')},
							{value: 'breadcrumblist_rdfa', label: __('Schema.org BreadcrumbList (RDFa)', 'breadcrumb-navxt')},
							{value: 'breadcrumblist_rdfa_wai_aria', label: __('Schema.org BreadcrumbList (RDFa) with WAI-ARIA', 'breadcrumb-navxt')},
							{value: 'breadcrumblist_microdata', label: __('Schema.org BreadcrumbList (microdata)', 'breadcrumb-navxt')},
							{value: 'plain', label: __('Plane (no Schema.org BreadcrumbList)', 'breadcrumb-navxt')},
						] }
						onChange={toggleAttribute( 'format' )}
					/>
					<ToggleControl
						label={__( 'Link the breadcrumbs', 'breadcrumb-navxt' )}
						checked={!!attributes.link}
						onChange={toggleAttribute( 'link' )}
					/>
					<ToggleControl
						label={__( 'Reverse the order of the trail', 'breadcrumb-navxt' )}
						checked={!!attributes.reverseOrder}
						onChange={toggleAttribute( 'reverseOrder' )}
					/>
					<ToggleControl
						label={__( 'Hide the breadcrumb trail on the front page', 'breadcrumb-navxt' )}
						checked={!!attributes.hideonHome}
						onChange={toggleAttribute( 'hideonHome' )}
					/>
					<ToggleControl
						label={__( 'Ignore the breadcrumb cache', 'breadcrumb-navxt' )}
						checked={!!attributes.ignoreCache}
						onChange={toggleAttribute( 'ignoreCache' )}
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender
					block="bcn/breadcrumb-trail"
					attributes={{ ...attributes }}
					urlQueryArgs={queryArgs}
				/>
			</Disabled>
		</div>
	);
}