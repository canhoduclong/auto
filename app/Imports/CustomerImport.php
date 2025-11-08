<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Facades\Excel;

class CustomerImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable;

    private $importedCount = 0;
    private $failedCount = 0;
    private $failedRows = [];

    public function model(array $row)
    {
        $this->importedCount++;

        return new Customer([
            'name'     => $row['name'],
            'email'    => $row['email'],
            'phone'    => $row['phone'],
        ]);
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.email' => 'required|email|unique:customers,email',
            '*.phone' => 'nullable|string|max:20',
        ];
    }

    public function onError(\Throwable $e)
    {
        // This method is called when a general error occurs during import.
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failedCount++;
            $this->failedRows[] = [
                'row' => $failure->row(),
                'error' => $failure->errors()[0],
                'data' => $failure->values(),
            ];
        }
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function getFailedRows(): array
    {
        return $this->failedRows;
    }
}
