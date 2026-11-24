<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;

/**
 * Membatasi transisi status saat PUT (Issue #375).
 *
 * Aturan:
 * - status sama dengan tersimpan selalu diterima (kirim ulang aman);
 * - status tersimpan yang sudah final (lihat finalResultStatuses) tidak bisa diubah;
 * - tidak bisa mundur ke draft;
 * - target lain hanya sah bila status tersimpan ada di daftar sumber target.
 * Pesan dari lang/{locale}.json.
 */
trait ValidatesStatusTransition
{
    /**
     * @return array<string, array<int, string>> target => status tersimpan yang boleh menuju target
     */
    abstract protected function resultStatusSources(): array;

    /**
     * Status batal/void yang tidak boleh berpindah ke status lain.
     * Kirim ulang status yang sama tetap diterima (dicek sebelum daftar ini).
     *
     * @return array<int, string>
     */
    protected function finalResultStatuses(): array
    {
        return ['cancelled', 'void'];
    }

    protected function validateResultStatusTransition(Validator $validator, ?string $current): void
    {
        $target = $this->input('status');

        if (! is_string($target) || $current === null || $target === $current) {
            return;
        }

        $blocked = $target === 'draft'
            || in_array($current, $this->finalResultStatuses(), true);

        $sources = $this->resultStatusSources()[$target] ?? null;

        if (! $blocked && ($sources === null || in_array($current, $sources, true))) {
            return;
        }

        $validator->errors()->add(
            'status',
            __('status_transition.invalid', ['from' => $current, 'to' => $target]),
        );
    }
}
