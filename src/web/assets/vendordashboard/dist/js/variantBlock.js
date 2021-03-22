function variantBlock() {
    return {
        visible: true,
        isDefault: false,
        isEnabled: true,
        isExpanded: true,
        titleText: '',

        initBlock: function(isDefault, isEnabled, fadeIn, titleText) {
            this.isDefault = isDefault;
            this.isEnabled = isEnabled;
            this.isExpanded = this.isEnabled;
            this.titleText = titleText;
            console.log(titleText);

            if (fadeIn) {
                this.visible = false;
            }

            // Enable all delete buttons or disable if there is only block
            const blocks = document.querySelectorAll('.variant-block');
            if (blocks.length > 1) {
                for (let i = 0; i < blocks.length; i++) {
                    let btn = blocks[i].querySelector('.variant-block-delete-btn');
                    btn.removeAttribute('disabled');
                    btn.classList.remove('opacity-30');
                }
            } else {
                let btn = blocks[0].querySelector('.variant-block-delete-btn');
                btn.setAttribute('disabled', 'disabled');
                btn.classList.add('opacity-30');
            }

            setTimeout(() => {
                this.visible = true;
            }, 200)
        },

        updateTitle: function($event) {
            this.titleText = $event.detail.titleValue;
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
            let blocks = document.querySelectorAll('.variant-block');

            // Check we have more than one block
            if (blocks.length > 1) {
                this.visible = false;
                setTimeout(() => {
                    this.$el.parentNode.removeChild(this.$el);

                    // If there is now only one block, set disable its delete button
                    let blocks = document.querySelectorAll('.variant-block');
                    if (blocks.length === 1) {
                        const btn = blocks[0].querySelector('.variant-block-delete-btn');
                        btn.setAttribute('disabled', 'disabled');
                        btn.classList.add('opacity-30');
                    }

                }, 250)
            }
        }
    }
}
