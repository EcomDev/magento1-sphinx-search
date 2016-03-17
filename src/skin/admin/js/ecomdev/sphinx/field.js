if (!window.EcomDev) {
    window.EcomDev = {};
}

if (!EcomDev.Sphinx) {
    EcomDev.Sphinx = {};
}

EcomDev.Sphinx.Field = Class.create({
    initialize: function (container, config) {
        if (Object.isArray(config.value) || !config.value) {
            config.value = {};
        }

        this.container = $(container);
        this.config = config;
        this.value = $H(config.value);

        if (config.options !== false) {
            this.options = $H(config.options);
        } else {
            this.options = false;
        }

        var containerTemplate = new Template(config.template);
        this.container.update(containerTemplate.evaluate({id: this.container.identify()}));
        this.template = new Template(config.row_template);
        this.position = 0;
        this.select = this.container.down('.footer .option');
        this.updateSelect();
        this.updateRows();
        this.container.down('.footer button.add').observe('click', this.addRow.bind(this));
    },
    updateSelect: function () {
        if (this.options === false) {
            return;
        }

        var keys = this.options.keys();
        this.select.update('');
        for (var i = 0, l = keys.length; i < l; i ++) {
            if (!this.value.get(keys[i])) {
                var option = new Element('option', {value: keys[i]});
                this.select.insert({bottom: option});
                option.update(this.options.get(keys[i]));
            }
        }
    },
    addRow: function () {
        if (this.select.value && !this.value.get(this.select.value)) {
            var map = Object.extend({position: this.position + 1 }, this.getDefaultOptions());
            var code = this.select.value;
            this.value.set(code, map);
            this.updateSelect();
            this.renderRow(code, map);
        }
    },
    getDefaultOptions: function () {
        return {};
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
                fieldPrefix: this.config.name,
                code: code,
                label: (
                    (this.options === false || !this.options.get(code))
                        ? code :
                        this.options.get(code)
                ),
                position: map.position
            })
        });

        this.setRowValues($(id), code, map);
        $(id).down('.delete').observe('click', this.removeRow.bind(this, id, code));
    },
    setRowValues: function (row, code, map) {
        if (map.label) {
            row.down('.default-label').value = map.label;
        }

        if (map.store_label) {
            var codes = Object.keys(map.store_label);
            for (var i = 0, l = codes.length; i < l; i++) {
                var input = row.down('.store-label-' + codes[i]);
                if (input) {
                    input.value = map.store_label[codes[i]];
                }
            }
        }
    },
    removeRow: function (id, code) {
        this.value.unset(code);
        this.updateSelect();
        var element = $(id);
        element.up().removeChild(element);
    }
});

EcomDev.Sphinx.FieldGrouped = Class.create(EcomDev.Sphinx.Field, {
    getDefaultOptions: function () {
        var options = {};
        if (this.options !== false) {
            options.target = [];
        } else {
            options.target = '';
        }

        return options;
    },
    setRowValues: function ($super, row, code, map) {
        $super(row, code, map);
        if (this.options !== false) {
            var select = row.down('.target');
            var keys = this.options.keys();
            for (var i = 0, l=keys.length; i < l; i++) {
                var option = new Element('option', {value: keys[i]});
                select.insert(option);
                option.update(this.options.get(keys[i]));
                if (map.target.indexOf(keys[i]) !== -1) {
                    option.selected = true;
                }
            }

            return;
        }
        row.down('.target').value = map.target;
    }
});

EcomDev.Sphinx.FieldAlias = Class.create(EcomDev.Sphinx.Field, {
    getDefaultOptions: function () {
        var options = {};

        options.target = '';
        return options;
    },
    setRowValues: function ($super, row, code, map) {
        $super(row, code, map);
        if (this.options !== false) {
            var select = row.down('.target');
            var keys = this.options.keys();
            for (var i = 0, l=keys.length; i < l; i++) {
                var option = new Element('option', {value: keys[i]});
                select.insert(option);
                option.update(this.options.get(keys[i]));
            }
        }
        row.down('.target').value = map.target;
    }
});


EcomDev.Sphinx.FieldRange = Class.create(EcomDev.Sphinx.Field, {
    getDefaultOptions: function () {
        var options = {};

        options.from = '';
        options.to = '';
        return options;
    },
    setRowValues: function ($super, row, code, map) {
        $super(row, code, map);
        row.down('.from').value = map.from;
        row.down('.to').value = map.to;
    }
});