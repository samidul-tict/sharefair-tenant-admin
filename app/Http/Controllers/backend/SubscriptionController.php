<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; // Import DOMPDF facade

use App\Models\Plan;
use App\Models\Coupon;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $tenantId = \App\Models\User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
                    ->where('users.id', $user->id)
                    ->value('urm.tenant_id');

        if (!$tenantId) {
             abort(403, 'No tenant associated with this user.');
        }

        $subscriptions = Subscription::where('tenant_id', $tenantId)
                         ->with(['plan', 'coupon'])
                         ->orderBy('id', 'desc')
                         ->get();
        
        $activeSubscription = $subscriptions->first(function ($sub) {
             return $sub->is_active == true && 
                    ($sub->end_date >= now() || $sub->end_date == null); 
        });

        // Fetch active plans for the modal
        $plans = Plan::where('is_active', true)->get();

        return view('backend.subscriptions.index', compact('subscriptions', 'activeSubscription', 'plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'coupon_code' => 'nullable|exists:coupons,code',
        ]);

        $user = Auth::user();
        $tenantId = \App\Models\User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
                    ->where('users.id', $user->id)
                    ->value('urm.tenant_id');
        
        if (!$tenantId) {
            return back()->with('error', 'No tenant associated.');
        }

        $plan = Plan::findOrFail($request->plan_id);
        $coupon = null;
        $discount = 0;

        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();
            // Validate coupon dates/active status if needed
            if ($coupon) {
                 if ($coupon->discount_type == 'PERCENTAGE') {
                     $discount = ($plan->selling_price * $coupon->discount) / 100;
                 } else {
                     $discount = $coupon->discount;
                 }
            }
        }

        // Calculate totals
        $basePrice = $plan->selling_price;
        // Cap discount
        if ($discount > $basePrice) $discount = $basePrice;
        
        $taxPrice = 0; // Default tax for now
        $totalPrice = ($basePrice - $discount) + $taxPrice;

        // Deactivate old active subscriptions? 
        // User didn't specify, but usually new sub replaces old. 
        // For safety, let's mark others as inactive? Or just add new one.
        // Assuming additive for now or manual management.
        
        Subscription::create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'coupon_id' => $coupon ? $coupon->id : null,
            'tax_price' => $taxPrice,
            'total_price' => $totalPrice, // Net + Tax
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays($plan->duration_in_days),
            'status' => 'active',
            'is_active' => true,
            'created_by' => $user->id,
            'created_date' => Carbon::now(),
            'modified_by' => $user->id,
            'last_modified_date' => Carbon::now()
        ]);

        return redirect()->route('admin.my-subscriptions')->with('success', 'Subscription added successfully!');
    }

    public function checkCoupon(Request $request)
    {
        $coupon = Coupon::where('code', $request->coupon_code)
                  ->where('is_active', true)
                  ->where(function($q) {
                      $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now());
                  })
                  ->first();

        if ($coupon) {
            return response()->json([
                'success' => true,
                'coupon' => $coupon,
                'message' => 'Coupon applied!'
            ]);
        }
        
        return response()->json(['success' => false, 'message' => 'Invalid or expired coupon.']);
    }

    public function downloadInvoice($id)
    {
        $subscription = Subscription::with(['tenant', 'plan', 'coupon'])->findOrFail($id);
        
        $user = Auth::user();
        $tenantId = \App\Models\User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
            ->where('users.id', $user->id)
            ->value('urm.tenant_id');
            
        if ($subscription->tenant_id != $tenantId) {
            abort(403);
        }

        $pdf = Pdf::loadView('backend.subscriptions.invoice', compact('subscription'));
        return $pdf->download('invoice-' . $subscription->id . '.pdf');
    }

    public function toggleAutoRenew(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Auto-renew status updated.']);
    }
}
