(function () {
	'use strict';

	var root = document.querySelector('.atshift-upf-tools');
	var settings = window.atshiftUPFTools || {};

	if (!root) {
		return;
	}

	settings.strings = settings.strings || {};

	var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-atshift-upf-tools-tab]'));
	var panels = Array.prototype.slice.call(root.querySelectorAll('[data-atshift-upf-tools-panel]'));
	var deleteConfirm = root.querySelector('[data-atshift-upf-tools-delete-confirm]');
	var deleteButton = root.querySelector('[data-atshift-upf-tools-delete]');

	function messageElement(name) {
		return root.querySelector('[data-atshift-upf-tools-message="' + name + '"]');
	}

	function showMessage(name, text, isError) {
		var element = messageElement(name);

		if (!element) {
			return;
		}

		element.textContent = text || '';
		element.classList.toggle('is-error', !!isError);
		element.classList.toggle('is-success', !!text && !isError);
	}

	function activateTab(name, updateHash) {
		tabs.forEach(function (tab) {
			var active = tab.getAttribute('data-atshift-upf-tools-tab') === name;
			tab.classList.toggle('nav-tab-active', active);
			tab.setAttribute('aria-selected', active ? 'true' : 'false');
		});

		panels.forEach(function (panel) {
			var active = panel.getAttribute('data-atshift-upf-tools-panel') === name;
			panel.classList.toggle('is-active', active);
			panel.hidden = !active;
		});

		if (updateHash && window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#' + name);
		}
	}

	function request(action, data) {
		var body = new URLSearchParams();

		body.append('action', 'atshift_upf_tools');
		body.append('action_type', action);
		body.append('nonce', settings.nonce || '');

		Object.keys(data || {}).forEach(function (key) {
			body.append(key, data[key]);
		});

		return window.fetch(settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: body.toString()
		}).then(function (response) {
			return response.json().catch(function () {
				throw new Error(settings.strings.requestFailed || 'The request could not be completed.');
			});
		}).then(function (response) {
			if (!response || !response.success) {
				throw new Error(response && response.data && response.data.message ? response.data.message : settings.strings.requestFailed);
			}

			return response.data || {};
		});
	}

	tabs.forEach(function (tab) {
		tab.addEventListener('click', function (event) {
			event.preventDefault();
			activateTab(tab.getAttribute('data-atshift-upf-tools-tab'), true);
		});
	});

	var initialTab = window.location.hash.replace('#', '');
	if (['export', 'import', 'delete'].indexOf(initialTab) !== -1) {
		activateTab(initialTab, false);
	}

	var exportButton = root.querySelector('[data-atshift-upf-tools-export]');
	var exportArea = root.querySelector('[data-atshift-upf-tools-export-area]');
	var exportOutput = root.querySelector('[data-atshift-upf-tools-export-output]');
	var packageName = root.querySelector('[data-atshift-upf-tools-package-name]');

	exportButton.addEventListener('click', function () {
		exportButton.disabled = true;
		showMessage('export', settings.strings.working || 'Processing...', false);

		request('export', {package_name: packageName.value.trim()}).then(function (data) {
			var blob = new window.Blob([data.code || ''], {type: 'application/json;charset=utf-8'});
			var url = window.URL.createObjectURL(blob);
			var link = document.createElement('a');

			link.href = url;
			link.download = data.filename || 'atshift-upf-profile-fields-set.json';
			document.body.appendChild(link);
			link.click();
			link.remove();
			window.setTimeout(function () {
				window.URL.revokeObjectURL(url);
			}, 0);

			exportOutput.value = data.code || '';
			exportArea.hidden = false;
			showMessage('export', data.message || '', false);
		}).catch(function (error) {
			showMessage('export', error.message || settings.strings.downloadFailed, true);
		}).finally(function () {
			exportButton.disabled = false;
		});
	});

	root.querySelector('[data-atshift-upf-tools-copy]').addEventListener('click', function () {
		if (!exportOutput.value || !window.navigator.clipboard) {
			showMessage('export', settings.strings.copyFailed || 'The export code could not be copied.', true);
			return;
		}

		window.navigator.clipboard.writeText(exportOutput.value).then(function () {
			showMessage('export', settings.strings.copySuccess || 'Export code copied.', false);
		}).catch(function () {
			showMessage('export', settings.strings.copyFailed || 'The export code could not be copied.', true);
		});
	});

	var importButton = root.querySelector('[data-atshift-upf-tools-import]');
	var importCode = root.querySelector('[data-atshift-upf-tools-import-code]');
	var importFile = root.querySelector('[data-atshift-upf-tools-import-file]');
	var importFileName = root.querySelector('[data-atshift-upf-tools-file-name]');

	function formatString(template, value) {
		return (template || '%s').replace('%s', value);
	}

	importFile.addEventListener('change', function () {
		var file = importFile.files && importFile.files[0];

		if (!file) {
			importCode.value = '';
			importFileName.textContent = '';
			return;
		}

		if (file.size > 1048576) {
			importCode.value = '';
			importFileName.textContent = '';
			showMessage('import', settings.strings.fileReadFailed || 'The selected distribution set could not be read.', true);
			return;
		}

		var reader = new window.FileReader();
		reader.addEventListener('load', function () {
			importCode.value = typeof reader.result === 'string' ? reader.result : '';
			importFileName.textContent = formatString(settings.strings.fileSelected, file.name);
			showMessage('import', '', false);
		});
		reader.addEventListener('error', function () {
			importCode.value = '';
			importFileName.textContent = '';
			showMessage('import', settings.strings.fileReadFailed || 'The selected distribution set could not be read.', true);
		});
		reader.readAsText(file);
	});

	importButton.addEventListener('click', function () {
		var code = importCode.value.trim();

		if (!code) {
			showMessage('import', settings.strings.fileReadFailed || importCode.getAttribute('placeholder'), true);
			importFile.focus();
			return;
		}

		if (!window.confirm(settings.importConfirm)) {
			return;
		}

		importButton.disabled = true;
		showMessage('import', settings.strings.working || 'Processing...', false);

		request('import', {import_code: code}).then(function (data) {
			showMessage('import', data.message || '', false);
		}).catch(function (error) {
			showMessage('import', error.message, true);
		}).finally(function () {
			importButton.disabled = false;
		});
	});

	deleteConfirm.addEventListener('change', function () {
		deleteButton.disabled = !deleteConfirm.checked;
	});

	deleteButton.addEventListener('click', function () {
		var deleteValues = root.querySelector('[data-atshift-upf-tools-delete-values]');

		if (!deleteConfirm.checked || !window.confirm(settings.deleteConfirm)) {
			return;
		}

		deleteButton.disabled = true;
		showMessage('delete', settings.strings.working || 'Processing...', false);

		request('delete', {
			delete_confirm: deleteConfirm.checked ? '1' : '',
			delete_values: deleteValues.checked ? '1' : ''
		}).then(function (data) {
			showMessage('delete', data.message || '', false);
			deleteConfirm.checked = false;
			deleteValues.checked = false;
		}).catch(function (error) {
			showMessage('delete', error.message, true);
			deleteButton.disabled = false;
		});
	});
}());
