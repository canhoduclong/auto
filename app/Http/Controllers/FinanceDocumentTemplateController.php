<?php
namespace App\Http\Controllers;
use App\Models\FinanceDocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class FinanceDocumentTemplateController extends Controller {
    public function index() {
        return view('accounting.document_templates', ['templates'=>FinanceDocumentTemplate::orderBy('id')->get()]);
    }
    public function store(Request $request) {
        FinanceDocumentTemplate::create($this->data($request));
        return back()->with('success','Đã thêm mẫu chứng từ.');
    }
    public function update(Request $request, FinanceDocumentTemplate $template) {
        $template->update($this->data($request, $template));
        return back()->with('success','Đã cập nhật mẫu chứng từ. Phiếu đã tạo giữ nguyên thông tin cũ.');
    }
    private function data(Request $request, ?FinanceDocumentTemplate $template = null): array {
        $request->merge(['name'=>trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name'=>['required','string','max:255',Rule::notIn(['__custom__']),Rule::unique('finance_document_templates')->ignore($template?->id)],
            'form_type'=>['required',Rule::in(['cash_request','payment_proposal','advance_request'])],
            'flow_direction'=>['required',Rule::in(['in','out','both'])],
            'is_active'=>['required','boolean'],
        ]);
        if ($data['form_type'] !== 'cash_request') $data['flow_direction'] = 'out';
        return $data;
    }
}
