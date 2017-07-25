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

class Query_Builder
{

	// SQL Query to get sold products with Name, SKU, Vendor, and Quantity information
	const BASE_QUERY =
		'
		SELECT
		  product.product_name AS product_name,
		  product.product_sku AS product_sku,
		  product.vendor_name AS vendor_name,
		  SUM(order_item.product_qty) AS product_qty
		FROM
		  (
			SELECT
			  product.product_id AS product_id,
			  product.product_name AS product_name,
			  product.product_sku AS product_sku,
			  vendor.vendor_name AS vendor_name
			FROM
			  (
				SELECT
				  posts.id AS product_id,
				  posts.post_title AS product_name,
				  meta.meta_value AS product_sku,
				  posts.post_author AS vendor_id
				FROM
				  wp_posts AS posts
				  INNER JOIN
					wp_postmeta AS meta
					ON posts.id = meta.post_id
				WHERE
				  meta.meta_key = "_sku"
				  AND posts.post_type = "product"
				  %s
			  )
			  AS product
			  INNER JOIN
				(
				  SELECT
					users.id AS vendor_id,
					users.display_name AS vendor_name
				  FROM
					wp_users AS users
					INNER JOIN
					  wp_usermeta AS meta
					  ON users.id = meta.user_id
				  WHERE
					meta.meta_key = "wp_capabilities"
					AND meta.meta_value LIKE "%%vendor%%"
					%s
				)
				AS vendor
				ON product.vendor_id = vendor.vendor_id
		  )
		  AS product
		  INNER JOIN
			(
			  SELECT
				id.product_id AS product_id,
				qty.product_qty AS product_qty
			  FROM
				(
				  SELECT
					meta.order_item_id AS order_item_id,
					meta.meta_value AS product_id
				  FROM
					wp_posts AS posts
					JOIN
					  wp_woocommerce_order_items AS items
					  ON posts.ID = items.order_id
					INNER JOIN
					  wp_woocommerce_order_itemmeta AS meta
					  ON items.order_item_id = meta.order_item_id
				  WHERE
					meta.meta_key = "_product_id"
					AND post_type = "shop_order"
					AND post_status = "wc-completed"
					%s
				)
				AS id
				INNER JOIN
				  (
					SELECT
					  meta.order_item_id AS order_item_id,
					  meta.meta_value AS product_qty
					FROM
					  wp_posts AS posts
					  JOIN
						wp_woocommerce_order_items AS items
						ON posts.ID = items.order_id
					  INNER JOIN
						wp_woocommerce_order_itemmeta AS meta
						ON items.order_item_id = meta.order_item_id
					WHERE
					  meta.meta_key = "_qty"
					  AND post_type = "shop_order"
					  AND post_status = "wc-completed"
					  %s
				  )
				  AS qty
				  ON id.order_item_id = qty.order_item_id
			)
			AS order_item
			ON product.product_id = order_item.product_id
		GROUP BY
		  product_name,
		  product_sku,
		  vendor_name
		';

	/**
	 * Gets details on all items sold based on a date range and vendor
	 */
	public static function get_items($start_date, $end_date, $vendor_id)
	{
		// Allow us to query the WordPress Database
		global $wpdb;

		$between = "";
		$posts_post_author = "";
		$users_id = "";

		// If a vendor was specified
		if($vendor_id != 0)
		{
			// Create prepared statements for SQL Query based on vendor
			$posts_post_author = $wpdb->prepare("AND posts.post_author = %d", $vendor_id);
			$users_id = $wpdb->prepare("AND users.id = %d", $vendor_id);
		}

		// If a start and end date were specified
		if($start_date != "" && $end_date != "")
		{
			// Create prepared statements for SQL Query based on dates
			$between = $wpdb->prepare("AND post_date BETWEEN %s AND %s", $start_date, $end_date);
		}

		// Final prepared query to use
		$prepared = sprintf(
			self::BASE_QUERY,
			$posts_post_author,
			$users_id,
			$between,
			$between
		);

		// Get the results as an array
		$items = $wpdb->get_results($prepared);

		return $items;
	}


}
