if (typeof Market === 'undefined') {
    var Market = {
        categoryFields: {},
        assetFields: {}
    };
}

function assetField() {
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
            Market.assetFields[this.fieldId] = {
                scroll: htmx.find('#field-'+this.fieldId+'-assets-index-container').scrollTop
            };
        },

        applyScroll(evt) {
            // Check we have a scroll val stored and that the event is coming from
            // the asset element checkbox and not anywhere else
            if (typeof Market.assetFields[this.fieldId] !== 'undefined' && evt.detail.requestConfig.elt.classList.contains("asset-toggle")){
                htmx.find('#field-'+this.fieldId+'-assets-index-container').scrollTop = Market.assetFields[this.fieldId].scroll;
            }
        },

        // Fired after uploading an asset
        uploaderOnLoad(evt) {
            // We want to cancel the response handling because we can’t actually use it
            evt.preventDefault();
            evt.stopPropagation();

            // Then manually trigger a refresh of only the asset index
            htmx.trigger('#field-'+this.fieldId+'-assets-index', 'refresh');
            return false;
        },

        // Progress bar on upload
        uploaderOnProgress(evt) {
            var percent = evt.detail.loaded/evt.detail.total * 100;
            htmx.find('#field-'+this.fieldId+'-uploader-progress').style.width = percent+'%';
        }
    }
}
