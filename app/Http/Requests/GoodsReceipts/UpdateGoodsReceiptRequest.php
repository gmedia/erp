<?php

namespace App\Http\Requests\GoodsReceipts;

use App\Http\Requests\Concerns\ValidatesStatusTransition;
use Illuminate\Contracts\Validation\Validator;

class UpdateGoodsReceiptRequest extends AbstractGoodsReceiptRequest
{
    use ValidatesStatusTransition;

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $this->validateResultStatusTransition($validator, $this->route('goodsReceipt')?->status);
        });
    }

    /** Hanya draft yang boleh dikonfirmasi; cancelled dicapai dari draft atau confirmed. */
    protected function resultStatusSources(): array
    {
        return [
            'confirmed' => ['draft'],
            'cancelled' => ['draft', 'confirmed'],
        ];
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
