const stockField = function() {
    return {
        hasUnlimitedStock: false,

        initField: function(hasUnlimitedStock) {
            this.hasUnlimitedStock = hasUnlimitedStock;
        }
    }
};

export default stockField;
