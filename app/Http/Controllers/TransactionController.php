<?php
namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Transaction;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Transaction::class);
        $transactions = Transaction::with(['order', 'customer'])
            ->orderByDesc('created_at')
            ->paginate(20);
        return view('transactions.index', compact('transactions'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Transaction::class);
        $orders = Order::all();
        $customers = Customer::all();
        return view('transactions.create', compact('orders', 'customers'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Transaction::class);
        $data = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'customer_id' => 'nullable|exists:customers,id',
            'amount' => 'required|numeric',
            'type' => 'required|string',
            'method' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:255',
        ]);
        $transaction = Transaction::create($data);

        if ($transaction->order_id) {
            $order = $transaction->order;
            $totalPaid = $order->transactions()->where('type', 'payment')->sum('amount') - $order->transactions()->where('type', 'refund')->sum('amount');
            $order->amount_paid = $totalPaid;

            if ($totalPaid >= $order->total) {
                $order->payment_status = 'paid';
            } elseif ($totalPaid > 0) {
                $order->payment_status = 'partially_paid';
            } else {
                $order->payment_status = 'unpaid';
            }

            if ($order->status === Order::STATUS_ORDER_PLACED) {
                $order->status = Order::STATUS_ORDER_CONFIRMED;
            }
            
            $order->save();
        }

        return redirect()->route('transactions.index')->with('success', 'Giao dịch đã được ghi nhận.');
    }
}