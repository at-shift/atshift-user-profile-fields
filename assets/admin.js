(function () {
	'use strict';

	var structureTypes = ['group', 'box', 'conditional', 'accordion'];
	var fullWidthFieldTypes = ['core_password', 'core_bio', 'core_profile_picture', 'passkeys'];
	var activeDraggedItem = null;
	var singleUseCoreFieldTypes = Array.isArray(window.atshiftUPFAdmin && window.atshiftUPFAdmin.singleUseCoreFieldTypes) ? window.atshiftUPFAdmin.singleUseCoreFieldTypes : [];

	function getFieldItemType(item) {
		var select = item ? item.querySelector('.atshift-upf-field-type-select') : null;
		return select ? select.value : (item ? item.getAttribute('data-field-type') || '' : '');
	}

	function isStructureFieldItem(item) {
		return structureTypes.indexOf(getFieldItemType(item)) !== -1;
	}

	function isSingleUseCoreFieldType(type) {
		return singleUseCoreFieldTypes.indexOf(type) !== -1;
	}

	function updateFullWidthBadges(root) {
		var strings = (window.atshiftUPFAdmin && window.atshiftUPFAdmin.strings) || {};
		var scope = root || document;

		scope.querySelectorAll('li[data-field-id]').forEach(function (item) {
			var field = directField(item);
			var label = field ? field.querySelector('.field_label') : null;
			var parentItem = item.parentElement && item.parentElement.classList.contains('atshift-upf-child-fields')
				? item.parentElement.closest('li[data-field-id]')
				: null;
			var showBadge = fullWidthFieldTypes.indexOf(getFieldItemType(item)) !== -1
				&& getFieldItemType(parentItem) === 'group';
			var badge = field ? field.querySelector('.atshift-upf-full-row-chip') : null;

			if (!showBadge) {
				if (badge) {
					badge.remove();
				}
				return;
			}

			if (!badge && label) {
				badge = document.createElement('span');
				badge.className = 'atshift-upf-cfs-chip is-context atshift-upf-full-row-chip';
				label.appendChild(badge);
			}

			if (badge) {
				badge.textContent = strings.fullRow || 'Full row';
			}
		});
	}

	function isDisallowedParentChild(parentItem, item) {
		var parentType = getFieldItemType(parentItem);
		var childType = getFieldItemType(item);

		if (!parentItem || !item) {
			return false;
		}

		if (parentType === 'group' && structureTypes.indexOf(childType) !== -1) {
			return true;
		}

		if (parentType === 'box' && structureTypes.indexOf(childType) !== -1 && childType !== 'conditional') {
			return true;
		}

		if (parentType === 'conditional' && childType === 'conditional') {
			return true;
		}

		return false;
	}

	function fieldItemLabel(item) {
		var field = directField(item);
		var label = field ? field.querySelector('.cfs-field-label-text') : null;
		return label ? label.textContent.trim() : '';
	}

	function findFieldItemById(root, fieldId) {
		var found = null;

		if (!root || !fieldId) {
			return null;
		}

		Array.prototype.some.call(root.querySelectorAll('li[data-field-id]'), function (item) {
			if (item.getAttribute('data-field-id') === fieldId) {
				found = item;
				return true;
			}
			return false;
		});

		return found;
	}

	function directField(item) {
		if (!item) {
			return null;
		}

		return Array.prototype.filter.call(item.children, function (child) {
			return child.classList && child.classList.contains('field');
		})[0] || null;
	}

	function directGroupDropzone(item) {
		if (!item) {
			return null;
		}

		return Array.prototype.filter.call(item.children, function (child) {
			return child.classList && child.classList.contains('atshift-upf-child-fields');
		})[0] || null;
	}

	function directChildLists(item) {
		if (!item) {
			return [];
		}

		return Array.prototype.filter.call(item.children, function (child) {
			return child.matches && child.matches('ul.atshift-upf-child-fields');
		});
	}

	function directFieldItems(list) {
		if (!list) {
			return [];
		}

		return Array.prototype.filter.call(list.children, function (child) {
			return child.matches && child.matches('li[data-field-id]');
		});
	}

	function ensureGroupDropzone(item) {
		var dropzone = directGroupDropzone(item);
		var parentId = item ? item.getAttribute('data-field-id') || '' : '';

		if (!item || !isStructureFieldItem(item)) {
			return null;
		}

		if (!dropzone) {
			dropzone = document.createElement('ul');
			dropzone.className = 'fields atshift-upf-child-fields';
			item.appendChild(dropzone);
		}

		dropzone.setAttribute('data-parent-field-id', parentId);
		dropzone.classList.remove('is-hidden');

		return dropzone;
	}

	function directChildList(item) {
		return directGroupDropzone(item);
	}

	function parseConditionalChoices(item) {
		var field = directField(item);
		var choicesInput = field ? field.querySelector('textarea[name$="[choices]"]') : null;

		if (!choicesInput) {
			return [];
		}

		return choicesInput.value.split(/\r\n|\r|\n/).map(function (line) {
			var value = line.trim();
			var label = value;
			var separator = value.indexOf(' : ');

			if (separator !== -1) {
				label = value.slice(separator + 3).trim();
				value = value.slice(0, separator).trim();
			}

			return {
				value: value,
				label: label || value
			};
		}).filter(function (choice) {
			return choice.value !== '';
		});
	}

	function structureBadgeLabel(type) {
		var labels = {
			group: 'GROUP',
			box: 'BOX',
			conditional: 'CONDITION',
			accordion: 'ACCORDION'
		};

		return labels[type] || type.toUpperCase();
	}

	function refreshStructureMarker(item) {
		var field = directField(item);
		var type = getFieldItemType(item);
		var label = field ? field.querySelector('.field_label .row-title') : null;
		var badge = label ? label.querySelector('.cfs-structure-badge') : null;

		if (!item) {
			return;
		}

		structureTypes.forEach(function (structureType) {
			item.classList.toggle('cfs-structure-' + structureType, type === structureType);
		});
		item.setAttribute('data-field-type', type);

		if (structureTypes.indexOf(type) === -1) {
			if (badge) {
				badge.remove();
			}
			return;
		}

		if (!badge && label) {
			badge = document.createElement('span');
			label.insertBefore(badge, label.firstChild);
		}

		if (badge) {
			badge.className = 'cfs-structure-badge cfs-structure-badge-' + type;
			badge.textContent = structureBadgeLabel(type);
		}
	}

	function conditionalBranchList(item, value, label) {
		var branch = null;
		var strings = (window.atshiftUPFAdmin && window.atshiftUPFAdmin.strings) || {};
		var dropLabel = strings.conditionalBranchDropLabel || 'Condition "%s"';

		if (!item || !value) {
			return null;
		}

		item.querySelectorAll(':scope > .atshift-upf-conditional-branch-list').forEach(function (candidate) {
			if ((candidate.getAttribute('data-conditional-value') || '') === value) {
				branch = candidate;
			}
		});

		if (!branch) {
			branch = document.createElement('ul');
			branch.className = 'fields atshift-upf-child-fields atshift-upf-conditional-branch-list';
			branch.setAttribute('data-conditional-value', value);
			item.appendChild(branch);
		}

		branch.setAttribute('data-parent-field-id', item.getAttribute('data-field-id') || '');
		branch.setAttribute('data-conditional-label', label || value);
		branch.setAttribute('data-conditional-drop-label', dropLabel.replace('%s', label || value));

		return branch;
	}

	function fieldScreenContext(type) {
		if (type === 'core_notification') {
			return 'registration';
		}

		if (['core_visual_editor', 'core_admin_color', 'core_syntax_highlighting', 'core_keyboard_shortcuts', 'core_toolbar', 'core_nickname', 'core_display_name', 'core_bio', 'core_profile_picture', 'core_sessions', 'core_application_passwords'].indexOf(type) !== -1) {
			return 'edit';
		}

		return structureTypes.indexOf(type) === -1 ? 'both' : '';
	}

	function getScreenContextFields(root) {
		var fields = {
			both: [],
			edit: [],
			registration: []
		};

		if (!root) {
			return fields;
		}

		root.querySelectorAll('li[data-field-id]').forEach(function (item) {
			var context = fieldScreenContext(getFieldItemType(item));

			if (context) {
				fields[context].push(fieldItemLabel(item));
			}
		});

		return fields;
	}

	function formatLocalized(template, values) {
		var result = template || '';

		values.forEach(function (value, index) {
			result = result.replace('%' + (index + 1) + '$s', value);
		});

		if (values.length === 1) {
			result = result.replace('%s', values[0]);
		}

		return result;
	}

	function formatItemList(items, template, separator) {
		return items.filter(Boolean).map(function (item) {
			return (template || '%s').replace('%s', item);
		}).join(separator || ', ');
	}

	function setStructureAvailabilityNote(item, messages) {
		var field = directField(item);
		var note = Array.prototype.filter.call(item.children, function (child) {
			return child.classList && child.classList.contains('atshift-upf-structure-availability-note');
		})[0] || null;

		if (!messages.length) {
			if (note) {
				note.remove();
			}
			return;
		}

		if (!note) {
			note = document.createElement('div');
			note.className = 'atshift-upf-structure-availability-note';
			note.setAttribute('role', 'note');
			if (field) {
				field.insertAdjacentElement('afterend', note);
			} else {
				item.insertBefore(note, item.firstChild);
			}
		}

		note.replaceChildren();
		messages.forEach(function (message) {
			var paragraph = document.createElement('p');
			paragraph.textContent = message;
			note.appendChild(paragraph);
		});
	}

	function updateConditionalAvailabilityNote(item, strings) {
		var choices = parseConditionalChoices(item);
		var branchStates = choices.map(function (choice) {
			var branch = conditionalBranchList(item, choice.value, choice.label);
			var fields = getScreenContextFields(branch);

			return {
				label: choice.label || choice.value,
				newAvailable: fields.both.length > 0 || fields.registration.length > 0,
				editAvailable: fields.both.length > 0 || fields.edit.length > 0
			};
		});
		var messages = [];
		var conditionFormat = strings.conditionalBranchDropLabel || 'Condition "%s"';
		var separator = strings.listSeparator || ', ';
		var newVisible = branchStates.filter(function (branch) { return branch.newAvailable; });
		var newHidden = branchStates.filter(function (branch) { return !branch.newAvailable; });
		var editVisible = branchStates.filter(function (branch) { return branch.editAvailable; });
		var editHidden = branchStates.filter(function (branch) { return !branch.editAvailable; });
		var formatConditions = function (branches) {
			return formatItemList(
				branches.map(function (branch) { return branch.label; }),
				conditionFormat,
				separator
			);
		};

		if (branchStates.length && !newVisible.length) {
			messages.push(strings.conditionalNewHidden || 'This Conditional Group is not shown on the Add New User screen because none of its conditions contain fields available there.');
		} else if (newHidden.length) {
			messages.push(formatLocalized(
				strings.conditionalNewPartial || 'On the Add New User screen, %1$s are unavailable, so only %2$s are shown.',
				[formatConditions(newHidden), formatConditions(newVisible)]
			));
		}

		if (branchStates.length && !editVisible.length) {
			messages.push(strings.conditionalEditHidden || 'This Conditional Group is not shown when editing an existing user because none of its conditions contain fields available there.');
		} else if (editHidden.length) {
			messages.push(formatLocalized(
				strings.conditionalEditPartial || 'When editing an existing user, %1$s are unavailable, so only %2$s are shown.',
				[formatConditions(editHidden), formatConditions(editVisible)]
			));
		}

		setStructureAvailabilityNote(item, messages);
	}

	function updateAccordionAvailabilityNote(item, strings) {
		var childList = directGroupDropzone(item);
		var fields = getScreenContextFields(childList);
		var messages = [];
		var fieldFormat = strings.fieldDisplayLabel || 'Field "%s"';
		var separator = strings.listSeparator || ', ';
		var formatFields = function (labels) {
			return formatItemList(labels, fieldFormat, separator);
		};
		var newAvailable = fields.both.length > 0 || fields.registration.length > 0;
		var editAvailable = fields.both.length > 0 || fields.edit.length > 0;

		if (!newAvailable && fields.edit.length) {
			messages.push(strings.accordionNewHidden || 'This Accordion is not shown on the Add New User screen because it contains no fields available there.');
		} else if (newAvailable && fields.edit.length) {
			messages.push(formatLocalized(
				strings.accordionNewPartial || 'On the Add New User screen, edit-only fields in this Accordion are omitted: %s.',
				[formatFields(fields.edit)]
			));
		}

		if (!editAvailable && fields.registration.length) {
			messages.push(strings.accordionEditHidden || 'This Accordion is not shown when editing an existing user because it contains no fields available there.');
		} else if (editAvailable && fields.registration.length) {
			messages.push(formatLocalized(
				strings.accordionEditPartial || 'When editing an existing user, registration-only fields in this Accordion are omitted: %s.',
				[formatFields(fields.registration)]
			));
		}

		setStructureAvailabilityNote(item, messages);
	}

	function updateStructureAvailabilityNotes() {
		var strings = (window.atshiftUPFAdmin && window.atshiftUPFAdmin.strings) || {};

		document.querySelectorAll('li[data-field-id]').forEach(function (item) {
			var type = getFieldItemType(item);

			if (type === 'conditional') {
				updateConditionalAvailabilityNote(item, strings);
			} else if (type === 'accordion') {
				updateAccordionAvailabilityNote(item, strings);
			} else {
				setStructureAvailabilityNote(item, []);
			}
		});
	}

	function getConditionalValueFromList(list) {
		return list && list.classList.contains('atshift-upf-conditional-branch-list') ? list.getAttribute('data-conditional-value') || '' : '';
	}

	function setConditionalValueForItem(item, value) {
		var field = directField(item);
		var conditionalInput = field ? field.querySelector('.atshift-upf-conditional-value-select') : null;

		if (!conditionalInput) {
			return;
		}

		conditionalInput.value = value || '';
		conditionalInput.setAttribute('data-current-value', conditionalInput.value);
		refreshConditionalAssignment(item);
	}

	function getConditionalValueForItem(item) {
		var field = directField(item);
		var conditionalInput = field ? field.querySelector('.atshift-upf-conditional-value-select') : null;

		return conditionalInput ? conditionalInput.value || conditionalInput.getAttribute('data-current-value') || '' : '';
	}

	function getDirectParentItem(item) {
		var parentList = item && item.parentElement && item.parentElement.classList.contains('atshift-upf-child-fields') ? item.parentElement : null;

		return parentList ? parentList.closest('li[data-field-id]') : null;
	}

	function refreshConditionalAssignment(item) {
		var field = directField(item);
		var row = field ? field.querySelector('.atshift-upf-condition-assignment-row') : null;
		var select = field ? field.querySelector('.atshift-upf-condition-assignment-select') : null;
		var parentItem = getDirectParentItem(item);
		var choices;
		var currentValue;
		var selectedExists;
		var strings = (window.atshiftUPFAdmin && window.atshiftUPFAdmin.strings) || {};

		if (!row || !select) {
			return;
		}

		if (!parentItem || getFieldItemType(parentItem) !== 'conditional') {
			row.hidden = true;
			select.innerHTML = '';
			return;
		}

		choices = parseConditionalChoices(parentItem);
		currentValue = getConditionalValueForItem(item);
		selectedExists = currentValue === '';
		select.innerHTML = '';
		select.appendChild(new Option(strings.alwaysShow || 'Always show', ''));

		choices.forEach(function (choice) {
			select.appendChild(new Option(choice.label + ' (' + choice.value + ')', choice.value));
			if (choice.value === currentValue) {
				selectedExists = true;
			}
		});

		if (!selectedExists) {
			select.appendChild(new Option(currentValue + ' (' + currentValue + ')', currentValue));
		}

		select.value = currentValue;
		row.hidden = false;
	}

	function moveItemToConditionalBranch(item, value) {
		var parentItem = getDirectParentItem(item);
		var choice = null;
		var target;

		if (!item || !parentItem || getFieldItemType(parentItem) !== 'conditional') {
			return;
		}

		parseConditionalChoices(parentItem).some(function (candidate) {
			if (candidate.value === value) {
				choice = candidate;
				return true;
			}
			return false;
		});

		setConditionalValueForItem(item, value);
		target = value ? conditionalBranchList(parentItem, value, choice ? choice.label : value) : directGroupDropzone(parentItem);

		if (target && item.parentElement !== target) {
			target.appendChild(item);
		}

		syncConditionalBranches(parentItem);
		syncParentFieldsFromDom();
		updateGroupDropzones(parentItem);
		initSortableFields();
	}

	function getConditionalBranchAtPoint(event, item) {
		var originalEvent = event && event.originalEvent ? event.originalEvent : event;
		var clientX = originalEvent && typeof originalEvent.clientX === 'number' ? originalEvent.clientX : null;
		var clientY = originalEvent && typeof originalEvent.clientY === 'number' ? originalEvent.clientY : null;
		var elements;
		var branch = null;

		if (clientX === null || clientY === null || !document.elementsFromPoint) {
			return null;
		}

		elements = document.elementsFromPoint(clientX, clientY);
		elements.some(function (element) {
			var candidate;

			if (item && (element === item || item.contains(element))) {
				return false;
			}

			candidate = element.closest ? element.closest('.atshift-upf-conditional-branch-list') : null;
			if (!candidate || (item && item.contains(candidate))) {
				return false;
			}

			branch = candidate;
			return true;
		});

		return branch;
	}

	function getNestedStructureDropzoneAtPoint(event, item) {
		var originalEvent = event && event.originalEvent ? event.originalEvent : event;
		var clientX = originalEvent && typeof originalEvent.clientX === 'number' ? originalEvent.clientX : null;
		var clientY = originalEvent && typeof originalEvent.clientY === 'number' ? originalEvent.clientY : null;
		var dropzone = null;
		var smallestArea = Infinity;

		if (clientX === null || clientY === null) {
			return null;
		}

		document.querySelectorAll('.atshift-upf-child-fields').forEach(function (candidate) {
			var parentItem;
			var parentType;
			var rect;
			var area;

			parentItem = candidate ? candidate.closest('li[data-field-id]') : null;
			parentType = parentItem ? getFieldItemType(parentItem) : '';

			if (
				!candidate ||
				!parentItem ||
				parentType === 'conditional' ||
				directGroupDropzone(parentItem) !== candidate ||
				(item && (item.contains(candidate) || item.contains(parentItem))) ||
				isDisallowedParentChild(parentItem, item)
			) {
				return;
			}

			rect = candidate.getBoundingClientRect();
			if (clientX < rect.left || clientX > rect.right || clientY < rect.top || clientY > rect.bottom) {
				return;
			}

			area = rect.width * rect.height;
			if (area < smallestArea) {
				smallestArea = area;
				dropzone = candidate;
			}
		});

		return dropzone;
	}

	function placeItemAtNestedDropPoint(event, item) {
		var itemData = window.jQuery ? window.jQuery(item) : null;
		var dropzone = itemData ? itemData.data('atshiftUpfNestedDropzone') : null;
		var branch = itemData ? itemData.data('atshiftUpfConditionalBranch') : null;
		var parentItem = branch ? branch.closest('li[data-field-id]') : null;

		dropzone = dropzone || getNestedStructureDropzoneAtPoint(event, item);
		branch = dropzone ? null : (branch || getConditionalBranchAtPoint(event, item));
		parentItem = branch ? branch.closest('li[data-field-id]') : null;

		if (itemData) {
			itemData.removeData('atshiftUpfNestedDropzone atshiftUpfConditionalBranch');
		}

		if (dropzone) {
			placeItemInNestedList(event, dropzone, item);
			setConditionalValueForItem(item, '');
			return true;
		}

		if (!branch || !parentItem || getFieldItemType(parentItem) !== 'conditional' || isDisallowedParentChild(parentItem, item)) {
			return false;
		}

		placeItemInNestedList(event, branch, item);
		setConditionalValueForItem(item, branch.getAttribute('data-conditional-value') || '');
		return true;
	}

	function placeItemInNestedList(event, list, item) {
		var originalEvent = event && event.originalEvent ? event.originalEvent : event;
		var clientY = originalEvent && typeof originalEvent.clientY === 'number' ? originalEvent.clientY : null;
		var nextItem = null;

		if (!list || !item) {
			return;
		}

		// jQuery UI has already resolved the exact sibling position in this list.
		if (item.parentElement === list) {
			return;
		}

		if (clientY !== null) {
			directFieldItems(list).some(function (candidate) {
				var rect;

				if (candidate === item) {
					return false;
				}

				rect = candidate.getBoundingClientRect();
				if (clientY < rect.top + (rect.height / 2)) {
					nextItem = candidate;
					return true;
				}

				return false;
			});
		}

		list.insertBefore(item, nextItem);
	}

	function rememberNestedDropPoint(event, item) {
		var itemData = window.jQuery ? window.jQuery(item) : null;
		var dropzone;
		var branch;

		if (!itemData) {
			return;
		}

		dropzone = getNestedStructureDropzoneAtPoint(event, item);
		branch = dropzone ? null : getConditionalBranchAtPoint(event, item);
		itemData.data('atshiftUpfNestedDropzone', dropzone || null);
		itemData.data('atshiftUpfConditionalBranch', branch || null);
	}

	function trackActiveNestedDropPoint(event) {
		if (activeDraggedItem) {
			rememberNestedDropPoint(event, activeDraggedItem);
		}
	}

	document.addEventListener('mousemove', trackActiveNestedDropPoint, true);
	document.addEventListener('pointermove', trackActiveNestedDropPoint, true);

	function countBranchChildren(branch) {
		return branch ? Array.prototype.filter.call(branch.children, function (child) {
			return child.matches && child.matches('li[data-field-id]');
		}).length : 0;
	}

	function autoAssignUnassignedConditionalChildren(context) {
		var scope = context || document;
		var conditionals = [];
		var changed = false;

		if (scope.matches && getFieldItemType(scope) === 'conditional') {
			conditionals.push(scope);
		}

		if (scope.querySelectorAll) {
			Array.prototype.forEach.call(scope.querySelectorAll('li[data-field-id]'), function (item) {
				if (getFieldItemType(item) === 'conditional') {
					conditionals.push(item);
				}
			});
		}

		conditionals.forEach(function (conditional) {
			var choices = parseConditionalChoices(conditional);
			var primary = directGroupDropzone(conditional);
			var emptyBranches = [];

			if (!choices.length || !primary) {
				return;
			}

			syncConditionalBranches(conditional);

			choices.forEach(function (choice) {
				var branch = conditionalBranchList(conditional, choice.value, choice.label);
				if (branch && countBranchChildren(branch) < 1) {
					emptyBranches.push({
						value: choice.value,
						list: branch
					});
				}
			});

			Array.prototype.slice.call(primary.children).forEach(function (child) {
				var branch;

				if (!child.matches || !child.matches('li[data-field-id]')) {
					return;
				}

				if (getConditionalValueForItem(child)) {
					return;
				}

				branch = emptyBranches.shift();
				if (!branch) {
					return;
				}

				setConditionalValueForItem(child, branch.value);
				branch.list.appendChild(child);
				changed = true;
			});

			syncConditionalBranches(conditional);
		});

		return changed;
	}

	function syncConditionalBranches(item) {
		var type = getFieldItemType(item);
		var primary;
		var choices;
		var valid = {};

		if (!item || type !== 'conditional') {
			return;
		}

		primary = ensureGroupDropzone(item);
		if (!primary) {
			return;
		}

		choices = parseConditionalChoices(item);
		primary.classList.add('atshift-upf-conditional-primary-list');
		primary.setAttribute('data-conditional-label', choices.length ? 'Always show' : 'Set choices before adding fields');

		choices.forEach(function (choice) {
			if (!valid[choice.value]) {
				valid[choice.value] = conditionalBranchList(item, choice.value, choice.label);
			}
		});

		item.querySelectorAll(':scope > .atshift-upf-conditional-branch-list').forEach(function (branch) {
			var value = branch.getAttribute('data-conditional-value') || '';

			if (valid[value]) {
				return;
			}

			Array.prototype.slice.call(branch.children).forEach(function (child) {
				primary.appendChild(child);
			});
			branch.remove();
		});

		item.querySelectorAll(':scope > .atshift-upf-child-fields > li[data-field-id]').forEach(function (child) {
			var childField = directField(child);
			var conditionalInput = childField ? childField.querySelector('.atshift-upf-conditional-value-select') : null;
			var value = conditionalInput ? conditionalInput.value || conditionalInput.getAttribute('data-current-value') || '' : '';
			var target = value && valid[value] ? valid[value] : primary;

			if (child.parentElement !== target) {
				target.appendChild(child);
			}
		});

		primary.classList.toggle('is-empty', directFieldItems(primary).length < 1);
		item.querySelectorAll(':scope > .atshift-upf-conditional-branch-list').forEach(function (branch) {
			branch.classList.toggle('has-fields', directFieldItems(branch).length > 0);
		});
	}

	function updateFieldParent(item, parentItem) {
		var field = directField(item);
		var parentInput = field ? field.querySelector('.atshift-upf-parent-select') : null;
		var conditional = field ? field.querySelector('.atshift-upf-conditional-value-select') : null;
		var parentId = parentItem ? parentItem.getAttribute('data-field-id') : '';

		if (!parentInput) {
			return;
		}

		parentInput.value = parentId || '';

		if (conditional && (!parentItem || getFieldItemType(parentItem) !== 'conditional')) {
			conditional.value = '';
			conditional.setAttribute('data-current-value', '');
		} else if (conditional && parentItem && getFieldItemType(parentItem) === 'conditional') {
			conditional.value = getConditionalValueFromList(item.parentElement);
			conditional.setAttribute('data-current-value', conditional.value || '');
		}

		refreshConditionalAssignment(item);
	}

	function moveChildrenOutsideStructure(item) {
		var insertionPoint = item;

		directChildLists(item).forEach(function (list) {
			directFieldItems(list).forEach(function (child) {
				insertionPoint.insertAdjacentElement('afterend', child);
				insertionPoint = child;
			});
			list.remove();
		});
	}

	function mergeConditionalLists(item) {
		var primary = directGroupDropzone(item);

		if (!primary) {
			return;
		}

		item.querySelectorAll(':scope > .atshift-upf-conditional-branch-list').forEach(function (branch) {
			directFieldItems(branch).forEach(function (child) {
				primary.appendChild(child);
			});
			branch.remove();
		});

		primary.classList.remove('atshift-upf-conditional-primary-list', 'is-empty');
		primary.removeAttribute('data-conditional-value');
		primary.removeAttribute('data-conditional-label');
	}

	function updateGroupDropzones(context) {
		var scope = context && context.querySelectorAll ? context : document;
		var items = [];

		if (scope.matches && scope.matches('li[data-field-id]')) {
			items.push(scope);
		}
		Array.prototype.forEach.call(scope.querySelectorAll('li[data-field-id]'), function (item) {
			if (items.indexOf(item) === -1) {
				items.push(item);
			}
		});

		items.forEach(function (item) {
			var type = getFieldItemType(item);

			refreshStructureMarker(item);
			if (structureTypes.indexOf(type) === -1) {
				if (directChildLists(item).length) {
					moveChildrenOutsideStructure(item);
				}
				return;
			}

			ensureGroupDropzone(item);
			if (type === 'conditional') {
				syncConditionalBranches(item);
			} else {
				mergeConditionalLists(item);
			}
		});

		updateStructureAvailabilityNotes();
	}

	function syncParentFieldsFromDom() {
		var root = document.querySelector('.atshift-upf-sortable-fields');

		if (!root) {
			return;
		}

		root.querySelectorAll('li[data-field-id]').forEach(function (item) {
			var parentList = item.parentElement && item.parentElement.classList.contains('atshift-upf-child-fields') ? item.parentElement : null;
			var parentItem = parentList ? parentList.closest('li[data-field-id]') : null;

			if (parentItem && isDisallowedParentChild(parentItem, item)) {
				parentItem.insertAdjacentElement('afterend', item);
				updateFieldParent(item, null);
				return;
			}

			updateFieldParent(item, parentItem);
		});
	}

	function placeSavedChildrenInGroups() {
		var root = document.querySelector('.atshift-upf-sortable-fields');

		if (!root) {
			return;
		}

		updateGroupDropzones(root);

		Array.prototype.slice.call(root.querySelectorAll('li[data-field-id]')).forEach(function (item) {
			var field = directField(item);
			var parentInput = field ? field.querySelector('.atshift-upf-parent-select') : null;
			var parentId = parentInput ? parentInput.value : '';
			var parentItem;
			var childList;
			var conditionalValue;

			if (!parentId) {
				return;
			}

			parentItem = findFieldItemById(root, parentId);
			childList = parentItem ? directChildList(parentItem) : null;
			conditionalValue = getConditionalValueForItem(item);

			if (parentItem && getFieldItemType(parentItem) === 'conditional' && conditionalValue) {
				childList = conditionalBranchList(parentItem, conditionalValue, conditionalValue) || childList;
			}

			if (childList && item.parentElement !== childList && !item.contains(parentItem)) {
				childList.appendChild(item);
			}
		});

		updateGroupDropzones(root);
	}

	function updateStandardFieldAvailability() {
		var strings = (window.atshiftUPFAdmin && window.atshiftUPFAdmin.strings) || {};
		var selectedCounts = {};
		var alreadyAddedLabel = strings.standardFieldAlreadyAdded || 'Already added';
		var selects = Array.prototype.slice.call(document.querySelectorAll('.atshift-upf-field-type-select'));

		selects.forEach(function (select) {
			var type = select.value;

			if (!isSingleUseCoreFieldType(type)) {
				return;
			}

			selectedCounts[type] = (selectedCounts[type] || 0) + 1;
		});

		selects.forEach(function (select) {
			Array.prototype.forEach.call(select.options, function (option) {
				var type = option.value;
				var shouldDisable = isSingleUseCoreFieldType(type) && selectedCounts[type] && select.value !== type;
				var originalText = option.getAttribute('data-atshift-upf-original-label');

				if (!isSingleUseCoreFieldType(type)) {
					return;
				}

				if (!originalText) {
					originalText = option.textContent;
					option.setAttribute('data-atshift-upf-original-label', originalText);
				}

				option.disabled = !!shouldDisable;
				option.textContent = shouldDisable ? originalText + ' (' + alreadyAddedLabel + ')' : originalText;
			});
		});
	}

	function initFieldTypeOptions() {
		var ajaxConfig = window.atshiftUPFAdmin || {};
		var strings = ajaxConfig.strings || {};
		var fieldTypeButtonLabels = ajaxConfig.fieldTypeButtonLabels || {};
		var legacyDefaultDescriptions = ajaxConfig.legacyDefaultDescriptions || [];
		var generatedKeyTypes = [];

		document.querySelectorAll('.atshift-upf-cfs-form').forEach(function (form) {
			var typeSelect = form.querySelector('.atshift-upf-field-type-select');
			var optionRows = form.querySelectorAll('[data-atshift-upf-types]');
			var keyInput = form.querySelector('.atshift-upf-field-key-input');
			var keyLabel = form.querySelector('.atshift-upf-generated-field-key-label');
			var parentSelect = form.querySelector('.atshift-upf-parent-select');
			var conditionalValueSelect = form.querySelector('.atshift-upf-conditional-value-select');
			var descriptionInput = form.querySelector('textarea[name$="[description]"]');
			var labelInput = form.querySelector('input[name$="[label]"]');
			var choicesInput = form.querySelector('textarea[name$="[choices]"]');
			var initialStateInput = form.querySelector('input[name$="[initial_enabled]"]');
			var initialStateSubject = form.querySelector('.atshift-upf-initial-state-subject');
			var initialStateDefaults = {
				core_visual_editor: true,
				core_syntax_highlighting: true,
				core_keyboard_shortcuts: false,
				core_toolbar: true,
				core_notification: true
			};

			if (form.dataset.atshiftUpfTypeReady === '1') {
				return;
			}

			if (!typeSelect || !optionRows.length) {
				return;
			}

			form.dataset.atshiftUpfTypeReady = '1';

			function isGeneratedKeyType(type) {
				return generatedKeyTypes.indexOf(type) !== -1 || !!fieldTypeButtonLabels[type];
			}

			function updateGeneratedKey() {
				var type = typeSelect.value;
				var generated = isGeneratedKeyType(type);

				if (keyInput) {
					keyInput.disabled = false;
					keyInput.required = false;
				}

				if (keyLabel) {
					var currentKey = keyInput ? keyInput.value.trim() : '';
					keyLabel.hidden = !generated;
					keyLabel.textContent = currentKey || strings.automaticallyNamed || 'Automatically named when saved.';
				}
			}

			function updateDefaultDescription() {
				var type = typeSelect.value;
				var defaults = [
					strings.coreUsernameDescription || '',
					strings.coreEmailDescription || '',
					strings.corePasswordDescription || '',
					strings.additionalNameDescription || ''
				].concat(legacyDefaultDescriptions);
				var canFillEmptyDescription = form.getAttribute('data-atshift-upf-saved-field') !== '1';
				var description = '';

				if (!descriptionInput) {
					return;
				}

				if (type === 'core_username') {
					description = strings.coreUsernameDescription || '';
				}

				if (type === 'core_email') {
					description = strings.coreEmailDescription || '';
				}

				if (type === 'core_password') {
					description = strings.corePasswordDescription || '';
				}

				if (type === 'additional_name') {
					description = strings.additionalNameDescription || '';
				}

				if (!description) {
					return;
				}

				if ((canFillEmptyDescription && descriptionInput.value.trim() === '') || defaults.indexOf(descriptionInput.value) !== -1) {
					descriptionInput.value = description;
				}
			}

			function updateHeaderLabel() {
				var field = form.closest('.field');
				var label = labelInput ? labelInput.value.trim() : '';

				if (!field) {
					return;
				}

				field.querySelectorAll('.cfs-field-label-text').forEach(function (labelNode) {
					labelNode.textContent = label || strings.newField || 'New Field';
				});
			}

			function initRoleControlSelect2() {
				if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
					return;
				}

				window.jQuery(form).find('.atshift-upf-role-control-select').each(function () {
					var select = this;
					var next = select.nextElementSibling;
					var placeholder = select.getAttribute('data-placeholder') || '';
					var optionNodes;

					if (window.jQuery(select).data('select2')) {
						return;
					}

					if (next && next.classList.contains('select2-container')) {
						next.remove();
					}

					select.classList.remove('select2-hidden-accessible');
					select.removeAttribute('data-select2-id');
					select.removeAttribute('tabindex');
					select.removeAttribute('aria-hidden');

					optionNodes = select.querySelectorAll('[data-select2-id]');
					optionNodes.forEach(function (option) {
						option.removeAttribute('data-select2-id');
					});

					window.jQuery(select).select2({
						placeholder: placeholder,
						width: '100%'
					});
				});
			}

			function updateConditionalChoices() {
				var fieldItem = form.closest('li[data-field-id]');

				if (fieldItem) {
					refreshConditionalAssignment(fieldItem);
				}
			}

			function updateInitialState(type, useTypeDefault) {
				if (!initialStateInput || !Object.prototype.hasOwnProperty.call(initialStateDefaults, type)) {
					return;
				}

				if (useTypeDefault) {
					initialStateInput.checked = initialStateDefaults[type];
				}

				if (initialStateSubject) {
					initialStateSubject.textContent = type === 'core_notification'
						? (strings.initialStateAccountEmail || 'Send account email')
						: (strings.initialStateEnabled || 'Enabled');
				}
			}

			function updateRows(useTypeDefault) {
				var type = typeSelect.value;
				var field = form.closest('.field');
				var fieldItem = field ? field.closest('li[data-field-id]') : null;

				optionRows.forEach(function (row) {
					var types = (row.getAttribute('data-atshift-upf-types') || '').split(/\s+/);
					var shouldShow = types.indexOf(type) !== -1;

					row.classList.toggle('atshift-upf-type-option-hidden', !shouldShow);
					row.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
				});

				updateGeneratedKey();
				updateDefaultDescription();
				updateConditionalChoices();
				updateInitialState(type, !!useTypeDefault);

				if (fieldItem) {
					fieldItem.setAttribute('data-field-type', type);
					refreshStructureMarker(fieldItem);
				}
				if (field) {
					field.querySelectorAll('.cfs-field-type-text').forEach(function (label) {
						label.textContent = fieldTypeButtonLabels[type] || type;
					});
				}

				window.setTimeout(function () {
					updateGroupDropzones(fieldItem || document);
					initSortableFields();
					syncParentFieldsFromDom();
					if (autoAssignUnassignedConditionalChildren(fieldItem || document)) {
						syncParentFieldsFromDom();
					}
					updateGroupDropzones(fieldItem || document);
					updateFullWidthBadges(document);
					updateStandardFieldAvailability();
				}, 0);
			}

			typeSelect.addEventListener('change', function () {
				updateRows(true);
			});
			if (labelInput) {
				labelInput.addEventListener('input', updateHeaderLabel);
			}
			if (choicesInput) {
				choicesInput.addEventListener('input', function () {
					window.setTimeout(function () {
						var item = form.closest('li[data-field-id]') || document;

						updateGroupDropzones(item);
						initSortableFields();
						syncParentFieldsFromDom();
						if (autoAssignUnassignedConditionalChildren(item)) {
							syncParentFieldsFromDom();
						}
						updateGroupDropzones(item);
					}, 0);
				});
			}
			if (parentSelect) {
				parentSelect.addEventListener('change', updateConditionalChoices);
			}
			if (conditionalValueSelect) {
				conditionalValueSelect.addEventListener('change', function () {
					conditionalValueSelect.setAttribute('data-current-value', conditionalValueSelect.value || '');
				});
			}
			updateRows(false);
			updateHeaderLabel();
			initRoleControlSelect2();
			updateStandardFieldAvailability();
		});
	}

	initFieldTypeOptions();

	function initFieldSetReset() {
		var form = document.querySelector('[data-atshift-upf-reset-form]');
		var strings = (window.atshiftUPFAdmin && window.atshiftUPFAdmin.strings) || {};

		if (!form) {
			return;
		}

		form.addEventListener('submit', function (event) {
			if (!window.confirm(strings.resetConfirm || 'Start over with this field set? This cannot be undone.')) {
				event.preventDefault();
			}
		});
	}

	initFieldSetReset();

	function initEmptyFieldSetUpload() {
		var trigger = document.querySelector('[data-atshift-upf-empty-upload-trigger]');
		var defaultTrigger = document.querySelector('[data-atshift-upf-empty-default-trigger]');
		var fileInput = document.querySelector('[data-atshift-upf-empty-upload-file]');
		var status = document.querySelector('[data-atshift-upf-empty-upload-status]');
		var config = window.atshiftUPFAdmin || {};
		var strings = config.strings || {};

		if (!trigger || !defaultTrigger || !fileInput || !status) {
			return;
		}

		function showStatus(message, isError) {
			status.textContent = message || '';
			status.classList.toggle('is-error', !!isError);
		}

		function finish() {
			trigger.disabled = false;
			defaultTrigger.disabled = false;
			fileInput.value = '';
		}

		function importFieldSet(actionType, code, workingMessage, fallbackMessage) {
			var body = new window.URLSearchParams();

			trigger.disabled = true;
			defaultTrigger.disabled = true;
			showStatus(workingMessage, false);
			body.append('action', 'atshift_upf_tools');
			body.append('action_type', actionType);
			body.append('nonce', config.toolsNonce || '');
			if (typeof code === 'string') {
				body.append('import_code', code);
			}

			window.fetch(config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				body: body.toString()
			}).then(function (response) {
				return response.json();
			}).then(function (response) {
				if (!response || !response.success) {
					throw new Error(response && response.data && response.data.message ? response.data.message : fallbackMessage);
				}

				window.location.assign((config.pageUrl || window.location.href) + '&atshift_upf_imported=1');
			}).catch(function (error) {
				showStatus(error.message || fallbackMessage, true);
				finish();
			});
		}

		defaultTrigger.addEventListener('click', function () {
			importFieldSet(
				'import_default_empty',
				null,
				strings.loadingDefaultFieldSet || 'Loading the default field set...',
				strings.defaultFieldSetFailed || 'The default field set could not be loaded.'
			);
		});

		trigger.addEventListener('click', function () {
			fileInput.value = '';
			fileInput.click();
		});

		fileInput.addEventListener('change', function () {
			var file = fileInput.files && fileInput.files[0];
			var reader;

			if (!file) {
				return;
			}

			if (file.size > 1048576) {
				showStatus(strings.fieldSetReadFailed || 'The selected field set could not be read.', true);
				finish();
				return;
			}

				reader = new window.FileReader();

				reader.addEventListener('load', function () {
					importFieldSet(
						'import_empty',
						typeof reader.result === 'string' ? reader.result : '',
						strings.uploadingFieldSet || 'Uploading field set...',
						strings.fieldSetUploadFailed || 'The field set could not be uploaded.'
					);
				});

			reader.addEventListener('error', function () {
				showStatus(strings.fieldSetReadFailed || 'The selected field set could not be read.', true);
				finish();
			});

			reader.readAsText(file);
		});
	}

	initEmptyFieldSetUpload();

	function initSortableFields() {
		if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.sortable) {
			return;
		}

		window.jQuery('.atshift-upf-sortable-fields, .atshift-upf-child-fields').each(function () {
			var list = window.jQuery(this);

			if (list.data('ui-sortable')) {
				list.sortable('refresh');
				list.sortable('refreshPositions');
				return;
			}

			list.sortable({
				connectWith: '.atshift-upf-sortable-fields, .atshift-upf-child-fields',
				cursor: 'move',
				dropOnEmpty: true,
				forcePlaceholderSize: true,
				handle: '.field_order',
				items: '> li[data-field-id]',
				placeholder: 'ui-sortable-placeholder',
				tolerance: 'pointer',
				start: function (event, ui) {
					var children = ui.item.children('.atshift-upf-child-fields').detach();

					activeDraggedItem = ui.item[0];
					ui.item.data('atshiftUpfDragChildren', children);
					document.querySelectorAll('.atshift-upf-cfs-fields').forEach(function (fields) {
						fields.classList.add('atshift-upf-is-dragging');
					});
				},
				sort: function (event, ui) {
					rememberNestedDropPoint(event, ui.item[0]);
				},
				beforeStop: function (event, ui) {
					var children = ui.item.data('atshiftUpfDragChildren');

					if (children && children.length) {
						ui.item.append(children);
					}
				},
				receive: function (event, ui) {
					var parentItem = this.classList.contains('atshift-upf-child-fields') ? this.closest('li[data-field-id]') : null;

					if (parentItem && isDisallowedParentChild(parentItem, ui.item[0])) {
						window.jQuery(this).sortable('cancel');
						return;
					}

					if (parentItem && getFieldItemType(parentItem) !== 'conditional') {
						ui.item.data('atshiftUpfNestedDropzone', this);
						ui.item.data('atshiftUpfConditionalBranch', null);
					}
				},
				over: function (event, ui) {
					var parentItem = this.classList.contains('atshift-upf-child-fields') ? this.closest('li[data-field-id]') : null;

					this.classList.add('atshift-upf-drop-target');
					if (parentItem && getFieldItemType(parentItem) !== 'conditional' && !isDisallowedParentChild(parentItem, ui.item[0])) {
						ui.item.data('atshiftUpfNestedDropzone', this);
						ui.item.data('atshiftUpfConditionalBranch', null);
					} else if (this.classList.contains('atshift-upf-conditional-branch-list')) {
						ui.item.data('atshiftUpfNestedDropzone', null);
						ui.item.data('atshiftUpfConditionalBranch', this);
					}
				},
				out: function () {
					this.classList.remove('atshift-upf-drop-target');
				},
				stop: function (event, ui) {
					var root = document.querySelector('.atshift-upf-sortable-fields') || document;

					ui.item.removeData('atshiftUpfDragChildren');
					placeItemAtNestedDropPoint(event, ui.item[0]);
					activeDraggedItem = null;
					document.querySelectorAll('.atshift-upf-cfs-fields').forEach(function (fields) {
						fields.classList.remove('atshift-upf-is-dragging');
					});
					document.querySelectorAll('.atshift-upf-drop-target').forEach(function (target) {
						target.classList.remove('atshift-upf-drop-target');
					});

					updateGroupDropzones(root);
					syncParentFieldsFromDom();
					if (autoAssignUnassignedConditionalChildren(root)) {
						syncParentFieldsFromDom();
					}
					updateGroupDropzones(root);
					updateFullWidthBadges(root);
					initSortableFields();
				}
			});
		});
	}

	initSortableFields();

	function initFieldRows() {
		var list = document.querySelector('.atshift-upf-sortable-fields');
		var template = document.querySelector('#atshift-upf-field-template');
		var empty = document.querySelector('.atshift-upf-empty');
		var strings = (window.atshiftUPFAdmin && window.atshiftUPFAdmin.strings) || {};

		function updateEmptyState() {
			if (!empty || !list) {
				return;
			}

			empty.classList.toggle('is-hidden', !!list.querySelector('li'));
		}

		function nextIndex() {
			var index = parseInt(list.getAttribute('data-next-field-index') || '0', 10);
			list.setAttribute('data-next-field-index', String(index + 1));
			return index;
		}

		function rewriteDirectFieldNames(item, index) {
			var field = directField(item);
			var clientId = 'new_' + index;

			if (!field) {
				return clientId;
			}

			field.querySelectorAll('[name]').forEach(function (input) {
				input.name = input.name.replace(/fields\\[[^\\]]+\\]/, 'fields[' + index + ']');
			});
			item.setAttribute('data-field-id', clientId);
			field.querySelectorAll('.atshift-upf-client-id-input').forEach(function (input) {
				input.value = clientId;
			});
			field.querySelectorAll('input[name$="[field_id]"]').forEach(function (input) {
				input.value = '';
			});

			return clientId;
		}

		function prepareDuplicatedSubtree(clone) {
			var items = [clone].concat(Array.prototype.slice.call(clone.querySelectorAll('li[data-field-id]')));
			var idMap = {};

			items.forEach(function (duplicatedItem) {
				var oldId = duplicatedItem.getAttribute('data-field-id') || '';
				var index = nextIndex();
				var newId = rewriteDirectFieldNames(duplicatedItem, index);

				if (oldId) {
					idMap[oldId] = newId;
				}
			});

			items.forEach(function (duplicatedItem) {
				var field = directField(duplicatedItem);
				var parentInput = field ? field.querySelector('.atshift-upf-parent-select') : null;
				var oldParentId = parentInput ? parentInput.value : '';

				if (parentInput && idMap[oldParentId]) {
					parentInput.value = idMap[oldParentId];
				}

				directChildLists(duplicatedItem).forEach(function (childList) {
					childList.setAttribute('data-parent-field-id', duplicatedItem.getAttribute('data-field-id') || '');
				});

				if (field) {
					field.querySelectorAll('.select2-container').forEach(function (container) {
						container.remove();
					});
					field.querySelectorAll('[data-select2-id]').forEach(function (node) {
						node.removeAttribute('data-select2-id');
					});
					field.querySelectorAll('.select2-hidden-accessible').forEach(function (select) {
						select.classList.remove('select2-hidden-accessible');
						select.removeAttribute('aria-hidden');
						select.removeAttribute('tabindex');
					});
					field.querySelectorAll('.atshift-upf-cfs-form').forEach(function (fieldForm) {
						fieldForm.dataset.atshiftUpfTypeReady = '';
					});
				}
			});
		}

		function copyCurrentControlValues(source, clone) {
			var sourceControls = source.querySelectorAll('input, select, textarea');
			var cloneControls = clone.querySelectorAll('input, select, textarea');

			sourceControls.forEach(function (sourceControl, controlIndex) {
				var cloneControl = cloneControls[controlIndex];

				if (!cloneControl) {
					return;
				}

				if (sourceControl.matches('input[type="checkbox"], input[type="radio"]')) {
					cloneControl.checked = sourceControl.checked;
					return;
				}

				if (sourceControl.matches('select[multiple]')) {
					Array.prototype.forEach.call(cloneControl.options, function (option, optionIndex) {
						option.selected = !!(sourceControl.options[optionIndex] && sourceControl.options[optionIndex].selected);
					});
					return;
				}

				cloneControl.value = sourceControl.value;
			});
		}

		function closeActionMenu(menu) {
			var wrapper = menu.closest('.cfs-field-action-menu');

			menu.hidden = true;
			if (wrapper) {
				wrapper.classList.remove('is-open-above');
			}
		}

		function updateActionMenuDirection(wrapper, menu, button) {
			var menuRect;
			var buttonRect;
			var field;
			var fieldRect;
			var lowerLimit;
			var upperLimit;
			var belowSpace;
			var aboveSpace;

			if (!wrapper || !menu || !button || menu.hidden) {
				return;
			}

			wrapper.classList.remove('is-open-above');

			field = wrapper.closest('.field');
			menuRect = menu.getBoundingClientRect();
			buttonRect = button.getBoundingClientRect();
			fieldRect = field ? field.getBoundingClientRect() : null;
			lowerLimit = Math.min(window.innerHeight, fieldRect ? fieldRect.bottom : window.innerHeight);
			upperLimit = Math.max(0, fieldRect ? fieldRect.top : 0);
			belowSpace = lowerLimit - buttonRect.bottom;
			aboveSpace = buttonRect.top - upperLimit;

			if (belowSpace < menuRect.height + 8 && aboveSpace > belowSpace) {
				wrapper.classList.add('is-open-above');
			}
		}

		function getFieldListItem(control) {
			var field = control ? control.closest('.field') : null;
			return field ? field.closest('li[data-field-id]') : null;
		}

			function updateAddBelowButtons() {
				document.querySelectorAll('.atshift-upf-add-field-below').forEach(function (button) {
					var item = getFieldListItem(button);
					var isStructure = isStructureFieldItem(item);

					button.textContent = isStructure ? strings.addFieldInsideGroup || 'Add field inside group' : strings.addFieldBelow || 'Add new field below';
				});
			}

			function addField(afterItem, parentItem) {
				var index;
				var wrapper;
				var item;
				var firstInput;
				var targetList = list;
				var childList;

				if (!list || !template) {
					return;
				}

			index = nextIndex();
			wrapper = document.createElement('div');
			wrapper.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(index));
			item = wrapper.firstElementChild;

			if (!item) {
				return;
			}

			directField(item).querySelectorAll('.atshift-upf-client-id-input').forEach(function (input) {
				input.value = item.getAttribute('data-field-id') || '';
			});

				if (parentItem) {
					childList = ensureGroupDropzone(parentItem);
					if (childList) {
						targetList = childList;
					}
				} else if (afterItem && afterItem.parentElement && afterItem.parentElement.classList.contains('fields')) {
					targetList = afterItem.parentElement;
				}

				if (afterItem && afterItem.parentNode === targetList) {
					afterItem.insertAdjacentElement('afterend', item);
				} else {
					targetList.appendChild(item);
				}

				if (parentItem) {
					updateFieldParent(item, parentItem);
				}

				updateEmptyState();
				initFieldTypeOptions();
				initSortableFields();
				updateGroupDropzones(parentItem || item);
				syncParentFieldsFromDom();
				if (autoAssignUnassignedConditionalChildren(parentItem || item)) {
					syncParentFieldsFromDom();
				}
				updateGroupDropzones(parentItem || item);
					updateAddBelowButtons();
					updateFullWidthBadges(document);
					updateStandardFieldAvailability();

				item.scrollIntoView({ block: 'center', behavior: 'smooth' });
			firstInput = item.querySelector('input:not([type="hidden"]):not(:disabled), select:not(:disabled), textarea:not(:disabled)');
			if (firstInput) {
				window.setTimeout(function () {
					firstInput.focus();
				}, 250);
			}
		}

		document.querySelectorAll('.atshift-upf-add-field-link').forEach(function (button) {
			if (button.dataset.atshiftUpfAddReady === '1') {
				return;
			}

			button.dataset.atshiftUpfAddReady = '1';
			button.addEventListener('click', function () {
				addField(null);
			});
		});

			document.addEventListener('click', function (event) {
				var toggle = event.target.closest('.cfs_edit_field');
				var addBelow = event.target.closest('.atshift-upf-add-field-below');
				var deleteButton = event.target.closest('.atshift-upf-delete-field');
			var duplicateButton = event.target.closest('.atshift-upf-duplicate-field');
			var actionsToggle = event.target.closest('.atshift-upf-field-actions-toggle');
			var field;
			var form;
			var item;
			var clone;
			var index;

			if (toggle) {
				event.preventDefault();
				field = toggle.closest('.field');
				form = field ? field.querySelector('.field_form') : null;

				if (!field || !form) {
					return;
				}

				var open = window.getComputedStyle(form).display === 'none';
				field.classList.toggle('form_open', open);
				if (window.jQuery) {
					window.jQuery(form).stop(true, true);
					if (open) {
						window.jQuery(form).slideDown('fast');
					} else {
						window.jQuery(form).slideUp('fast');
					}
				} else {
					form.style.display = open ? 'block' : 'none';
				}
				field.querySelectorAll('.cfs-field-type-toggle').forEach(function (typeToggle) {
					var icon = typeToggle.querySelector('.cfs-field-toggle-icon');
					typeToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
					typeToggle.setAttribute('title', open ? strings.closeFieldSettings || 'Close field settings' : strings.openFieldSettings || 'Open field settings');
					if (icon) {
						icon.classList.toggle('dashicons-arrow-up-alt2', open);
						icon.classList.toggle('dashicons-arrow-down-alt2', !open);
					}
				});
				return;
			}

				if (addBelow) {
					event.preventDefault();
					item = getFieldListItem(addBelow);
					if (isStructureFieldItem(item)) {
						addField(null, item);
					} else {
						addField(item);
					}
					return;
				}

			if (actionsToggle) {
				event.preventDefault();
				item = actionsToggle.closest('.cfs-field-action-menu');
				if (!item) {
					return;
				}
				document.querySelectorAll('.cfs-field-action-menu-list').forEach(function (menu) {
					if (menu !== item.querySelector('.cfs-field-action-menu-list')) {
						closeActionMenu(menu);
					}
				});
				form = item.querySelector('.cfs-field-action-menu-list');
				form.hidden = !form.hidden;
				if (form.hidden) {
					closeActionMenu(form);
				} else {
					updateActionMenuDirection(item, form, actionsToggle);
				}
				actionsToggle.setAttribute('aria-expanded', form.hidden ? 'false' : 'true');
				return;
			}

			if (deleteButton) {
				event.preventDefault();
				item = getFieldListItem(deleteButton);
				if (item) {
					item.remove();
					updateEmptyState();
					updateGroupDropzones(list);
					syncParentFieldsFromDom();
					initSortableFields();
					updateStandardFieldAvailability();
				}
				return;
			}

			if (duplicateButton) {
				event.preventDefault();
				item = getFieldListItem(duplicateButton);
				if (!item || !list) {
					return;
				}
				clone = item.cloneNode(true);
				copyCurrentControlValues(item, clone);
				prepareDuplicatedSubtree(clone);
				clone.querySelectorAll('.field').forEach(function (fieldNode) {
					fieldNode.classList.remove('form_open');
				});
				clone.querySelectorAll('.field_form').forEach(function (fieldForm) {
					fieldForm.style.display = 'none';
					fieldForm.dataset.atshiftUpfTypeReady = '';
				});
				directField(clone).classList.add('form_open');
				directField(clone).querySelector('.field_form').style.display = 'block';
				item.insertAdjacentElement('afterend', clone);
				updateGroupDropzones(clone);
				initFieldTypeOptions();
				initSortableFields();
				syncParentFieldsFromDom();
				updateGroupDropzones(clone);
					updateAddBelowButtons();
					updateFullWidthBadges(document);
					updateStandardFieldAvailability();
			}
			});

			document.addEventListener('change', function (event) {
				var conditionAssignment = event.target.closest('.atshift-upf-condition-assignment-select');
				var conditionItem;

				if (conditionAssignment) {
					conditionItem = getFieldListItem(conditionAssignment);
					if (conditionItem) {
						moveItemToConditionalBranch(conditionItem, conditionAssignment.value || '');
					}
					return;
				}

				if (!event.target.closest('.atshift-upf-field-type-select')) {
					return;
				}

			window.setTimeout(function () {
			updateAddBelowButtons();
			updateFullWidthBadges(document);
			updateStandardFieldAvailability();
			}, 0);
		});

			document.addEventListener('click', function (event) {
			if (!event.target.closest('.cfs-field-action-menu')) {
				document.querySelectorAll('.cfs-field-action-menu-list').forEach(function (menu) {
					closeActionMenu(menu);
				});
				document.querySelectorAll('.atshift-upf-field-actions-toggle').forEach(function (button) {
					button.setAttribute('aria-expanded', 'false');
				});
			}
		});

		if (list) {
			var settingsForm = list.closest('form');

			if (settingsForm) {
				settingsForm.addEventListener('submit', function () {
					updateGroupDropzones(list);
					syncParentFieldsFromDom();
				});
			}
		}

		placeSavedChildrenInGroups();
		updateGroupDropzones(list || document);
		initSortableFields();
		syncParentFieldsFromDom();
		if (autoAssignUnassignedConditionalChildren(list || document)) {
			syncParentFieldsFromDom();
		}
		updateGroupDropzones(list || document);
		updateAddBelowButtons();
		updateStandardFieldAvailability();
		updateEmptyState();
	}

	initFieldRows();

	function initExtrasBehaviorOptions() {
		document.querySelectorAll('.atshift-upf-extras-option[data-atshift-upf-extra-key]').forEach(function (option) {
			var hideInput = option.querySelector('[data-atshift-upf-extra-hide]');
			var disableInput = option.querySelector('[data-atshift-upf-disable-hidden-feature]');
			var disableOption = option.querySelector('.atshift-upf-extras-off-option');

			if (!hideInput || !disableInput || !disableOption) {
				return;
			}

			function syncState() {
				disableInput.disabled = !hideInput.checked;
				disableOption.classList.toggle('is-disabled', !hideInput.checked);
			}

			hideInput.addEventListener('change', syncState);
			syncState();
		});
	}

	initExtrasBehaviorOptions();

	function initDisplayOptions() {
		var optionInputs = document.querySelectorAll('[data-atshift-upf-screen-option]');
		var layoutShell = document.querySelector('.atshift-upf-editor-columns');
		var extrasPanel = document.querySelector('.atshift-upf-extras-panel');
		var ajaxConfig = window.atshiftUPFAdmin || {};

		if (!optionInputs.length) {
			return;
		}

		function currentLayout() {
			var checked = document.querySelector('input[name="editor_layout"]:checked');
			return checked && checked.value === 'one' ? 'one' : 'two';
		}

		function extrasEnabled() {
			var extrasInput = document.querySelector('input[name="show_extras"]');
			return !!(extrasInput && extrasInput.checked);
		}

		function applyOptions() {
			var layout = currentLayout();
			var showExtras = extrasEnabled();

			if (layoutShell) {
				layoutShell.classList.toggle('is-one-column', layout === 'one');
				layoutShell.classList.toggle('is-two-column', layout === 'two');
			}

			if (extrasPanel) {
				extrasPanel.hidden = !showExtras;
				extrasPanel.classList.toggle('is-hidden', !showExtras);
			}
		}

		function persistOptions() {
			var data;

			if (!ajaxConfig.ajaxUrl || !ajaxConfig.screenOptionsNonce || !window.fetch) {
				return;
			}

			data = new window.FormData();
			data.append('action', 'atshift_upf_save_screen_options');
			data.append('nonce', ajaxConfig.screenOptionsNonce);
			data.append('editor_layout', currentLayout());
			data.append('show_extras', extrasEnabled() ? '1' : '');

			window.fetch(ajaxConfig.ajaxUrl, {
				body: data,
				credentials: 'same-origin',
				method: 'POST'
			});
		}

		optionInputs.forEach(function (input) {
			input.addEventListener('change', function () {
				applyOptions();
				persistOptions();
			});
		});

		applyOptions();
	}

	initDisplayOptions();

}());
