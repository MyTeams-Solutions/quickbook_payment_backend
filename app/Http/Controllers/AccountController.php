<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|string',
            'exp_month' => 'required|digits:2',
            'exp_year' => 'required|digits:4',
            'cvc' => 'required|string|min:3|max:4',
            'name' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'user_id' => 'nullable',
        ]);

        // $account = Account::create(array_merge($validated, [ 'user_id' => $request->user()->id,]));
        $account = Account::create(array_merge($validated));

        return response()->json([
            'message' => 'Account saved successfully.',
            'data' => $account,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $account = Account::where('user_id', $id)->get();

        if (!$account) {
            return response()->json([
                'message' => 'Account not found.',
            ], 404);
        }

        return response()->json([
            'data' => $account,
        ]);
    }
}