
if (!window.EcomDev) {
    window.EcomDev = {};
}

EcomDev.Sphinx = {};
EcomDev.Sphinx.instances = [];
EcomDev.Sphinx.Layer = Class.create({
    initialize: function (linkCssSelector, // Css selector for links
                          filterCssSelector, // Css selector for filter container
                          listingCssSelector, // Css selector for listing selector
                          replaceItems, // Flag for replacing items
                          callbacks
    ) {
        this.linkCssSelector = linkCssSelector;
        this.filterCssSelector = filterCssSelector;

        if (Object.isArray(listingCssSelector)) {
            this.listingCssSelector = listingCssSelector[0];
            this.contentCssSelector = listingCssSelector[1];
        } else {
            this.listingCssSelector = listingCssSelector;
        }
        this.replaceItems = replaceItems;
        this.onClick = this.handleClick.bind(this);
        this.onLoadUrl = this.handleLoadUrl.bind(this);
        this.callbacks = callbacks || {};
        Event.observe(window, 'popstate', this.handlePopState.bind(this));
        EcomDev.Sphinx.instances.push(this);
        this.bindListener();
    },
    bindListener: function () {
        var links = $$(this.linkCssSelector);
        for (var i = 0, l = links.length; i < l; i ++) {
            if (!links[i].hasClassName('system-link-ajax-observed')) {
                links[i].addClassName('system-link-ajax-observed');
                links[i].observe('click', this.onClick);
            }
        }
    },
    handlePopState: function(evt) {
        var state = evt.state;
        if (state !== null) {
            if (state.page !== undefined && state.page !== '') {
                this.loadUrl(state.page, true);
            }
        }
    },
    handleClick: function (evt) {
        Event.stop(evt);
        var link = Event.findElement(evt, 'a');
        this.loadUrl(link.href);
    },
    loadUrl: function (url, updateHistory) {
        if (window.history && history.pushState && !updateHistory) {
            history.pushState({page:url}, null, url);
        }

        if (this.callbacks && Object.isFunction(this.callbacks.onLoad)) {
            this.callbacks.onLoad(url);
        }

        return new Ajax.Request(url, {method: 'get', onComplete: this.onLoadUrl});
    },
    handleLoadUrl: function (response) {
        if (response && response.responseText && response.responseText.isJSON()) {
            var data = response.responseText.evalJSON();
            this.updateContent(data);
            this.bindListener();
            if (this.callbacks && Object.isFunction(this.callbacks.onComplete)) {
                this.callbacks.onComplete(data);
            }
        } else if (window.console && Object.isFunction(console.log)) {
            console.log('[EcomDev Sphinx] Unknown response');
            console.log(response);
            if (this.callbacks && Object.isFunction(this.callbacks.onError)) {
                this.callbacks.onError(response);
            }
        }
    },
    updateContent: function (data) {
        if (data.filters) {
            this.updateByCssRule(this.filterCssSelector, data.filters);
        }

        if (this.contentCssSelector && data.content) {
            this.updateByCssRule(this.contentCssSelector, data.content);
        } else if (data.products) {
            this.updateByCssRule(this.listingCssSelector, data.products);
        }
    },
    updateByCssRule: function (rule, html) {
        var elements = $$(rule);
        for (var i = 0, l = elements.length; i < l; i ++) {
            if (this.replaceItems) {
                elements[i].replace(html.stripScripts());
            } else {
                elements[i].update(html.stripScripts());
            }
        }

        try {
            html.evalScripts();
        } catch (e) {
            if (window.console && Object.isFunction(console.log)) {
                console.log(e);
            }
        }
    }
});

EcomDev.Sphinx.OptionsStorage = $H({});
EcomDev.Sphinx.Options = Class.create({
    initialize: function (container, options) {
        this.container = $(container);
        this.attributes = options;
        this.id = this.attributes.id;
        this.onExpandClick = this.handleClick.bind(this, true);
        this.onCollapseClick = this.handleClick.bind(this, false);
        this.bindListener();
        this.animation = false;
        this.updateVisibility();
        this.animation = this.attributes.animation;
    },
    isExpanded: function () {
        return !!this.storage().isExpanded;
    },
    updateVisibility: function () {
        if (this.isExpanded()) {
            this.expand();
        } else {
            this.collapse();
        }
    },
    expand: function () {
        if (this.hiddenItems && this.collapseBtn && this.expandBtn) {
            for (var i = 0, l = this.hiddenItems.length; i < l; i ++) {
                var element = this.hiddenItems[i];
                if (element.hasClassName(this.attributes.activeClass)) {
                    continue;
                }

                if (this.animation) {
                    this.animate(element, true);
                } else {
                    element.removeClassName(this.attributes.hiddenClass);
                }
            }

            this.collapseBtn.removeClassName(this.attributes.hiddenClass);
            this.expandBtn.addClassName(this.attributes.hiddenClass);
        }
    },
    animate: function (element, toShow) {
        var elementOpacity = false;
        if (!element.hasClassName(this.attributes.hiddenClass)) {
            elementOpacity = element.getOpacity();
        }

        var options = {
            from: (toShow ? (elementOpacity || 0.0) : 1.0),
            to:   (toShow ? (elementOpacity || 1.0) : 0.0),
            duration: 0.3,
            afterFinishInternal: function(effect) {
                effect.element.forceRerendering();
            },
            beforeSetup: function(effect) {
                if (toShow) {
                    effect.element.removeClassName(this.attributes.hiddenClass);
                }
                effect.element.setOpacity(effect.attributes.from);
            }.bind(this)
        };

        if (!toShow) {
            options.afterFinish = function() {
                element.addClassName(this.attributes.hiddenClass);
            }.bind(this);
        }



        if (!element.effect) {
            element.effect = new Effect.Opacity(element, options);
        } else {
            if (element.effect.state == 'running') {
                element.effect.cancel();
            }
            element.effect.start(options);
        }
    },
    collapse: function () {
        if (this.hiddenItems && this.collapseBtn && this.expandBtn) {
            for (var i = 0, l = this.hiddenItems.length; i < l; i ++) {
                var element = this.hiddenItems[i];
                if (element.hasClassName(this.attributes.activeClass)) {
                    continue;
                }

                if (this.animation) {
                    this.animate(element, false);
                } else {
                    element.addClassName(this.attributes.hiddenClass);
                }
            }

            this.expandBtn.removeClassName(this.attributes.hiddenClass);
            this.collapseBtn.addClassName(this.attributes.hiddenClass);
        }
    },
    handleClick: function (flag, evt) {
        Event.stop(evt);
        this.storage().isExpanded = flag;
        this.updateVisibility();
    },
    storage: function () {
        if (!EcomDev.Sphinx.OptionsStorage.get(this.id)) {
            EcomDev.Sphinx.OptionsStorage.set(this.id, {});
        }

        return EcomDev.Sphinx.OptionsStorage.get(this.id);
    },
    bindListener: function () {
        this.collapseBtn = this.container.down(this.attributes.collapseCssRule);
        this.expandBtn = this.container.down(this.attributes.expandCssRule);

        var sortedItems = this.container.select(this.attributes.itemCssRule);

        var topActive = function (left, right) {
            // Move active elements to top
            if (left.hasClassName('active') && !right.hasClassName('active')) {
                return -1;
            } else if (!left.hasClassName('active') && right.hasClassName('active')) {
                return 1;
            }

            return 0;
        };

        var topCount = function (left, right) {
            var orderLeft = parseInt(left.readAttribute('data-option-count'));
            var orderRight = parseInt(right.readAttribute('data-option-count'));

            var compareActive = topActive(left, right);

            if (compareActive !== 0) {
                return compareActive;
            }

            if (orderLeft > orderRight) {
                return -1;
            } else if (orderLeft < orderRight) {
                return 1;
            }

            return 0;
        };


        if (this.attributes.optionByCount) {
            sortedItems = sortedItems.sort(topCount);
        } else {
            sortedItems = sortedItems.sort(topActive);
        }

        if (this.attributes.optionLimit > 0 && sortedItems.length > this.attributes.optionLimit) {
            this.hiddenItems = sortedItems.slice(this.attributes.optionLimit, sortedItems.length);
        } else {
            this.hiddenItems = false;
        }

        if (this.collapseBtn) {
            this.collapseBtn.observe('click', this.onCollapseClick);
        }

        if (this.expandBtn) {
            this.expandBtn.observe('click', this.onExpandClick);
        }
    }
});


EcomDev.Sphinx.Slider = Class.create({
    initialize: function (container, options) {
        this.layer = false;
        this.attributes = options;
        this.container = $(container);
        this.onUpdate = this.handleUpdate.bind(this);
        this.tryCreateSlider();
    },
    /**
     *
     * @returns {boolean|EcomDev.Sphinx.Layer}
     */
    getLayer: function () {
        if (!this.layer) {
            if (EcomDev.Sphinx.instances.length) {
                this.layer = EcomDev.Sphinx.instances[EcomDev.Sphinx.instances.length - 1];
            } else {
                this.layer = false;
            }
        }

        return this.layer;
    },
    handleUpdate: function (values) {
        if (this.getLayer()) {
            this.getLayer().loadUrl(this.attributes.url
                .replace('{start}', encodeURIComponent(values[0]))
                .replace('{end}', encodeURIComponent(values[1])));
        }
    }
    ,
    tryCreateSlider: function () {
        if (!window.noUiSlider) {
            setTimeout(this.tryCreateSlider, 2);
        } else {
            this.createSlider();
        }
    },
    createSlider: function () {
        this.slider = noUiSlider.create(this.container, this.getSliderOptions());
        this.slider.on('change', this.onUpdate);
    },

    getSliderOptions: function () {
        return {
            range: {
                'min': this.attributes.available['min'],
                'max': this.attributes.available['max']
            },
            step: this.attributes.step,
            margin: this.attributes.step*10,
            connect: true,
            behaviour: 'tap-drag',
            tooltips: { format: this.getTooltip.bind(this) },
            start: [this.attributes.current['min'], this.attributes.current['max']]
        };
    },

    getTooltip: function (formattedValue) {
        if (formattedValue === false) {
            return formattedValue;
        }

        formattedValue = parseFloat(formattedValue);

        if (this.attributes.currency) {
            return this.attributes.currency.replace('%s', formattedValue.toFixed(2).replace('.', this.attributes.decimal_separator));
        }

        return ceil(formattedValue).toString();
    }
});
