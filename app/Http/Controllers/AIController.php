<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    public function generateDescription(Request $request)
    {
        $name = $request->input('name');
        $category = $request->input('category');
        $wordCount = (int) $request->input('word_count', 80);
        $requirement = trim($request->input('requirement', ''));
        // Demo: sinh mô tả mẫu, lặp lại cho đủ số từ, có chèn yêu cầu
        $base = "Sản phẩm $name thuộc danh mục $category nổi bật với chất lượng tuyệt vời, phù hợp nhiều nhu cầu sử dụng. ";
        if ($requirement) {
            $base .= "Yêu cầu: $requirement. ";
        }
        $base .= "Hãy trải nghiệm ngay hôm nay!";
        $words = explode(' ', $base);
        while (count($words) < $wordCount) {
            $words = array_merge($words, explode(' ', $base));
        }
        $desc = implode(' ', array_slice($words, 0, $wordCount));
        return response()->json(['description' => $desc]);
    }
}
