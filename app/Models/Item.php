<?php

namespace App\Models;

use App\Services\BarcodeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    /**
     * Prefix used for automatically generated internal barcodes.
     */
    public const BARCODE_PREFIX = 'P';

    protected $fillable = [
        'item_name',
        'barcode',
        'manufacturer_barcode',
        'quantity',
        'price',
        'category',
    ];

    /**
     * Guarantee every item ends up with a scannable barcode, even when the
     * request did not supply one.
     */
    protected static function booted(): void
    {
        static::creating(function (Item $item) {
            if (blank($item->barcode)) {
                $item->barcode = static::generateBarcode();
            }
        });
    }

    /**
     * Generate an internal barcode value that is not already used.
     */
    public static function generateBarcode(): string
    {
        do {
            $barcode = self::BARCODE_PREFIX.random_int(100000, 999999);
        } while (static::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Find an item by either kind of barcode.
     *
     * A product can be scanned straight off its packaging, which matches
     * manufacturer_barcode, or off a printed label, which matches barcode. The
     * scanned value is normalised first so that a GS1 suffix or surrounding
     * whitespace does not defeat the match, and the 12/13 digit UPC/EAN forms
     * of the same product are both accepted.
     *
     * @param  string|null  $code  Raw value coming off a scanner.
     */
    public static function findByAnyBarcode($code): ?self
    {
        foreach (BarcodeService::lookupCandidates($code) as $candidate) {
            $item = static::where('barcode', $candidate)
                ->orWhere('manufacturer_barcode', $candidate)
                ->first();

            if ($item) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Which of the two barcode columns produced the match, for display.
     */
    public function matchedBarcodeColumn(?string $scanned = null): ?string
    {
        if ($scanned === null) {
            return null;
        }

        $candidates = BarcodeService::lookupCandidates($scanned);

        if ($this->barcode !== null && in_array($this->barcode, $candidates, true)) {
            return 'barcode';
        }

        if ($this->manufacturer_barcode !== null
            && in_array($this->manufacturer_barcode, $candidates, true)) {
            return 'manufacturer_barcode';
        }

        return null;
    }

    /**
     * Limit a query to items matching a free text search across the columns a
     * cashier would plausibly type into.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search) {
            $query->where('item_name', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%")
                ->orWhere('manufacturer_barcode', 'like', "%{$search}%");

            if (is_numeric($search)) {
                $query->orWhere('id', (int) $search);
            }
        });
    }
}
