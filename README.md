# BiostileGifts

Brezplačna darila po izbiri (1 / 2 / 3 darila po pragovih) za Biostile WooCommerce trge.

- **Način A** (privzeto): blok v FunnelKit košarici + pop-up ob »Zaključi nakup«, če darila niso izbrana.
- **Način B**: pop-up takoj ob dodajanju v košarico.
- »Izvedi več« odpre mini okno z opisom darila.

## Admin

Meni **BiostileGifts** (vsaka domena posebej): vklop/obdobje, način A/B, pragovi v valuti trga, darila (poteg za vrstni red, neobvezno ime/velikost), vsi teksti, predogled (`?bfg_preview=1`, samo admin), statistika.
Nastavitve v adminu prepišejo privzete iz `includes/markets.php`.

## Trgi

Domena izbere trg v `includes/markets.php`:

| Domena | Trg | Stanje |
|---|---|---|
| dev.biostile.si | dev | aktiven vedno (test), posodobitve z veje `dev` |
| biostile.si | si | izklopljen, vklop ročno v meniju BiostileGifts |
| biostile.sk / .cz / .hu / .hr, bio-stile.it | sk, cz, hu, hr, it | izklopljen, dokler ni vpisanih daril in prevodov |

## Posodobitve

- **dev.biostile.si** sledi veji `dev` – vsak push je na voljo kot posodobitev.
- **Live domene** sledijo GitHub Releases (push na `main` z novo verzijo → GitHub Action zgradi `biostile-bf-gifts.zip`).
- Samodejne posodobitve so za ta plugin vklopljene (WP cron) – ali takoj z gumbom »Update« v Plugins.

Zasebni repozitorij: v `wp-config.php` vsake domene

```php
define( 'BFG_GITHUB_TOKEN', 'github_pat_...' ); // fine-grained, Contents: Read-only, samo ta repo
```

## Izdaja nove verzije

1. Sprememba na veji `dev` → test na dev.biostile.si.
2. Dvigni `Version` in `BFG_VERSION` v `biostile-bf-gifts.php`.
3. Merge `dev` → `main` – GitHub Action sam ustvari release `vX.Y.Z` (zip asset).

## Združljivost

- FunnelKit Cart: osvežitev side carta, skrita količina pri darilih.
- Discount Rules (Flycart v2/v3): darila niso vidna pravilom.
- jupiterx-child `manage_discounted_product()` (buybox 2+1): darila ne štejejo v količino.
