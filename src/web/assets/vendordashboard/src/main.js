import 'alpinejs';
import Sortable from 'sortablejs';

import tabs from './modules/tabs.js';
import assetIndex from './modules/assetIndex.js';
import variantBlock from './modules/variantBlock.js';

import assetField from './modules/fields/assetField.js';
import categoryField from './modules/fields/categoryField.js';
import { DP_MONTH_NAMES, DP_DAYS, datepickerField } from './modules/fields/datepickerField.js';
import lightswitchField from './modules/fields/lightswitchField.js';
import skuField from './modules/fields/skuField.js';
import slugField from './modules/fields/slugField.js';
import stockField from './modules/fields/stockField.js';
import textField from './modules/fields/textField.js';
import timepickerField from './modules/fields/timepickerField.js';
import titleField from './modules/fields/titleField.js';

export default {
    Sortable,
    categoryFields: {},
    assetFields: {},
    tabs,
    assetIndex,
    variantBlock,
    assetField, // CHECK Market.var stuff
    categoryField, // CHECK Market.var stuff
    DP_MONTH_NAMES,
    DP_DAYS,
    datepickerField,
    lightswitchField,
    skuField,
    slugField,
    stockField,
    textField,
    timepickerField,
    titleField,
};
