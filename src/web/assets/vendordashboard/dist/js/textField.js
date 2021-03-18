function textField() {
    return {
        fieldId: null,
        value: null,
        limit: null,

        initField: function(fieldId, value, limit, isMultiline) {
            this.fieldId = fieldId;
            this.value = value;
            this.limit = limit;

            // Initial resize size
            if (isMultiline) {
                const inputEl = document.getElementById('field-' + this.fieldId);
                this.resize(inputEl);
            }
        },

        resize: function(el) {
            el.style.height = 'auto';
            el.style.height = (el.scrollHeight + 2) + 'px';
        },

        get remaining() {
            return this.limit - this.value.length
        },
    }
}
