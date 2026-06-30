<?php

namespace App\Domain\Branch;

use App\Models\ApPayment;
use App\Models\ArReceipt;
use App\Models\Asset;
use App\Models\AssetDepreciationRun;
use App\Models\BankReconciliation;
use App\Models\CustomerInvoice;
use App\Models\GoodsReceipt;
use App\Models\PeriodClosing;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RecurringJournal;
use App\Models\StockAdjustment;
use App\Models\SupplierBill;
use App\Models\SupplierReturn;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class BranchResolverRegistry
{
    /**
     * Maps a polymorphic type to how its branch is reached. No morph map is
     * registered, so keys are FQCNs via ::class.
     *
     * @var array<class-string<Model>, BranchResolutionStrategy>
     */
    private const STRATEGIES = [
        ApPayment::class => BranchResolutionStrategy::Direct,
        ArReceipt::class => BranchResolutionStrategy::Direct,
        Asset::class => BranchResolutionStrategy::Direct,
        CustomerInvoice::class => BranchResolutionStrategy::Direct,
        PurchaseRequest::class => BranchResolutionStrategy::Direct,
        SupplierBill::class => BranchResolutionStrategy::Direct,
        GoodsReceipt::class => BranchResolutionStrategy::Warehouse,
        StockAdjustment::class => BranchResolutionStrategy::Warehouse,
        SupplierReturn::class => BranchResolutionStrategy::Warehouse,
        PurchaseOrder::class => BranchResolutionStrategy::Warehouse,
        BankReconciliation::class => BranchResolutionStrategy::None,
        RecurringJournal::class => BranchResolutionStrategy::None,
        PeriodClosing::class => BranchResolutionStrategy::None,
        AssetDepreciationRun::class => BranchResolutionStrategy::None,
    ];

    /**
     * Resolve the branch id for a polymorphic source model, or null when it
     * has no resolvable branch (warehouse without a branch, or a None type).
     *
     * @throws InvalidArgumentException when the model's type is not registered
     */
    public function resolve(Model $source): ?int
    {
        return match ($this->strategyFor($source::class)) {
            BranchResolutionStrategy::Direct => $this->intOrNull($source->getAttribute('branch_id')),
            BranchResolutionStrategy::Warehouse => $this->resolveViaWarehouse($source),
            BranchResolutionStrategy::None => null,
        };
    }

    /**
     * The eager-load relations needed to resolve a branch for the given type.
     *
     * @param  class-string<Model>  $type
     * @return list<string>
     */
    public function relationsFor(string $type): array
    {
        return match ($this->strategyFor($type)) {
            BranchResolutionStrategy::Warehouse => ['warehouse'],
            default => [],
        };
    }

    /**
     * Types that can resolve to a branch (Direct or Warehouse), excluding None.
     *
     * @return list<class-string<Model>>
     */
    public function branchBearingTypes(): array
    {
        return array_keys(array_filter(
            self::STRATEGIES,
            static fn (BranchResolutionStrategy $strategy): bool => $strategy !== BranchResolutionStrategy::None,
        ));
    }

    public function isRegistered(string $type): bool
    {
        return array_key_exists($type, self::STRATEGIES);
    }

    /**
     * @param  class-string<Model>|string  $type
     *
     * @throws InvalidArgumentException
     */
    private function strategyFor(string $type): BranchResolutionStrategy
    {
        if (! array_key_exists($type, self::STRATEGIES)) {
            throw new InvalidArgumentException(
                "No branch resolution strategy registered for [{$type}]. " .
                'Register it in ' . self::class . '::STRATEGIES ' .
                '(use BranchResolutionStrategy::None for intentionally unscopable types).',
            );
        }

        return self::STRATEGIES[$type];
    }

    private function resolveViaWarehouse(Model $source): ?int
    {
        $warehouse = $source->getAttribute('warehouse');

        if (! $warehouse instanceof Model) {
            return null;
        }

        return $this->intOrNull($warehouse->getAttribute('branch_id'));
    }

    private function intOrNull(mixed $value): ?int
    {
        return $value !== null ? (int) $value : null;
    }
}
