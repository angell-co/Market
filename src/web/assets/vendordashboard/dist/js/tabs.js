function tabs() {
    return {
        tab: null,

        initTabs: function(defaultTab) {
            if (window.location.hash) {
                this.tab = window.location.hash.substring(1);
            } else {
                this.tab = defaultTab;
            }
        },

        changeTab: function(tabName) {
            this.tab = tabName;
            window.location.hash = tabName;

            // Trigger size updates on textareas
            const tabEl = document.getElementById('tab-'+this.tab);
            const textareas = tabEl.querySelectorAll("textarea.resize-none");
            for (let i = 0; i < textareas.length; i++) {
                setTimeout(function() {
                    textareas[i].dispatchEvent(new CustomEvent('update-size', {
                        bubbles: true
                    }));
                }, 100);
            }
        }

    };
}
