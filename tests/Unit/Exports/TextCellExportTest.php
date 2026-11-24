<?php

use App\Exports\Concerns\BaseExport;
use App\Exports\Concerns\SimpleCrudExport;
use App\Exports\Concerns\TextSafeValueBinder;
use App\Exports\Concerns\WritesTextCells;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

uses()->group('exports');

/**
 * Tulis ekspor ke xlsx/csv lalu baca kembali sebagai Spreadsheet.
 */
function textCellRoundTrip(object $export, string $format): Spreadsheet
{
    $writerType = $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX;

    // Jalur produksi (ExportStorage::store) mengaktifkan netralisasi hanya untuk CSV.
    TextSafeValueBinder::neutralizeFormulaPrefixes($writerType === ExcelFormat::CSV);

    try {
        $binary = Excel::raw($export, $writerType);
    } finally {
        TextSafeValueBinder::neutralizeFormulaPrefixes(false);
    }

    $path = tempnam(sys_get_temp_dir(), 'textcell_').'.'.$format;
    file_put_contents($path, $binary);

    try {
        // Pembacaan memakai binder default, seperti jalur impor.
        Cell::setValueBinder(app(config('excel.value_binder.default', DefaultValueBinder::class)));

        return IOFactory::createReaderForFile($path)->load($path);
    } finally {
        @unlink($path);
    }
}

/**
 * Ekspor minimal yang memakai binder bersama.
 *
 * @param  list<list<mixed>>  $rows
 */
function makeTextCellExport(array $rows): object
{
    return new class($rows) implements FromCollection, WithCustomValueBinder
    {
        use WritesTextCells;

        /**
         * @param  list<list<mixed>>  $rows
         */
        public function __construct(private readonly array $rows) {}

        public function collection(): Collection
        {
            return collect($this->rows);
        }
    };
}

/**
 * Semua kelas konkret di app/Exports, termasuk yang mewarisi base class.
 *
 * @return list<string>
 */
function textCellExportClasses(): array
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Exports')));
    $classes = [];

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $relative = substr($file->getPathname(), strlen(app_path()) + 1, -4);
        $class = 'App\\'.str_replace('/', '\\', $relative);

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
            continue;
        }

        if (is_subclass_of($class, FromCollection::class) || is_subclass_of($class, FromQuery::class)) {
            $classes[] = $class;
        }
    }

    sort($classes);

    return $classes;
}

describe('TextSafeValueBinder', function () {

    test('string berawalan karakter berbahaya dibaca kembali sebagai string persis sama', function (string $value) {
        $sheet = textCellRoundTrip(makeTextCellExport([[$value]]), 'xlsx')->getActiveSheet();
        $cell = $sheet->getCell('A1');

        expect($cell->getValue())->toBe($value)
            ->and($cell->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($cell->isFormula())->toBeFalse();
    })->with([
        'formula sederhana' => ['=SUM(1,2)'],
        'plus' => ['+cmd|calc'],
        'minus' => ['-2+3'],
        'at' => ['@SUM(A1)'],
        'tab' => ["\tleading-tab"],
    ]);

    test('carriage return di awal tetap string dan bukan formula', function () {
        $cell = textCellRoundTrip(makeTextCellExport([["\rleading-cr"]]), 'xlsx')->getActiveSheet()->getCell('A1');

        // XML menormalkan CR menjadi LF saat dibaca kembali; yang dijamin adalah tipe string.
        expect($cell->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($cell->isFormula())->toBeFalse()
            ->and(trim($cell->getValue()))->toBe('leading-cr');
    });

    test('string yang menyerupai angka tetap string', function () {
        $sheet = textCellRoundTrip(makeTextCellExport([['+62812345678'], ['007'], ['1e3']]), 'xlsx')->getActiveSheet();

        foreach (['A1', 'A2', 'A3'] as $address) {
            expect($sheet->getCell($address)->getDataType())->toBe(DataType::TYPE_STRING);
        }

        expect($sheet->getCell('A1')->getValue())->toBe('+62812345678')
            ->and($sheet->getCell('A2')->getValue())->toBe('007');
    });

    test('angka tetap bertipe numerik', function () {
        $sheet = textCellRoundTrip(makeTextCellExport([[42, 3.5]]), 'xlsx')->getActiveSheet();

        expect($sheet->getCell('A1')->getDataType())->toBe(DataType::TYPE_NUMERIC)
            ->and($sheet->getCell('A1')->getValue())->toBe(42)
            ->and($sheet->getCell('B1')->getDataType())->toBe(DataType::TYPE_NUMERIC);
    });

    test('csv menetralkan awalan berbahaya dengan apostrof dan tidak menulis formula', function () {
        $sheet = textCellRoundTrip(makeTextCellExport([['=SUM(1,2)', '+62812', 'aman']]), 'csv')->getActiveSheet();

        expect($sheet->getCell('A1')->isFormula())->toBeFalse()
            ->and($sheet->getCell('A1')->getValue())->toBe("'=SUM(1,2)")
            ->and($sheet->getCell('B1')->getValue())->toBe("'+62812")
            ->and($sheet->getCell('C1')->getValue())->toBe('aman');
    });

    test('xlsx tidak memakai apostrof untuk awalan berbahaya', function () {
        $sheet = textCellRoundTrip(makeTextCellExport([['=SUM(1,2)']]), 'xlsx')->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())->toBe('=SUM(1,2)');
    });

    test('binder bersama memperluas binder default Maatwebsite', function () {
        expect(new TextSafeValueBinder)->toBeInstanceOf(DefaultValueBinder::class);
    });
});

describe('arsitektur ekspor', function () {

    test('semua kelas konkret di app/Exports mengimplementasi WithCustomValueBinder', function () {
        $classes = textCellExportClasses();

        expect($classes)->not->toBeEmpty();

        foreach ($classes as $class) {
            expect(is_subclass_of($class, WithCustomValueBinder::class))
                ->toBeTrue("Kelas {$class} harus mengimplementasi WithCustomValueBinder");
        }
    });

    test('semua kelas konkret di app/Exports memakai trait WritesTextCells', function () {
        foreach (textCellExportClasses() as $class) {
            expect(in_array(WritesTextCells::class, class_uses_recursive($class), true))
                ->toBeTrue("Kelas {$class} harus memakai trait WritesTextCells (langsung atau lewat base)");
        }
    });

    test('base export membawa binder bersama', function () {
        expect(is_subclass_of(BaseExport::class, WithCustomValueBinder::class))->toBeTrue()
            ->and(is_subclass_of(SimpleCrudExport::class, WithCustomValueBinder::class))->toBeTrue();
    });
});
