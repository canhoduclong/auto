<?php
namespace App\Imports;

use App\Models\Company;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CompanyImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $userId;

    public function __construct()
    {
        $this->userId = auth()->id();
    }

    public function model(array $row)
    {
        $company = new Company([
            'name' => $row['name'] ?? null,
            'mst' => $row['mst'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $row['address'] ?? null,
            'note' => $row['note'] ?? null,
            'assigned_to' => $this->userId,
        ]);
        $company->save();
        return $company;
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.mst' => 'nullable|string|max:255',
            '*.phone' => 'nullable|string|max:30',
            '*.email' => 'nullable|email|unique:companies,email',
            '*.address' => 'nullable|string|max:1000',
        ];
    }
}
