const skuField = function() {
    return {
        prefix: '',
        skuValue: '',

        initField: function(prefix, value) {
            this.prefix = prefix;
            this.skuValue = value;
        },

        onUpdate: function($event) {
            if (this.skuValue) {
                this.$refs.input.value = this.prefix + this.skuValue;
            } else {
                this.$refs.input.value = '';
            }
        }
    }
};

export default skuField;
