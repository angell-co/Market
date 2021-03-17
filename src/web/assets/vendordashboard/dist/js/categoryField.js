if (typeof Market === 'undefined') {
    var Market = {
        categoryFields: {}
    };
}

function categoryField() {
    return {
        open: false,
        fieldId: null,

        initField(fieldId) {
            this.fieldId = fieldId;
        },

        onSave(ids) {
            // Close the panel
            this.open = false;

            // Find the input in the parent, update it with the ids and then trigger a refresh
            htmx.find('#field-'+this.fieldId+'-selectedIds').value = ids;
            htmx.trigger('#field-'+this.fieldId, 'refresh');
        },

        trackScroll(evt) {
            // Store the scroll of this field’s index
            Market.categoryFields[this.fieldId] = {
                scroll: htmx.find('#field-'+this.fieldId+'-categories-index-container').scrollTop
            };
        },

        applyScroll(evt) {
            // Check we have a scroll val stored and that the event is coming from
            // the category toggle button and not anywhere else
            if (typeof Market.categoryFields[this.fieldId] !== 'undefined' && evt.detail.requestConfig.elt.classList.contains("category-toggle")){
                htmx.find('#field-'+this.fieldId+'-categories-index-container').scrollTop = Market.categoryFields[this.fieldId].scroll;
            }
        }
    }
}
