<?php

namespace App\Actions\VehicleLoans;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class StoreVehicleLoanPhotoAction
{
    /**
     * @return array{path: string, name: string}
     */
    public function execute(UploadedFile $file, string $folder): array
    {
        $maxBytes = config('vehicle_loan.photo_max_kb', 5120) * 1024;

        if ($file->getSize() > $maxBytes) {
            throw new InvalidArgumentException('Photo is too large.');
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, config('vehicle_loan.photo_mimes', ['jpg', 'jpeg', 'png']), true)) {
            throw new InvalidArgumentException('Unsupported photo format.');
        }

        $name = $file->getClientOriginalName();
        $path = $file->storeAs(
            $folder,
            uniqid('', true) . '.' . $extension,
            config('vehicle_loan.photo_disk', 'local'),
        );

        return ['path' => $path, 'name' => $name];
    }
}
