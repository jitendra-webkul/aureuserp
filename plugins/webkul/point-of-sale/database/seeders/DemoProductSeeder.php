<?php

namespace Webkul\PointOfSale\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Webkul\PointOfSale\Models\Category;
use Webkul\Product\Enums\AttributeType;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Attribute;
use Webkul\Product\Models\AttributeOption;
use Webkul\Product\Models\Category as ProductCategory;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductAttribute;
use Webkul\Product\Models\ProductAttributeValue;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

class DemoProductSeeder extends Seeder
{
    protected const IMAGE_DIRECTORY = 'products/demo';

    protected const PHOTO_ENDPOINT = 'https://loremflickr.com';

    public function run(): void
    {
        $company = Company::query()->firstOrFail();

        $uom = UOM::query()->firstOrFail();

        $productCategory = ProductCategory::query()->firstOrFail();

        $posCategories = $this->posCategories($company);

        $attributes = $this->attributes();

        foreach ($this->catalogue() as $entry) {
            $product = $this->createProduct($entry, $company, $uom, $productCategory);

            $posCategories[$entry['category']]->products()->syncWithoutDetaching([$product->id]);

            if (empty($entry['variants'])) {
                continue;
            }

            $this->addVariants($product, $entry['variants'], $attributes, $posCategories[$entry['category']]);
        }
    }

    protected function createProduct(array $entry, Company $company, UOM $uom, ProductCategory $productCategory): Product
    {
        $product = Product::withoutGlobalScopes()->firstOrNew([
            'name'       => $entry['name'],
            'company_id' => $company->id,
        ]);

        $product->fill([
            'type'             => ProductType::GOODS,
            'reference'        => $entry['reference'],
            'barcode'          => $entry['barcode'],
            'price'            => $entry['price'],
            'cost'             => $entry['cost'],
            'enable_sales'     => true,
            'is_storable'      => true,
            'available_in_pos' => true,
            'is_configurable'  => empty($entry['variants']) ? null : true,
            'uom_id'           => $uom->id,
            'uom_po_id'        => $uom->id,
            'category_id'      => $productCategory->id,
            'company_id'       => $company->id,
            'images'           => [$this->image($entry['name'], $entry['color'], $entry['keyword'])],
        ])->save();

        return $product;
    }

    protected function addVariants(Product $product, array $variants, array $attributes, Category $posCategory): void
    {
        foreach ($variants as $attributeName => $options) {
            $attribute = $attributes[$attributeName];

            $productAttribute = ProductAttribute::firstOrCreate([
                'product_id'   => $product->id,
                'attribute_id' => $attribute->id,
            ]);

            foreach ($options as $optionName => $extraPrice) {
                ProductAttributeValue::firstOrCreate([
                    'product_id'           => $product->id,
                    'attribute_id'         => $attribute->id,
                    'product_attribute_id' => $productAttribute->id,
                    'attribute_option_id'  => $attribute->options->firstWhere('name', $optionName)->id,
                ], [
                    'extra_price' => $extraPrice,
                ]);
            }
        }

        $product->refresh()->generateVariants();

        $product->variants()->each(function (Product $variant) use ($posCategory, $product): void {
            $variant->forceFill([
                'available_in_pos' => true,
                'enable_sales'     => true,
                'is_storable'      => true,
                'images'           => $product->images,
            ])->save();

            $posCategory->products()->syncWithoutDetaching([$variant->id]);
        });
    }

    /**
     * @return array<string, Category>
     */
    protected function posCategories(Company $company): array
    {
        $categories = [];

        $palette = [
            'Drinks'      => ['#1e6091', 'drinks,beverage'],
            'Food'        => ['#b5651d', 'food,meal'],
            'Clothing'    => ['#5a4e8c', 'clothes,wardrobe'],
            'Accessories' => ['#2d6a4f', 'accessories,fashion'],
        ];

        foreach (array_keys($palette) as $sort => $name) {
            $category = Category::withoutGlobalScopes()->firstOrCreate(
                ['name' => $name, 'company_id' => $company->id],
                ['sort' => $sort + 1],
            );

            [$color, $keyword] = $palette[$name];

            $category->forceFill([
                'color' => $color,
                'image' => $this->image($name.' Category', $color, $keyword),
            ])->save();

            $categories[$name] = $category;
        }

        return $categories;
    }

    /**
     * @return array<string, Attribute>
     */
    protected function attributes(): array
    {
        $definitions = [
            'Size'  => ['S', 'M', 'L', 'XL'],
            'Color' => ['Black', 'White', 'Blue'],
            'Cup'   => ['Small', 'Medium', 'Large'],
        ];

        $attributes = [];

        foreach ($definitions as $name => $options) {
            $attribute = Attribute::firstOrCreate(
                ['name' => $name],
                ['type' => AttributeType::RADIO],
            );

            foreach ($options as $sort => $option) {
                AttributeOption::firstOrCreate(
                    ['attribute_id' => $attribute->id, 'name' => $option],
                    ['sort' => $sort + 1],
                );
            }

            $attributes[$name] = $attribute->load('options');
        }

        return $attributes;
    }

    protected function image(string $name, string $color, ?string $keyword = null): string
    {
        $slug = Str::slug($name);

        foreach (['jpg', 'png'] as $extension) {
            $existing = static::IMAGE_DIRECTORY.'/'.$slug.'.'.$extension;

            if (Storage::disk('public')->exists($existing)) {
                return $existing;
            }
        }

        $photo = $keyword ? $this->photo($keyword) : null;

        $path = static::IMAGE_DIRECTORY.'/'.$slug.'.'.($photo['extension'] ?? 'png');

        Storage::disk('public')->put($path, $photo['contents'] ?? $this->render($name, $color));

        return $path;
    }

    /**
     * @return array{contents: string, extension: string}|null
     */
    protected function photo(string $keyword): ?array
    {
        foreach ($this->searchTerms($keyword) as $term) {
            $photo = $this->fetch($term);

            if ($photo) {
                return $photo;
            }
        }

        return null;
    }

    /**
     * Narrow searches return nothing often enough that a broader term is worth a second try.
     *
     * @return array<int, string>
     */
    protected function searchTerms(string $keyword): array
    {
        $terms = explode(',', $keyword);

        return array_values(array_unique([$keyword, $terms[0]]));
    }

    /**
     * @return array{contents: string, extension: string}|null
     */
    protected function fetch(string $term): ?array
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        try {
            $response = Http::timeout(20)->get(static::PHOTO_ENDPOINT.'/640/640/'.rawurlencode($term), [
                'lock' => crc32($term) % 10000,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $extension = $extensions[Str::before($response->header('Content-Type'), ';')] ?? null;

            if (! $extension || strlen($response->body()) < 5000) {
                return null;
            }

            return ['contents' => $response->body(), 'extension' => $extension];
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    protected function render(string $name, string $color): string
    {
        $size = 320;

        $canvas = imagecreatetruecolor($size, $size);

        [$red, $green, $blue] = sscanf($color, '#%02x%02x%02x');

        imagefilledrectangle($canvas, 0, 0, $size, $size, imagecolorallocate($canvas, $red, $green, $blue));

        $initials = collect(explode(' ', $name))
            ->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');

        $white = imagecolorallocate($canvas, 255, 255, 255);

        imagestring($canvas, 5, (int) (($size - (imagefontwidth(5) * strlen($initials))) / 2), (int) ($size / 2) - 8, $initials, $white);

        ob_start();

        imagepng($canvas);

        $contents = (string) ob_get_clean();

        imagedestroy($canvas);

        return $contents;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function catalogue(): array
    {
        return [
            ['name' => 'Espresso', 'keyword' => 'espresso,coffee', 'reference' => 'POS-DRK-01', 'barcode' => '2000000000015', 'price' => 2.5, 'cost' => 0.8, 'category' => 'Drinks', 'color' => '#4b2e2b', 'variants' => ['Cup' => ['Small' => 0, 'Medium' => 0.5, 'Large' => 1.0]]],
            ['name' => 'Green Tea', 'keyword' => 'green-tea,teacup', 'reference' => 'POS-DRK-03', 'barcode' => '2000000000039', 'price' => 2.2, 'cost' => 0.6, 'category' => 'Drinks', 'color' => '#4f7942', 'variants' => []],
            ['name' => 'Orange Juice', 'keyword' => 'orange-juice,glass', 'reference' => 'POS-DRK-04', 'barcode' => '2000000000046', 'price' => 3.8, 'cost' => 1.4, 'category' => 'Drinks', 'color' => '#e8890c', 'variants' => []],
            ['name' => 'Croissant', 'keyword' => 'croissant,bakery', 'reference' => 'POS-FOD-01', 'barcode' => '2000000000077', 'price' => 2.4, 'cost' => 0.9, 'category' => 'Food', 'color' => '#d7a95b', 'variants' => []],
            ['name' => 'Chicken Sandwich', 'keyword' => 'sandwich,chicken', 'reference' => 'POS-FOD-03', 'barcode' => '2000000000091', 'price' => 6.5, 'cost' => 2.6, 'category' => 'Food', 'color' => '#c8892f', 'variants' => []],
            ['name' => 'Margherita Pizza', 'keyword' => 'pizza,margherita', 'reference' => 'POS-FOD-05', 'barcode' => '2000000000114', 'price' => 9.5, 'cost' => 3.8, 'category' => 'Food', 'color' => '#b23c2e', 'variants' => []],
            ['name' => 'Classic T-Shirt', 'keyword' => 't-shirt,shirt', 'reference' => 'POS-CLO-01', 'barcode' => '2000000000145', 'price' => 19.9, 'cost' => 7.5, 'category' => 'Clothing', 'color' => '#2f3640', 'variants' => ['Size' => ['S' => 0, 'M' => 0, 'L' => 1.0, 'XL' => 2.0], 'Color' => ['Black' => 0, 'White' => 0, 'Blue' => 1.5]]],
            ['name' => 'Leather Belt', 'keyword' => 'leather-belt,belt', 'reference' => 'POS-ACC-02', 'barcode' => '2000000000213', 'price' => 29.0, 'cost' => 11.0, 'category' => 'Accessories', 'color' => '#5d4037', 'variants' => []],
            ['name' => 'Sunglasses', 'keyword' => 'sunglasses', 'reference' => 'POS-ACC-04', 'barcode' => '2000000000237', 'price' => 55.0, 'cost' => 20.0, 'category' => 'Accessories', 'color' => '#212121', 'variants' => []],
            ['name' => 'Baseball Cap', 'keyword' => 'baseball-cap,cap', 'reference' => 'POS-ACC-05', 'barcode' => '2000000000244', 'price' => 16.0, 'cost' => 5.5, 'category' => 'Accessories', 'color' => '#455a64', 'variants' => []],
        ];
    }
}
