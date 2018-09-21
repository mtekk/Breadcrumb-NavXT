/**
 * A Gutenberg Breadcrumb Block
 */
( function( blocks, components, i18n, element ) {
	const { __ } = wp.i18n;
	const { registerBlockType, InspectorControls } = wp.blocks;
	const { Component } = wp.element;
	const { decodeEntities } = wp.htmlEntities;
	const { data, apiFetch } = wp;
	const { registerStore, withSelect, select, dispatch } = data;
	const DEFAULT_STATE = {
			breadcrumbTrails: {}
	}
	registerStore('breadcrumb-navxt', {
		reducer( state = DEFAULT_STATE, action ) {
			switch ( action.type ) {
				case 'SET_BREADCRUMB_TRAIL' :
					return {
						...state,
						breadcrumbTrails: {
							...state.breadcrumbTrails,
							[action.post]: action.breadcrumbTrail,
							},
					};
			}
			return state;
		},
		actions: {
			setBreadcrumbTrail( post, breadcrumbTrail) {
				return {
					type: 'SET_BREADCRUMB_TRAIL',
					post,
					breadcrumbTrail
				}
			}
		},
		selectors: {
			getBreadcrumbTrail( state, post ) {
				const { breadcrumbTrails } = state;
				const breadcrumbTrail = breadcrumbTrails[ post ];
				return breadcrumbTrail;
			}
		},
		resolvers: {
			async getBreadcrumbTrail( state, post ) {
				const breadcrumbTrail = await apiFetch( { path: '/bcn/v1/post/' + post } );
				dispatch( 'breadcrumb-navxt' ).setBreadcrumbTrail( post, breadcrumbTrail )
			}
		},
	} );
	function renderBreadcrumbTrail( breadcrumbTrail ) {
		var el = wp.element.createElement;
		var trailString = [];
		const length = breadcrumbTrail.itemListElement.length;
		breadcrumbTrail.itemListElement.forEach( function( listElement, index ) {
			if( index > 0 ) {
				trailString.push( ' > ' );
			}
			trailString.push( el( 'a', { href: listElement.item['@id'] }, decodeEntities( listElement.item.name ) ) );
		});
		return trailString;
	}
	function displayBreadcrumbTrail( { breadcrumbTrail } ) {
		console.log(breadcrumbTrail);
		if( ! breadcrumbTrail ) {
			return "Loading...";
		}
		if( breadcrumbTrail.itemListElement === 0 ) {
			return "No breadcrumb trail";
		}
		var breadcrumb = breadcrumbTrail.itemListElement[ 0 ];
		return renderBreadcrumbTrail(breadcrumbTrail);
	}
	registerBlockType( 'bcn/breadcrumb-trail', {
		title: __( 'Breadcrumb Trail', 'breadcrumb-navxt' ),
		icon: 'lock',
		category: 'widgets',

		edit: withSelect( ( select, ownProps ) => {
			const { getBreadcrumbTrail } = select( 'breadcrumb-navxt' );
			return {
				breadcrumbTrail: getBreadcrumbTrail( select( 'core/editor' ).getCurrentPostId() ),
			};
		} )( displayBreadcrumbTrail ),

		save: function() {
			//Rendering in PHP
			return null;
		},
	} );
} )(
	window.wp.blocks,
	window.wp.components,
	window.wp.i18n,
	window.wp.element
);
