(function () {
    'use strict';
    document.querySelectorAll('.atshift-upf-owner-form').forEach(function (form) {
        function update() {
            var nodes = Array.from(form.querySelectorAll('[data-owner-id]'));
            nodes.forEach(function (node) {
                var parent = nodes.find(function (n) { return n.dataset.ownerId === node.dataset.ownerParent; });
                var controller = parent && parent.querySelector('[data-atshift-upf-condition-controller]');
                // UPF controller markup varies between radio and select.
                if (!controller && parent) controller = parent.querySelector('select, input[type="radio"]:checked, input[type="hidden"]');
                var selected = controller ? controller.value : '';
                if (controller && controller.type === 'radio') {
                    var checked = parent.querySelector('input[type="radio"]:checked');
                    selected = checked ? checked.value : '';
                }
                node.hidden = !!(parent && parent.hidden) || !!(node.dataset.ownerChoice && selected !== node.dataset.ownerChoice);
                node.querySelectorAll('input,select,textarea').forEach(function (input) {
                    input.disabled = !!input.closest('[hidden]');
                });
            });
        }
        form.addEventListener('change', update);
        update();
    });
}());
