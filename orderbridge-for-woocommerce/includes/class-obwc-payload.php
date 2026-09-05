<?php
defined( 'ABSPATH' ) || exit;

final class OBWC_Payload {
	public static function from_order( WC_Order $order ) {
		$modified = $order->get_date_modified();
		$revision = $modified ? $modified->getTimestamp() : time();
		$items = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			$items[] = array(
				'line_id' => (int) $item_id,
				'sku' => $product ? $product->get_sku() : '',
				'name' => $item->get_name(),
				'quantity' => (int) $item->get_quantity(),
				'total' => (string) $item->get_total(),
			);
		}
		return array(
			'event' => 'order.upserted',
			'idempotency_key' => 'wc-' . $order->get_id() . '-' . $revision,
			'order' => array(
				'id' => $order->get_id(), 'number' => $order->get_order_number(), 'status' => $order->get_status(),
				'currency' => $order->get_currency(), 'total' => $order->get_total(),
				'customer' => array( 'email' => $order->get_billing_email(), 'first_name' => $order->get_billing_first_name(), 'last_name' => $order->get_billing_last_name() ),
				'shipping' => array( 'country' => $order->get_shipping_country(), 'city' => $order->get_shipping_city(), 'postcode' => $order->get_shipping_postcode() ),
				'items' => $items,
			),
		);
	}
}
