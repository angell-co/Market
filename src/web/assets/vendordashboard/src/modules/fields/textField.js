const textField = function() {
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
                setTimeout(() => {
                    this.resize(inputEl);
                }, 100);
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
};

export default textField;
