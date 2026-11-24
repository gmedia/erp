<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;

/**
 * Membatasi status hasil pembayaran/rekonsiliasi saat PUT.
 *
 * Status hasil hanya boleh dikirim ulang bila sama dengan status tersimpan,
 * atau dicapai dari status sumber yang diizinkan. Pesan dari lang/{locale}.json.
 */
trait ValidatesStatusTransition
{
    /**
     * @return array<string, array<int, string>> target => status tersimpan yang boleh menuju target
     */
    abstract protected function resultStatusSources(): array;

    protected function validateResultStatusTransition(Validator $validator, ?string $current): void
    {
        $target = $this->input('status');

        if (! is_string($target) || $current === null || $target === $current) {
            return;
        }

        $sources = $this->resultStatusSources()[$target] ?? null;

        if ($sources === null || in_array($current, $sources, true)) {
            return;
        }

        $validator->errors()->add(
            'status',
            __('status_transition.invalid', ['from' => $current, 'to' => $target]),
        );
    }
}
