<?php
namespace App\Imports;

use App\Models\Company;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class CompanyImportWithErrorReport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;
    public $imported = [];
    protected $userId;

    public function __construct()
    {
        $this->userId = auth()->id();
    }

    public function model(array $row)
    {
        try {
            $row['phone'] = isset($row['phone']) ? (string)$row['phone'] : null;
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
            $this->imported[] = [
                'row' => $row,
                'status' => 'success',
                'company_id' => $company->id,
            ];
            return $company;
        } catch (\Exception $e) {
            $this->imported[] = [
                'row' => $row,
                'status' => 'fail',
                'error' => $e->getMessage(),
            ];
            return null;
        }
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
    public function getImported()
    {
        return $this->imported;
    }
}
