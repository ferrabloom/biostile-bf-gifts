<?php
/**
 * Nastavitve po trgu. Domena (brez www.) izbere trg.
 *
 * Trg brez 'enabled' => true se ne prikaže (plugin je nameščen, a neaktiven),
 * dokler ne vpišemo ID-jev daril in prevodov za ta trg.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Domena → trg. */
function bfg_market_hosts() {
	return array(
		'biostile.si'     => 'si',
		'dev.biostile.si' => 'dev',
		'biostile.sk'     => 'sk',
		'biostile.cz'     => 'cz',
		'biostile.hu'     => 'hu',
		'bio-stile.it'    => 'it',
		'biostile.hr'     => 'hr',
	);
}

/** Skupne nastavitve (veljajo za vse trge, trg jih lahko prepiše). */
function bfg_market_defaults() {
	return array(
		'enabled'    => false,
		'start'      => '2026-11-17 00:00:00',
		'end'        => '2026-11-30 23:59:59',
		'timezone'   => 'Europe/Ljubljana',
		'thresholds' => array( 100, 150, 200 ), // 1, 2, 3 darila (v valuti trga).
		'gifts'      => array(),
		'strings'    => array(),
		// Bel logotip z deteljico (temno ozadje pop-upa).
		'logo'       => 'https://www.biostile.si/wp-content/uploads/2026/01/Nov-logo-Biostile-8.png',
	);
}

/** Slovenski teksti (SI + dev). */
function bfg_strings_sl() {
	return array(
		'event'      => 'BLACK FRIDAY AKCIJA',
		'title'      => 'Popusti do <span>−60 %</span>',
		'title_sub'  => '+ brezplačna darila po izbiri',
		'subtitle'   => 'Ob vsakem nakupu prejmete tudi <strong>kodo za −25 %</strong> pri naslednjem nakupu.',
		'cart_value' => 'Vrednost vaše košarice',
		'gift_1'     => '1 darilo',
		'gift_2'     => '2 darili',
		'gift_3'     => '3 darila',
		'from'       => 'od',
		'head_0'     => 'Izberite darilo ob nakupu od %s',
		'head_1'     => 'Izberite 1 brezplačno darilo',
		'head_2'     => 'Izberite 2 brezplačni darili',
		'head_3'     => 'Izberite 3 brezplačna darila',
		'selected'   => 'Izbrano',
		'nudge'      => 'Do %1$s darila vam manjka le <strong>%2$s</strong>.',
		'nth'        => array( 'prvega', 'drugega', 'tretjega' ),
		'nudge_all'  => '<strong>Čestitamo! Pripadajo vam 3 brezplačna darila.</strong>',
		'choose'     => '+ Izberi darilo',
		'chosen'     => '✓ Izbrano',
		'swap'       => 'Za zamenjavo najprej odznačite eno od izbranih daril.',
		'continue'   => 'NADALJUJ Z NAKUPOVANJEM',
		'checkout'   => 'ZAKLJUČI NAKUP ZDAJ',
		'unpicked'   => 'Na voljo so še neizbrana darila. Izberite jih ali ponovno kliknite za nakup brez njih.',
		'note'       => 'Darila veljajo za vrednost izdelkov po popustih, brez dostave.',
		'removed'    => 'Vrednost košarice se je znižala, zato smo odstranili darilo.',
		'error'      => 'Prišlo je do napake. Poskusite znova.',
		'close'      => 'Zapri',
		'regular'    => 'Redna cena',
		'cart_label' => 'BF darilo',
		'meta_label' => 'Darilo',
		'more'       => 'Izvedi več »',
		'free'       => 'BREZPLAČNO',
	);
}

/** Nastavitve posameznih trgov. */
function bfg_markets() {
	// Darila po SKU (EAN, enak na vseh trgih); display_price velja samo na dev.
	$si_gifts = array(
		array( 'sku' => '3830057684833', 'display_price' => 41.90 ), // Sunrise Rejuvenation day cream, 50 ml
		array( 'sku' => '3830057685229', 'display_price' => 39.90, 'name' => 'Triple action rejuvenation', 'size' => 'Krema za oči, 15 ml' ),
		array( 'sku' => '3830057685847', 'display_price' => 19.90 ), // Hair.O shampoo, 200 ml
		array( 'sku' => '3830057686479', 'display_price' => 15.90 ), // Moisturizing Intimate Gel, 50 ml
		array( 'sku' => '3830057686172', 'display_price' => 24.90 ), // Sun Kissed, 150 ml
		array( 'sku' => '3830057686349', 'display_price' => 29.90 ), // Celuline, 150 ml
	);

	return array(
		'si'  => array(
			'enabled' => true,
			'gifts'   => $si_gifts,
			'strings' => bfg_strings_sl(),
		),
		// Dev: vedno aktiven, prikaz rednih cen iz display_price (na dev so cene 1 €).
		'dev' => array(
			'enabled'     => true,
			'always_on'   => true,
			'use_display' => true,
			'gifts'       => $si_gifts,
			'strings'     => bfg_strings_sl(),
		),
		// TODO: ID-ji daril, prevodi in pragovi (valuta!) za ostale trge.
		'sk'  => array( 'enabled' => false ),
		'cz'  => array( 'enabled' => false ),
		'hu'  => array( 'enabled' => false ),
		'it'  => array( 'enabled' => false ),
		'hr'  => array( 'enabled' => false ),
	);
}

function bfg_market_key() {
	$host  = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$host  = preg_replace( '/^www\./', '', $host );
	$hosts = bfg_market_hosts();
	return $hosts[ $host ] ?? '';
}
