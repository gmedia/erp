<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\CustomerInvoice;
use App\Models\JournalEntry;
use App\Models\SupplierBill;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * Data demo Audit Log: beberapa perubahan realistis pada dokumen contoh.
 *
 * Baris demo ditandai di `properties` sehingga seeder dapat menghapus dan
 * membuat ulang barisnya sendiri (idempoten) tanpa menyentuh catatan asli.
 * Nilai lama/baru diambil dari data seed aktual agar riwayat konsisten.
 */
class AuditLogSampleDataSeeder extends Seeder
{
    private const DEMO_KEY = 'demo_seed_578';

    public function run(): void
    {
        Activity::query()
            ->where('properties->' . self::DEMO_KEY, true)
            ->delete();

        $admin = User::where('email', config('app.admin'))->first();
        $rina = User::where('email', 'rina.kartika@example.com')->first();
        $dewi = User::where('email', 'director.finance@example.com')->first();
        $agus = User::where('email', 'staff.it@example.com')->first();

        $now = Carbon::now();

        $this->seedJournalOpeningBalance($dewi, $now);
        $this->seedAssetJournalReference($rina, $now);
        $this->seedSupplierBillConfirmation($dewi, $now);
        $this->seedInvoicePartialPayment($rina, $now);
        $this->seedReceiptConfirmation($agus, $now);
        $this->seedAccountRename($admin, $now);
    }

    private function seedJournalOpeningBalance(?User $causer, Carbon $now): void
    {
        $journal = JournalEntry::query()
            ->where('reference', 'like', 'OPEN-CAP-%')
            ->orderBy('id')
            ->first();

        if ($journal === null) {
            return;
        }

        $oldDescription = str_contains($journal->description, 'disetor')
            ? str_replace('disetor ', '', $journal->description)
            : $journal->description . ' (lama)';
        $oldReference = (string) preg_replace('/\d{4}/', '2025', (string) $journal->reference, 1);

        $this->record($journal, $causer, 10080, $now, [
            'description' => $oldDescription,
            'reference' => $oldReference,
        ], [
            'description' => $journal->description,
            'reference' => $journal->reference,
        ]);
    }

    private function seedAssetJournalReference(?User $causer, Carbon $now): void
    {
        $journal = JournalEntry::query()
            ->where('reference', 'like', 'ACQ-FA-%')
            ->orderBy('id')
            ->first();

        if ($journal === null) {
            return;
        }

        $reference = (string) $journal->reference;
        $oldReference = (string) preg_replace('/0000(\d)$/', '000$1', $reference, 1);

        $this->record($journal, $causer, 4320, $now, [
            'reference' => $oldReference === $reference ? $reference . '-LAMA' : $oldReference,
        ], [
            'reference' => $reference,
        ]);
    }

    private function seedSupplierBillConfirmation(?User $causer, Carbon $now): void
    {
        $bill = SupplierBill::query()
            ->where('status', 'confirmed')
            ->orderBy('id')
            ->first();

        if ($bill === null) {
            return;
        }

        $this->record($bill, $causer, 2880, $now, [
            'status' => 'draft',
        ], [
            'status' => $bill->status,
        ]);
    }

    private function seedInvoicePartialPayment(?User $causer, Carbon $now): void
    {
        $invoice = CustomerInvoice::query()
            ->where('status', 'partially_paid')
            ->orderBy('id')
            ->first();

        if ($invoice === null) {
            return;
        }

        $this->record($invoice, $causer, 1440, $now, [
            'status' => 'confirmed',
        ], [
            'status' => $invoice->status,
        ]);
    }

    private function seedReceiptConfirmation(?User $causer, Carbon $now): void
    {
        $receipt = ArReceipt::query()
            ->where('status', 'confirmed')
            ->orderBy('id')
            ->first();

        if ($receipt === null) {
            return;
        }

        $this->record($receipt, $causer, 360, $now, [
            'status' => 'draft',
        ], [
            'status' => $receipt->status,
        ]);
    }

    private function seedAccountRename(?User $causer, Carbon $now): void
    {
        $account = Account::query()
            ->where('name', 'Cash')
            ->orderBy('id')
            ->first();

        if ($account === null) {
            return;
        }

        $this->record($account, $causer, 120, $now, [
            'name' => 'Kas',
        ], [
            'name' => $account->name,
        ]);
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function record(
        Model $subject,
        ?User $causer,
        int $minutesAgo,
        Carbon $now,
        array $old,
        array $new,
    ): void {
        if ($old === $new) {
            return;
        }

        $activity = new Activity;
        $activity->log_name = 'finance';
        $activity->description = 'updated';
        $activity->event = 'updated';
        $activity->subject_type = $subject->getMorphClass();
        $activity->subject_id = $subject->getKey();
        $activity->causer_type = $causer?->getMorphClass();
        $activity->causer_id = $causer?->getKey();
        $activity->attribute_changes = [
            'attributes' => $new,
            'old' => $old,
        ];
        $activity->properties = [self::DEMO_KEY => true];
        $activity->created_at = $now->copy()->subMinutes($minutesAgo);
        $activity->updated_at = $activity->created_at;
        $activity->save();
    }
}
