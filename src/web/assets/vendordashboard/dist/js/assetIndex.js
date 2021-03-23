function assetIndex() {
    return {
        deleting: false,
        uploading: false,

        // Fired after uploading an asset
        uploaderOnLoad(evt) {
            // We want to cancel the response handling because we can’t actually use it
            evt.preventDefault();
            evt.stopPropagation();

            // TODO: at this point, we might be able to request another upload?

            // Then manually trigger a refresh of only the asset index
            htmx.trigger('#files-index', 'refresh');

            this.uploading = false;
            return false;
        },

        // Progress bar on upload
        uploaderOnProgress(evt) {
            var percent = evt.detail.loaded/evt.detail.total * 100;
            htmx.find('#file-uploader-progress').style.width = percent+'%';
        }
    }
}
