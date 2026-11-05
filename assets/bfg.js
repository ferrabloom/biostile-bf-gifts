/* BiostileGifts – frontend */
(function ($) {
	'use strict';
	if (!window.bfgData) return;
	var D = window.bfgData, S = D.strings, state = null, picked = [], busy = false, confirmUnpicked = false, notice = '', $o,
		checkoutHref = '', bypass = false, detail = 0;
	var NTH = [S.nth_1, S.nth_2, S.nth_3];

	// Zapis cene kot v trgovini (WooCommerce: decimalke, ločila, simbol).
	function money(v) {
		var dec = D.decimals == null ? 2 : parseInt(D.decimals, 10), neg = v < 0;
		var parts = Math.abs(v).toFixed(dec).split('.');
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, D.thouSep == null ? '.' : D.thouSep);
		var num = parts.join(D.decSep || ','), f = D.priceFormat || '%v\u00a0€';
		return (neg ? '−' : '') + f.replace('%v', num).replace(/ /g, '\u00a0');
	}
	function from(amount) { return S.from.indexOf('%s') > -1 ? fmt(S.from, amount) : S.from + ' ' + amount; }
	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function fmt(s) { var a = [].slice.call(arguments, 1), i = 0; return String(s).replace(/%(\d\$)?s/g, function (m, n) { return n ? a[parseInt(n, 10) - 1] : a[i++]; }); }
	function track(ev) { if (D.trackUrl) $.post(D.trackUrl, { ev: ev }); }

	function refreshCart() {
		$(document.body).trigger('wc_fragment_refresh');
		$(document.body).trigger('fkcart_update_side_cart', [false]);
	}

	function goCheckout() {
		bypass = true;
		window.location.href = checkoutHref || D.checkoutUrl;
	}

	function build() {
		if ($o) return;
		$o = $('<div id="bfg-overlay" hidden><section id="bfg-popup" role="dialog" aria-modal="true" aria-labelledby="bfg-title" tabindex="-1"></section></div>').appendTo(document.body);
		$o.on('click', function (e) { if (e.target === this && !detail) close(); });
		$o.on('click', '.bfg-close', close);
		$o.on('click', '.bfg-cta', close);
		$o.on('click', '.bfg-secondary', function () {
			// Iz košarice (način A) je pop-up že opomnik – drugi klik gre takoj na blagajno.
			if (!checkoutHref && state && picked.length < state.allowed && !confirmUnpicked) {
				confirmUnpicked = true; $o.find('.bfg-msg').text(S.unpicked); return;
			}
			goCheckout();
		});
		$o.on('click', '.bfg-choose', function () { toggle(parseInt($(this).data('id'), 10)); });
		$o.on('click', '.bfg-more, .bfg-img', function (e) { e.preventDefault(); showDetail(parseInt($(this).data('id'), 10)); });
		$o.on('click', '.bfg-mini-back, .bfg-mini-x', function () { hideDetail(); });
		$o.on('click', '.bfg-mini', function (e) { if (e.target === this) hideDetail(); });
		$o.on('click', '.bfg-mini-pick', function () {
			var id = parseInt($(this).data('id'), 10); hideDetail(); toggle(id);
		});
		$(document).on('keydown', function (e) {
			if (e.key !== 'Escape' || !$o || $o.prop('hidden')) return;
			if (detail) hideDetail(); else close();
		});
	}

	var CLOVER = '<svg class="bfg-clover" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><g fill="currentColor">' +
		[0, 90, 180, 270].map(function (a) { return '<path transform="translate(12 12) rotate(' + (a + 45) + ') scale(.5) translate(-12 -21)" d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/>'; }).join('') +
		'</g><path d="M12 12.5c.6 3.2 2.2 5.8 4.8 7.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';

	function roundMoney(v) { var d = D.decSep || ','; return money(v).replace(new RegExp('\\' + d + '0+(?=\\D*$)'), ''); }
	function gift(id) { return state ? state.gifts.filter(function (g) { return g.id === id; })[0] : null; }

	function render(msg) {
		var s = state, n = s.allowed, th = s.thresholds, max = th[th.length - 1], h = '';
		h += '<header class="bfg-hero">' + CLOVER.replace('bfg-clover', 'bfg-clover bfg-watermark');
		h += '<div class="bfg-brand">' + (D.logo ? '<img class="bfg-logo" src="' + esc(D.logo) + '" alt="Biostile">' : '') + '<span class="bfg-event">' + esc(S.event) + '</span></div>';
		h += '<button type="button" class="bfg-close" aria-label="' + esc(S.close) + '">×</button>';
		h += '<h2 class="bfg-title" id="bfg-title">' + S.title + '</h2>';
		if (S.title_sub) h += '<p class="bfg-title-sub">' + esc(S.title_sub) + '</p>';
		if (S.subtitle) h += '<p class="bfg-sub"><span class="bfg-ticket">%</span><span>' + S.subtitle + '</span></p>';
		h += '</header>';
		h += '<div class="bfg-body"><div class="bfg-cartrow"><span>' + esc(S.cart_value) + '</span><strong>' + money(s.total) + '</strong></div>';
		h += '<div class="bfg-progress" role="progressbar" aria-valuemin="0" aria-valuemax="' + max + '" aria-valuenow="' + Math.min(max, s.total) + '"><div class="bfg-fill" style="width:' + Math.min(100, s.total / max * 100) + '%"></div></div>';
		h += '<div class="bfg-milestones" style="grid-template-columns:repeat(' + th.length + ',1fr)">';
		th.forEach(function (t, i) {
			h += '<div class="bfg-milestone' + (n > i ? ' is-active' : '') + '">' + (n > i ? CLOVER : '') + '<b>' + esc(S['gift_' + (i + 1)]) + '</b><span>' + esc(from(roundMoney(t))) + '</span></div>';
		});
		h += '</div><div class="bfg-nudge" aria-live="polite">' + (n >= th.length ? S.nudge_all : fmt(S.nudge, esc(NTH[n]), money(th[n] - s.total))) + '</div>';
		h += '<div class="bfg-head"><h3>' + esc(n === 0 ? fmt(S.head_0, roundMoney(th[0])) : S['head_' + n]) + '</h3><span class="bfg-count">' + esc(S.selected) + ': <b>' + picked.length + ' / ' + n + '</b></span></div>';
		h += '<div class="bfg-gifts">';
		s.gifts.forEach(function (g) {
			var sel = picked.indexOf(g.id) > -1;
			h += '<div class="bfg-gift' + (sel ? ' is-selected' : '') + '"><span class="bfg-free">' + esc(S.free) + '</span>';
			h += '<a class="bfg-img" href="' + esc(g.url || '#') + '" data-id="' + g.id + '" aria-label="' + esc(S.more + ' ' + g.name) + '"><img src="' + esc(g.image) + '" alt="' + esc(g.name) + '" loading="lazy"></a>';
			h += '<p class="bfg-name">' + esc(g.name) + '</p><div class="bfg-size">' + esc(g.size) + '</div>';
			h += '<div class="bfg-price"><del aria-label="' + esc(S.regular) + ' ' + esc(money(g.regular)) + '">' + money(g.regular) + '</del><strong>' + money(0) + '</strong></div>';
			h += '<button type="button" class="bfg-more" data-id="' + g.id + '">' + esc(S.more) + '</button>';
			h += '<button type="button" class="bfg-choose" data-id="' + g.id + '" aria-pressed="' + sel + '"' + ((n === 0 || !g.in_stock) && !sel ? ' disabled' : '') + '>' + esc(sel ? S.chosen : S.choose) + '</button></div>';
		});
		h += '</div><p class="bfg-msg" aria-live="polite">' + esc(msg || notice || '') + '</p></div>';
		h += '<footer class="bfg-footer"><button type="button" class="bfg-cta">' + esc(S.continue) + '</button><button type="button" class="bfg-secondary">' + esc(S.checkout) + '</button>' + (S.note ? '<p class="bfg-note">' + esc(S.note) + '</p>' : '') + '</footer>';
		$o.find('#bfg-popup').html(h);
		if (detail) showDetail(detail);
	}

	/* Mini okno »Izvedi več« */
	function showDetail(id) {
		var g = gift(id); if (!g) return;
		detail = id;
		var n = state.allowed, sel = picked.indexOf(id) > -1, can = sel || (n > 0 && g.in_stock), h = '';
		h += '<div class="bfg-mini" role="dialog" aria-modal="true" aria-labelledby="bfg-mini-title"><div class="bfg-mini-card">';
		h += '<div class="bfg-mini-img"><span class="bfg-mini-free">' + esc(S.free_gift) + '</span><button type="button" class="bfg-mini-x" aria-label="' + esc(S.close) + '">×</button><img src="' + esc(g.image_lg || g.image) + '" alt="' + esc(g.name) + '"></div>';
		h += '<div class="bfg-mini-c"><h3 id="bfg-mini-title">' + esc(g.name) + '</h3>' + (g.size ? '<div class="bfg-mini-sz">' + esc(g.size) + '</div>' : '');
		if (g.desc) h += '<p class="bfg-mini-desc">' + esc(g.desc) + '</p>';
		h += '<div class="bfg-mini-pr"><del>' + money(g.regular) + '</del><strong>' + money(0) + '</strong></div>';
		if (!can && n === 0) h += '<p class="bfg-mini-note">' + fmt(S.nudge, esc(NTH[0]), money(state.thresholds[0] - state.total)) + '</p>';
		h += '<div class="bfg-mini-btns"><button type="button" class="bfg-mini-back">' + esc(S.back) + '</button>';
		h += '<button type="button" class="bfg-mini-pick' + (sel ? ' is-selected' : '') + '" data-id="' + id + '"' + (can ? '' : ' disabled') + '>' + esc(sel ? S.unpick : S.pick) + '</button></div>';
		h += '</div></div></div>';
		$o.find('.bfg-mini').remove();
		$o.append(h);
		setTimeout(function () { $o.find('.bfg-mini-pick:not(:disabled), .bfg-mini-back').first().trigger('focus'); }, 30);
	}
	function hideDetail() {
		detail = 0;
		if ($o) { $o.find('.bfg-mini').remove(); $o.find('#bfg-popup').trigger('focus'); }
	}

	function open(msg, ev) {
		build(); confirmUnpicked = false; detail = 0; if (msg) notice = msg; render(msg);
		$o.prop('hidden', false); $('body').addClass('bfg-open');
		setTimeout(function () { $o.find('#bfg-popup').trigger('focus'); }, 30);
		track(ev || 'open');
	}
	function close() {
		notice = ''; checkoutHref = ''; detail = 0;
		if (!$o) return; $o.prop('hidden', true); $('body').removeClass('bfg-open');
	}

	function apply(data) {
		state = data; picked = (data.selected || []).slice();
		if (data.removed) refreshCart();
	}

	function load(forceOpen, ev) {
		return $.post(D.stateUrl).done(function (r) {
			if (!r || !r.success || !r.data.active) return;
			apply(r.data);
			var removed = r.data.removed && r.data.count > 0, msg = removed ? S.removed : '';
			if (forceOpen || r.data.show || (removed && D.mode === 'popup')) open(msg, ev);
			else if ($o && !$o.prop('hidden')) render();
		});
	}

	function toggle(id) {
		if (busy || !state) return;
		notice = '';
		var next = picked.slice(), i = next.indexOf(id), msg = '';
		if (i > -1) next.splice(i, 1);
		else if (next.length < state.allowed) next.push(id);
		else if (state.allowed === 1) next = [id];
		else { render(S.swap); return; }
		busy = true; $o.find('#bfg-popup').addClass('is-busy');
		$.post(D.setUrl, { gifts: next }).done(function (r) {
			if (r && r.success) { apply(r.data); refreshCart(); }
			else msg = S.error;
		}).fail(function () { msg = S.error; }).always(function () {
			busy = false; confirmUnpicked = false; $o.find('#bfg-popup').removeClass('is-busy'); render(msg);
		});
	}

	// AJAX dodajanje (arhivi, FunnelKit, Elementor gumbi).
	$(document.body).on('added_to_cart wc-blocks_added_to_cart', function () { load(false); });
	// Spremembe v side cartu – strežnik po potrebi odstrani odvečna darila.
	$(document.body).on('removed_from_cart fkcart_quantity_updated updated_cart_totals', function () { load(false); });
	// Klasično dodajanje (produktna stran z reloadom) – strežnik nastavi zastavico.
	$(function () {
		var hasItems = document.cookie.indexOf('woocommerce_items_in_cart') > -1;
		if (D.mode === 'popup' && hasItems) load(false);
		// FunnelKit hrani HTML košarice v brskalniku: če je bila košarica napolnjena pred začetkom akcije,
		// bloka ni – košarico enkrat osvežimo, da se blok izriše.
		if (D.mode === 'cart' && hasItems) {
			setTimeout(function () {
				if ($('#fkcart-modal').length && !$('.bfgb').length) $(document.body).trigger('fkcart_update_side_cart', [false]);
			}, 800);
		}
	});
	// Ročni odpiralnik: .bfg-open-popup (blok v košarici, top bar, banner).
	$(document).on('click', '.bfg-open-popup', function (e) { e.preventDefault(); load(true, $(this).data('bfg-ev') || 'open'); });
	window.bfgOpen = function () { return load(true); };

	// Način A: klik »Zaključi nakup« v FunnelKit košarici – če darila niso izbrana, najprej pop-up.
	if (D.mode === 'cart') {
		document.addEventListener('click', function (e) {
			var a = e.target && e.target.closest ? e.target.closest('#fkcart-checkout-button') : null;
			if (!a || bypass || e.ctrlKey || e.metaKey || e.shiftKey) return;
			var href = a.getAttribute('href') || D.checkoutUrl;
			var $b = $('.bfgb').first();
			// Blok v košarici pove stanje brez dodatne zahteve.
			if ($b.length && parseInt($b.data('allowed'), 10) <= parseInt($b.data('selected'), 10)) return;
			e.preventDefault(); e.stopImmediatePropagation();
			$.post(D.stateUrl).done(function (r) {
				if (r && r.success && r.data.active && r.data.allowed > (r.data.selected || []).length) {
					apply(r.data); checkoutHref = href; build(); open('', 'checkout'); checkoutHref = href;
				} else { checkoutHref = href; goCheckout(); }
			}).fail(function () { checkoutHref = href; goCheckout(); });
		}, true);
	}
})(jQuery);
