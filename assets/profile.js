(function () {
	'use strict';

	if (!window.atshiftUPFProfile) {
		return;
	}

	var hiddenFields = Array.isArray(window.atshiftUPFProfile.hiddenFields) ? window.atshiftUPFProfile.hiddenFields : [];
	var disabledHiddenFields = Array.isArray(window.atshiftUPFProfile.disabledHiddenFields) ? window.atshiftUPFProfile.disabledHiddenFields : [];
	var replacementFields = Array.isArray(window.atshiftUPFProfile.replacementFields) ? window.atshiftUPFProfile.replacementFields : [];
	var roleRestrictedFields = Array.isArray(window.atshiftUPFProfile.roleRestrictedFields) ? window.atshiftUPFProfile.roleRestrictedFields : [];
	var adminColorSchemes = window.atshiftUPFProfile.adminColorSchemes || {};
	var profileUserId = Number(window.atshiftUPFProfile.profileUserId || 0);
	var strings = window.atshiftUPFProfile.strings || {};
	var languagePreview = window.atshiftUPFProfile.languagePreview || {};
	var selectors = {
		username: ['.user-user-login-wrap', '.form-field.form-required:has(#user_login)'],
		email: ['.user-email-wrap', '.form-field.form-required:has(#email)'],
		visual_editor: ['.user-rich-editing-wrap'],
		syntax_highlighting: ['.user-syntax-highlighting-wrap'],
		admin_color: ['.user-admin-color-wrap'],
		keyboard_shortcuts: ['.user-comment-shortcuts-wrap'],
		toolbar: ['.show-admin-bar'],
		language: ['.user-language-wrap'],
		first_name: ['.user-first-name-wrap'],
		last_name: ['.user-last-name-wrap'],
		nickname: ['.user-nickname-wrap'],
		display_name: ['.user-display-name-wrap'],
		website: ['.user-url-wrap'],
		bio: ['.user-description-wrap'],
		password: ['.user-pass1-wrap', '.user-pass2-wrap', '.pw-weak', '.user-generate-reset-link-wrap', '.form-field:has(#pass1)', '.form-field:has(.wp-generate-pw)'],
		sessions: ['.user-sessions-wrap'],
		notification: ['.user-send-user-notification-wrap', '.form-field:has(#send_user_notification)'],
		role: ['.user-role-wrap', '.form-field:has(#role)'],
		profile_picture: ['.user-profile-picture'],
		application_passwords: ['.application-passwords', '#application-passwords-section'],
		submit_button: ['p.submit']
	};
	var inputIds = {
		username: ['user_login'],
		email: ['email'],
		visual_editor: ['rich_editing'],
		admin_color: ['admin_color_fresh'],
		syntax_highlighting: ['syntax_highlighting'],
		keyboard_shortcuts: ['comment_shortcuts'],
		toolbar: ['admin_bar_front'],
		language: ['locale'],
		first_name: ['first_name'],
		last_name: ['last_name'],
		nickname: ['nickname'],
		display_name: ['display_name'],
		website: ['url'],
		bio: ['description'],
		password: ['pass1', 'pass2'],
		notification: ['send_user_notification'],
		role: ['role']
	};

	function hideCoreElement(element) {
		var wrapper;

		if (!element) {
			return;
		}

		if (element.closest('[data-atshift-upf-native-section-target]')) {
			return;
		}

		wrapper = element.closest('tr') || element.closest('.form-field') || element;
		wrapper.classList.add('atshift-upf-hidden-core-field');
	}

	function turnOffHiddenFeature(field) {
		var checkedState = {
			visual_editor: true,
			syntax_highlighting: true,
			keyboard_shortcuts: false,
			toolbar: false,
			notification: false
		};

		if (!Object.prototype.hasOwnProperty.call(checkedState, field)) {
			return;
		}

		(inputIds[field] || []).forEach(function (id) {
			var input = document.getElementById(id);

			if (input && (input.type === 'checkbox' || input.type === 'radio')) {
				input.checked = checkedState[field];
			}
		});
	}

	function embedNativeApplicationPasswords() {
		var target = document.querySelector('[data-atshift-upf-native-section-target="application_passwords"]');
		var section = document.getElementById('application-passwords-section');

		if (!target || !section || target.contains(section)) {
			return;
		}

		target.innerHTML = '';
		target.appendChild(section);
		section.classList.add('atshift-upf-native-section-embedded');
	}

	function initRequiredBadges() {
		var markerPattern = /[\(（]\s*(required|必須)\s*[\)）]/i;

		document.querySelectorAll('.form-table th label, .form-table th, .form-field label, .atshift-upf-profile-field-block label').forEach(function (label) {
			var hasMarker = false;

			if (label.querySelector('.atshift-upf-required-badge')) {
				return;
			}

			Array.prototype.slice.call(label.childNodes).forEach(function (node) {
				if (node.nodeType !== window.Node.TEXT_NODE || !markerPattern.test(node.nodeValue || '')) {
					return;
				}

				node.nodeValue = node.nodeValue.replace(markerPattern, '').replace(/\s+$/, '');
				hasMarker = true;
			});

			if (!hasMarker) {
				return;
			}

			var badge = document.createElement('span');
			badge.className = 'atshift-upf-required-badge';
			badge.textContent = strings.required || 'Required';
			label.insertBefore(badge, label.firstChild);
		});
	}

	embedNativeApplicationPasswords();

	hiddenFields.forEach(function (field) {
		if (
			replacementFields.indexOf(field) !== -1
			&& roleRestrictedFields.indexOf(field) === -1
			&& !document.querySelector('[data-atshift-upf-core-replacement="' + field + '"]')
		) {
			return;
		}

		if (
			disabledHiddenFields.indexOf(field) !== -1
			&& !document.querySelector('[data-atshift-upf-core-replacement="' + field + '"]')
		) {
			turnOffHiddenFeature(field);
		}

		(selectors[field] || []).forEach(function (selector) {
			try {
				document.querySelectorAll(selector).forEach(function (element) {
					hideCoreElement(element);
				});
			} catch (error) {
				return;
			}
		});

		(inputIds[field] || []).forEach(function (id) {
			hideCoreElement(document.getElementById(id));
		});
	});

	initRequiredBadges();

	function renderSessionNotice(control, message, type) {
		var notice = document.createElement('div');
		var paragraph = document.createElement('p');

		control.querySelectorAll('.notice').forEach(function (existingNotice) {
			existingNotice.remove();
		});

		notice.className = 'notice notice-' + type + ' inline';
		notice.setAttribute('role', 'alert');
		paragraph.textContent = message;
		notice.appendChild(paragraph);
		control.insertBefore(notice, control.firstChild);
	}

	document.querySelectorAll('[data-atshift-upf-destroy-sessions]').forEach(function (button) {
		button.addEventListener('click', function () {
			var control = button.closest('.atshift-upf-session-control');
			var nonce = document.getElementById('_wpnonce');

			if (!control || !nonce || !window.wp || !window.wp.ajax || !profileUserId) {
				return;
			}

			button.disabled = true;
			window.wp.ajax.post('destroy-sessions', {
				nonce: nonce.value,
				user_id: profileUserId
			}).done(function (response) {
				renderSessionNotice(control, response.message || '', 'success');
			}).fail(function (response) {
				button.disabled = false;
				renderSessionNotice(control, response && response.message ? response.message : '', 'error');
			});
		});
	});

	function initAdminColorSelects() {
		document.querySelectorAll('[data-atshift-upf-admin-color]').forEach(function (select) {
			function previewAdminColor() {
				var schemeKey = select.value || '';
				var scheme = adminColorSchemes[schemeKey] || {};
				var colors = Array.isArray(scheme.colors) ? scheme.colors : [];
				var nativeRadio;
				var colorStylesheet;
				var targets;

				document.querySelectorAll('.user-admin-color-wrap input[name="admin_color"]').forEach(function (radio) {
					if (radio.value === schemeKey) {
						nativeRadio = radio;
					}
				});

				if (nativeRadio) {
					nativeRadio.checked = true;
				}

				colorStylesheet = document.getElementById('colors-css');
				if (colorStylesheet && scheme.url) {
					colorStylesheet.setAttribute('href', scheme.url);
				}

				Array.prototype.slice.call(document.body.classList).forEach(function (className) {
					if (className.indexOf('admin-color-') === 0) {
						document.body.classList.remove(className);
					}
				});
				document.body.classList.add('admin-color-' + schemeKey);

				targets = [document.body].concat(Array.prototype.slice.call(document.querySelectorAll('.atshift-upf-profile-card, .atshift-upf-profile-fields')));
				targets.forEach(function (target) {
					if (colors[0]) {
						target.style.setProperty('--atshift-upf-chrome', colors[0]);
						target.style.setProperty('--atshift-upf-accent-dark', colors[0]);
					}
					if (colors[1]) {
						target.style.setProperty('--atshift-upf-chrome-alt', colors[1]);
					}
					if (colors[2]) {
						target.style.setProperty('--atshift-upf-accent', colors[2]);
						target.style.setProperty('--wp-admin-theme-color', colors[2]);
					}
					if (colors[3]) {
						target.style.setProperty('--atshift-upf-accent-alt', colors[3]);
					}
				});

				if (window.wp && window.wp.svgPainter && scheme.iconColors) {
					window.wp.svgPainter.setColors(scheme.iconColors);
					window.wp.svgPainter.paint();
				}
			}

			select.addEventListener('change', previewAdminColor);
			previewAdminColor();
		});
	}

	initAdminColorSelects();

	function initLanguagePreview() {
		var select = document.querySelector('#atshift_upf_locale')
			|| document.querySelector('.atshift-upf-profile-card select[name="locale"]')
			|| document.querySelector('select[name="locale"]');
		var previewTranslations = languagePreview.translations || {};

		if (!select || !Object.keys(previewTranslations).length) {
			return;
		}

		function previewLanguage(value) {
			var locale = value || '';
			var language;

			if (locale === 'site-default' || locale === '') {
				language = languagePreview.siteLanguage || languagePreview.currentLanguage || 'en';
			} else {
				language = locale.toLowerCase().replace('_', '-').split('-')[0];
			}

			return previewTranslations[language] ? language : (languagePreview.currentLanguage || 'en');
		}

		function replaceMappedTextNode(node, map) {
			var value = node.nodeValue || '';
			var trimmed = value.trim();
			var replacement;

			if (!trimmed || !map[trimmed]) {
				return;
			}

			replacement = map[trimmed];
			node.nodeValue = value.replace(trimmed, replacement);
		}

		function replaceDescendantText(element, map) {
			Array.prototype.slice.call(element.childNodes).forEach(function (node) {
				if (node.nodeType === window.Node.TEXT_NODE) {
					replaceMappedTextNode(node, map);
					return;
				}

				if (node.nodeType === window.Node.ELEMENT_NODE && !node.matches('script, style, input, textarea, select, option')) {
					replaceDescendantText(node, map);
				}
			});
		}

		function updatePreview() {
			var language = previewLanguage(select.value);
			var translations = previewTranslations[language] || {};
			var labelMap = translations.label || {};
			var descriptionMap = translations.description || {};
			var nativeMap = translations.native || {};
			var requiredText = translations.required || strings.required || 'Required';

			document.querySelectorAll('.atshift-upf-profile-card label, .atshift-upf-profile-card th, .atshift-upf-profile-card h3, .atshift-upf-profile-accordion-title').forEach(function (element) {
				replaceDescendantText(element, labelMap);
			});

			document.querySelectorAll('.atshift-upf-profile-card p.description').forEach(function (element) {
				if (descriptionMap[element.textContent.trim()]) {
					element.textContent = descriptionMap[element.textContent.trim()];
				}
			});

			document.querySelectorAll('[data-atshift-upf-core-replacement]').forEach(function (element) {
				replaceDescendantText(element, nativeMap);
			});

			document.querySelectorAll('[data-atshift-upf-core-replacement] input[type="button"], [data-atshift-upf-core-replacement] input[type="submit"]').forEach(function (element) {
				var value = element.value.trim();
				if (nativeMap[value]) {
					element.value = nativeMap[value];
				}
			});

			document.querySelectorAll('.atshift-upf-profile-card select option').forEach(function (option) {
				var text = option.textContent.trim();
				if (nativeMap[text]) {
					option.textContent = nativeMap[text];
				}
			});

			document.querySelectorAll('.atshift-upf-profile-label-note').forEach(function (element) {
				replaceDescendantText(element, nativeMap);
			});

			document.querySelectorAll('[data-atshift-upf-core-replacement="profile_picture"] p').forEach(function (element) {
				Array.prototype.slice.call(element.childNodes).forEach(function (node) {
					if (node.nodeType === window.Node.TEXT_NODE && (node.nodeValue.trim() === '.' || node.nodeValue.trim() === '。')) {
						node.nodeValue = language === 'ja' ? '。' : '.';
					}
				});
			});

			document.querySelectorAll('[data-atshift-upf-validation-label]').forEach(function (element) {
				var validationLabel = element.getAttribute('data-atshift-upf-validation-label') || '';
				if (labelMap[validationLabel]) {
					element.setAttribute('data-atshift-upf-validation-label', labelMap[validationLabel]);
				}
			});

			document.querySelectorAll('.atshift-upf-required-badge').forEach(function (badge) {
				badge.textContent = requiredText;
			});

			document.querySelectorAll('.atshift-upf-readonly-value[aria-label]').forEach(function (element) {
				var label = element.getAttribute('aria-label') || '';
				if (labelMap[label]) {
					element.setAttribute('aria-label', labelMap[label]);
				}
			});
		}

		select.addEventListener('change', updatePreview);
		select.addEventListener('input', updatePreview);
		updatePreview();
	}

	initLanguagePreview();

	function generatedPassword() {
		var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!#$%&()*+,-./:;<=>?@[]^_{|}~';
		var password = '';
		var array = new Uint32Array(18);

		if (window.crypto && window.crypto.getRandomValues) {
			window.crypto.getRandomValues(array);
		}

		for (var i = 0; i < 18; i++) {
			var seed = array[i] || Math.floor(Math.random() * chars.length);
			password += chars.charAt(seed % chars.length);
		}

		return password;
	}

	function passwordIsStrong(value) {
		return value.length >= 8 && !/\s/.test(value) && !/^[A-Za-z]+$/.test(value) && !/^[0-9]+$/.test(value);
	}

	document.querySelectorAll('[data-atshift-upf-password-field]').forEach(function (field) {
		var source = field.querySelector('[data-atshift-upf-password-source]');
		var confirm = field.querySelector('[data-atshift-upf-password-confirm]');
		var generateButton = field.querySelector('.atshift-upf-generate-password');
		var setButton = field.querySelector('.atshift-upf-set-password');
		var cancelButton = field.querySelector('.atshift-upf-cancel-password');
		var editor = field.querySelector('[data-atshift-upf-password-editor]');
		var toggleButton = field.querySelector('.atshift-upf-toggle-password');
		var strength = field.querySelector('[data-atshift-upf-password-strength]');

		if (!source || !confirm) {
			return;
		}

		function updatePasswordState() {
			var value = source.value || '';
			var strong = passwordIsStrong(value);

			confirm.value = source.value;

			if (strength) {
				strength.hidden = value === '';
				strength.textContent = strong ? strings.passwordStrengthStrong || 'Strong' : strings.passwordStrengthWeak || 'Weak';
				strength.classList.toggle('is-strong', strong);
				strength.classList.toggle('is-weak', !strong);
			}
		}

		source.addEventListener('input', updatePasswordState);

			function fillGeneratedPassword() {
				source.value = generatedPassword();
				source.type = 'text';
				if (toggleButton) {
					toggleButton.textContent = strings.hidePassword || 'Hide';
				}
				updatePasswordState();
				source.dispatchEvent(new window.Event('input', { bubbles: true }));
				source.focus();
			}

		if (generateButton) {
			generateButton.addEventListener('click', function () {
				fillGeneratedPassword();
			});
		}

		if (setButton && editor) {
			setButton.addEventListener('click', function () {
				if (editor.hidden) {
					editor.hidden = false;
					setButton.setAttribute('aria-expanded', 'true');
					fillGeneratedPassword();
				} else {
					source.focus();
				}
			});
		}

		if (cancelButton && editor) {
			cancelButton.addEventListener('click', function () {
				source.value = '';
				source.type = 'password';
				editor.hidden = true;
				if (setButton) {
					setButton.setAttribute('aria-expanded', 'false');
				}
				if (toggleButton) {
					toggleButton.textContent = strings.showPassword || 'Show';
				}
				updatePasswordState();
			});
		}

		if (toggleButton) {
			toggleButton.addEventListener('click', function () {
				var show = source.type === 'password';
				source.type = show ? 'text' : 'password';
				toggleButton.textContent = show ? strings.hidePassword || 'Hide' : strings.showPassword || 'Show';
			});
		}

		updatePasswordState();
	});

	document.querySelectorAll('[data-atshift-upf-image-field]').forEach(function (field) {
		var input = field.querySelector('[data-atshift-upf-image-input]');
		var preview = field.querySelector('[data-atshift-upf-image-preview]');
		var selectButton = field.querySelector('.atshift-upf-select-image');
		var removeButton = field.querySelector('.atshift-upf-remove-image');
		var frame;

			function renderImage(url) {
				var image;

			if (!preview || !input) {
				return;
			}

			input.value = url || '';
			preview.innerHTML = '';
			if (url) {
				image = document.createElement('img');
				image.src = url;
				image.alt = '';
				preview.appendChild(image);
			}
				if (removeButton) {
					removeButton.hidden = !url;
				}

				input.dispatchEvent(new window.Event('change', { bubbles: true }));
			}

		if (selectButton) {
			selectButton.addEventListener('click', function () {
				if (!window.wp || !window.wp.media) {
					return;
				}

				if (!frame) {
					frame = window.wp.media({
						button: { text: strings.useThisImage || 'Use this image' },
						library: { type: 'image' },
						multiple: false,
						title: strings.selectImage || 'Select Image'
					});

					frame.on('select', function () {
						var selected = frame.state().get('selection').first();
						var image = selected ? selected.toJSON() : null;

						renderImage(image && image.url ? image.url : '');
					});
				}

				frame.open();
			});
		}

		if (removeButton) {
			removeButton.addEventListener('click', function () {
				renderImage('');
			});
		}
	});

	function hideEmptyCoreSections() {
		document.querySelectorAll('h2').forEach(function (heading) {
			var table = heading.nextElementSibling;
			var visibleRows;
			var isAccountManagementTable;
			var nativeApplicationPasswords;
			var hasVisibleNativeApplicationPasswords;

			if (!table || !table.matches('table.form-table')) {
				return;
			}

			isAccountManagementTable = !!table.querySelector('.user-pass1-wrap, .user-sessions-wrap, .pw-weak');
			nativeApplicationPasswords = isAccountManagementTable
				? document.getElementById('application-passwords-section')
				: null;
			hasVisibleNativeApplicationPasswords = !!nativeApplicationPasswords
				&& !nativeApplicationPasswords.closest('[data-atshift-upf-native-section-target="application_passwords"]')
				&& !nativeApplicationPasswords.classList.contains('atshift-upf-hidden-core-field')
				&& window.getComputedStyle(nativeApplicationPasswords).display !== 'none';

			if (hasVisibleNativeApplicationPasswords) {
				return;
			}

			visibleRows = Array.prototype.filter.call(table.querySelectorAll('tr'), function (row) {
				return !row.hidden
					&& !row.classList.contains('atshift-upf-hidden-core-field')
					&& window.getComputedStyle(row).display !== 'none';
			});

			if (!visibleRows.length) {
				heading.classList.add('atshift-upf-hidden-core-field');
				table.classList.add('atshift-upf-hidden-core-field');
			}
		});
	}

	hideEmptyCoreSections();

	function initConditionalGroups() {
		document.querySelectorAll('[data-atshift-upf-conditional-controller]').forEach(function (controller) {
			var controllerId = controller.getAttribute('data-atshift-upf-conditional-controller');

			function currentValue() {
				var checked;

				if (controller.matches('fieldset')) {
					checked = controller.querySelector('input[type="radio"]:checked');
					return checked ? checked.value : '';
				}

				return controller.value || '';
			}

			function updateChildren() {
				var value = currentValue();

				document.querySelectorAll('[data-atshift-upf-parent="' + controllerId + '"]').forEach(function (field) {
					var choice = field.getAttribute('data-atshift-upf-choice') || '';
					var shouldShow = choice === '' || choice === value;

					field.hidden = !shouldShow;
					field.classList.toggle('atshift-upf-conditional-hidden', !shouldShow);
					field.classList.toggle('atshift-upf-conditional-visible', shouldShow);
				});
			}

			controller.addEventListener('change', updateChildren);
			updateChildren();
		});
	}

	initConditionalGroups();

		function initAccordionGroups() {
			document.querySelectorAll('.atshift-upf-profile-accordion-toggle').forEach(function (button) {
				var accordion = button.closest('.atshift-upf-profile-accordion');
				var body = accordion ? accordion.querySelector('.atshift-upf-profile-accordion-body') : null;

				if (!accordion || !body || button.dataset.atshiftUpfAccordionReady === '1') {
					return;
				}

				button.dataset.atshiftUpfAccordionReady = '1';
				button.addEventListener('click', function () {
					var isOpen = !accordion.classList.contains('is-open');

					accordion.classList.toggle('is-open', isOpen);
					button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
					body.hidden = !isOpen;
				});
			});
		}

		initAccordionGroups();

		function initProfileValidation() {
			var cards = Array.prototype.slice.call(document.querySelectorAll('.atshift-upf-profile-card'));
			var fieldSelector = '[data-atshift-upf-validation-field="1"]';
			var forms = [];

			if (!cards.length) {
				return;
			}

			function fieldKey(field) {
				return field.getAttribute('data-atshift-upf-field') || '';
			}

			function fieldLabel(field) {
				return field.getAttribute('data-atshift-upf-validation-label') || fieldKey(field);
			}

			function fieldType(field) {
				return field.getAttribute('data-atshift-upf-validation-type') || 'text';
			}

			function fieldId(field) {
				var key = fieldKey(field).replace(/[^A-Za-z0-9_-]/g, '-');

				if (!field.id) {
					field.id = 'atshift-upf-validation-field-' + (key || Math.random().toString(36).slice(2));
				}

				return field.id;
			}

			function controlsForField(field) {
				var key = fieldKey(field);
				var preferred = key ? document.getElementById('atshift_upf_' + key) : null;
				var controls;

				if (
					preferred
					&& field.contains(preferred)
					&& preferred.matches('input, select, textarea')
				) {
					return [preferred];
				}

				if (fieldType(field) === 'radio' || fieldType(field) === 'conditional') {
					controls = field.querySelectorAll('input[type="radio"]');
					if (controls.length) {
						return Array.prototype.slice.call(controls);
					}
				}

				controls = field.querySelectorAll('input:not([type="button"]):not([type="submit"]):not([type="reset"]), select, textarea');
				return Array.prototype.slice.call(controls).filter(function (control) {
					return !control.disabled;
				});
			}

			function primaryControl(field) {
				var controls = controlsForField(field);
				var interactive = controls.filter(function (control) {
					return control.type !== 'hidden';
				});

				return interactive[0] || controls[0] || null;
			}

			function isFieldActive(field) {
				var node = field;

				while (node && !node.classList.contains('atshift-upf-profile-card')) {
					if (node.classList && node.classList.contains('atshift-upf-conditional-hidden')) {
						return false;
					}

					if (node.hidden && !node.classList.contains('atshift-upf-profile-accordion-body')) {
						return false;
					}

					node = node.parentElement;
				}

				return true;
			}

			function requiredValueMissing(field) {
				var controls = controlsForField(field);
				var type = fieldType(field);
				var control = controls[0];

				if (!controls.length) {
					return false;
				}

				if (type === 'radio' || controls.some(function (item) { return item.type === 'radio'; })) {
					return !controls.some(function (item) { return item.checked; });
				}

				if (type === 'checkbox' || (control && control.type === 'checkbox')) {
					return !control.checked;
				}

				return !control || String(control.value || '').trim() === '';
			}

			function errorHost(field) {
				if (field.matches('tr')) {
					return field.querySelector('td:last-child') || field;
				}

				return field.querySelector('.atshift-upf-feature-control-main') || field;
			}

			function removeDescriptionToken(control, token) {
				var tokens = (control.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);

				tokens = tokens.filter(function (item) {
					return item !== token;
				});

				if (tokens.length) {
					control.setAttribute('aria-describedby', tokens.join(' '));
				} else {
					control.removeAttribute('aria-describedby');
				}
			}

			function clearFieldError(field) {
				var error = field.querySelector(':scope .atshift-upf-field-error');
				var errorId = error ? error.id : '';

				field.classList.remove('atshift-upf-field-invalid');
				field.removeAttribute('data-atshift-upf-validation-message');

				if (error) {
					error.remove();
				}

				controlsForField(field).forEach(function (control) {
					control.removeAttribute('aria-invalid');
					if (errorId) {
						removeDescriptionToken(control, errorId);
					}
				});
			}

			function setFieldError(field, message) {
				var host = errorHost(field);
				var error = field.querySelector(':scope .atshift-upf-field-error');
				var description = host.querySelector(':scope > p.description');
				var errorId = fieldId(field) + '-error';

				field.classList.add('atshift-upf-field-invalid');
				field.setAttribute('data-atshift-upf-validation-message', message);

				if (!error) {
					error = document.createElement('div');
					error.className = 'atshift-upf-field-error';
					error.id = errorId;
					error.setAttribute('role', 'alert');
					if (description) {
						host.insertBefore(error, description);
					} else {
						host.appendChild(error);
					}
				}

				error.textContent = message;
				controlsForField(field).forEach(function (control) {
					var describedBy = (control.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);

					control.setAttribute('aria-invalid', 'true');
					if (describedBy.indexOf(errorId) === -1) {
						describedBy.push(errorId);
						control.setAttribute('aria-describedby', describedBy.join(' '));
					}
				});
			}

			function validateField(field, showEmptyRequired) {
				var controls;
				var control;
				var value;
				var message = '';

				if (!isFieldActive(field)) {
					clearFieldError(field);
					return '';
				}

				controls = controlsForField(field);
				control = primaryControl(field);
				value = control ? String(control.value || '') : '';

				if (field.getAttribute('data-atshift-upf-validation-required') === '1' && requiredValueMissing(field)) {
					if (showEmptyRequired) {
						message = strings.requiredMessage || 'This field is required.';
					}
				} else if (fieldType(field) === 'core_password' && value && !passwordIsStrong(value)) {
					message = strings.passwordWeak || 'Use at least 8 characters and combine letters, numbers, or symbols.';
				} else if (control && control.validity && !control.validity.valid) {
					if (control.validity.patternMismatch && fieldType(field) === 'core_username') {
						message = strings.usernameInvalid || 'Use only letters, numbers, and these symbols: _ . - @';
					} else if (!control.validity.valueMissing) {
						message = control.validationMessage || strings.invalidValue || 'Please enter a valid value.';
					}
				}

				if (message) {
					setFieldError(field, message);
				} else {
					clearFieldError(field);
				}

				return message;
			}

			function refreshContainerErrors(card) {
				card.querySelectorAll('.atshift-upf-profile-accordion').forEach(function (accordion) {
					accordion.classList.remove('atshift-upf-has-error');
				});

				card.querySelectorAll(fieldSelector + '.atshift-upf-field-invalid').forEach(function (field) {
					var node = field.parentElement;

					while (node && node !== card) {
						if (node.classList.contains('atshift-upf-profile-accordion')) {
							node.classList.add('atshift-upf-has-error');
						}
						node = node.parentElement;
					}
				});
			}

			function revealField(field, shouldScroll) {
				var accordions = [];
				var node = field.parentElement;
				var control;

				while (node && !node.classList.contains('atshift-upf-profile-card')) {
					if (node.classList.contains('atshift-upf-profile-accordion')) {
						accordions.push(node);
					}
					node = node.parentElement;
				}

				accordions.reverse().forEach(function (accordion) {
					var button = accordion.querySelector(':scope > .atshift-upf-profile-accordion-toggle');
					var body = accordion.querySelector(':scope > .atshift-upf-profile-accordion-body');

					accordion.classList.add('is-open');
					if (button) {
						button.setAttribute('aria-expanded', 'true');
					}
					if (body) {
						body.hidden = false;
					}
				});

				if (shouldScroll === false) {
					return;
				}

				field.scrollIntoView({ behavior: 'smooth', block: 'center' });
				control = primaryControl(field);
				if (control && control.type !== 'hidden' && !control.disabled) {
					control.focus({ preventScroll: true });
				}
			}

			function renderValidationNotice(card) {
				var invalidFields = Array.prototype.slice.call(card.querySelectorAll(fieldSelector + '.atshift-upf-field-invalid')).filter(isFieldActive);
				var notice = card.querySelector(':scope > .atshift-upf-validation-notice');
				var list;

				if (!invalidFields.length) {
					if (notice) {
						notice.remove();
					}
					refreshContainerErrors(card);
					return null;
				}

				if (!notice) {
					notice = document.createElement('div');
					notice.className = 'atshift-upf-validation-notice';
					notice.setAttribute('role', 'alert');
					notice.setAttribute('tabindex', '-1');
					notice.innerHTML = '<p><strong></strong></p><ul></ul>';
					card.insertBefore(notice, card.querySelector('.atshift-upf-profile-fields'));
				}

				notice.querySelector('strong').textContent = strings.validationNotice || 'Please correct the highlighted fields.';
				list = notice.querySelector('ul');
				list.innerHTML = '';

				invalidFields.forEach(function (field) {
					var item = document.createElement('li');
					var link = document.createElement('a');
					var message = field.getAttribute('data-atshift-upf-validation-message') || '';

					link.href = '#' + fieldId(field);
					link.textContent = fieldLabel(field) + ': ' + message;
					link.addEventListener('click', function (event) {
						event.preventDefault();
						revealField(field);
					});
					item.appendChild(link);
					list.appendChild(item);
				});

				refreshContainerErrors(card);
				return notice;
			}

			function clearInactiveErrors(form) {
				form.querySelectorAll(fieldSelector).forEach(function (field) {
					if (!isFieldActive(field)) {
						clearFieldError(field);
					}
				});
			}

			function updateNotices(form) {
				clearInactiveErrors(form);
				form.querySelectorAll('.atshift-upf-profile-card').forEach(renderValidationNotice);
			}

			function validateForm(form) {
				var invalidFields = [];

				form.querySelectorAll(fieldSelector).forEach(function (field) {
					if (validateField(field, true)) {
						invalidFields.push(field);
					}
				});

				updateNotices(form);
				return invalidFields;
			}

			cards.forEach(function (card) {
				var form = card.closest('form');

				card.querySelectorAll(fieldSelector).forEach(function (field) {
					controlsForField(field).forEach(function (control) {
						if (field.getAttribute('data-atshift-upf-validation-required') === '1') {
							control.setAttribute('aria-required', 'true');
						}
					});
				});

				if (form && forms.indexOf(form) === -1) {
					forms.push(form);
				}
			});

			forms.forEach(function (form) {
				if (form.dataset.atshiftUpfValidationReady === '1') {
					return;
				}

				form.dataset.atshiftUpfValidationReady = '1';
				form.noValidate = true;

				form.addEventListener('submit', function (event) {
					var invalidFields = validateForm(form);
					var notice;

					if (invalidFields.length) {
						event.preventDefault();
						event.stopImmediatePropagation();
						revealField(invalidFields[0], false);
						notice = invalidFields[0].closest('.atshift-upf-profile-card').querySelector(':scope > .atshift-upf-validation-notice');
						if (notice) {
							notice.scrollIntoView({ behavior: 'smooth', block: 'start' });
							notice.focus({ preventScroll: true });
						}
						return;
					}

					if (!form.checkValidity()) {
						event.preventDefault();
						form.reportValidity();
					}
				}, true);

				form.addEventListener('input', function (event) {
					var field = event.target.closest ? event.target.closest(fieldSelector) : null;

					if (!field) {
						return;
					}

					validateField(field, false);
					updateNotices(form);
				});

				form.addEventListener('change', function (event) {
					var field = event.target.closest ? event.target.closest(fieldSelector) : null;

					if (field) {
						validateField(field, false);
					}
					updateNotices(form);
				});

				form.addEventListener('blur', function (event) {
					var field = event.target.closest ? event.target.closest(fieldSelector) : null;

					if (!field) {
						return;
					}

					validateField(field, true);
					updateNotices(form);
				}, true);
			});
		}

		initProfileValidation();
	}());
