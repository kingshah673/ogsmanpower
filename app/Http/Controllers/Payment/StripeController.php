<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Traits\PaymentTrait;
use App\Models\BilangualResumeSubscription;
use App\Models\Candidate;
use App\Models\CandidateSubscription;
use Illuminate\Http\Request;
use Stripe\Stripe;
use App\Services\PlanService;
use Modules\Plan\Entities\Plan;
use Stripe\Checkout\Session;

class StripeController extends Controller
{
    use PaymentTrait;

    /**
     * Main Stripe Payment
     */
    public function stripePost(Request $request)
    {
        try {

            // Getting payment info from session
            $job_payment_type = session('job_payment_type') ?? 'package_job';

            if ($job_payment_type == 'per_job') {

                $price = session('job_total_amount') ?? 100;

                $productName = 'Job Post Payment';

            } else {

                $plan = session('plan');
                session([
    'selected_plan_id' => $plan->id
]);

                $price = $plan->price ?? 100;

                $productName = $plan->name ?? 'Package Plan';
            }

            // Amount conversion
            $converted_amount = currencyConversion($price);

            // Store payment info in session
            session([
                'order_payment' => [
                    'payment_provider' => 'stripe',
                    'amount' => $converted_amount,
                    'currency_symbol' => '$',
                    'usd_amount' => $converted_amount,
                ]
            ]);

            Stripe::setApiKey(config('templatecookie.stripe_secret'));

            // Stripe Checkout Session
            $checkout_session = Session::create([

                'payment_method_types' => ['card'],

                'line_items' => [[

                    'price_data' => [

                        'currency' => 'usd',

                        'unit_amount' => (int) ($converted_amount * 100),

                        'product_data' => [
                            'name' => $productName,
                        ],

                    ],

                    'quantity' => 1,

                ]],

                'mode' => 'payment',

                'success_url' => route('stripe.success'),

                'cancel_url' => route('stripe.cancel'),

            ]);

            return redirect($checkout_session->url);

        } catch (\Exception $e) {

            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Stripe Success
     */
    /**
 * Stripe Success
 */
public function success()
{
    try {

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION ID
        |--------------------------------------------------------------------------
        */

        session([
            'transaction_id' => 'stripe_' . time()
        ]);

        /*
        |--------------------------------------------------------------------------
        | PLACE ORDER
        |--------------------------------------------------------------------------
        */

        $this->orderPlacing();

        /*
        |--------------------------------------------------------------------------
        | GET PLAN
        |--------------------------------------------------------------------------
        */

        $planId = session('selected_plan_id');

        if ($planId) {

            $plan = Plan::find($planId);

            /*
            |--------------------------------------------------------------------------
            | ASSIGN PLAN
            |--------------------------------------------------------------------------
            */

            if ($plan) {

                PlanService::assign(

                    auth()->user(),

                    $plan

                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('website.home')
            ->with(
                'success',
                'Payment Successful & Plan Activated'
            );

    } catch (\Exception $e) {

        return redirect()
            ->route('website.home')
            ->with(
                'error',
                $e->getMessage()
            );
    }
}

    /**
     * Stripe Cancel
     */
    public function cancel()
    {
        return redirect()
            ->back()
            ->with('error', 'Payment Cancelled');
    }

    /**
     * Candidate Stripe Payment
     */
    public function candidateStripe(Request $request)
    {
        try {

            $validated = $request->validate([
                'candidate_id' => 'required',
                'plan_id' => 'required',
                'duration' => 'required',
                'amount' => 'required',
            ]);

            // Store session data
            session([
                'candidate_payment' => $validated
            ]);

            Stripe::setApiKey(config('templatecookie.stripe_secret'));

            // Stripe Checkout Session
            $checkout_session = Session::create([

                'payment_method_types' => ['card'],

                'line_items' => [[

                    'price_data' => [

                        'currency' => 'usd',

                        'unit_amount' => (int) ($request->amount * 100),

                        'product_data' => [
                            'name' => 'Candidate Featured Plan',
                        ],

                    ],

                    'quantity' => 1,

                ]],

                'mode' => 'payment',

                'success_url' => route('candidate.stripe.success'),

                'cancel_url' => route('stripe.cancel'),

            ]);

            return redirect($checkout_session->url);

        } catch (\Exception $e) {

            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Candidate Stripe Success
     */
    public function candidateStripeSuccess()
    {
        try {

            $data = session('candidate_payment');

            $candidate = Candidate::findOrFail($data['candidate_id']);

            CandidateSubscription::create([
                'candidate_id' => $candidate->id,
                'candidate_plan_id' => $data['plan_id'],
                'duration' => $data['duration'],
                'payment_type' => 'online',
                'status' => 'approved',
            ]);

            $candidate->update([
                'is_candidate_featured' => '1',
            ]);

            return redirect()
                ->route('candidate.plan')
                ->with('success', 'Plan Subscribe Successfully.');

        } catch (\Exception $e) {

            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Bilangual Resume Stripe Payment
     */
    public function bilangualStripe(Request $request)
    {
        try {

            $validated = $request->validate([
                'candidate_id' => 'required',
                'language_code' => 'required',
                'amount' => 'required',
            ]);

            // Store session data
            session([
                'bilangual_payment' => $validated
            ]);

            Stripe::setApiKey(config('templatecookie.stripe_secret'));

            // Stripe Checkout Session
            $checkout_session = Session::create([

                'payment_method_types' => ['card'],

                'line_items' => [[

                    'price_data' => [

                        'currency' => 'usd',

                        'unit_amount' => (int) ($request->amount * 100),

                        'product_data' => [
                            'name' => 'Bilangual Resume Download',
                        ],

                    ],

                    'quantity' => 1,

                ]],

                'mode' => 'payment',

                'success_url' => route('bilangual.stripe.success'),

                'cancel_url' => route('stripe.cancel'),

            ]);

            return redirect($checkout_session->url);

        } catch (\Exception $e) {

            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Bilangual Resume Success
     */
    public function bilangualStripeSuccess()
    {
        try {

            $data = session('bilangual_payment');

            $candidate = Candidate::findOrFail($data['candidate_id']);

            BilangualResumeSubscription::create([
                'candidate_id' => $candidate->id,
                'language_code' => $data['language_code'],
                'status' => 'approved',
                'payment_method' => 'online',
            ]);

            return redirect()
                ->route('candidate.view.cv')
                ->with('success', 'Plan Subscribe Successfully.');

        } catch (\Exception $e) {

            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}