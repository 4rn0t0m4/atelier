<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;

class MenuHelper
{
    public static function getMainMenu(): array
    {
        return cache()->remember('main_menu', 1800, function () {
            return [
                [
                    'name' => 'Mariage & Baptême',
                    'url' => '/boutique?tag=mariage-bapteme',
                    'children' => [
                        self::category('marque-place'),
                        self::product('medaillon-bois-personnalise-bapteme-mariage-champetre', 'Médaillons'),
                        self::category('urne-en-bois'),
                        self::category('numero-de-table', 'Numéros de table'),
                        self::category('cake-topper'),
                        self::category('porte-alliances'),
                    ],
                ],
                [
                    'name' => 'Enfant & Naissance',
                    'url' => '/boutique?tag=bebe-naissance',
                ],
                [
                    'name' => 'Prénoms en bois',
                    'url' => '#',
                    'children' => [
                        self::product('prenom-en-bois-decoration-murale-porte-chambre', 'Prénom en bois mural'),
                        self::category('lettre-en-bois-3d', 'Lettre en bois relief'),
                        self::category('les-geometriques', 'Géométriques'),
                        self::category('prenom-en-bois', 'Prénoms en attaché'),
                        self::category('lettres-en-bois', 'Lettres en capital'),
                        self::category('couronnes'),
                        self::category('decorations-murales-plaques-de-porte', 'Décorations murales'),
                    ],
                ],
                [
                    'name' => 'Univers',
                    'url' => '#',
                    'children' => [
                        self::tag('boheme'),
                        self::tag('foret-automne', 'Forêt - Automne'),
                        self::tag('sauvage-savane', 'Sauvage - Savane'),
                        self::tag('tropical'),
                        self::tag('fee-princesse', 'Fée - Princesse'),
                        self::tag('mer-et-ocean', 'Mer et océan'),
                        self::tag('nuit-espace', 'Nuit - Espace'),
                    ],
                ],
                [
                    'name' => 'Objets personnalisés',
                    'url' => self::categoryUrl('objets-personnalises'),
                    'children' => [
                        self::category('medaillons'),
                        self::category('calendriers-anniversaire', 'Calendriers perpétuels'),
                        self::product('porte-medailles-en-bois-personnalise', 'Porte médailles'),
                        self::product('arbre-de-vie-en-bois-avec-prenoms-personnalisable', 'Arbre de vie'),
                        self::category('cake-topper'),
                        self::category('marque-pages-personnalises', 'Marque-pages'),
                        self::category('boules-de-noel', 'Boules de Noël'),
                        self::category('professionnel'),
                        self::product('votre-photo-gravee-sur-du-bois-de-peuplier', 'Votre photo gravée'),
                        self::tag('cadeaux-personnalises', 'Cadeaux personnalisés'),
                        self::category('sac-cabas-jute-personnalise', 'Sacs cabas en jute'),
                    ],
                ],
            ];
        });
    }

    public static function getFooterMenu(): array
    {
        return [
            ['name' => 'Délai & tarif de livraison', 'url' => '/livraison'],
            ['name' => 'Politique de confidentialité', 'url' => '/politique-de-confidentialite'],
            ['name' => 'Conditions Générales de Ventes', 'url' => '/conditions-generales-de-ventes'],
            ['name' => 'Mentions légales', 'url' => '/mentions-legales'],
            ['name' => 'Contact', 'url' => '/contact'],
        ];
    }

    private static function category(string $slug, ?string $name = null): array
    {
        $cat = ProductCategory::where('slug', $slug)->first();

        return [
            'name' => $name ?? $cat?->name ?? ucfirst($slug),
            'url' => $cat?->url() ?? '/boutique',
        ];
    }

    private static function categoryUrl(string $slug): string
    {
        $cat = ProductCategory::where('slug', $slug)->first();

        return $cat?->url() ?? '/boutique';
    }

    private static function product(string $slug, ?string $name = null): array
    {
        $product = Product::with('category.parent')->where('slug', $slug)->first();

        return [
            'name' => $name ?? $product?->name ?? ucfirst($slug),
            'url' => $product?->url() ?? '/boutique',
        ];
    }

    private static function tag(string $slug, ?string $name = null): array
    {
        $tag = ProductTag::where('slug', $slug)->first();

        return [
            'name' => $name ?? $tag?->name ?? ucfirst($slug),
            'url' => '/boutique?tag=' . $slug,
        ];
    }
}
