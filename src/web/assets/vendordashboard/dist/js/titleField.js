function titleField() {
    return {
        titleValue: null,
        allowSlugUpdate: true,

        initField: function(titleVal) {
            this.titleValue = titleVal;
        },

        updateSlug: function(evt) {
            if (this.allowSlugUpdate) {
                if (evt.target.value.trim() !== '') {
                    const slugInput = document.getElementById('slug');
                    slugInput.value = this.titleValue;
                    slugInput.dispatchEvent(new CustomEvent('update-from-title', {
                        bubbles: true
                    }));
                    this.allowSlugUpdate = false;
                }
            }
        },

        updateVariantBlock: function(evt) {
            evt.target.closest(".variant-block").dispatchEvent(new CustomEvent('update-from-title', {
                bubbles: true,
                detail: {
                    titleValue: this.titleValue
                }
            }));
        }
    }
}
