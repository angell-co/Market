const shippingDestinationBlock = function() {
    return {
        visible: true,
        limit: 1,

        initBlock: function(fadeIn, limit) {
            this.limit = Number(limit);

            if (fadeIn) {
                this.visible = false;
            }

            // Enable all delete buttons or disable if there is only block
            const blocks = document.querySelectorAll('.destination-block');
            if (blocks.length > 1) {
                for (let i = 0; i < blocks.length; i++) {
                    let btn = blocks[i].querySelector('.destination-block-delete-btn');
                    btn.classList.remove('opacity-30');
                    btn.removeAttribute('disabled');
                }
            } else {
                let btn = blocks[0].querySelector('.destination-block-delete-btn');
                btn.classList.add('opacity-30');
                btn.setAttribute('disabled', 'disabled');
            }

            // Sort out the add block button state - have to wait for it to be swapped in by htmx
            setTimeout(() => {
                let addBtn = document.getElementById('add-destination-button');
                if (blocks.length === this.limit) {
                    addBtn.classList.add('opacity-30');
                    addBtn.setAttribute('disabled', 'disabled');
                } else {
                    addBtn.classList.remove('opacity-30');
                    addBtn.removeAttribute('disabled');
                }
            }, 250)

            if (fadeIn) {
                setTimeout(() => {
                    this.visible = true;
                }, 200)
            }
        },

        removeBlock: function($event) {
            let blocks = document.querySelectorAll('.destination-block');

            // Check we have more than one block
            if (blocks.length > 1) {
                this.visible = false;

                setTimeout(() => {
                    this.$el.parentNode.removeChild(this.$el);

                    // If there is now only one block, set disable its delete button
                    let blocks = document.querySelectorAll('.destination-block');
                    if (blocks.length === 1) {
                        const btn = blocks[0].querySelector('.destination-block-delete-btn');
                        btn.classList.add('opacity-30');
                        btn.setAttribute('disabled', 'disabled');
                    }

                    // Sort out the add block button state
                    let addBtn = document.getElementById('add-destination-button');
                    if (blocks.length === this.limit) {
                        addBtn.classList.add('opacity-30');
                        addBtn.setAttribute('disabled', 'disabled');
                    } else {
                        addBtn.classList.remove('opacity-30');
                        addBtn.removeAttribute('disabled');
                    }

                }, 250)
            }




        }
    }
};

export default shippingDestinationBlock;
