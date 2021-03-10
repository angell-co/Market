const DP_MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const DP_DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

function datepicker() {
    return {
        showDatepicker: false,
        datepickerValue: '',

        month: '',
        year: '',
        no_of_days: [],
        blankdays: [],
        days: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],

        initDate(value) {
            if (value) {
                let currDate = new Date(Date.parse(value));
                this.month = currDate.getMonth();
                this.year = currDate.getFullYear();
                this.datepickerValue = currDate.toDateString();
            } else {
                let today = new Date();
                this.month = today.getMonth();
                this.year = today.getFullYear();
                this.datepickerValue = new Date(this.year, this.month, today.getDate()).toDateString();
            }
        },

        isToday(date) {
            const today = new Date();
            const d = new Date(this.year, this.month, date);

            return today.toDateString() === d.toDateString() ? true : false;
        },

        isSelectedDate(date) {
            const d = new Date(this.year, this.month, date);

            return this.datepickerValue === d.toDateString() ? true : false;
        },

        getDateValue(date) {
            let selectedDate = new Date(this.year, this.month, date);
            this.datepickerValue = selectedDate.toDateString();

            this.$refs.date.value = selectedDate.getFullYear() +"-"+ ('0'+ selectedDate.getMonth()).slice(-2) +"-"+ ('0' + selectedDate.getDate()).slice(-2);

            this.showDatepicker = false;
        },

        getNoOfDays() {
            let daysInMonth = new Date(this.year, this.month + 1, 0).getDate();

            // find where to start calendar day of week
            let dayOfWeek = new Date(this.year, this.month).getDay();
            let blankdaysArray = [];
            for ( var i=1; i <= dayOfWeek; i++) {
                blankdaysArray.push(i);
            }

            let daysArray = [];
            for ( var i=1; i <= daysInMonth; i++) {
                daysArray.push(i);
            }

            this.blankdays = blankdaysArray;
            this.no_of_days = daysArray;
        },

        next() {
            if (this.month == 11) {
                this.year++;
                this.month = 0;
            } else {
                this.month++;
            }
        },

        prev() {
            if (this.month == 0) {
                this.year--;
                this.month = 11;
            } else {
                this.month--;
            }
        }
    }
}
