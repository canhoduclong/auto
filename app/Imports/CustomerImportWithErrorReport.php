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
            // Map Vietnamese/English column names to internal keys
            $map = [
                'tên khách hàng' => 'name',
                'email' => 'email',
                'số điện thoại' => 'phone',
                'địa chỉ' => 'address',
                'thời gian giao hàng' => 'delivery_time',
                'size' => 'size',
                'sản lượng' => 'production',
                // English fallback
                'name' => 'name',
                'phone' => 'phone',
                'address' => 'address',
                'delivery_time' => 'delivery_time',
                'production' => 'production',
            ];
            $data = [];
            foreach ($map as $col => $key) {
                if (isset($row[$col])) {
                    $data[$key] = $row[$col];
                }
            }
            // Ensure phone is string
            if (isset($data['phone'])) {
                $data['phone'] = (string)$data['phone'];
            }
            // Validate required fields
            if (empty($data['name'])) {
                throw new \Exception('Trường "Tên khách hàng" (name) là bắt buộc.');
            }
            if (empty($data['address'])) {
                throw new \Exception('Trường "Địa chỉ" (address) là bắt buộc.');
            }
            $customer = new Customer([
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'delivery_time' => $data['delivery_time'] ?? null,
                'size' => $data['size'] ?? null,
                'production' => $data['production'] ?? null,
                'assigned_to' => $this->userId,
            ]);
            $customer->save();
            \App\Models\CustomerAddress::create([
                'customer_id' => $customer->id,
                'note' => $data['address'],
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
        // Accept both Vietnamese and English column names
        return [
            '*.tên khách hàng' => 'required|string|max:255',
            '*.name' => 'required_without:tên khách hàng|string|max:255',
            '*.số điện thoại' => 'nullable|string|max:30',
            '*.phone' => 'nullable|string|max:30',
            '*.địa chỉ' => 'required|string',
            '*.address' => 'required_without:địa chỉ|string',
            '*.email' => 'nullable|email|unique:customers,email',
            '*.thời gian giao hàng' => 'nullable|string|max:255',
            '*.delivery_time' => 'nullable|string|max:255',
            '*.size' => 'nullable|string|max:255',
            '*.sản lượng' => 'nullable|numeric',
            '*.production' => 'nullable|numeric',
        ];
    }
    public function getImported()
    {
        return $this->imported;
    }
}
