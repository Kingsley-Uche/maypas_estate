<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\{LandlordVerificationMail};
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\LandlordAgent;

class UserController extends Controller
{
    public function viewOwn(Request $request){
        $user = $request->user();

        $user = User::where('id', $user->id)
        ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'user_type_id', 'created_at']) // fix: created_at not create_at
        ->with([
            'landlordAgent:id,user_id,business_name,business_state,business_lga,about_business,business_services,business_address,logo,verified',
            'user_type:id,name'
        ])
        ->first();

        if(!$user){
            return response()->json(['message'=>'Not found'], 404);
        }

        return response()->json(['data'=> $user], 200);
    }

    public function update(Request $request){
        $userToView = $request->user();

        $validator = Validator::make($request->all(), [
            'phone' => [
                'required',
                'string',
                Rule::unique('users', 'phone')->ignore($userToView->id),
                ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($userToView->id);

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone = $request->phone;

        $response = $user->update();

        if(!$response){
            return response()->json(['message'=>'Something went wrong. Try again later'], 500);
        }

        return response()->json(['message' =>'Profile updated successfully'], 201);

    }

    public function deactivate(Request $request){
        $userToView = $request->user();

        $user = User::findOrFail($userToView->id);

        $user->deactivated = 'yes';

        $response = $user->update();

        if(!$response){
            return response()->json(['message'=>'Something went wrong.Try again later'], 500);
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' =>'Profile deactivated'], 201);

    }

    public function completeLandlordProfile(Request $request){
        $user = app('landlord');

        // Validate request
        $validated = $request->validate([
            'id_card'           => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'selfie_photo'      => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'cac'               => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
            'business_name'     => 'required|string|max:255',
            'business_state'    => 'required|string|max:255',
            'business_lga'      => 'required|string|max:255',
            'about_business'    => 'nullable|string',
            'business_services' => 'nullable|string',
            'business_address'  => 'required|string|max:500',
            'logo'              => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $validated['user_id'] = $user->id;

        // Handle file uploads (store only filenames)
        if ($request->hasFile('id_card')) {
            $filename = time() . '_id_card.' . $request->id_card->extension();
            $request->id_card->storeAs('id_cards', $filename, 'public');
            $validated['id_card'] = $filename;
        }

        if ($request->hasFile('selfie_photo')) {
            $filename = time() . '_selfie.' . $request->selfie_photo->extension();
            $request->selfie_photo->storeAs('selfies', $filename, 'public');
            $validated['selfie_photo'] = $filename;
        }

        if ($request->hasFile('cac')) {
            $filename = time() . '_cac.' . $request->cac->extension();
            $request->cac->storeAs('cac_files', $filename, 'public');
            $validated['cac'] = $filename;
        }

        if ($request->hasFile('logo')) {
            $filename = time() . '_logo.' . $request->logo->extension();
            $request->logo->storeAs('logos', $filename, 'public');
            $validated['logo'] = $filename;
        }

        // Create record
        $landlordAgent = LandlordAgent::create($validated);

        return response()->json([
            'message' => 'Record created successfully',
            'data'    => $landlordAgent
        ], 201);
    }

    public function fetchLandlordsForVerification(){
        $users = User::whereHas('landlordAgent', function ($query) {
            $query->whereNotNull('id_card');
        })->with('landlordAgent')->get();

        return response()->json($users);
    }

    public function verifyLandlordDocuments($userId)
    {
        $landlord = LandlordAgent::where('user_id', $userId)->with('user')->firstOrFail();

        $updated = $landlord->update(['verified' => 'yes']);

        if (!$updated) {
            return response()->json([
                'message' => 'Something went wrong. Please try again later'
            ], 500);
        }

        $messageContent = [
            'name' => $landlord->user->first_name,
            'email' => $landlord->user->email,
            //'message' => 'Successfully Verified',
        ];

        // Send OTP via email
        try {
            Mail::to($landlord->user->email)->send(new LandlordVerificationMail($messageContent));
        } catch (\Exception $e) {
            $updated = $landlord->update(['verified' => 'no']);
            // Log and respond to mail failure
            return response()->json(['message' => 'Failed to send Verification response Mail. Please try again.'], 500);
        }

        return response()->json([
            'message' => 'Verification status successfully updated'
        ], 200);
    }

    public function rejectLandlordDocuments(Request $request, $userId){
        $user = LandlordAgent::where('user_id', $userId)->with('user')->firstOrFail();

        if($user->verified === 'yes'){
            return response()->json(['message'=>'This user has already been verified'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $response = $user->delete();

        if(!$response){
            return response()->json(['message'=>'Something went wrong. Please try again later'], 500);
        }

        $messageContent = [
            'name' => $user->user->first_name,
            'email' => $user->user->email,
            'message' => $request->reason,
        ];

        // Send OTP via email
        try {
            Mail::to($user->user->email)->send(new LandlordVerificationMail($messageContent));
        } catch (\Exception $e) {
            //respond to mail failure
            return response()->json(['message' => 'Failed to send Verification response Mail. Please try again.'], 500);
        }

        return response()->json([
            'message' => 'Verification status successfully updated'
        ], 200);

    }


}
