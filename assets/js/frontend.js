( function () {
	'use strict';

	const config = window.kbPyxdDrapingConfig || {};
	const scriptId = 'kbpyxd-draping-sdk';
	let sdkPromise = null;
	const availabilityPromises = new Map();
	const preloadPromises = new Map();

	function hasApi() {
		return Boolean( window.pyxdDraping && typeof window.pyxdDraping.showModal === 'function' );
	}

	function waitForApi() {
		return new Promise( function ( resolve, reject ) {
			let attempts = 0;
			const maximumAttempts = 80;

			function check() {
				if ( hasApi() ) {
					resolve( window.pyxdDraping );
					return;
				}

				attempts += 1;

				if ( attempts >= maximumAttempts ) {
					reject( new Error( 'Pyxd Draping SDK did not become ready.' ) );
					return;
				}

				window.setTimeout( check, 250 );
			}

			check();
		} );
	}

	function loadSdk() {
		if ( sdkPromise ) {
			return sdkPromise;
		}

			sdkPromise = new Promise( function ( resolve, reject ) {
			if ( hasApi() ) {
				resolve( window.pyxdDraping );
				return;
			}

			const existing = document.getElementById( scriptId ) ||
				document.querySelector( 'script[src*="js.pyxmagic.com/build/draping.js"]' );

			if ( existing ) {
				if ( existing.dataset.companyId && existing.dataset.companyId !== config.companyId ) {
					reject( new Error( 'A Pyxd Draping SDK instance for another company is already loaded.' ) );
					return;
				}

				waitForApi().then( resolve ).catch( reject );
				return;
			}

			const script = document.createElement( 'script' );
			script.id = scriptId;
			script.src = config.sdkUrl;
			script.dataset.companyId = config.companyId;
			script.async = true;
			script.addEventListener( 'load', function () {
				waitForApi().then( resolve ).catch( reject );
			}, { once: true } );
			script.addEventListener( 'error', function () {
				reject( new Error( 'Unable to load the Pyxd Draping SDK.' ) );
			}, { once: true } );
			document.head.appendChild( script );
		} ).catch( function ( error ) {
			sdkPromise = null;
			throw error;
		} );

		return sdkPromise;
	}

	function checkAvailability( flexibleId ) {
		if ( ! flexibleId ) {
			return Promise.reject( new Error( 'A Pyxd Draping Flexible ID is required.' ) );
		}

		if ( availabilityPromises.has( flexibleId ) ) {
			return availabilityPromises.get( flexibleId );
		}

		const availabilityPromise = loadSdk().then( function ( api ) {
			if ( typeof api.lookup !== 'function' ) {
				return { api: api, available: true };
			}

			return api.lookup( flexibleId ).then( function ( frameId ) {
				return { api: api, available: Boolean( frameId ) };
			} );
		} ).catch( function ( error ) {
			availabilityPromises.delete( flexibleId );
			throw error;
		} );

		availabilityPromises.set( flexibleId, availabilityPromise );

		return availabilityPromise;
	}

	function preload( flexibleId ) {
		if ( preloadPromises.has( flexibleId ) ) {
			return preloadPromises.get( flexibleId );
		}

		const preloadPromise = checkAvailability( flexibleId ).then( function ( availability ) {
			if ( ! availability.available ) {
				return null;
			}

			const api = availability.api;

			if ( typeof api.preload !== 'function' ) {
				return api;
			}

			return api.preload( flexibleId ).then( function () {
				return api;
			} );
		} ).catch( function ( error ) {
			preloadPromises.delete( flexibleId );
			throw error;
		} );

		preloadPromises.set( flexibleId, preloadPromise );

		return preloadPromise;
	}

	function setButtonVisibility( button, visible ) {
		const wrapper = button.closest( '.kbpyxd-draping' );

		if ( wrapper ) {
			wrapper.hidden = ! visible;
		}
	}

	function verifyButton( button ) {
		const flexibleId = button.dataset.kbpyxdFlexibleId || config.flexibleId;

		setButtonVisibility( button, false );

		checkAvailability( flexibleId ).then( function ( availability ) {
			setButtonVisibility( button, availability.available );

			if ( availability.available && config.preload ) {
				preload( flexibleId ).catch( function () {} );
			}
		} ).catch( function () {
			setButtonVisibility( button, true );
		} );
	}

	function setStatus( wrapper, message, isError ) {
		const status = wrapper.querySelector( '[data-kbpyxd-status]' );

		if ( ! status ) {
			return;
		}

		status.textContent = message || '';
		status.hidden = ! message;
		status.classList.toggle( 'kbpyxd-draping__status--error', Boolean( isError ) );
	}

	function selectedMessage( outputString ) {
		return String( config.i18n.selected || 'Selected option: %s' ).replace( '%s', outputString );
	}

	function openModal( button ) {
		const wrapper = button.closest( '.kbpyxd-draping' );
		const originalLabel = button.textContent;
		const flexibleId = button.dataset.kbpyxdFlexibleId || config.flexibleId;

		button.disabled = true;
		button.textContent = config.i18n.loading;
		button.setAttribute( 'aria-busy', 'true' );
		setStatus( wrapper, '', false );

		preload( flexibleId ).then( function ( api ) {
			if ( ! api ) {
				setButtonVisibility( button, false );
				return null;
			}

			return api.showModal(
				flexibleId,
				undefined,
				{ hoverPreview: Boolean( config.hoverPreview ) }
			);
		} ).then( function ( result ) {
			if ( null === result ) {
				return;
			}

			if ( result && result.outputString ) {
				setStatus( wrapper, selectedMessage( result.outputString ), false );
			}

			document.dispatchEvent( new CustomEvent( 'kbpyxdDrapingSelection', { detail: result } ) );
		} ).catch( function () {
			setStatus( wrapper, config.i18n.loadError, true );
		} ).finally( function () {
			button.disabled = false;
			button.textContent = originalLabel;
			button.removeAttribute( 'aria-busy' );
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		const button = event.target.closest( '[data-kbpyxd-open]' );

		if ( button ) {
			openModal( button );
		}
	} );

	function preloadOnInteraction( event ) {
		const button = event.target.closest( '[data-kbpyxd-open]' );

		if ( button ) {
			document.removeEventListener( 'pointerover', preloadOnInteraction );
			document.removeEventListener( 'focusin', preloadOnInteraction );
			preload( button.dataset.kbpyxdFlexibleId || config.flexibleId ).catch( function () {} );
		}
	}

	document.addEventListener( 'pointerover', preloadOnInteraction );
	document.addEventListener( 'focusin', preloadOnInteraction );

	function verifyButtons() {
		document.querySelectorAll( '[data-kbpyxd-open]' ).forEach( verifyButton );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', verifyButtons, { once: true } );
	} else {
		verifyButtons();
	}
}() );
