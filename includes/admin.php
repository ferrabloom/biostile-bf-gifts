<?php
/**
 * BiostileGifts – admin zavihek (nastavitve na vsaki domeni posebej) in statistika.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BFG_OPTION = 'bfg_settings';
const BFG_STATS  = 'bfg_stats';

/** Opisi tekstov za admin (ključ => [oznaka, kje se prikaže]). */
function bfg_string_labels() {
	return array(
		'campaign'   => array( 'Ime akcije', 'Na naročilu (»Darilo: …«) in v statistiki' ),
		'event'      => array( 'Oznaka akcije', 'Zelena oznaka v pop-upu in naslov bloka v košarici' ),
		'title'      => array( 'Naslov pop-upa', 'HTML: <span>…</span> obarva besedo zeleno' ),
		'title_sub'  => array( 'Podnaslov pop-upa', '' ),
		'subtitle'   => array( 'Kupon vrstica', 'HTML dovoljen (<strong>)' ),
		'block_tag'  => array( 'Blok: desni napis', 'Desno v temni glavi bloka v košarici' ),
		'block_btn'  => array( 'Blok: gumb', '%1$s = izbrano, %2$s = na voljo' ),
		'block_done' => array( 'Blok: gumb, ko so vsa darila izbrana', '%1$s = izbrano, %2$s = na voljo' ),
		'block_view' => array( 'Blok: gumb pod prvim pragom', '' ),
		'cart_value' => array( 'Vrednost košarice', '' ),
		'gift_1'     => array( '1 darilo', 'Mejnik' ),
		'gift_2'     => array( '2 darili', 'Mejnik' ),
		'gift_3'     => array( '3 darila', 'Mejnik' ),
		'from'       => array( '»od« pri mejniku', '%s = znesek, npr. »od %s«' ),
		'head_0'     => array( 'Naslov seznama – pod pragom', '%s = prvi prag' ),
		'head_1'     => array( 'Naslov seznama – 1 darilo', '' ),
		'head_2'     => array( 'Naslov seznama – 2 darili', '' ),
		'head_3'     => array( 'Naslov seznama – 3 darila', '' ),
		'selected'   => array( 'Izbrano', 'Števec »Izbrano: 1 / 2«' ),
		'nudge'      => array( 'Manjka do darila', '%1$s = prvega/drugega/tretjega, %2$s = znesek' ),
		'nth_1'      => array( 'prvega', '' ),
		'nth_2'      => array( 'drugega', '' ),
		'nth_3'      => array( 'tretjega', '' ),
		'nudge_all'  => array( 'Vsa darila dosežena', 'HTML dovoljen' ),
		'free'       => array( 'Oznaka BREZPLAČNO', 'Na kartici darila' ),
		'choose'     => array( 'Gumb izberi', '' ),
		'chosen'     => array( 'Gumb izbrano', '' ),
		'more'       => array( 'Izvedi več', 'Povezava pod darilom' ),
		'free_gift'  => array( 'Oznaka v mini oknu', '' ),
		'pick'       => array( 'Mini okno: izberi', '' ),
		'unpick'     => array( 'Mini okno: odstrani', '' ),
		'back'       => array( 'Mini okno: nazaj', '' ),
		'swap'       => array( 'Opozorilo: zamenjava', '' ),
		'continue'   => array( 'Gumb nadaljuj', '' ),
		'checkout'   => array( 'Gumb zaključi nakup', '' ),
		'unpicked'   => array( 'Opozorilo: neizbrana darila', '' ),
		'note'       => array( 'Opomba pod gumbi', '' ),
		'removed'    => array( 'Obvestilo: darilo odstranjeno', '' ),
		'error'      => array( 'Napaka', '' ),
		'close'      => array( 'Zapri (za bralnike zaslona)', '' ),
		'regular'    => array( 'Redna cena (za bralnike zaslona)', '' ),
		'cart_label' => array( 'Oznaka v košarici', 'Ob imenu darila v košarici' ),
		'meta_label' => array( 'Oznaka na naročilu', '»Darilo«' ),
	);
}

function bfg_settings() {
	$s = get_option( BFG_OPTION, array() );
	return is_array( $s ) ? $s : array();
}

/* ------------------------------------------------------------------ meni */

add_action( 'admin_menu', function () {
	$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="black">' .
		implode( '', array_map( function ( $a ) {
			return '<path transform="translate(12 12) rotate(' . ( $a + 45 ) . ') scale(.5) translate(-12 -21)" d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/>';
		}, array( 0, 90, 180, 270 ) ) ) . '</g></svg>' );
	add_menu_page( 'BiostileGifts', 'BiostileGifts', 'manage_woocommerce', 'biostile-gifts', 'bfg_admin_page', $icon, 56 );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_biostile-gifts' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'wc-enhanced-select' );
	wp_enqueue_style( 'woocommerce_admin_styles' );
	wp_enqueue_script( 'jquery-ui-sortable' );
} );

/* ------------------------------------------------------------- shranjevanje */

add_action( 'admin_post_bfg_save', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Ni dovoljenja.' );
	}
	check_admin_referer( 'bfg_save' );
	$in = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	$s             = array();
	$s['schedule'] = in_array( $in['schedule'] ?? '', array( 'off', 'dates', 'always' ), true ) ? $in['schedule'] : 'off';
	$s['start']    = sanitize_text_field( str_replace( 'T', ' ', $in['start'] ?? '' ) );
	$s['end']      = sanitize_text_field( str_replace( 'T', ' ', $in['end'] ?? '' ) );
	$s['mode']     = in_array( $in['mode'] ?? '', array( 'cart', 'popup' ), true ) ? $in['mode'] : 'cart';
	$s['logo']     = esc_url_raw( $in['logo'] ?? '' );

	$th = array();
	foreach ( (array) ( $in['thresholds'] ?? array() ) as $t ) {
		$t = (float) str_replace( ',', '.', (string) $t );
		if ( $t > 0 ) {
			$th[] = $t;
		}
	}
	sort( $th );
	$s['thresholds'] = array_slice( $th, 0, 3 );

	$gifts = array();
	foreach ( (array) ( $in['gifts'] ?? array() ) as $g ) {
		$id = absint( $g['id'] ?? 0 );
		if ( ! $id || isset( $gifts[ $id ] ) ) {
			continue;
		}
		$gifts[ $id ] = array_filter( array(
			'id'   => $id,
			'name' => sanitize_text_field( $g['name'] ?? '' ),
			'size' => sanitize_text_field( $g['size'] ?? '' ),
		) );
	}
	$s['gifts'] = array_values( $gifts );

	$allowed_html = array( 'strong' => array(), 'span' => array(), 'b' => array(), 'em' => array() );
	$strings      = array();
	foreach ( array_keys( bfg_string_labels() ) as $k ) {
		$v = trim( (string) ( $in['strings'][ $k ] ?? '' ) );
		if ( '' !== $v ) {
			$strings[ $k ] = wp_kses( $v, $allowed_html );
		}
	}
	$s['strings'] = $strings;

	update_option( BFG_OPTION, $s, false );
	wp_safe_redirect( add_query_arg( array( 'page' => 'biostile-gifts', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
} );

add_action( 'admin_post_bfg_reset_stats', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Ni dovoljenja.' );
	}
	check_admin_referer( 'bfg_reset_stats' );
	delete_option( BFG_STATS );
	wp_safe_redirect( add_query_arg( array( 'page' => 'biostile-gifts', 'tab' => 'stats' ), admin_url( 'admin.php' ) ) );
	exit;
} );

/* ------------------------------------------------------------- statistika */

function bfg_stats() {
	$s = get_option( BFG_STATS, array() );
	return array_merge( array( 'since' => current_time( 'mysql' ), 'opens' => 0, 'block_clicks' => 0, 'checkout_prompts' => 0, 'selections' => array(), 'orders' => 0, 'revenue' => 0, 'order_gifts' => array() ), is_array( $s ) ? $s : array() );
}

function bfg_stats_bump( $key, $sub = null, $by = 1 ) {
	$s = bfg_stats();
	if ( null === $sub ) {
		$s[ $key ] = ( $s[ $key ] ?? 0 ) + $by;
	} else {
		$s[ $key ][ $sub ] = ( $s[ $key ][ $sub ] ?? 0 ) + $by;
	}
	update_option( BFG_STATS, $s, false );
}

/** Naročila z darili. */
function bfg_count_order( $order ) {
	$order = is_numeric( $order ) ? wc_get_order( $order ) : $order;
	if ( ! $order || $order->get_meta( '_bfg_counted' ) ) {
		return;
	}
	$pids = array();
	foreach ( $order->get_items() as $item ) {
		if ( 'yes' === $item->get_meta( '_bfg_gift' ) ) {
			$pids[] = (int) $item->get_product_id();
		}
	}
	if ( ! $pids ) {
		return;
	}
	$s           = bfg_stats();
	$s['orders'] = ( $s['orders'] ?? 0 ) + 1;
	$s['revenue'] = round( ( $s['revenue'] ?? 0 ) + (float) $order->get_total(), 2 );
	foreach ( $pids as $pid ) {
		$s['order_gifts'][ $pid ] = ( $s['order_gifts'][ $pid ] ?? 0 ) + 1;
	}
	update_option( BFG_STATS, $s, false );
	$order->update_meta_data( '_bfg_counted', 1 );
	$order->save();
}
add_action( 'woocommerce_checkout_order_created', 'bfg_count_order' );
add_action( 'woocommerce_store_api_checkout_order_processed', 'bfg_count_order' );

/* ------------------------------------------------------------- stran */

function bfg_admin_page() {
	$c    = bfg_config();
	$s    = bfg_settings();
	$tab  = sanitize_key( $_GET['tab'] ?? 'settings' ); // phpcs:ignore WordPress.Security.NonceVerification
	$base = admin_url( 'admin.php?page=biostile-gifts' );
	$sched = $s['schedule'] ?? ( ! empty( $c['always_on'] ) ? 'always' : ( ! empty( $c['enabled'] ) ? 'dates' : 'off' ) );
	$active = bfg_is_active();
	?>
	<div class="wrap bfg-admin">
		<h1>BiostileGifts <small style="font-size:13px;color:#646970">v<?php echo esc_html( BFG_VERSION ); ?> · trg: <?php echo esc_html( $c['market'] ?: '—' ); ?></small></h1>
		<p>
			Stanje: <?php echo $active ? '<strong style="color:#1C3A35">● aktivno</strong>' : '<strong style="color:#b32d2e">● neaktivno</strong>'; ?>
			&nbsp;·&nbsp; <a class="button" href="<?php echo esc_url( add_query_arg( 'bfg_preview', 1, home_url( '/' ) ) ); ?>" target="_blank">Predogled na strani</a>
			<span class="description">(vidiš samo ti kot administrator, tudi ko je akcija izklopljena)</span>
		</p>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore ?>
			<div class="notice notice-success is-dismissible"><p>Shranjeno.</p></div>
		<?php endif; ?>
		<nav class="nav-tab-wrapper">
			<a class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $base ); ?>">Nastavitve</a>
			<a class="nav-tab <?php echo 'stats' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $base . '&tab=stats' ); ?>">Statistika</a>
		</nav>
		<style>
			.bfg-admin .form-table th{width:220px}
			.bfg-gifts-list{max-width:820px;margin:0;padding:0}
			.bfg-gifts-list li{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:8px 10px;margin:0 0 6px;cursor:move}
			.bfg-gifts-list li img{width:40px;height:40px;object-fit:contain}
			.bfg-gifts-list .bfg-n{flex:1;min-width:180px}.bfg-gifts-list .bfg-n small{color:#646970;display:block}
			.bfg-gifts-list input[type=text]{width:170px}
			.bfg-handle{color:#8c8f94;font-size:18px}
			.bfg-admin .bfg-th input{width:110px}
			.bfg-stats td,.bfg-stats th{padding:8px 12px}
		</style>

		<?php if ( 'stats' === $tab ) : bfg_admin_stats(); else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="bfg_save">
			<?php wp_nonce_field( 'bfg_save' ); ?>
			<div>
				<table class="form-table" role="presentation">
					<tr><th>Akcija</th><td>
						<label><input type="radio" name="schedule" value="off" <?php checked( $sched, 'off' ); ?>> Izklopljena</label><br>
						<label><input type="radio" name="schedule" value="dates" <?php checked( $sched, 'dates' ); ?>> Vklopljena v obdobju spodaj</label><br>
						<label><input type="radio" name="schedule" value="always" <?php checked( $sched, 'always' ); ?>> Vedno vklopljena</label>
					</td></tr>
					<tr><th>Obdobje</th><td>
						od <input type="datetime-local" name="start" value="<?php echo esc_attr( str_replace( ' ', 'T', substr( $s['start'] ?? $c['start'], 0, 16 ) ) ); ?>">
						do <input type="datetime-local" name="end" value="<?php echo esc_attr( str_replace( ' ', 'T', substr( $s['end'] ?? $c['end'], 0, 16 ) ) ); ?>">
						<p class="description">Časovni pas: <?php echo esc_html( $c['timezone'] ); ?></p>
					</td></tr>
					<tr><th>Prikaz</th><td>
						<label><input type="radio" name="mode" value="cart" <?php checked( $s['mode'] ?? $c['mode'], 'cart' ); ?>> <strong>A</strong> – blok v košarici (FunnelKit) + pop-up ob »Zaključi nakup«, če darila niso izbrana</label><br>
						<label><input type="radio" name="mode" value="popup" <?php checked( $s['mode'] ?? $c['mode'], 'popup' ); ?>> <strong>B</strong> – pop-up takoj ob dodajanju v košarico</label>
					</td></tr>
					<tr><th>Pragovi (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</th><td class="bfg-th">
						<?php $th = $s['thresholds'] ?? $c['thresholds']; for ( $i = 0; $i < 3; $i++ ) : ?>
							<?php echo (int) ( $i + 1 ); ?>. darilo od <input type="number" step="0.01" min="0" name="thresholds[]" value="<?php echo esc_attr( $th[ $i ] ?? '' ); ?>"> &nbsp;
						<?php endfor; ?>
						<p class="description">Vrednost izdelkov po popustih z DDV, brez dostave. Prazno polje = manj stopenj.</p>
					</td></tr>
					<tr><th>Darila</th><td>
						<ul class="bfg-gifts-list" id="bfg-gifts">
							<?php foreach ( $c['gifts'] as $i => $g ) : $p = wc_get_product( $g['id'] ); if ( ! $p ) { continue; } ?>
								<li>
									<span class="bfg-handle">⋮⋮</span>
									<?php echo wp_kses_post( $p->get_image( array( 40, 40 ) ) ); ?>
									<span class="bfg-n"><?php echo esc_html( $p->get_name() ); ?><small>#<?php echo (int) $p->get_id(); ?> · <?php echo wp_kses_post( wc_price( wc_get_price_including_tax( $p, array( 'price' => $p->get_regular_price() ) ) ) ); ?> · <?php echo $p->is_in_stock() ? 'na zalogi' : '<b style="color:#b32d2e">ni na zalogi</b>'; ?></small></span>
									<input type="hidden" name="gifts[<?php echo (int) $i; ?>][id]" value="<?php echo (int) $p->get_id(); ?>">
									<input type="text" name="gifts[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $g['name'] ?? '' ); ?>" placeholder="Prikazno ime (neobvezno)">
									<input type="text" name="gifts[<?php echo (int) $i; ?>][size]" value="<?php echo esc_attr( $g['size'] ?? '' ); ?>" placeholder="Velikost (neobvezno)">
									<button type="button" class="button-link-delete bfg-rm">Odstrani</button>
								</li>
							<?php endforeach; ?>
						</ul>
						<p style="display:flex;gap:8px;align-items:center;max-width:820px">
							<select id="bfg-add" class="wc-product-search" style="width:420px" data-placeholder="Poišči izdelek…" data-action="woocommerce_json_search_products"></select>
							<button type="button" class="button" id="bfg-add-btn">Dodaj darilo</button>
						</p>
						<p class="description">Vrstni red spreminjaš s potegom. Ime in velikost se privzeto vzameta iz imena izdelka (del za vejico je velikost).</p>
					</td></tr>
					<tr><th>Logotip (bel)</th><td>
						<input type="url" class="regular-text" name="logo" value="<?php echo esc_attr( $s['logo'] ?? $c['logo'] ); ?>">
					</td></tr>
				</table>
			</div>
			<?php
			$defaults = bfg_default_strings();
			$saved    = $s['strings'] ?? array();
			$labels   = bfg_string_labels();
			$main     = array( 'event', 'title', 'title_sub', 'subtitle', 'note' );
			$row      = function ( $k ) use ( $defaults, $saved, $labels ) {
				?>
				<tr><th><label for="bfg-s-<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $labels[ $k ][0] ); ?></label></th><td>
					<input type="text" class="large-text" id="bfg-s-<?php echo esc_attr( $k ); ?>" name="strings[<?php echo esc_attr( $k ); ?>]" value="<?php echo esc_attr( $saved[ $k ] ?? '' ); ?>" placeholder="<?php echo esc_attr( $defaults[ $k ] ?? '' ); ?>">
					<?php if ( $labels[ $k ][1] ) : ?><p class="description"><?php echo esc_html( $labels[ $k ][1] ); ?></p><?php endif; ?>
				</td></tr>
				<?php
			};
			?>
			<h2>Teksti akcije</h2>
			<p class="description">Prazno polje = privzeti tekst (prikazan sivo). Ostali teksti so že prevedeni za ta trg.</p>
			<table class="form-table" role="presentation">
				<?php foreach ( $main as $k ) { $row( $k ); } ?>
			</table>
			<details style="margin-top:12px">
				<summary style="cursor:pointer;font-weight:600">Napredno: ostali teksti (gumbi, obvestila) – običajno ni treba spreminjati</summary>
				<table class="form-table" role="presentation">
					<?php foreach ( array_keys( $labels ) as $k ) { if ( ! in_array( $k, $main, true ) ) { $row( $k ); } } ?>
				</table>
			</details>
			<?php submit_button( 'Shrani' ); ?>
		</form>
		<script>
		jQuery(function ($) {
			var $list = $('#bfg-gifts');
			function reindex() {
				$list.children('li').each(function (i) {
					$(this).find('input').each(function () {
						this.name = this.name.replace(/gifts\[\d+\]/, 'gifts[' + i + ']');
					});
				});
			}
			$list.sortable({ handle: '.bfg-handle, .bfg-n, img', update: reindex });
			$list.on('click', '.bfg-rm', function () { $(this).closest('li').remove(); reindex(); });
			$('#bfg-add-btn').on('click', function () {
				var $s = $('#bfg-add'), id = $s.val(), txt = $s.find('option:selected').text();
				if (!id || $list.find('input[value="' + id + '"][name$="[id]"]').length) return;
				var i = $list.children().length;
				$list.append('<li><span class="bfg-handle">⋮⋮</span><span class="bfg-n"></span>' +
					'<input type="hidden" name="gifts[' + i + '][id]" value="' + id + '">' +
					'<input type="text" name="gifts[' + i + '][name]" placeholder="Prikazno ime (neobvezno)">' +
					'<input type="text" name="gifts[' + i + '][size]" placeholder="Velikost (neobvezno)">' +
					'<button type="button" class="button-link-delete bfg-rm">Odstrani</button></li>');
				$list.children().last().find('.bfg-n').text(txt);
				$s.val(null).trigger('change');
			});
		});
		</script>
		<?php endif; ?>
	</div>
	<?php
}

function bfg_admin_stats() {
	$st  = bfg_stats();
	$c   = bfg_config();
	$pname = function ( $pid ) {
		$p = wc_get_product( $pid );
		return $p ? $p->get_name() : '#' . $pid;
	};
	?>
	<p>Od: <?php echo esc_html( $st['since'] ); ?></p>
	<table class="widefat striped bfg-stats" style="max-width:640px">
		<tbody>
			<tr><th>Odpiranja pop-upa</th><td><?php echo (int) $st['opens']; ?></td></tr>
			<tr><th>Kliki na gumb v košarici</th><td><?php echo (int) $st['block_clicks']; ?></td></tr>
			<tr><th>Pop-up ob »Zaključi nakup«</th><td><?php echo (int) $st['checkout_prompts']; ?></td></tr>
			<tr><th>Naročila z darili</th><td><?php echo (int) $st['orders']; ?></td></tr>
			<tr><th>Prihodek naročil z darili</th><td><?php echo wp_kses_post( wc_price( $st['revenue'] ) ); ?></td></tr>
		</tbody>
	</table>
	<h2>Darila</h2>
	<table class="widefat striped bfg-stats" style="max-width:640px">
		<thead><tr><th>Darilo</th><th>Izbrano v košarici</th><th>V naročilih</th></tr></thead>
		<tbody>
		<?php
		$ids = array_unique( array_merge( wp_list_pluck( $c['gifts'], 'id' ), array_keys( (array) $st['selections'] ), array_keys( (array) $st['order_gifts'] ) ) );
		foreach ( $ids as $pid ) :
			?>
			<tr><td><?php echo esc_html( $pname( $pid ) ); ?></td><td><?php echo (int) ( $st['selections'][ $pid ] ?? 0 ); ?></td><td><?php echo (int) ( $st['order_gifts'][ $pid ] ?? 0 ); ?></td></tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px" onsubmit="return confirm('Ponastavim statistiko?')">
		<input type="hidden" name="action" value="bfg_reset_stats">
		<?php wp_nonce_field( 'bfg_reset_stats' ); ?>
		<button class="button">Ponastavi statistiko</button>
	</form>
	<?php
}
