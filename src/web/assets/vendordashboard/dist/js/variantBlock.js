function variantBlock() {
    return {
        visible: true,
        isDefault: false,

        initBlock: function(isDefault) {
            this.isDefault = isDefault;
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

        toggleOff: function($event) {
            this.isDefault = false;
            this.$refs.defaultInput.value = '';
        },

        removeBlock: function($event) {
            this.visible = false;
            setTimeout(function() {
                // TODO remove from DOM
                this.$el.parentNode.removeChild(this.$el);
            }, 250)
        }
    }
}
