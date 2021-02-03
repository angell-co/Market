<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

/**
 * Market en Translation
 *
 * Returns an array with the string to be translated (as passed to `Craft::t('market', '...')`) as
 * the key, and the translation as the value.
 *
 * http://www.yiiframework.com/doc-2.0/guide-tutorial-i18n.html
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
return [
    'Market' => 'Market',
    'Vendor' => 'Vendor',
    'Vendors' => 'Vendors',
    'Active' => 'Active',
    'Pending' => 'Pending',
    'Suspended' => 'Suspended',
    'Profile Picture' => 'Profile Picture',
    'Code' => 'Code',
    'Add a vendor' => 'Add a vendor',
    'Vendor Fields' => 'Vendor Fields',
    'General' => 'General',
    'Vendor Settings' => 'Vendor Settings',
    'Market Settings' => 'Market Settings',
    'Vendor Settings ({siteName})' => 'Vendor Settings ({siteName})',
    'Couldn’t save Vendor settings.' => 'Couldn’t save Vendor settings.',
    'Vendor settings saved.' => 'Vendor settings saved.',
    'What the vendor URLs should look like. You can include tags that output vendor properties, such as {ex1} or {ex2}.' => 'What the vendor URLs should look like. You can include tags that output vendor properties, such as {ex1} or {ex2}.',
    'The template to use when a vendor’s URL is requested.' => 'The template to use when a vendor’s URL is requested.',
    'The asset volume that should used to store this vendor’s files.' => 'The asset volume that should used to store this vendor’s files.',
    'Default Shipping Origin' => 'Default Shipping Origin',
    'Where Vendors will ship from by default, they won’t be able to change this in their shipping profiles but admins can in the cp.' => 'Where Vendors will ship from by default, they won’t be able to change this in their shipping profiles but admins can in the cp.',
    'Client ID' => 'Client ID',
    'A unique identifier for your platform, generated by Stripe. This can be found from your {platform_settings_link}.' => 'A unique identifier for your platform, generated by Stripe. This can be found from your {platform_settings_link}.',
    'Secret Key' => 'Secret Key',
    'Publishable Key' => 'Publishable Key',
    'This can be found in your {api_settings_link}.' => 'This can be found in your {api_settings_link}.',
    'Redirect URI (success)' => 'Redirect URI (success)',
    'A URI to redirect to when a user has successfully connected to Stripe via OAuth.' => 'A URI to redirect to when a user has successfully connected to Stripe via OAuth.',
    'Redirect URI (error)' => 'Redirect URI (error)',
    'A URI to redirect to when there was a problem connecting to Stripe via OAuth.' => 'A URI to redirect to when there was a problem connecting to Stripe via OAuth.',
    'Stripe' => 'Stripe',
    'Stripe Settings ({siteName})' => 'Stripe Settings ({siteName})',
    'Couldn’t save Stripe settings.' => 'Couldn’t save Stripe settings.',
    'Stripe settings saved.' => 'Stripe settings saved.',
    'Vendor Dashboard Title' => 'Vendor Dashboard Title',
    'The title used in the vendor dashboard, defaults to ‘Marketplace’.' => 'The title used in the vendor dashboard, defaults to ‘Marketplace’.',
    'Apply To Sell URL' => 'Apply To Sell URL',
    'If you have a URL where people can apply to sell on your platform, add it here.' => 'If you have a URL where people can apply to sell on your platform, add it here.',
    'Order confirmation email' => 'Order confirmation email',
    'Specify the path of the template you want to use for order confirmation emails.' => 'Specify the path of the template you want to use for order confirmation emails.',
    'Google Analytics UA' => 'Google Analytics UA',
    'If you set this then Google Analytics will be implemented across the marketplace templates.' => 'If you set this then Google Analytics will be implemented across the marketplace templates.',
    'Delete vendor' => 'Delete vendor',
    'Are you sure you want to delete this vendor?' => 'Are you sure you want to delete this vendor?',
    'Create a new vendor' => 'Create a new vendor',
    'Edit Vendor' => 'Edit Vendor',
    'Couldn’t duplicate vendor.' => 'Couldn’t duplicate vendor.',
    'An error occurred when duplicating the vendor.' => 'An error occurred when duplicating the vendor.',
    'Vendor settings not configured properly.' => 'Vendor settings not configured properly.',
    'Couldn’t save vendor.' => 'Couldn’t save vendor.',
    'Vendor saved.' => 'Vendor saved.',
    'New vendor' => 'New vendor',
    'Code must be exactly 3 letters.' => 'Code must be exactly 3 letters.',
    'View vendor' => 'View vendor',
    'Edit vendor' => 'Edit vendor',
    'Vendors restored.' => 'Vendors restored.',
    'Some vendors restored.' => 'Some vendors restored.',
    'Vendors not restored.' => 'Vendors not restored.',
    'Couldn’t delete vendor.' => 'Couldn’t delete vendor.',
    'Vendor deleted.' => 'Vendor deleted.',
    'Couldn’t save vendor volume folders.' => 'Couldn’t save vendor volume folders.',
    'Shipping Profiles' => 'Shipping Profiles',
    'Orders' => 'Orders',
    '1 Business Day' => '1 Business Day',
    '1-2 Business Days' => '1-2 Business Days',
    '1-3 Business Days' => '1-3 Business Days',
    '3-5 Business Days' => '3-5 Business Days',
    '1-2 Weeks' => '1-2 Weeks',
    '2-3 Weeks' => '2-3 Weeks',
    '3-4 Weeks' => '3-4 Weeks',
    '4-6 Weeks' => '4-6 Weeks',
    '6-8 Weeks' => '6-8 Weeks',
    'Unknown' => 'Unknown',
    'Processing Time' => 'Processing Time',
    'Origin Country' => 'Origin Country',
    'Shipping' => 'Shipping',
    'No shipping profiles available.' => 'No shipping profiles available.',
    'Search shipping profiles by name, vendor or origin country…' => 'Search shipping profiles by name, vendor or origin country…',
    'No. of Destinations' => 'No. of Destinations',
    'Edit shipping profile' => 'Edit shipping profile',
    'Create a new shipping profile' => 'Create a new shipping profile',
    'Delete shipping profile' => 'Delete shipping profile',
    'Are you sure you want to delete this shipping profile?' => 'Are you sure you want to delete this shipping profile?',
    'What this shipping profile will be called, this will be seen by customers during checkout.' => 'What this shipping profile will be called, this will be seen by customers during checkout.',
    'Where this profile is shipping from in the world (usually wherever the vendor is).' => 'Where this profile is shipping from in the world (usually wherever the vendor is).',
    'How long it takes you to ship this item.' => 'How long it takes you to ship this item.',
    'The Vendor this profile is for.' => 'The Vendor this profile is for.',
    'Shipping Costs' => 'Shipping Costs',
    'Add a shipping cost for each destination you ship to.' => 'Add a shipping cost for each destination you ship to.',
    'Add a destination' => 'Add a destination',
    'Destination *' => 'Destination *',
    'One item *' => 'One item *',
    'Each additional item' => 'Each additional item',
    'Estimated delivery time *' => 'Estimated delivery time *',
    'No shipping profile exists with the ID “{id}”' => 'No shipping profile exists with the ID “{id}”',
    'Shipping profile saved.' => 'Shipping profile saved.',
    'Couldn’t save shipping profile.' => 'Couldn’t save shipping profile.',
    'No shipping destination exists with the ID “{id}”' => 'No shipping destination exists with the ID “{id}”',
    'Couldn’t delete shipping profile.' => 'Couldn’t delete shipping profile.',
    'Shipping profile deleted.' => 'Shipping profile deleted.',
    'New shipping profile' => 'New shipping profile',
    'Shipping Profile Options' => 'Shipping Profile Options',
    'Shipping Profile' => 'Shipping Profile',
    'Please add a Vendor and save first.' => 'Please add a Vendor and save first.',
    'This Vendor doesn’t yet have any shipping profiles, please create some first.' => 'This Vendor doesn’t yet have any shipping profiles, please create some first.',
    'Date Ordered' => 'Date Ordered',
    'Search orders by customer name, email, or order number…' => 'Search orders by customer name, email, or order number…',
    'No orders available.' => 'No orders available.',
    'Dashboard' => 'Dashboard',
    'Files' => 'Files',
    'Reports' => 'Reports',
    'Products' => 'Products',
    'Activate vendor' => 'Activate vendor',
    'Couldn’t unsuspend vendor.' => 'Couldn’t unsuspend vendor.',
    'Vendor unsuspended.' => 'Vendor unsuspended.',
    'Set as pending' => 'Set as pending',
    'Couldn’t suspend vendor.' => 'Couldn’t suspend vendor.',
    'Vendor suspended.' => 'Vendor suspended.',
    'Couldn’t set vendor as pending.' => 'Couldn’t set vendor as pending.',
    'Vendor set as pending.' => 'Vendor set as pending.',
    'Order #{reference}' => 'Order #{reference}',
    'Settings' => 'Settings',
    'Sign out' => 'Sign out',
    'Account settings' => 'Account settings',
    'Edit shop front' => 'Edit shop front',
    'View shop' => 'View shop',
    'Signed in as' => 'Signed in as',
    'Update Status' => 'Update Status',
    'Packing Slip' => 'Packing Slip',
    'Refund' => 'Refund',
    'Create new product' => 'Create new product',
    'Create new shipping profile' => 'Create new shipping profile',
    'Account {status}' => 'Account {status}',
    'Recent orders' => 'Recent orders',
    'View all' => 'View all',
    'Setup Payments' => 'Setup Payments',
    'Reference' => 'Reference',
    'Customer' => 'Customer',
    'Status' => 'Status',
    'Amount' => 'Amount',
    'Date' => 'Date',
    'Total' => 'Total',
    'Sub Total' => 'Sub Total',
    'Qty' => 'Qty',
    'Price' => 'Price',
    'SKU' => 'SKU',
    'Item' => 'Item',
    'Last 30 days' => 'Last 30 days',
    'Total Orders' => 'Total Orders',
    'Increased by' => 'Increased by',
    'Decreased by' => 'Decreased by',
    'Upload' => 'Upload',
    'Download' => 'Download',
    'New Orders' => 'New Orders',
    'Overview' => 'Overview',
];
