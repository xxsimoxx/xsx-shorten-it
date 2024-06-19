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

