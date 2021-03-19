function variantBlock() {
    return {
        visible: true,
        isDefault: false,
        isEnabled: true,
        isExpanded: true,

        initBlock: function(isDefault, isEnabled) {
            this.isDefault = isDefault;
            this.isEnabled = isEnabled;
            this.isExpanded = this.isEnabled;
        },

        setAsDefault: function($event) {
            const vbs = document.querySelectorAll('.variant-block-default-input');
            for (let i = 0; i < vbs.length; i++) {
                vbs[i].dispatchEvent(new CustomEvent('toggle-off', {
                    bubbles: true
                }));
            }
            this.isDefault = true;
            this.$refs.defaultInput.value = '1';
        },

        setNotDefault: function($event) {
            this.isDefault = false;
            this.$refs.defaultInput.value = '';
        },

        toggleEnabled: function (val) {
            this.isEnabled = val;
            this.$refs.enabledInput.value = this.isEnabled ? '1' : '';
            this.toggleExpanded(this.isEnabled);
        },

        toggleExpanded: function (val) {
            this.isExpanded = val;
        },

        removeBlock: function($event) {
            this.visible = false;
            setTimeout(() => {
                this.$el.parentNode.removeChild(this.$el);
            }, 250)
        }
    }
}
