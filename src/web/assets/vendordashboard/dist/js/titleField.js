function titleField() {
    return {
        allowSlugUpdate: true,

        updateSlug: function(evt) {
            if (this.allowSlugUpdate) {
                if (evt.target.value.trim() !== '') {
                    const slugInput = document.getElementById('slug');
                    slugInput.value = evt.target.value;
                    slugInput.dispatchEvent(new CustomEvent('update-from-title', {
                        bubbles: true
                    }));
                    this.allowSlugUpdate = false;
                }
            }
        }
    }
}
