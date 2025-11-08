<?php
namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class CustomerImportWithErrorReport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
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
            // Ép kiểu phone về string
            $row['phone'] = isset($row['phone']) ? (string)$row['phone'] : null;
            // Kiểm tra address rõ ràng
            if (!isset($row['address']) || trim($row['address']) === '') {
                throw new \Exception('Trường address (địa chỉ) là bắt buộc và không được để trống.');
            }
            $customer = new Customer([
                'name' => $row['name'] ?? null,
                'phone' => $row['phone'] ?? null,
                'email' => $row['email'] ?? null,
                'website' => $row['website'] ?? null,
                'gender' => $row['gender'] ?? null,
                'dob' => $row['dob'] ?? null,
                'customer_type_id' => $row['customer_type_id'] ?? null,
                'note' => $row['note'] ?? null,
                'assigned_to' => $this->userId,
            ]);
            $customer->save();
            CustomerAddress::create([
                'customer_id' => $customer->id,
                'note' => $row['address'],
                'is_default' => 1,
            ]);
            $this->imported[] = [
                'row' => $row,
                'status' => 'success',
                'customer_id' => $customer->id,
            ];
            return $customer;
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
            '*.phone' => 'required|string|max:30',
            '*.address' => 'required|string',
            '*.email' => 'nullable|email|unique:customers,email',
            '*.website' => 'nullable|url',
        ];
    }
    public function getImported()
    {
        return $this->imported;
    }
}
