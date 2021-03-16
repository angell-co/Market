function categoryField() {
    return {
        open: false,

        refreshParent(fieldId, ids) {
            htmx.find('#field-'+fieldId+'-selectedIds').value = ids;
            htmx.trigger('#field-'+fieldId, 'refresh');
            console.log(fieldId);
            console.log(ids);
        }
    }
}
