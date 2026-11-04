/* Biostile BF Gifts – frontend */
(function ($) {
	'use strict';
	if (!window.bfgData) return;
	var D = window.bfgData, S = D.strings, state = null, picked = [], busy = false, confirmUnpicked = false, notice = '', $o;

	function money(v) {
		try { return new Intl.NumberFormat(D.locale, { style: 'currency', currency: D.currency }).format(v); }
		catch (e) { return v.toFixed(2).replace('.', ',') + ' €'; }
	}
	function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function fmt(s) { var a = [].slice.call(arguments, 1), i = 0; return s.replace(/%(\d\$)?s/g, function (m, n) { return n ? a[parseInt(n, 10) - 1] : a[i++]; }); }

	function refreshCart() {
		$(document.body).trigger('wc_fragment_refresh');
		$(document.body).trigger('fkcart_update_side_cart', [false]);
	}

	function build() {
		if ($o) return;
		$o = $('<div id="bfg-overlay" hidden><section id="bfg-popup" role="dialog" aria-modal="true" aria-labelledby="bfg-title" tabindex="-1"></section></div>').appendTo(document.body);
		$o.on('click', function (e) { if (e.target === this) close(); });
		$o.on('click', '.bfg-close', close);
		$o.on('click', '.bfg-cta', close);
		$o.on('click', '.bfg-secondary', function () {
			if (state && picked.length < state.allowed && !confirmUnpicked) {
				confirmUnpicked = true; $o.find('.bfg-msg').text(S.unpicked); return;
			}
			window.location.href = D.checkoutUrl;
		});
		$o.on('click', '.bfg-choose', function () { toggle(parseInt($(this).data('id'), 10)); });
		$(document).on('keydown', function (e) { if (e.key === 'Escape' && $o && !$o.prop('hidden')) close(); });
	}

	var CLOVER = '<svg class="bfg-clover" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><g fill="currentColor">' +
		[0, 90, 180, 270].map(function (a) { return '<path transform="translate(12 12) rotate(' + (a + 45) + ') scale(.5) translate(-12 -21)" d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/>'; }).join('') +
		'</g><path d="M12 12.5c.6 3.2 2.2 5.8 4.8 7.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';

	function roundMoney(v) { return money(v).replace(/[,.]00(?=\D*$)/, ''); }

	function render(msg) {
		var s = state, n = s.allowed, th = s.thresholds, max = th[th.length - 1], h = '';
		h += '<header class="bfg-hero">' + CLOVER.replace('bfg-clover', 'bfg-clover bfg-watermark');
		h += '<div class="bfg-brand">' + (D.logo ? '<img class="bfg-logo" src="' + esc(D.logo) + '" alt="Biostile">' : '') + '<span class="bfg-event">' + esc(S.event) + '</span></div>';
		h += '<button type="button" class="bfg-close" aria-label="' + esc(S.close) + '">\u00d7</button>';
		h += '<h2 class="bfg-title" id="bfg-title">' + S.title + '</h2>';
		h += '<p class="bfg-title-sub">' + esc(S.title_sub) + '</p>';
		h += '<p class="bfg-sub"><span class="bfg-ticket">%</span><span>' + S.subtitle + '</span></p></header>';
		h += '<div class="bfg-body"><div class="bfg-cartrow"><span>' + esc(S.cart_value) + '</span><strong>' + money(s.total) + '</strong></div>';
		h += '<div class="bfg-progress" role="progressbar" aria-valuemin="0" aria-valuemax="' + max + '" aria-valuenow="' + Math.min(max, s.total) + '"><div class="bfg-fill" style="width:' + Math.min(100, s.total / max * 100) + '%"></div></div>';
		h += '<div class="bfg-milestones">';
		th.forEach(function (t, i) {
			h += '<div class="bfg-milestone' + (n > i ? ' is-active' : '') + '">' + (n > i ? CLOVER : '') + '<b>' + esc(S['gift_' + (i + 1)]) + '</b><span>' + esc(S.from) + ' ' + roundMoney(t) + '</span></div>';
		});
		h += '</div><div class="bfg-nudge" aria-live="polite">' + (n >= th.length ? S.nudge_all : fmt(S.nudge, esc(S.nth[n]), money(th[n] - s.total))) + '</div>';
		h += '<div class="bfg-head"><h3>' + esc(n === 0 ? fmt(S.head_0, roundMoney(th[0])) : S['head_' + n]) + '</h3><span class="bfg-count">' + esc(S.selected) + ': <b>' + picked.length + ' / ' + n + '</b></span></div>';
		h += '<div class="bfg-gifts">';
		s.gifts.forEach(function (g) {
			var sel = picked.indexOf(g.id) > -1, link = g.url ? ' href="' + esc(g.url) + '" target="_blank" rel="noopener"' : '';
			h += '<div class="bfg-gift' + (sel ? ' is-selected' : '') + '"><span class="bfg-free">' + esc(S.free) + '</span>';
			h += '<a class="bfg-img"' + link + ' aria-label="' + esc(S.more + ' ' + g.name) + '"><img src="' + esc(g.image) + '" alt="' + esc(g.name) + '" loading="lazy"></a>';
			h += '<p class="bfg-name">' + esc(g.name) + '</p><div class="bfg-size">' + esc(g.size) + '</div>';
			h += '<div class="bfg-price"><del aria-label="' + esc(S.regular) + ' ' + esc(money(g.regular)) + '">' + money(g.regular) + '</del><strong>' + money(0) + '</strong></div>';
			if (g.url) h += '<a class="bfg-more"' + link + '>' + esc(S.more) + '</a>';
			h += '<button type="button" class="bfg-choose" data-id="' + g.id + '" aria-pressed="' + sel + '"' + ((n === 0 || !g.in_stock) && !sel ? ' disabled' : '') + '>' + esc(sel ? S.chosen : S.choose) + '</button></div>';
		});
		h += '</div><p class="bfg-msg" aria-live="polite">' + esc(msg || notice || '') + '</p></div>';
		h += '<footer class="bfg-footer"><button type="button" class="bfg-cta">' + esc(S.continue) + '</button><button type="button" class="bfg-secondary">' + esc(S.checkout) + '</button><p class="bfg-note">' + esc(S.note) + '</p></footer>';
		$o.find('#bfg-popup').html(h);
	}

	function open(msg) {
		build(); confirmUnpicked = false; if (msg) notice = msg; render(msg);
		$o.prop('hidden', false); $('body').addClass('bfg-open');
		setTimeout(function () { $o.find('#bfg-popup').trigger('focus'); }, 30);
	}
	function close() { notice = ''; if (!$o) return; $o.prop('hidden', true); $('body').removeClass('bfg-open'); }

	function apply(data) {
		state = data; picked = (data.selected || []).slice();
		if (data.removed) refreshCart();
	}

	function load(forceOpen) {
		return $.post(D.stateUrl).done(function (r) {
			if (!r || !r.success || !r.data.active) return;
			apply(r.data);
			var removed = r.data.removed && r.data.count > 0, msg = removed ? S.removed : '';
			if (forceOpen || r.data.show || removed) open(msg);
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
		if (document.cookie.indexOf('woocommerce_items_in_cart') > -1) load(false);
	});
	// Ročni odpiralnik: element z razredom .bfg-open-popup (npr. top bar / banner).
	$(document).on('click', '.bfg-open-popup', function (e) { e.preventDefault(); load(true); });
	window.bfgOpen = function () { return load(true); };
})(jQuery);
