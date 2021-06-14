import 'alpinejs';
import Sortable from 'sortablejs';

import tabs from './modules/tabs.js';
import assetIndex from './modules/assetIndex.js';
import variantBlock from './modules/variantBlock.js';
import shippingDestinationBlock from './modules/shippingDestinationBlock.js';

import assetField from './modules/fields/assetField.js';
import categoryField from './modules/fields/categoryField.js';
import { DP_MONTH_NAMES, DP_DAYS, datepickerField } from './modules/fields/datepickerField.js';
import lightswitchField from './modules/fields/lightswitchField.js';
import richtextField from './modules/fields/richtextField.js';
import skuField from './modules/fields/skuField.js';
import slugField from './modules/fields/slugField.js';
import stockField from './modules/fields/stockField.js';
import textField from './modules/fields/textField.js';
import timepickerField from './modules/fields/timepickerField.js';
import titleField from './modules/fields/titleField.js';

export default {
    Sortable,

    tabs,
    assetIndex,
    variantBlock,
    shippingDestinationBlock,

    categoryFields: {},
    assetFields: {},
    assetField,
    categoryField,

    DP_MONTH_NAMES,
    DP_DAYS,
    datepickerField,
    lightswitchField,

    richtextField,
    skuField,
    slugField,
    stockField,
    textField,
    timepickerField,
    titleField,
};
