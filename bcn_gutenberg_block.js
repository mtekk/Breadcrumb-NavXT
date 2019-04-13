/**
 * A Gutenberg Breadcrumb Block
 */
( function( blocks, components, i18n, element ) {
	const { __ } = wp.i18n;
	const { registerBlockType, InspectorControls } = wp.blocks;
	const { Component } = wp.element;
	const { decodeEntities } = wp.htmlEntities;
	wp.data.use( wp.data.plugins.controls );
	const { data, apiFetch } = wp;
	const { registerStore, withSelect, select, dispatch } = data;
	const el = wp.element.createElement;
	const iconBCN = el('svg', { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg" },
			el('path', { d: "M0.6 7.2C0.4 7.2 0.4 7.2 0.4 7.4V16.9C0.4 17.1 0.4 17.1 0.6 17.1H10.9C11.1 17.1 11.1 17.1 11.3 16.9L16 12.1 11.3 7.4C11.1 7.2 11.1 7.2 10.9 7.2ZM15 7.2 19.9 12.1 15 17.1H18.7C18.9 17.1 18.9 17.1 19.1 16.9L23.8 12.1 19.1 7.4C18.9 7.2 18.9 7.2 18.7 7.2Z" } )
		);
	
	const DEFAULT_STATE = {
			breadcrumbTrails: {}
	};
	
	const actions = {
		setBreadcrumbTrail( post, breadcrumbTrail) {
			return {
				type: 'SET_BREADCRUMB_TRAIL',
				post,
				breadcrumbTrail,
			}
		},
		fetchFromAPI( path ) {
			return {
				type: 'FETCH_FROM_API',
				path,
			}
		}
	};
	
	registerStore('breadcrumb-navxt', {
		reducer( state = DEFAULT_STATE, action ) {
			switch ( action.type ) {
				case 'SET_BREADCRUMB_TRAIL' :
					return {
						...state,
						breadcrumbTrails: {
							...state.breadcrumbTrails,
							[ action.post ]: action.breadcrumbTrail,
							},
					};
			}
			return state;
		},
		
		actions,
		
		selectors: {
			getBreadcrumbTrail( state, post ) {
				const { breadcrumbTrails } = state;
				const breadcrumbTrail = breadcrumbTrails[ post ];
				return breadcrumbTrail;
			},
		},
		
		controls: {
			FETCH_FROM_API( action ) {
				return apiFetch( { path: action.path } );
			},
		},
		
		resolvers: {
			* getBreadcrumbTrail( post ) {
				const path = '/bcn/v1/post/' + post;
				const breadcrumbTrail = yield actions.fetchFromAPI( path );
				return actions.setBreadcrumbTrail( post, breadcrumbTrail );
			}
		},
	} );
	function renderBreadcrumbTrail( breadcrumbTrail ) {
		var trailString = [];
		const length = breadcrumbTrail.itemListElement.length;
		breadcrumbTrail.itemListElement.forEach( function( listElement, index ) {
			if( index > 0 ) {
				trailString.push( decodeEntities( bcnOpts.hseparator ) );
			}
			if( index < length - 1 || bcnOpts.bcurrent_item_linked) {
				trailString.push( el( 'a', { href: listElement.item['@id'] }, decodeEntities( listElement.item.name ) ) );
			}
			else {
				trailString.push( el( 'span', { }, decodeEntities( listElement.item.name ) ) );
			}
		});
		return trailString;
	}
	function displayBreadcrumbTrail( { breadcrumbTrail } ) {
		if( ! breadcrumbTrail ) {
			return __( 'Loading...', 'breadcrumb-navxt' );
		}
		if( breadcrumbTrail.itemListElement === 0 ) {
			return __( 'No breadcrumb trail', 'breadcrumb-navxt' );
		}
		var breadcrumb = breadcrumbTrail.itemListElement[ 0 ];
		return renderBreadcrumbTrail(breadcrumbTrail);
	}
	registerBlockType( 'bcn/breadcrumb-trail', {
		title: __( 'Breadcrumb Trail', 'breadcrumb-navxt' ),
		description: __( "Display a breadcrumb trail representing this post's location on this website.", 'breadcrumb-navxt'),
		icon: iconBCN,
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
