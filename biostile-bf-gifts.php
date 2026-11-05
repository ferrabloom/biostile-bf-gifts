<?php
/**
 * Plugin Name: BiostileGifts
 * Description: Brezplačna darila po izbiri (1 / 2 / 3 darila po pragovih) – blok v FunnelKit košarici in pop-up. Nastavitve: meni BiostileGifts.
 * Version: 0.4.1
 * Author: Biostile
 * Requires Plugins: woocommerce
 * Text Domain: biostile-bf-gifts
 * Update URI: https://github.com/ferrabloom/biostile-bf-gifts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BFG_VERSION', '0.4.1' );
define( 'BFG_FILE', __FILE__ );
define( 'BFG_URL', plugin_dir_url( __FILE__ ) );
define( 'BFG_REPO', 'https://github.com/ferrabloom/biostile-bf-gifts/' );

require_once __DIR__ . '/includes/markets.php';
require_once __DIR__ . '/includes/strings.php';
require_once __DIR__ . '/includes/admin.php';

/* -------------------------------------------------------------------------
 * POSODOBITVE IZ GITHUBA
 *  - live trgi: zadnji GitHub Release (zip asset),
 *  - dev: veja "dev" (vsak push je takoj na voljo za test).
 *  Zasebni repo: v wp-config.php define( 'BFG_GITHUB_TOKEN', '...' );
 * ---------------------------------------------------------------------- */
if ( file_exists( __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';
	add_action( 'plugins_loaded', function () {
		if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}
		$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker( BFG_REPO, BFG_FILE, 'biostile-bf-gifts' );
		if ( 'dev' === bfg_market_key() ) {
			$checker->setBranch( 'dev' );
		} else {
			$checker->setBranch( 'main' );
			$checker->getVcsApi()->enableReleaseAssets( '/biostile-bf-gifts\.zip$/' );
		}
		if ( defined( 'BFG_GITHUB_TOKEN' ) && BFG_GITHUB_TOKEN ) {
			$checker->setAuthentication( BFG_GITHUB_TOKEN );
		}
	} );
}

/** Samodejne posodobitve samo za ta plugin. */
add_filter( 'auto_update_plugin', function ( $update, $item ) {
	return ( isset( $item->plugin ) && plugin_basename( BFG_FILE ) === $item->plugin ) ? true : $update;
}, 20, 2 );

/* -------------------------------------------------------------------------
 * KONFIGURACIJA
 *  privzeto: includes/markets.php (po domeni),
 *  prepiše: nastavitve v adminu (meni BiostileGifts, opcija bfg_settings),
 *  na koncu filter 'bfg_config'.
 * ---------------------------------------------------------------------- */

/** Privzeti teksti trga (brez sprememb iz admina). */
function bfg_default_strings() {
	$markets = bfg_markets();
	$market  = $markets[ bfg_market_key() ] ?? array();
	return array_merge( bfg_strings_sl(), (array) ( $market['strings'] ?? array() ) );
}

/** Objavljen izdelek s tem SKU (če ima isti SKU tudi osnutek ali duplikat, vzame objavljenega). */
function bfg_published_id_by_sku( $sku ) {
	static $cache = array();
	if ( isset( $cache[ $sku ] ) ) {
		return $cache[ $sku ];
	}
	$ids = get_posts( array(
		'post_type'        => 'product',
		'post_status'      => 'publish',
		'posts_per_page'   => 1,
		'fields'           => 'ids',
		'orderby'          => 'ID',
		'order'            => 'ASC',
		'no_found_rows'    => true,
		'suppress_filters' => true,
		'meta_query'       => array( array( 'key' => '_sku', 'value' => (string) $sku, 'compare' => '=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	return $cache[ $sku ] = $ids ? (int) $ids[0] : 0;
}

function bfg_config() {
	static $config = null;
	if ( null !== $config ) {
		return $config;
	}
	$markets = bfg_markets();
	$key     = bfg_market_key();
	$market  = $markets[ $key ] ?? array();
	$config  = array_merge( bfg_market_defaults(), $market, array( 'market' => $key ) );
	$config['strings'] = bfg_default_strings();

	$s = function_exists( 'bfg_settings' ) ? bfg_settings() : array();
	if ( $s ) {
		if ( isset( $s['schedule'] ) ) {
			$config['enabled']   = 'off' !== $s['schedule'];
			$config['always_on'] = 'always' === $s['schedule'];
		}
		foreach ( array( 'start', 'end', 'mode', 'logo' ) as $k ) {
			if ( ! empty( $s[ $k ] ) ) {
				$config[ $k ] = $s[ $k ];
			}
		}
		if ( ! empty( $s['thresholds'] ) ) {
			$config['thresholds'] = array_values( $s['thresholds'] );
		}
		if ( isset( $s['gifts'] ) ) {
			// display_price (samo dev) ohranimo iz markets.php.
			$display = array();
			foreach ( (array) $config['gifts'] as $g ) {
				$id = ! empty( $g['id'] ) ? (int) $g['id'] : ( ! empty( $g['sku'] ) ? bfg_published_id_by_sku( $g['sku'] ) : 0 );
				if ( $id && ! empty( $g['display_price'] ) ) {
					$display[ $id ] = $g['display_price'];
				}
			}
			$config['gifts'] = array_map( function ( $g ) use ( $display ) {
				if ( isset( $display[ (int) $g['id'] ] ) ) {
					$g['display_price'] = $display[ (int) $g['id'] ];
				}
				return $g;
			}, (array) $s['gifts'] );
		}
		if ( ! empty( $s['strings'] ) ) {
			$config['strings'] = array_merge( $config['strings'], $s['strings'] );
		}
		if ( ! empty( $s['start'] ) && strlen( $s['start'] ) === 16 ) {
			$config['start'] .= ':00';
		}
		if ( ! empty( $s['end'] ) && strlen( $s['end'] ) === 16 ) {
			$config['end'] .= ':59';
		}
	}
	$config['mode'] = 'popup' === ( $config['mode'] ?? '' ) ? 'popup' : 'cart';
	// Darila lahko podamo s SKU (EAN) – ID se poišče na vsaki domeni posebej.
	$config['gifts'] = array_values( array_filter( array_map( function ( $g ) {
		if ( empty( $g['id'] ) && ! empty( $g['sku'] ) ) {
			$g['id'] = bfg_published_id_by_sku( $g['sku'] );
		}
		return $g;
	}, (array) $config['gifts'] ), function ( $g ) {
		return ! empty( $g['id'] ) && 'publish' === get_post_status( $g['id'] );
	} ) );
	$config  = apply_filters( 'bfg_config', $config );
	return $config;
}

function bfg_is_dev() {
	return (bool) apply_filters( 'bfg_is_dev', ! empty( bfg_config()['use_display'] ) );
}

/** Predogled za administratorja: ?bfg_preview=1 (vklop), ?bfg_preview=0 (izklop). */
function bfg_is_preview() {
	static $p = null;
	if ( null !== $p ) {
		return $p;
	}
	if ( empty( $_COOKIE['bfg_preview'] ) ) {
		return false;
	}
	if ( ! did_action( 'set_current_user' ) ) {
		return false; // uporabnik še ni znan – ne shranimo rezultata.
	}
	$p = is_user_logged_in() && current_user_can( 'manage_woocommerce' );
	return $p;
}

add_action( 'init', function () {
	if ( ! isset( $_GET['bfg_preview'] ) || ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$on = '1' === $_GET['bfg_preview']; // phpcs:ignore
	setcookie( 'bfg_preview', $on ? '1' : '', $on ? 0 : time() - 3600, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
	$_COOKIE['bfg_preview'] = $on ? '1' : '';
} );

function bfg_is_active() {
	$c = bfg_config();
	if ( empty( $c['gifts'] ) ) {
		return (bool) apply_filters( 'bfg_is_active', false );
	}
	if ( bfg_is_preview() ) {
		return (bool) apply_filters( 'bfg_is_active', true );
	}
	if ( empty( $c['enabled'] ) ) {
		return (bool) apply_filters( 'bfg_is_active', false );
	}
	if ( ! empty( $c['always_on'] ) ) {
		return (bool) apply_filters( 'bfg_is_active', true );
	}
	$tz  = new DateTimeZone( $c['timezone'] );
	$now = new DateTime( 'now', $tz );
	$on  = $now >= new DateTime( $c['start'], $tz ) && $now <= new DateTime( $c['end'], $tz );
	return (bool) apply_filters( 'bfg_is_active', $on );
}

function bfg_gift_ids() {
	return array_map( 'intval', wp_list_pluck( bfg_config()['gifts'], 'id' ) );
}

function bfg_is_gift_item( $item ) {
	return ! empty( $item['bfg_gift'] );
}

/* -------------------------------------------------------------------------
 * IZRAČUN
 * ---------------------------------------------------------------------- */

/** Vrednost izdelkov po popustih (z DDV), brez dostave in brez daril. */
function bfg_eligible_total() {
	if ( ! WC()->cart ) {
		return 0;
	}
	$total = 0;
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( bfg_is_gift_item( $item ) ) {
			continue;
		}
		$total += (float) ( $item['line_total'] ?? 0 ) + (float) ( $item['line_tax'] ?? 0 );
	}
	return round( $total, 2 );
}

function bfg_allowance( $total = null ) {
	$total = null === $total ? bfg_eligible_total() : $total;
	$n     = 0;
	foreach ( bfg_config()['thresholds'] as $t ) {
		if ( $total >= $t ) {
			$n++;
		}
	}
	return $n;
}

function bfg_cart_gifts() {
	$out = array();
	if ( ! WC()->cart ) {
		return $out;
	}
	foreach ( WC()->cart->get_cart() as $key => $item ) {
		if ( bfg_is_gift_item( $item ) ) {
			$out[ $key ] = (int) $item['product_id'];
		}
	}
	return $out;
}

/* -------------------------------------------------------------------------
 * KOŠARICA: cena 0 €, količina 1, omejitev po pragovih
 * ---------------------------------------------------------------------- */

add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	foreach ( $cart->get_cart() as $item ) {
		if ( bfg_is_gift_item( $item ) && isset( $item['data'] ) ) {
			$item['data']->set_price( 0 );
		}
	}
}, PHP_INT_MAX );

/**
 * Discount Rules (Flycart): darila ne štejejo v pogoje, količine in BOGO/"cheapest" pravila
 * in ne dobijo dodatnega popusta. Sicer lahko darilo sproži npr. "Buy X Get Y" brezplačni izdelek.
 */
// Discount Rules v3: darila sploh niso vidna pravilom (tudi BOGO/auto-add, ki bere košarico neposredno).
add_filter( 'wdr_cart_items', function ( $items ) {
	return is_array( $items ) ? array_filter( $items, function ( $i ) {
		return ! ( is_array( $i ) && bfg_is_gift_item( $i ) );
	} ) : $items;
}, 100 );
add_filter( 'wdr_discount_get_cart_items', function ( $items ) {
	return is_array( $items ) ? array_filter( $items, function ( $i ) {
		return ! ( is_array( $i ) && bfg_is_gift_item( $i ) );
	} ) : $items;
}, 100 );
add_filter( 'wdr_include_cart_item_to_count_quantity', function ( $include, $cart_item = array() ) {
	return ( is_array( $cart_item ) && bfg_is_gift_item( $cart_item ) ) ? false : $include;
}, 100, 2 );

// Discount Rules v2 (stari hooki, za vsak slučaj).
foreach ( array(
	'advanced_woo_discount_rules_calculate_discount_for_cart_item',
	'advanced_woo_discount_rules_include_cart_item_to_count_quantity',
	'advanced_woo_discount_rules_process_cart_item_for_cheapest_rule',
) as $bfg_wdr_filter ) {
	add_filter( $bfg_wdr_filter, function ( $value, $cart_item = array() ) {
		return ( is_array( $cart_item ) && bfg_is_gift_item( $cart_item ) ) ? false : $value;
	}, 100, 2 );
}

/**
 * Biostile child tema (jupiterx-child): manage_discounted_product() za buybox ponudbe
 * (ACF first/second/third_option, npr. 2+1) sešteje količino vseh vrstic z istim product_id.
 * Darila ne smejo šteti, sicer npr. 1 plačan + 1 darilo sproži brezplačni kos.
 *  - ko se doda / spremeni / odstrani darilo samo: funkcijo teme preskočimo,
 *  - ko se spremeni navaden izdelek: med izvajanjem teme so darila skrita iz WC()->cart->get_cart().
 */
function bfg_strip_gifts_from_contents( $contents ) {
	return array_filter( (array) $contents, function ( $i ) {
		return ! bfg_is_gift_item( $i );
	} );
}

function bfg_theme_guard_start( $is_gift ) {
	$GLOBALS['bfg_theme_guard'] = ( $GLOBALS['bfg_theme_guard'] ?? 0 ) + 1;
	if ( 1 !== $GLOBALS['bfg_theme_guard'] ) {
		return;
	}
	if ( $is_gift && empty( $GLOBALS['manage_discounted_product_is_updating'] ) ) {
		$GLOBALS['manage_discounted_product_is_updating'] = true;
		$GLOBALS['bfg_theme_guard_set_flag']             = true;
	}
	add_filter( 'woocommerce_get_cart_contents', 'bfg_strip_gifts_from_contents', PHP_INT_MAX );
}

function bfg_theme_guard_end() {
	$GLOBALS['bfg_theme_guard'] = max( 0, ( $GLOBALS['bfg_theme_guard'] ?? 1 ) - 1 );
	if ( 0 !== $GLOBALS['bfg_theme_guard'] ) {
		return;
	}
	remove_filter( 'woocommerce_get_cart_contents', 'bfg_strip_gifts_from_contents', PHP_INT_MAX );
	if ( ! empty( $GLOBALS['bfg_theme_guard_set_flag'] ) ) {
		$GLOBALS['manage_discounted_product_is_updating'] = false;
		$GLOBALS['bfg_theme_guard_set_flag']             = false;
	}
}

add_action( 'woocommerce_add_to_cart', function ( $key, $pid, $qty, $vid, $var, $data ) {
	bfg_theme_guard_start( ! empty( $data['bfg_gift'] ) );
}, 9, 6 );
add_action( 'woocommerce_add_to_cart', 'bfg_theme_guard_end', 11, 0 );

add_action( 'woocommerce_after_cart_item_quantity_update', function ( $key, $qty = 0, $old = 0, $cart = null ) {
	$item = WC()->cart ? ( WC()->cart->cart_contents[ $key ] ?? array() ) : array();
	bfg_theme_guard_start( bfg_is_gift_item( $item ) );
}, 9, 4 );
add_action( 'woocommerce_after_cart_item_quantity_update', 'bfg_theme_guard_end', 11, 0 );

add_action( 'woocommerce_cart_item_removed', function ( $key, $cart = null ) {
	$item = WC()->cart ? ( WC()->cart->removed_cart_contents[ $key ] ?? array() ) : array();
	$is_gift = bfg_is_gift_item( $item );
	bfg_theme_guard_start( $is_gift );
	// Funkcija teme na odstranitvi nima zaščite z zastavico – pri darilu jo začasno odklopimo.
	if ( $is_gift && function_exists( 'manage_discounted_product_on_cart_item_removed' ) && 1 === $GLOBALS['bfg_theme_guard'] ) {
		remove_action( 'woocommerce_cart_item_removed', 'manage_discounted_product_on_cart_item_removed', 10 );
		$GLOBALS['bfg_theme_removed_unhooked'] = true;
	}
}, 9, 2 );
add_action( 'woocommerce_cart_item_removed', function () {
	if ( ! empty( $GLOBALS['bfg_theme_removed_unhooked'] ) ) {
		add_action( 'woocommerce_cart_item_removed', 'manage_discounted_product_on_cart_item_removed', 10, 2 );
		$GLOBALS['bfg_theme_removed_unhooked'] = false;
	}
	bfg_theme_guard_end();
}, 11, 0 );

add_action( 'woocommerce_after_calculate_totals', function ( $cart ) {
	static $running = false;
	if ( $running || ! did_action( 'wp_loaded' ) ) {
		return;
	}
	$gifts = bfg_cart_gifts();
	if ( ! $gifts ) {
		return;
	}
	$running = true;
	$allowed = bfg_is_active() ? bfg_allowance() : 0;
	$valid   = bfg_gift_ids();
	$kept    = 0;
	$removed = 0;
	foreach ( $gifts as $key => $pid ) {
		$item = $cart->get_cart_item( $key );
		if ( $kept < $allowed && in_array( $pid, $valid, true ) ) {
			if ( 1 !== (int) $item['quantity'] ) {
				$cart->set_quantity( $key, 1, false );
			}
			$kept++;
		} else {
			$cart->remove_cart_item( $key );
			$removed++;
		}
	}
	if ( $removed ) {
		if ( WC()->session ) {
			WC()->session->set( 'bfg_removed', 1 );
		}
		$cart->calculate_totals();
	}
	$running = false;
}, 20 );

/** Ob dodajanju navadnega izdelka označi, da se prikaže pop-up. */
add_action( 'woocommerce_add_to_cart', function ( $key, $pid, $qty, $vid, $variation, $data ) {
	if ( ! empty( $data['bfg_gift'] ) || ! bfg_is_active() || ! WC()->session ) {
		return;
	}
	WC()->session->set( 'bfg_show', 1 );
}, 10, 6 );

/** Količina darila je fiksna 1. */
add_filter( 'woocommerce_cart_item_quantity', function ( $html, $key, $item ) {
	return bfg_is_gift_item( $item ) ? '1' : $html;
}, 20, 3 );

add_filter( 'woocommerce_cart_item_name', function ( $name, $item ) {
	if ( bfg_is_gift_item( $item ) ) {
		$name .= ' <span class="bfg-cart-badge">' . esc_html( bfg_config()['strings']['cart_label'] ) . '</span>';
	}
	return $name;
}, 20, 2 );

add_filter( 'woocommerce_get_item_data', function ( $data, $item ) {
	if ( bfg_is_gift_item( $item ) ) {
		$data[] = array( 'key' => bfg_config()['strings']['meta_label'], 'value' => bfg_config()['strings']['campaign'] );
	}
	return $data;
}, 20, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $line, $key, $values ) {
	if ( bfg_is_gift_item( $values ) ) {
		$line->add_meta_data( '_bfg_gift', 'yes', true );
		$line->add_meta_data( bfg_config()['strings']['meta_label'], bfg_config()['strings']['campaign'], true );
	}
}, 10, 3 );

add_filter( 'woocommerce_hidden_order_itemmeta', function ( $keys ) {
	$keys[] = '_bfg_gift';
	return $keys;
} );

/* -------------------------------------------------------------------------
 * AJAX (wc-ajax, ne gre skozi page cache)
 * ---------------------------------------------------------------------- */

/** Darila za prikaz (ime, velikost, redna cena, slike, opis). */
function bfg_gifts_display() {
	$c     = bfg_config();
	$dev   = bfg_is_dev();
	$gifts = array();
	foreach ( $c['gifts'] as $g ) {
		$p = wc_get_product( $g['id'] );
		if ( ! $p ) {
			continue;
		}
		$name = wp_strip_all_tags( $p->get_name() );
		$size = '';
		if ( false !== strrpos( $name, ',' ) ) {
			$size = trim( substr( $name, strrpos( $name, ',' ) + 1 ) );
			$name = trim( substr( $name, 0, strrpos( $name, ',' ) ) );
		}
		$regular = (float) wc_get_price_including_tax( $p, array( 'price' => $p->get_regular_price() ) );
		if ( $dev && ! empty( $g['display_price'] ) ) {
			$regular = (float) $g['display_price'];
		}
		$desc = wp_strip_all_tags( strip_shortcodes( $p->get_short_description() ?: $p->get_description() ) );
		$desc = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $desc, ENT_QUOTES, 'UTF-8' ) ) );
		$desc = wp_trim_words( $desc, 45, '…' );
		$gifts[] = array(
			'id'       => (int) $g['id'],
			'name'     => ! empty( $g['name'] ) ? $g['name'] : $name,
			'size'     => ! empty( $g['size'] ) ? $g['size'] : $size,
			'regular'  => $regular,
			'image'    => wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src(),
			'image_lg' => wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_single' ) ?: wc_placeholder_img_src(),
			'desc'     => $desc,
			'url'      => get_permalink( $p->get_id() ),
			'in_stock' => $p->is_in_stock() && $p->is_purchasable(),
		);
	}
	return $gifts;
}

function bfg_state( $extra = array() ) {
	$c       = bfg_config();
	$total   = bfg_eligible_total();
	$allowed = bfg_allowance( $total );
	$gifts   = bfg_gifts_display();
	$show    = WC()->session ? (int) WC()->session->get( 'bfg_show' ) : 0;
	$removed = WC()->session ? (int) WC()->session->get( 'bfg_removed' ) : 0;
	if ( WC()->session ) {
		WC()->session->set( 'bfg_show', 0 );
		WC()->session->set( 'bfg_removed', 0 );
	}
	return array_merge( array(
		'active'     => bfg_is_active(),
		'total'      => $total,
		'allowed'    => $allowed,
		'thresholds' => $c['thresholds'],
		'selected'   => array_values( bfg_cart_gifts() ),
		'gifts'      => $gifts,
		'show'       => $show && bfg_is_active() && 'popup' === $c['mode'],
		'mode'       => $c['mode'],
		'removed'    => $removed,
		'count'      => WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
	), $extra );
}

add_action( 'wc_ajax_bfg_state', function () {
	WC()->cart->calculate_totals();
	wp_send_json_success( bfg_state() );
} );

add_action( 'wc_ajax_bfg_set_gifts', function () {
	if ( ! bfg_is_active() ) {
		wp_send_json_error( array( 'message' => 'inactive' ) );
	}
	$cart = WC()->cart;
	$cart->calculate_totals();
	$want    = isset( $_POST['gifts'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['gifts'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
	$want    = array_values( array_unique( array_intersect( $want, bfg_gift_ids() ) ) );
	$allowed = bfg_allowance();
	$want    = array_slice( $want, 0, $allowed );

	$current = bfg_cart_gifts();
	foreach ( $current as $key => $pid ) {
		if ( ! in_array( $pid, $want, true ) ) {
			$cart->remove_cart_item( $key );
		}
	}
	$have = array_values( bfg_cart_gifts() );
	foreach ( $want as $pid ) {
		if ( ! in_array( $pid, $have, true ) ) {
			if ( $cart->add_to_cart( $pid, 1, 0, array(), array( 'bfg_gift' => 1 ) ) && ! bfg_is_preview() ) {
				bfg_stats_bump( 'selections', $pid );
			}
		}
	}
	wc_clear_notices();
	$cart->calculate_totals();
	if ( WC()->session ) {
		WC()->session->set( 'bfg_show', 0 );
	}
	wp_send_json_success( bfg_state() );
} );

/** Statistika prikazov: open (pop-up), block (klik v košarici), checkout (pop-up ob zaključku). */
add_action( 'wc_ajax_bfg_track', function () {
	$map = array( 'open' => 'opens', 'block' => 'block_clicks', 'checkout' => 'checkout_prompts' );
	$ev  = sanitize_key( wp_unslash( $_POST['ev'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $map[ $ev ] ) && bfg_is_active() && ! bfg_is_preview() ) {
		bfg_stats_bump( $map[ $ev ] );
	}
	wp_send_json_success();
} );

/* -------------------------------------------------------------------------
 * BLOK V FUNNELKIT KOŠARICI (način A)
 * ---------------------------------------------------------------------- */

function bfg_render_cart_block() {
	if ( ! bfg_is_active() || 'cart' !== bfg_config()['mode'] || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	$c       = bfg_config();
	$S       = $c['strings'];
	$th      = array_values( $c['thresholds'] );
	$total   = bfg_eligible_total();
	$n       = bfg_allowance( $total );
	$max     = end( $th );
	$sel     = array_values( bfg_cart_gifts() );
	$imgs    = array();
	foreach ( $sel as $pid ) {
		$p = wc_get_product( $pid );
		$imgs[] = $p ? ( wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_gallery_thumbnail' ) ?: wc_placeholder_img_src() ) : '';
	}
	$round = function ( $v ) {
		return preg_replace( '/[,.]00(?=\D*$)/', '', wp_strip_all_tags( wc_price( $v ) ) );
	};
	if ( $n >= count( $th ) ) {
		$msg = $S['nudge_all'];
	} else {
		$msg = sprintf( $S['nudge'], esc_html( $S[ 'nth_' . ( $n + 1 ) ] ?? '' ), wp_strip_all_tags( wc_price( $th[ $n ] - $total ) ) );
	}
	if ( 0 === $n ) {
		$btn = $S['block_view'];
	} elseif ( count( $sel ) >= $n ) {
		$btn = sprintf( $S['block_done'], count( $sel ), $n );
	} else {
		$btn = sprintf( $S['block_btn'], count( $sel ), $n );
	}
	$clover = '<svg class="bfgb-clover" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><g fill="currentColor">';
	foreach ( array( 0, 90, 180, 270 ) as $a ) {
		$clover .= '<path transform="translate(12 12) rotate(' . ( $a + 45 ) . ') scale(.5) translate(-12 -21)" d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/>';
	}
	$clover .= '</g></svg>';
	$allowed_html = array( 'strong' => array(), 'span' => array(), 'b' => array(), 'em' => array() );
	?>
	<div class="bfgb" data-allowed="<?php echo (int) $n; ?>" data-selected="<?php echo (int) count( $sel ); ?>">
		<div class="bfgb-top"><?php echo $clover; // phpcs:ignore ?><b><?php echo esc_html( $S['event'] ); ?></b><span><?php echo esc_html( $S['block_tag'] ); ?></span></div>
		<div class="bfgb-body">
			<div class="bfgb-msg"><?php echo wp_kses( $msg, $allowed_html ); ?></div>
			<div class="bfgb-prog"><i style="width:<?php echo esc_attr( round( min( 100, $max > 0 ? $total / $max * 100 : 0 ), 1 ) ); ?>%"></i></div>
			<div class="bfgb-ms">
				<?php foreach ( $th as $i => $t ) : ?>
					<span class="<?php echo $n > $i ? 'on' : ''; ?>"><?php echo esc_html( $S[ 'gift_' . ( $i + 1 ) ] ?? '' ); ?> · <?php echo esc_html( $round( $t ) ); ?></span>
				<?php endforeach; ?>
			</div>
			<div class="bfgb-chips">
				<?php for ( $i = 0; $i < count( $th ); $i++ ) : ?>
					<?php if ( isset( $imgs[ $i ] ) ) : ?>
						<span class="bfgb-chip is-full"><img src="<?php echo esc_url( $imgs[ $i ] ); ?>" alt=""></span>
					<?php elseif ( $i < $n ) : ?>
						<span class="bfgb-chip is-open">+</span>
					<?php else : ?>
						<span class="bfgb-chip is-locked"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 10V8a5 5 0 0 1 10 0v2h1a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-9a1 1 0 0 1 1-1h1zm2 0h6V8a3 3 0 0 0-6 0v2z"/></svg></span>
					<?php endif; ?>
				<?php endfor; ?>
			</div>
			<button type="button" class="bfgb-btn bfg-open-popup<?php echo ( $n > 0 && count( $sel ) >= $n ) ? ' is-done' : ''; ?>" data-bfg-ev="block"><?php echo esc_html( $btn ); ?></button>
		</div>
	</div>
	<?php
}
add_action( 'fkcart_before_cart_items', 'bfg_render_cart_block', 5 );

/* -------------------------------------------------------------------------
 * FRONTEND
 * ---------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', function () {
	if ( ! bfg_is_active() || is_admin() ) {
		return;
	}
	wp_enqueue_style( 'bfg', BFG_URL . 'assets/bfg.css', array(), BFG_VERSION );
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return; // Na checkoutu samo CSS (oznaka darila), brez pop-upa.
	}
	wp_enqueue_script( 'bfg', BFG_URL . 'assets/bfg.js', array( 'jquery' ), BFG_VERSION, true );
	wp_localize_script( 'bfg', 'bfgData', array(
		'stateUrl'    => WC_AJAX::get_endpoint( 'bfg_state' ),
		'trackUrl'    => WC_AJAX::get_endpoint( 'bfg_track' ),
		'mode'        => bfg_config()['mode'],
		'setUrl'      => WC_AJAX::get_endpoint( 'bfg_set_gifts' ),
		'checkoutUrl' => wc_get_checkout_url(),
		'locale'      => str_replace( '_', '-', get_locale() ),
		// Zapis cen kot v trgovini (decimalke, ločila, simbol) – enako kot ostala stran.
		'decimals'    => wc_get_price_decimals(),
		'decSep'      => wc_get_price_decimal_separator(),
		'thouSep'     => wc_get_price_thousand_separator(),
		'priceFormat' => html_entity_decode( sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), '%v' ), ENT_QUOTES, 'UTF-8' ),
		'currency'    => get_woocommerce_currency(),
		'strings'     => bfg_config()['strings'],
		'logo'        => bfg_config()['logo'] ?? '',
	) );
} );
