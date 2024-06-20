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

function xsi_get_ajax_qr($link) {
	let data = {
		action     : 'xsiqr',
		qr		   : $link,
		remote_url : xsiQR.url,
		nonce      : xsiQR.nonce,
	};
	let dataJSON = (new URLSearchParams(data)).toString();
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			let response = JSON.parse(this.responseText);
			xsi_render_qr(response.title, response.qr);
		}
	};
	xhttp.open('POST', xsiQR.url, true);
	xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded; charset=UTF-8');
	xhttp.send(dataJSON);
}

function xsi_get_qr(link) {
	xsi_get_ajax_qr(link);
}

function xsi_render_qr(title, qr) {
	// Code adapted from Tim Kaye - Directory Integration Plugin
	var width = window.innerWidth,
	height = window.innerHeight,
	size = Math.min(width, height) / 2
	dialog = document.getElementById('qr-modal');
	dialog.showModal();
	dialog.innerHTML = '<div id="qr-container" style="width: ' + size + 'px;height: auto;" title="' + title + '"><button type="button" id="dialog-close-button" autofocus><span class="screen-reader-text">' + xsiQR.close + '</span></button><div><h4>' + title + '</h4>' + qr + '</div></div>';
	closeButton = dialog.querySelector('#dialog-close-button');
	closeButton.focus();
	closeButton.addEventListener('click', function() {
		dialog.close();
		dialog.querySelector('#qr-container').remove();
	});
	dialog.addEventListener( 'keydown', function( e ) {
		if ( e.key === 'Escape' ) { // Remove modal contents
			if ( dialog.querySelector('#qr-container') !== null ) {
				dialog.querySelector('#qr-container').remove();
			}
		}
		else if (e.key === 'Enter' && e.target.id === 'dialog-close-button') { // Remove modal contents
			e.preventDefault();
			dialog.close();
			if ( dialog.querySelector('#qr-container') !== null ) {
				dialog.querySelector('#qr-container').remove();
			}
		}
	});
}
