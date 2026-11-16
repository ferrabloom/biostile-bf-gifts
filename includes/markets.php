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
		'biostile.rs'     => 'rs',
		'biostile.ba'     => 'ba',
	);
}

/** Skupne nastavitve (veljajo za vse trge, trg jih lahko prepiše). */
function bfg_market_defaults() {
	return array(
		'enabled'    => false,
		'mode'       => 'cart', // cart = A (blok v košarici + pop-up ob zaključku), popup = B (pop-up ob dodajanju).
		'start'      => '2026-11-16 00:00:00',
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
		'campaign'   => 'Black Friday',
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
		'nth_1'      => 'prvega',
		'nth_2'      => 'drugega',
		'nth_3'      => 'tretjega',
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
		'free_gift'  => 'BREZPLAČNO DARILO',
		'pick'       => '+ Izberi to darilo',
		'unpick'     => 'Odstrani darilo',
		'back'       => '‹ Nazaj',
		'block_tag'  => 'Brezplačna darila',
		'block_btn'  => 'Izberi brezplačno darilo (%1$s/%2$s) »',
		'block_done' => 'Izbrana darila (%1$s/%2$s) – zamenjaj »',
		'block_view' => 'Oglej si darila »',
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
		// Izklopljeno, dokler akcije ročno ne vklopimo v meniju BiostileGifts.
		'si'  => array(
			'enabled' => false,
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
		// Vsi trgi so izklopljeni, dokler akcije ročno ne vklopimo (meni BiostileGifts).
		'sk'  => array(
			'logo'       => 'https://www.biostile.sk/wp-content/uploads/2020/08/logo-Biostile-SKpdf.png', // bel logotip trga (slogan v jeziku trga)
			'enabled' => false,
			'gifts'   => bfg_gifts_for( 'sk' ),
			'strings' => bfg_strings_sk(),
		),
		// CZK: pragovi kot na BF bannerjih (nad 2 500 / 3 700 / 4 900 Kč).
		'cz'  => array(
			'logo'       => 'https://www.biostile.cz/wp-content/uploads/2026/10/logo-Biostile-CZ-white.png', // bel logotip trga (slogan v jeziku trga)
			'enabled'    => false,
			'thresholds' => array( 2500, 3700, 4900 ),
			'gifts'      => bfg_gifts_for( 'cs' ),
			'strings'    => bfg_strings_cs(),
		),
		// HUF: pragovi kot na BF bannerjih (36 500 / 54 500 / 72 500 Ft felett).
		'hu'  => array(
			'logo'       => 'https://www.biostile.hu/wp-content/uploads/2026/10/logo-Biostile-HU-white.png', // bel logotip trga (slogan v jeziku trga)
			'enabled'    => false,
			'thresholds' => array( 36500, 54500, 72500 ),
			'gifts'      => bfg_gifts_for( 'hu' ),
			'strings'    => bfg_strings_hu(),
		),
		'it'  => array(
			'logo'       => 'https://www.bio-stile.it/wp-content/uploads/2026/09/logo-Biostile-IT-pdf-1.png', // bel logotip trga (slogan v jeziku trga)
			'enabled' => false,
			'gifts'   => bfg_gifts_for( 'it' ),
			'strings' => bfg_strings_it(),
		),
		// RSD: ~123 RSD = 1 € (razmerje cen na biostile.rs). Pragovi v potrditvi.
		'rs'  => array(
			'enabled'    => false,
			'logo'       => 'https://www.biostile.rs/wp-content/uploads/2026/06/logo-SRB-BEL.png',
			'thresholds' => array( 12000, 18000, 24000 ),
			'gifts'      => bfg_gifts_for( 'sr' ),
			'strings'    => bfg_strings_sr(),
		),
		// KM: ~2,05 KM = 1 € (razmerje cen na biostile.ba). Pragovi v potrditvi.
		'ba'  => array(
			'enabled'    => false,
			'logo'       => 'https://www.biostile.ba/wp-content/uploads/2026/07/logo-SRB-BEL.png',
			'thresholds' => array( 200, 300, 400 ),
			'gifts'      => bfg_gifts_for( 'bs' ),
			'strings'    => bfg_strings_bs(),
		),
		// HR nima FunnelKit Cart: način B (pop-up ob dodajanju v košarico).
		'hr'  => array(
			'logo'       => 'https://www.biostile.hr/wp-content/uploads/2024/03/logo_biostile_HR_bel.png', // bel logotip trga (slogan v jeziku trga)
			'enabled' => false,
			'mode'    => 'popup',
			'gifts'   => bfg_gifts_for( 'hr' ),
			'strings' => bfg_strings_hr(),
		),
	);
}

function bfg_market_key() {
	$host  = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$host  = preg_replace( '/^www\./', '', $host );
	$hosts = bfg_market_hosts();
	return $hosts[ $host ] ?? '';
}
