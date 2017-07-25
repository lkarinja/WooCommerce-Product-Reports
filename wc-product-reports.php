<?php
/*
	Plugin Name: WooCommerce Product Reports
	Description: Generates reports based on WooCommerce Products
	Version: 1.0.0
	Author: <a href="https://github.com/lkarinja">Leejae Karinja</a>
	License: GPL3
	License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

/*
	WooCommerce Product Reports
	Copyright (C) 2017 Leejae Karinja

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Prevents execution outside of core WordPress
if(!defined('ABSPATH'))
{
	exit;
}

// Defines path to this plugin
define('WC_PRODUCT_REPORTS_PATH', plugin_dir_path(__FILE__));

// Include the Query Builder
include_once(WC_PRODUCT_REPORTS_PATH . 'includes/query-builder.php');

// If the class for the plugin is not defined
if(!class_exists('WC_Product_Reports'))
{
	// Define the class for the plugin
	class WC_Product_Reports
	{
		/**
		 * Plugin constructor
		 */
		public function __construct()
		{
			// Used for debugging, allows us to 'echo' for JS 'alert()' and such
			ob_start();

			// Set plugin textdomain for the Admin Pages
			$this->textdomain = 'wc-product-reports';

			// On every page load
			add_action('init', array($this, 'init'));
		}

		/**
		 * Creates a controller page for the Plugin in the Admin Menu
		 */
		public function init()
		{
			// Add page in the admin
			add_action('admin_menu', array($this, 'add_admin_page'));
		}

		/**
		 * Adds a page in the Admin Menu
		 *
		 * Parts of this function are referenced from Terry Tsang (http://shop.terrytsang.com) Extra Fee Option Plugin (http://terrytsang.com/shop/shop/woocommerce-extra-fee-option/)
		 * Licensed under GPL2 (Or later)
		 */
		public function add_admin_page()
		{
			add_submenu_page(
				'woocommerce',
				__('Product Reports', $this->textdomain),
				__('Product Reports', $this->textdomain),
				'manage_options',
				'wc-product-reports',
				array(
					$this,
					'product_reports_page'
				)
			);
		}

		/**
		 * Adds a page in the Admin Menu
		 *
		 * Parts of this function are referenced from Matt Gates (http://mgates.me) WC Vendors Admin Reports (https://github.com/wcvendors/wcvendors/blob/master/classes/admin/class-admin-reports.php)
		 * Licensed under GPL2 (Or later)
		 */
		public function product_reports_page()
		{
			// Get the entered start date (Default is first of the month)
			$start_date = !empty($_POST['start_date']) ? date('Y-m-d', strtotime($_POST['start_date'])) : date('Y-m-d', strtotime(date('Ym', current_time('timestamp')) . '01'));
			// Get the entered end date (Default is today)
			$end_date = !empty($_POST['end_date']) ? date('Y-m-d', strtotime($_POST['end_date'])) : date('Y-m-d', current_time('timestamp'));

			// Get all vendors for vendor filter
			$vendors = get_users(array('role' => 'vendor'));
			// Selected vendor to filter by, if selected
			$selected_vendor = !empty($_POST['show_vendor']) ? (int) $_POST['show_vendor'] : false;

			// Items sold based on filter parameters
			$items = Query_Builder::get_items($start_date, date('Y-m-d', strtotime('+1 day', strtotime($end_date))), $selected_vendor);

			// HTML/PHP for the page display
			?>

			<form method="post" action="">
				<p>
					<label for="from"><?php _e('From:', $this->textdomain); ?></label>
					<input type="text" size="9" placeholder="YYYY-MM-DD" value="<?php echo esc_attr($start_date); ?>" name="start_date"/>

					<label for="to"><?php _e('To:', $this->textdomain); ?></label>
					<input type="text" size="9" placeholder="YYYY-MM-DD" value="<?php echo esc_attr($end_date); ?>" name="end_date"/>

					<label for="to"><?php _e('Vendor:', $this->textdomain); ?></label>
					<select class="chosen_select" id="show_vendor" name="show_vendor" style="width: 300px;">
						<option></option>
						<?php foreach($vendors as $key => $vendor) printf('<option value="%s" %s>%s</option>', $vendor->ID, selected($selected_vendor, $vendor->ID, false), $vendor->display_name); ?>
					</select>

					<input type="submit" class="button" value="<?php _e('Filter', $this->textdomain); ?>"/>
				</p>
			</form>

			<div>
				<h3><span><?php _e('Items', $this->textdomain); ?></span></h3>

				<div>
					<style>
					table, th, td {
						border: 1px solid black;
					}
					</style>
					<table>
						<thead>
							<tr>
								<th><?php _e('Product', $this->textdomain) ?></th>
								<th><?php _e('SKU', $this->textdomain) ?></th>
								<th><?php _e('Vendor', $this->textdomain) ?></th>
								<th><?php _e('Quantity', $this->textdomain) ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach($items as $item): ?>
							<tr>
								<td><?php echo $item->product_name; ?></td>
								<td><?php echo $item->product_sku; ?></td>
								<td><?php echo $item->vendor_name; ?></td>
								<td><?php echo $item->product_qty; ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<?php
		}

	}
	// Create new instance of 'WC_Product_Reports' class
	$wc_product_reports = new WC_Product_Reports();
}
