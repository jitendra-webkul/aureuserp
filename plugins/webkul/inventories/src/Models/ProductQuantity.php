<?php

namespace Webkul\Inventory\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Database\Factories\ProductQuantityFactory;
use Webkul\Inventory\Settings\OperationSettings;
use Webkul\Inventory\Enums\ProductTracking;
use Webkul\Inventory\Models\Packaging;
use Webkul\Support\Models\UOM;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class ProductQuantity extends Model
{
    use HasFactory;

    protected $table = 'inventories_product_quantities';

    protected $fillable = [
        'quantity',
        'reserved_quantity',
        'counted_quantity',
        'difference_quantity',
        'inventory_diff_quantity',
        'inventory_quantity_set',
        'scheduled_at',
        'incoming_at',
        'product_id',
        'location_id',
        'storage_category_id',
        'lot_id',
        'package_id',
        'partner_id',
        'user_id',
        'company_id',
        'creator_id',
    ];

    protected $casts = [
        'inventory_quantity_set' => 'boolean',
        'scheduled_at'           => 'date',
        'incoming_at'            => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function storageCategory(): BelongsTo
    {
        return $this->belongsTo(StorageCategory::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAvailableQuantityAttribute(): float
    {
        return $this->quantity - $this->reserved_quantity;
    }

    public function updateScheduledAt()
    {
        $this->scheduled_at = Carbon::create(
            now()->year,
            app(OperationSettings::class)->annual_inventory_month,
            app(OperationSettings::class)->annual_inventory_day,
            0,
            0,
            0
        );

        if ($this->location?->cyclic_inventory_frequency) {
            $this->scheduled_at = now()->addDays($this->location->cyclic_inventory_frequency);
        }
    }

    public static function gather(
        Product $product,
        Location $location,
        ?Lot $lot = null,
        ?Package $package = null,
        ?Partner $partner = null,
        bool $strict = false,
        float $qty = 0,
    ): \Illuminate\Database\Eloquent\Collection {
        $removalStrategy = static::getRemovalStrategy($product, $location);

        $domain = static::getGatherDomain($product, $location, $lot, $package, $partner, $strict);

        if ($removalStrategy === 'least_packages' && $qty) {
            $domain = static::runLeastPackagesRemovalStrategyAstar($domain, $qty);
        }

        $order = static::getRemovalStrategyOrder($removalStrategy);

        $query = static::query()->where($domain);

        if ($order) {
            $query->orderByRaw($order);
        }

        $quants = $query->get();

        if ($removalStrategy === 'closest') {
            $quants = $quants->sortBy(fn ($q) => [$q->location->complete_name, -$q->id])->values();
        }

        return $quants->sortBy(fn ($q) => $q->lot_id ? 0 : 1)->values();
    }

    public static function getRemovalStrategy(Product $product, Location $location): string
    {
        if ($product->category?->removalStrategy?->method) {
            return $product->category->removalStrategy->method;
        }

        $loc = $location;

        while ($loc) {
            if ($loc->removalStrategy?->method) {
                return $loc->removalStrategy->method;
            }

            $loc = $loc->location;
        }

        return 'fifo';
    }

    public static function getGatherDomain(
        Product $product,
        Location $location,
        ?Lot $lot = null,
        ?Package $package = null,
        ?Partner $partner = null,
        bool $strict = false,
    ): \Closure {
        return function ($query) use ($product, $location, $lot, $package, $partner, $strict) {
            $query->where('product_id', $product->id);

            if (! $strict) {
                if ($lot) {
                    $query->where(fn ($q) => $q->where('lot_id', $lot->id)->orWhereNull('lot_id'));
                }

                if ($package) {
                    $query->where('package_id', $package->id);
                }

                if ($partner) {
                    $query->where('partner_id', $partner->id);
                }

                $childIds = Location::where('parent_path', 'LIKE', $location->parent_path . '%')->pluck('id');

                $query->whereIn('location_id', $childIds);
            } else {
                if ($lot) {
                    $query->where(fn ($q) => $q->where('lot_id', $lot->id)->orWhereNull('lot_id'));
                } else {
                    $query->whereNull('lot_id');
                }

                $query->where('package_id', $package?->id);
                $query->where('partner_id', $partner?->id);
                $query->where('location_id', $location->id);
            }
        };
    }

    public static function getRemovalStrategyOrder(string $removalStrategy): ?string
    {
        return match ($removalStrategy) {
            'fifo', 'least_packages' => 'incoming_at ASC, id',
            'lifo'                   => 'incoming_at DESC, id DESC',
            'closest'                => null,
            default                  => throw new \RuntimeException(__('Removal strategy :strategy not implemented.', ['strategy' => $removalStrategy])),
        };
    }

    public static function runLeastPackagesRemovalStrategyAstar(\Closure $domain, float $qty): \Closure
    {
        $qtyByPackage = static::query()
            ->where($domain)
            ->selectRaw('package_id, SUM(quantity - reserved_quantity) AS available_qty')
            ->groupBy('package_id')
            ->havingRaw('SUM(quantity - reserved_quantity) > 0')
            ->orderByRaw('available_qty DESC')
            ->get()
            ->map(fn ($row) => [$row->package_id, (float) $row->available_qty])
            ->toArray();

        $pkgFound = false;
        $newQtyByPackage = [];
        $noneElements = [];

        foreach ($qtyByPackage as $elem) {
            if ($elem[0] === null) {
                for ($i = 0; $i < (int) $elem[1]; $i++) {
                    $noneElements[] = [null, 1.0];
                }
            } elseif ($elem[1] != 0) {
                $newQtyByPackage[] = $elem;
                $pkgFound = true;
            }
        }

        $qtyByPackage = array_merge($newQtyByPackage, $noneElements);

        if (! $pkgFound) {
            return $domain;
        }

        $size = count($qtyByPackage);

        $heuristic = function (array $node) use ($size, $qtyByPackage): float {
            [$countRemaining, $takenPackages, $nextIndex] = $node;

            if ($nextIndex < $size) {
                return count($takenPackages) + $countRemaining / $qtyByPackage[$nextIndex][1];
            }

            return (float) count($takenPackages);
        };

        $generateDomain = function (array $node) use ($domain): \Closure {
            [, $takenPackages] = $node;

            $packageIds = [];
            $hasNullPackage = false;

            foreach ($takenPackages as $pkg) {
                if ($pkg[0] === null) {
                    $hasNullPackage = true;
                } else {
                    $packageIds[] = $pkg[0];
                }
            }

            $selectedSingleItemIds = [];

            if ($hasNullPackage) {
                $selectedSingleItemIds = static::query()
                    ->where($domain)
                    ->whereNull('package_id')
                    ->pluck('id')
                    ->toArray();
            }

            return function ($query) use ($domain, $packageIds, $selectedSingleItemIds) {
                $query->where($domain);
                $query->where(function ($q) use ($packageIds, $selectedSingleItemIds) {
                    $q->whereIn('package_id', $packageIds);

                    if (! empty($selectedSingleItemIds)) {
                        $q->orWhereIn('id', $selectedSingleItemIds);
                    }
                });
            };
        };

        $frontier = new \SplPriorityQueue();
        $frontier->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);

        $startNode = [$qty, [], 0];
        $frontier->insert($startNode, -$heuristic($startNode));

        $bestLeaf = $startNode;

        try {
            while (! $frontier->isEmpty()) {
                $current = $frontier->extract()['data'];
                [$countRemaining, $takenPackages, $nextIndex] = $current;

                if ($countRemaining <= 0) {
                    return $generateDomain($current);
                }

                $lastCount = null;
                $i = $nextIndex;

                while ($i < $size) {
                    $pkg = $qtyByPackage[$i];
                    $i++;

                    if ($pkg[1] === $lastCount) {
                        continue;
                    }

                    $lastCount = $pkg[1];

                    $count = $countRemaining - $pkg[1];
                    $taken = array_merge($takenPackages, [$pkg]);
                    $node = [$count, $taken, $i];

                    if ($count < 0) {
                        if (
                            $bestLeaf[0] > 0
                            || count($node[1]) < count($bestLeaf[1])
                            || (count($node[1]) === count($bestLeaf[1]) && $node[0] > $bestLeaf[0])
                        ) {
                            $bestLeaf = $node;
                        }

                        continue;
                    }

                    if ($i >= $size && $count != 0) {
                        if ($node[0] < $bestLeaf[0]) {
                            $bestLeaf = $node;
                        }

                        continue;
                    }

                    $frontier->insert($node, -$heuristic($node));
                }
            }
        } catch (\Exception $e) {
            return $domain;
        }

        return $generateDomain($bestLeaf);
    }

    public static function getAvailableQuantity(
        Product $product,
        Location $location,
        ?Lot $lot = null,
        ?Package $package = null,
        ?Partner $partner = null,
        bool $strict = false,
        bool $allowNegative = false,
    ): float {
        $quants = static::gather($product, $location, $lot, $package, $partner, $strict);

        $rounding = $product->uom->rounding;

        if (! in_array($product->tracking, [ProductTracking::LOT, ProductTracking::SERIAL])) {
            $available = $quants->sum('quantity') - $quants->sum('reserved_quantity');

            if ($allowNegative) {
                return $available;
            }

            return float_compare($available, 0.0, precisionRounding: $rounding) >= 0 ? $available : 0.0;
        }

        $availableQuantities = array_fill_keys(
            array_merge($quants->pluck('lot_id')->filter()->unique()->toArray(), ['untracked']),
            0.0
        );

        foreach ($quants as $quant) {
            if (! $quant->lot_id && $strict && $lot) {
                continue;
            }

            $bucketKey = $quant->lot_id ?? 'untracked';

            $availableQuantities[$bucketKey] = ($availableQuantities[$bucketKey] ?? 0.0) + ($quant->quantity - $quant->reserved_quantity);
        }

        if ($allowNegative) {
            return (float) array_sum($availableQuantities);
        }

        return (float) array_sum(array_filter($availableQuantities, fn ($v) => float_compare($v, 0.0, precisionRounding: $rounding) > 0));
    }

    public static function getReserveQuantity(
        Product $product,
        Location $location,
        float $quantity,
        ?Packaging $productPackaging = null,
        ?UOM $uom = null,
        ?Lot $lot = null,
        ?Package $package = null,
        ?Partner $partner = null,
        bool $strict = false,
    ): array {
        $rounding = $product->uom->rounding;

        $quants = static::gather($product, $location, $lot, $package, $partner, $strict);

        $availableQuantity = static::getAvailableQuantity($product, $location, $lot, $package, $partner, $strict);

        if ($productPackaging && $product->category?->packaging_reserve_method === 'full') {
            $availableQuantity = $productPackaging->checkQty($availableQuantity, $product->uom, 'DOWN');
        }

        $quantity = min($quantity, $availableQuantity);

        if (! $strict && $uom && $product->uom->id !== $uom->id) {
            $quantityMoveUom = $product->uom->computeQuantity($quantity, $uom, roundingMethod: 'DOWN');

            $quantity = $uom->computeQuantity($quantityMoveUom, $product->uom, roundingMethod: 'HALF-UP');
        }

        if ($product->tracking === ProductTracking::SERIAL) {
            if (float_compare($quantity, (float)(int)$quantity, precisionRounding: $rounding) !== 0) {
                $quantity = 0.0;
            }
        }

        $reservedQuants = [];

        if (float_compare($quantity, 0.0, precisionRounding: $rounding) === 0) {
            return $reservedQuants;
        }

        if (float_compare($quantity, 0.0, precisionRounding: $rounding) > 0) {
            $available = $quants->filter(fn ($q) => float_compare($q->quantity, 0.0, precisionRounding: $rounding) > 0)->sum('quantity')
                - $quants->sum('reserved_quantity');
        } else {
            $available = $quants->sum('reserved_quantity');

            if (float_compare(abs($quantity), $available, precisionRounding: $rounding) > 0) {
                throw new \RuntimeException(__('It is not possible to unreserve more products of :name than you have in stock.', ['name' => $product->name]));
            }
        }

        $negativeReserved = [];

        foreach ($quants as $quant) {
            $net = $quant->quantity - $quant->reserved_quantity;

            if (float_compare($net, 0.0, precisionRounding: $rounding) < 0) {
                $negKey = implode('_', [$quant->location_id, $quant->lot_id, $quant->package_id, $quant->partner_id]);

                $negativeReserved[$negKey] = ($negativeReserved[$negKey] ?? 0.0) + $net;
            }
        }

        foreach ($quants as $quant) {
            if (float_compare($quantity, 0.0, precisionRounding: $rounding) > 0) {
                $maxOnQuant = $quant->quantity - $quant->reserved_quantity;

                if (float_compare($maxOnQuant, 0.0, precisionRounding: $rounding) <= 0) {
                    continue;
                }

                $negKey = implode('_', [$quant->location_id, $quant->lot_id, $quant->package_id, $quant->partner_id]);

                $negQty = $negativeReserved[$negKey] ?? 0.0;

                if ($negQty) {
                    $toRemove = min(abs($negQty), $maxOnQuant);

                    $negativeReserved[$negKey] += $toRemove;
                    
                    $maxOnQuant -= $toRemove;
                }

                if (float_compare($maxOnQuant, 0.0, precisionRounding: $rounding) <= 0) {
                    continue;
                }

                $toReserve = min($maxOnQuant, $quantity);

                $reservedQuants[] = [$quant, $toReserve];

                $quantity -= $toReserve;

                $available -= $toReserve;
            } else {
                $toRelease = min($quant->reserved_quantity, abs($quantity));

                $reservedQuants[] = [$quant, -$toRelease];

                $quantity += $toRelease;

                $available += $toRelease;
            }

            if (
                float_is_zero($quantity, precisionRounding: $rounding)
                || float_is_zero($available, precisionRounding: $rounding)
            ) {
                break;
            }
        }

        return $reservedQuants;
    }

    protected static function newFactory(): ProductQuantityFactory
    {
        return ProductQuantityFactory::new();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($productQuantity) {
            $productQuantity->creator_id ??= Auth::id();
        });

        static::saving(function ($productQuantity) {
            $productQuantity->updateScheduledAt();
        });
    }
}
