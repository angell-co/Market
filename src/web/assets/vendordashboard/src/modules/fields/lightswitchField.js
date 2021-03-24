const lightswitchField = function() {
    return {
        on: false,

        initValue: function(val) {
            this.on = val;
        },

        updateValue: function (val) {
            this.on = val;
            this.$refs.input.value = this.on ? '1' : '';
        }
    }
};

export default lightswitchField;
