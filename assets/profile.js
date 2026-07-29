(function () {
	'use strict';

	if (!window.atshiftUPFProfile) {
		return;
	}

	var hiddenFields = Array.isArray(window.atshiftUPFProfile.hiddenFields) ? window.atshiftUPFProfile.hiddenFields : [];
	var replacementFields = Array.isArray(window.atshiftUPFProfile.replacementFields) ? window.atshiftUPFProfile.replacementFields : [];
	var adminColorSchemes = window.atshiftUPFProfile.adminColorSchemes || {};
	var currentUserId = Number(window.atshiftUPFProfile.currentUserId || 0);
	var profileUserId = Number(window.atshiftUPFProfile.profileUserId || 0);
	var strings = window.atshiftUPFProfile.strings || {};
	var selectors = {
		username: ['.user-user-login-wrap', '.form-field.form-required:has(#user_login)'],
		email: ['.user-email-wrap', '.form-field.form-required:has(#email)'],
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
		if (replacementFields.indexOf(field) !== -1 && !document.querySelector('[data-atshift-upf-core-replacement="' + field + '"]')) {
			return;
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
			select.addEventListener('change', function () {
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

				if (!currentUserId || !profileUserId || currentUserId !== profileUserId) {
					return;
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
			});
		});
	}

	initAdminColorSelects();

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
	}());
