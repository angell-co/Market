function timepicker() {
    return {
        timepickerValue: '',

        initTime(value) {
            if (value) {
                this.timepickerValue = value;
            }
        }
    }
}
