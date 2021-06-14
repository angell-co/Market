const timepickerField = function() {
    return {
        timepickerValue: '',

        initTime(value) {
            if (value) {
                this.timepickerValue = value;
            }
        }
    }
};

export default timepickerField;
