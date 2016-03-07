
if (!window.EcomDev) {
    window.EcomDev = {};
}

if (!EcomDev.Sphinx) {
    EcomDev.Sphinx = {};
}

EcomDev.Sphinx.SortOrder = Class.create({
    initialize: function (container, options) {
        if (Object.isArray(options.value) || !options.value) {
            options.value = {};
        }

        this.container = $(container);
        this.attributes = options;
        this.value = $H(options.value);
        this.orders = $H(options.orders);
        var containerTemplate = new Template(options.template);
        this.container.update(containerTemplate.evaluate({id: this.container.identify()}));
        this.template = new Template(options.row_template);
        this.select = this.container.down('.footer .select');
        this.position = 0;
        this.updateSelect();
        this.updateRows();
        this.container.down('.footer button.add').observe('click', this.addRow.bind(this));
    },
    updateSelect: function () {
        var keys = this.orders.keys();
        this.select.update('');
        for (var i = 0, l = keys.length; i < l; i ++) {
            if (!this.value.get(keys[i])) {
                var option = new Element('option', {value: keys[i]});
                this.select.insert({bottom: option});
                option.update(this.orders.get(keys[i]));
            }
        }
    },
    addRow: function () {
        if (this.select.value) {
            var map = {asc: 'asc', desc: 'desc', position: this.position + 1 };
            var code = this.select.value;
            this.value.set(code, map);
            this.updateSelect();
            this.renderRow(code, map);
        }

        if (!this.select.value) {
            this.container.down('.footer button.add').disabled = true;
        }
    },
    updateRows: function () {
        var keys = this.value.keys();
        for (var i = 0, l = keys.length; i < l; i ++) {
            var map = this.value.get(keys[i]);
            this.renderRow(keys[i], map);
        }
    },
    renderRow: function (code, map) {
        var table = this.container.down('.body');
        var id = this.container.identify() + '-' + code;
        this.position = Math.max(this.position, map.position);
        table.insert({
            bottom: this.template.evaluate({
                id: id,
                fieldPrefix: this.attributes.name,
                code: code,
                name: this.orders.get(code),
                position: map.position
            })
        });

        $(id).down('.asc').value = map.asc || 'asc';
        $(id).down('.desc').value = map.desc || 'desc';
        $(id).down('.delete').observe('click', this.removeRow.bind(this, id, code));
    },
    removeRow: function (id, code) {
        this.value.unset(code);
        this.updateSelect();
        var element = $(id);
        element.up().removeChild(element);
        if (this.select.value) {
            this.container.down('.footer button.add').disabled = false;
        }
    }
});
