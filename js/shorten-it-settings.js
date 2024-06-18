function xsi_copy(text) {
	navigator.clipboard.writeText(text)
}

function xsi_mod(path, dest, code) {
	document.getElementById('submit_button').value = xsiWords['edit'];
	document.getElementById('cancel_button').style.visibility = 'visible'
	document.getElementById('new_path').value = path;
	document.getElementById('new_path').setAttribute('readonly', true);
	document.getElementById('new_dest').value = dest;
	document.getElementById('new_code').value = code;
	document.getElementById('new_dest').focus();
}

function xsi_cancel() {
	document.getElementById('new_path').removeAttribute('readonly');
	document.getElementById('submit_button').value = xsiWords['save'];
	document.getElementById('cancel_button').style.visibility = 'hidden';
	document.getElementById('new_path').value = '';
	document.getElementById('new_dest').value = '';
	document.getElementById('new_code').value = 302;
	location.hash = '';
}

function xsi_help() {
	document.getElementById('contextual-help-link').click();
}

/**
 * @file Functionality for the ClassicPress install screens.
 */
document.addEventListener( 'DOMContentLoaded', function() {

	var openers = document.querySelectorAll( '.link-txt' ),
		width = window.innerWidth,
		height = window.innerHeight,
		dialog = document.createElement( 'dialog' ),
		{ __, _x, _n, _nx } = wp.i18n;

	dialog.className = 'plugin-details-modal';
	document.body.append( dialog ); // append dialog element to page

	/**
	 * Open modal dialog
	 */
	openers.forEach( function( opener ) {
		opener.addEventListener( 'click', function( e ) {
			var closeButton,
				header = 'header',
				content = 'content',
				title = 'title';

			e.preventDefault();
			e.stopPropagation();

			content = reduceheaders( content );

			dialog.showModal();
			dialog.innerHTML = '<div id="plugin-information" style="width: ' + ( width * 9 / 10 ) + 'px;height: ' + ( height * 9 / 10 ) + 'px;" title="' + title + '"><button type="button" id="dialog-close-button" autofocus><span class="screen-reader-text">' + wp.i18n.__( 'Close' ) + '</span></button><div id="plugin-information-scrollable"><h2>' + header + '</h2>' + content + '<div style="height:60px"></div></div></div>';

			// Set initial focus on the "Close" button
			closeButton = dialog.querySelector( '#dialog-close-button' );
			closeButton.focus();

			// Remove modal contents using mouse
			closeButton.addEventListener( 'click', function() {
				dialog.close();
				dialog.querySelector( '#plugin-information' ).remove();
			} );

			// Keyboard interactions
			dialog.addEventListener( 'keydown', function( e ) {
				if ( e.key === 'Escape' ) { // Remove modal contents
					if ( dialog.querySelector( '#directory-item-content' ) !== null ) {
						dialog.querySelector( '#directory-item-content' ).remove();
					}
				}
				else if ( e.key === 'Enter' && e.target.id === 'dialog-close-button' ) { // Remove modal contents
					e.preventDefault();
					dialog.close();
					if ( dialog.querySelector( '#directory-item-content' ) !== null ) {
						dialog.querySelector( '#directory-item-content' ).remove();
					}
				}
				else if ( e.key === 'Tab' ) { // Prevent tabbing out of modal
					if ( e.target.id === status.id && ! e.shiftKey ) {
						e.preventDefault();
						closeButton.focus();
					} else if ( closeButton === e.target && e.shiftKey ) {
						e.preventDefault();
						dialog.querySelector( '#' + status.id ).focus();
					}
				}
			} );
		} );
	} );

	/**
	 * Helper function to reduce each element's header level by 1 so that modal header can be an `<h2>`.
	 */
	function reduceheaders( content ) {
		return content.replaceAll( '<h5', '<h6' ).replaceAll( '</h5>', '</h6>' ).replaceAll( '<h4', '<h5' ).replaceAll( '</h4>', '</h5>' ).replaceAll( '<h3', '<h4' ).replaceAll( '</h3>', '</h4>' ).replaceAll( '<h2', '<h3' ).replaceAll( '</h2>', '</h3>' );
	}

} );

