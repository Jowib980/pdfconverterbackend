<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentDetails;
    use Illuminate\Support\Carbon;

class PaymentController extends Controller
{
    //

    public function index()
    {
        $payments = PaymentDetails::orderByDesc('created_at')->paginate(10);

        return view('admin.payments.index', compact('payments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer',
            'payer_email' => 'required|email',
            'payer_id' => 'nullable|string',
            'payer_name' => 'nullable|string',
            'plan_type' => 'required|string',
            'plan_amount' => 'required|numeric',
            'transaction_id' => 'required|string',
            'transaction_status' => 'required|string',
            'payment_date' => 'required|string',
            'gateway' => 'required|in:paypal,razorpay',
            'currency' => 'required|string',
            'raw_response' => 'nullable|string',
        ]);

        $validated['payment_date'] = Carbon::parse($validated['payment_date'])->format('Y-m-d H:i:s');

        $payment = PaymentDetails::create($validated);

        return response()->json(['message' => 'Payment saved']);
    }

    public function view(Request $request, $id)
    {
        $payment = PaymentDetails::findOrFail($id);

        return view('admin.payments.view', compact('payment'));
    }


    public function destroy(Request $request, $id)
    {
        $data = PaymentDetails::find($id);

        if (!$data) {
            return redirect()->back()->with('error', 'Payment record not found!');
        }

        $data->delete();
        return redirect()->back()->with('message', 'Delete Payment record successfully!');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            PaymentDetails::whereIn('id', $ids)->delete();
            return redirect()->back()->with('message', 'Selected payment record deleted.');
        }

        return redirect()->back()->with('error', 'No payment rocord selected.');
    }


}
