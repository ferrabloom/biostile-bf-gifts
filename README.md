# Biostile BF Gifts

Black Friday pop-up z izbiro brezplačnih daril (1 / 2 / 3 darila po pragovih) za Biostile WooCommerce trge.

## Trgi

Domena izbere trg v `includes/markets.php`:

| Domena | Trg | Stanje |
|---|---|---|
| dev.biostile.si | dev | aktiven vedno (test), posodobitve z veje `dev` |
| biostile.si | si | aktiven 17.–30. 11. |
| biostile.sk / .cz / .hu / .hr, bio-stile.it | sk, cz, hu, hr, it | izklopljen, dokler ni vpisanih daril in prevodov |

## Posodobitve

- **dev.biostile.si** sledi veji `dev` – vsak push je na voljo kot posodobitev.
- **Live domene** sledijo GitHub Releases (`vX.Y.Z` tag → GitHub Action zgradi `biostile-bf-gifts.zip`).
- Samodejne posodobitve so za ta plugin vklopljene (WP cron) – ali takoj z gumbom »Update« v Plugins.

Zasebni repozitorij: v `wp-config.php` vsake domene

```php
define( 'BFG_GITHUB_TOKEN', 'github_pat_...' ); // fine-grained, Contents: Read-only, samo ta repo
```

## Izdaja nove verzije

1. Sprememba na veji `dev` → test na dev.biostile.si.
2. Dvigni `Version` in `BFG_VERSION` v `biostile-bf-gifts.php`.
3. Merge `dev` → `main`, tag `vX.Y.Z`, push tag.

## Združljivost

- FunnelKit Cart: osvežitev side carta, skrita količina pri darilih.
- Discount Rules (Flycart v2/v3): darila niso vidna pravilom.
- jupiterx-child `manage_discounted_product()` (buybox 2+1): darila ne štejejo v količino.
