function slugField() {
    return {
        slug: '',

        initSlug: function(val) {
            this.slug = val;
        },

        updateFromTitle: function() {
            this.formatValue(this.slug);
        },

        formatValue: function (val) {
            // Clean it all up
            val = val
                    // Remove HTML tags
                    .replace(/<(.*?)>/g, '')

                    // Remove inner-word punctuation
                    .replace(/['"‘’“”\[\]\(\)\{\}:]/g, '')

                    // Remove leading and trailing whitespace
                    .trim()

                    // Replace non alpha chars with dashes
                    .replace(/\W+/g, '-')

                    // Make it lowercase
                    .toLowerCase();

            // Set it on the model
            this.slug = val;
        }
    }
}
