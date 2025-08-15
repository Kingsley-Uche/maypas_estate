<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetLinkMail;
use Illuminate\Validation\Rule;

use App\Models\{Admin,User,EstateManager,LandlordAgent};

class EstateManagerController extends Controller
{
    public function create(Request $request){
        $admin = $request->user();

        $role = Admin::where('id', $admin->id)->select('id', 'role_id')->with(['role:id,manage_estate_manager'])->get();

        if($role[0]['role']['manage_estate_manager'] !== 'yes'){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }
       // Validate request data
       $validator = Validator::make($request->all(), [
            'estate_name' => 'required|string|max:255|unique:estate_managers,estate_name',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|',
            'phone' => 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
       ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Retrieve validated data from the validator instance
        $validatedData = $validator->validated();

        $sanitizedSlug = strtolower(str_replace(' ', '', $request->estate_name));

        $createdby = '';

        if($admin->role_id == 1){
            $createdby = null;
        }else{
            $createdby = $admin->id;
        }

        $estateManager = EstateManager::create([
            'estate_name'=> $validatedData['estate_name'],
            'slug' => $sanitizedSlug,
            'created_by_admin_id' => $createdby,
            'subscription_id' => null,
        ]); 

        if(!$estateManager){
            return response()->json(['message'=> 'something went wrong, please try again'],500);
        }

        $user = User::create([
            'first_name' => htmlspecialchars($validatedData['first_name'], ENT_QUOTES, 'UTF-8'),
            'last_name' => htmlspecialchars($validatedData['last_name'], ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($validatedData['email'], FILTER_SANITIZE_EMAIL),
            'phone' => htmlspecialchars($validatedData['phone'], ENT_QUOTES, 'UTF-8'),
            'user_type_id' => 1,
            'password' => hash::make('TestingPassword'),
            'estate_manager_id' => $estateManager->id,
        ]);

       if(!$user){
            return response()->json(['message'=>'Something went wrong'], 500);
        }

        $landlordAgent = LandlordAgent::create([
            'user_id' => $user->id
        ]);

        if(!$landlordAgent){
            $user->delete();
            return response()->json(['message'=>'Something went wrong, Try again'], 500);
        }
        
        // Encrypt user ID for the token
        $encryptedId = Crypt::encryptString($user->id);

        $signature = hash_hmac('sha256', $encryptedId, config('app.key'));

        // Generate signed URL that expires in 30 minutes
        $resetUrl = config('app.frontend_url').'/'.$estateManager->slug.'/reset-password?token=' . urlencode($encryptedId) . '&signature=' . urlencode($signature);

        $messageContent = [
            'name' => $user->first_name,
            'email' => $user->email,
            'resetUrl' => $resetUrl,
        ];

        // Send OTP via email
        try {
            Mail::to($user->email)->send(new PasswordResetLinkMail($messageContent));
        } catch (\Exception $e) {
            // Log and respond to mail failure
            return response()->json(['message' => 'Failed to send password setting email. Please try again.'], 500);
        }
        
        return response()->json(['message' => 'User added successfully! An email has been sent to new admin to complete registration.', 'user' => $user], 201);
    }

    public function update(Request $request, $id){
        $admin = $request->user();

        $role = Admin::where('id', $admin->id)->select('id', 'role_id')->with(['role:id,manage_estate_manager'])->get();

        if($role[0]['role']['manage_estate_manager'] !== 'yes'){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }
       // Validate request data
        $validator = Validator::make($request->all(), [
            'estate_name' => [
                            'required',
                            'string',
                            Rule::unique('estate_managers', 'estate_name')->ignore($id),
                            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }    

        // Retrieve validated data from the validator instance
        $validatedData = $validator->validated();

        $sanitizedSlug = strtolower(str_replace(' ', '', $request->estate_name));

        $estateManager = EstateManager::findOrFail($id);

        $estateManager->estate_name = $validatedData['estate_name'];
        $estateManager->slug = $sanitizedSlug;

        $response = $estateManager->save();

        if(!$response){
            return response()->json(['message'=> 'something went wrong, please try again'],500);    
        }

        return response()->json(['message'=>'Estate Updated successfully', 'data'=>$estateManager], 200);
    }

    public function getEstateManager(Request $request, $id){
        $admin = $request->user();

        $role = Admin::where('id', $admin->id)->select('id', 'role_id')->with(['role:id,manage_estate_manager'])->get();

        if($role[0]['role']['manage_estate_manager'] !== 'yes'){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $estateManager = EstateManager::where('id', $id)->FirstOrFail();

        return response()->json(['data'=> $estateManager],200);
    }

    public function getEstateManagers(Request $request){
        $admin = $request->user();

        $role = Admin::where('id', $admin->id)->select('id', 'role_id')->with(['role:id,manage_estate_manager'])->get();

        if($role[0]['role']['manage_estate_manager'] !== 'yes'){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $estateManager = EstateManager::paginate(20);

        return response()->json(['data'=> $estateManager ],200);
    }

    public function destroy(Request $request)
    {
        $admin = $request->user();

        // Retrieve admin role with permission to manage estate Manager
        $adminWithRole = Admin::with('role:id,manage_estate_manager')
            ->select('id', 'role_id')
            ->find($admin->id);

        if (!$adminWithRole || $adminWithRole->role->manage_estate_manager !== 'yes') {
            return response()->json(['message' => 'You are not authorized to do this'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Attempt to delete estate manager and its users
        $estateManager = EstateManager::find($request->id);

        if (!$estateManager) {
            return response()->json(['message' => 'Estate Manager not found'], 404);
        }

        User::where('estate_manager_id', $estateManager->id)->delete();

        if (!$estateManager->delete()) {
            return response()->json(['message' => 'Failed to delete, try again later'], 500);
        }

        return response()->json(['message' => 'Estate Manager deleted successfully'], 200);
    }
}
