<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSpreadsheetService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductSpreadsheetController extends Controller
{
    public function export(Request $request, ProductSpreadsheetService $files)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $filters = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'status_filter' => ['nullable', 'in:active,deleted,all'],
        ]);
        $products = Product::query()->with('variants.latestPriceRule')
            ->when($filters['name'] ?? null, fn ($query, $name) => $query->where('name', 'like', '%'.$name.'%'))
            ->when($filters['category_id'] ?? null, fn ($query, $category) => $query->where('category_id', $category));
        $status = $filters['status_filter'] ?? 'active';
        if ($status !== 'all') $products->where('status', $status === 'active');
        return $this->download($files->workbook($files->productRows($products->lazyById(200))), 'san-pham-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function template(Request $request, ProductSpreadsheetService $files)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $book = $files->workbook([]);
        $guide = $book->createSheet()->setTitle('Huong dan');
        $guide->fromArray([
            ['HƯỚNG DẪN NHẬP SẢN PHẨM'],
            ['Điền dữ liệu ở sheet San pham, giữ nguyên tên và thứ tự cột. Mỗi dòng là một SKU.'],
            ['product_id: để trống khi thêm mới; lấy ID từ file xuất để xác định chính xác sản phẩm khi sửa.'],
            ['product_name: tên sản phẩm; các dòng cùng sản phẩm phải có cùng thông tin chung.'],
            ['category_id: ID danh mục có sẵn, bắt buộc khi thêm sản phẩm; xem sheet Danh muc.'],
            ['unit: con, kg, bo, banh hoặc cai. product_type: whole (nguyên con) hoặc cut (pha lóc).'],
            ['product_kg: kg mỗi đơn vị sản phẩm (để trống mặc định 1 kg). is_priced_by_kg: 1 tính giá/kg, 0 tính giá/đơn vị.'],
            ['product_status và variant_status: 1 đang hoạt động, 0 ngừng hoạt động.'],
            ['variant_is_priced_by_kg: 1 tính giá/kg, 0 tính giá/đơn vị cho biến thể; để trống để dùng giá trị hiện có hoặc theo sản phẩm.'],
            ['variant_id: giữ nguyên ID từ file xuất khi sửa; để trống khi tạo biến thể mới.'],
            ['sku: mã duy nhất của biến thể. Giữ nguyên SKU khi cập nhật; SKU mới tạo biến thể mới.'],
            ['variant_name: tên biến thể; size: quy cách dạng số (ví dụ 2.3); variant_kg: kg mỗi đơn vị, không nhập tổng kg của cả lô.'],
            ['inventory_name: tên kho, phải duy nhất nếu có nhập. description: mô tả sản phẩm.'],
            ['price / min_price: giá bán / giá tối thiểu. Để trống price để giữ nguyên giá khi cập nhật.'],
            ['Số tiền không có dấu phân cách hàng nghìn. Khối lượng có thể nhập 2.3 hoặc 2,3.'],
            ['Sản phẩm chưa có biến thể: để trống tất cả các cột từ variant_id đến min_price.'],
            ['Chỉ thêm mới: từ chối sản phẩm/SKU đã có. Cập nhật: tìm theo product_id, SKU, rồi tên + danh mục.'],
            ['Thông tin chung, trạng thái và biến thể có trong file sẽ được cập nhật theo file. Không xóa các biến thể vắng trong file.'],
            ['Không nhập tồn kho hay hình ảnh bằng file này. Nhập/xuất kho được quản lý riêng.'],
            ['Tối đa 2.000 dòng/file, 5 MB. Có lỗi sẽ hủy toàn bộ lần nhập và báo dòng bị lỗi.'],
            ['Ví dụ (thay category_id bằng ID thật; không sao chép dòng hướng dẫn vào sheet dữ liệu):'],
            ProductSpreadsheetService::HEADERS,
            [null, 'Vịt nguyên con mẫu', Category::query()->value('id'), 'con', 'whole', '2.3', 1, 1, null, null, 'VIT-MAU-23', 'Vịt size 2.3', '2.3', '2.3', null, 1, 1, '66000', '60000'],
        ], null, 'A1', true);
        $guide->getColumnDimension('A')->setWidth(110);
        $guide->getStyle('A1')->getFont()->setBold(true);
        $categories = $book->createSheet()->setTitle('Danh muc');
        $categories->fromArray([['category_id', 'Tên danh mục']], null, 'A1');
        foreach (Category::orderBy('name')->get(['id', 'name']) as $index => $category) {
            $categories->setCellValueExplicit([1, $index + 2], (string) $category->id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $categories->setCellValueExplicit([2, $index + 2], $category->name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        $categories->getColumnDimension('A')->setWidth(20);
        $categories->getColumnDimension('B')->setWidth(50);
        $book->setActiveSheetIndex(0);
        return $this->download($book, 'mau-nhap-san-pham.xlsx');
    }

    public function import(Request $request, ProductSpreadsheetService $files)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120'],
            'mode' => ['required', 'in:create,upsert'],
        ]);
        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'csv'], true)) throw ValidationException::withMessages(['file' => 'Chỉ nhận file .xlsx hoặc .csv.']);
        try {
            $rows = $files->read($request->file('file')->getRealPath(), $extension);
            $result = $files->import($rows, (int) $request->user()->id, $request->input('mode') === 'upsert');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['file' => 'Không thể nhập file. Chưa lưu dữ liệu; kiểm tra định dạng theo file mẫu.']);
        }
        return redirect()->route('products.index')->with('success', sprintf(
            'Đã nhập file: %d sản phẩm mới, %d sản phẩm cập nhật, %d biến thể mới, %d biến thể cập nhật.',
            $result['products_created'], $result['products_updated'], $result['variants_created'], $result['variants_updated']
        ));
    }

    private function download(\PhpOffice\PhpSpreadsheet\Spreadsheet $book, string $name)
    {
        return response()->streamDownload(function () use ($book) {
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
