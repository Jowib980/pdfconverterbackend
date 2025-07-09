<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentDetails;
use Illuminate\Support\Carbon;
use Razorpay\Api\Api;
use Illuminate\Support\Str;
use App\Models\PaymentGateway;

class PaymentController extends Controller
{
    //

    public function allGateway(Request $request)
    {
        $gateways = PaymentGateway::orderByDesc('created_at')->paginate(10);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $gateways
            ]);
        }

        return view('admin.payments.payment-gateway', compact('gateways'));
    }

    public function create() {
        return view('admin.payments.create-gateway');
    }

    public function storeGateway(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'is_enabled' => 'required|boolean',
            'client_id' => 'nullable|string',
            'client_secret' => 'nullable|string',
        ]);

        PaymentGateway::create([
            'name' => $request->input('name'),
            'is_enabled' => $request->input('is_enabled'),
            'client_id' => $request->input('client_id') ?? '',
            'client_secret' => $request->input('client_secret') ?? '',
        ]);

        return redirect()->route('all-gateways')->with('success', 'Payment Gateway created successfully!');
    }


     public function editGateway(Request $request, $id)
    {
        $payment = PaymentGateway::find($id);

        return view('admin.payments.edit-gateway', compact('payment'));
    }

    public function updateGateway(Request $request, $id)
    {

        try {
            $data = PaymentGateway::findOrFail($id);

            $data->name = $request->input('name');
            $data->is_enabled = $request->input('is_enabled');
            $data->client_id = $request->input('client_id') ?? '';
            $data->client_secret = $request->input('client_secret') ?? '';

            $data->save();

            return redirect()->route('all-gateways')->with('success', 'Payment Gateway updated successfully!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Payment gateway not found.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the payment gateway.');
        }
    }




    public function destroyGateway(Request $request, $id)
    {
        $data = PaymentGateway::find($id);

        if (!$data) {
            return redirect()->back()->with('error', 'Payment gateway not found!');
        }

        $data->delete();
        return redirect()->back()->with('message', 'Delete Payment gateway successfully!');
    }

    public function bulkDeleteGateway(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            PaymentGateway::whereIn('id', $ids)->delete();
            return redirect()->back()->with('message', 'Selected payment gateway deleted.');
        }

        return redirect()->back()->with('error', 'No payment gateway selected.');
    }



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


    public function createRazorpayOrder(Request $request)
    {
        $api = new \Razorpay\Api\Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        $order = $api->order->create([
            'receipt' => Str::uuid(),
            'amount' => $request->amount * 100, // INR in paise
            'currency' => 'INR'
        ]);

        return response()->json(['order_id' => $order->id]);
    }

    public function currentPlan(Request $request, $id) {
        $plan = PaymentDetails::where('user_id', $id)->latest()->first();

        if (!$plan) {
            return response()->json(['message' => 'No plan found'], 404);
        }

        return response()->json($plan); // success response
    }

}
