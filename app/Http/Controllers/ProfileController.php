<?php

namespace App\Http\Controllers;

use App\User;
use App\Rules\ValidETHAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use QrCode;

class ProfileController extends Controller
{
    /**
     * Reset the user's avatar to default.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function clearAvatar(Request $request)
    {
        $user = Auth::user();
        if ($user->avatar !== null) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
        }
        $user->save();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Update the user's avatar.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateAvatar(Request $request)
    {
        $rules = Validator::make($request->all(), [
            'avatar' => 'required|file|image|max:8000',
        ]);
        if ($rules->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $rules->errors()->all(),
            ], 400);
        }
        if (!$request->file('avatar')->isValid()) {
            return response()->json([
                'success' => false,
                'errors' => 'File was corrupted',
            ]);
        }
        $user = Auth::user();
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->avatar = Storage::disk('public')->putFile('userdata', $request->avatar);
        $user->save();

        return response()->json([
            'success' => true,
            'avatar' => $user->avatar,
        ]);
    }

    /**
     * Store the user's identification (such as a driver license).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateID(Request $request)
    {
        $rules = Validator::make($request->all(), [
            'id' => 'required|array|max:30',
            'id.*' => 'required|file|image|max:8000'
        ]);
        if ($rules->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $rules->errors()->all(),
            ], 400);
        }
        $user = Auth::user();
        foreach ($request->id as $img) {
            if (!$img->isValid()) {
                return response()->json([
                    'success' => false,
                    'errors' => [ 'File "' . $img->path() . '" was corrupted' ],
                ]);
            }
            Storage::putFile("private/{$user->id}", $img);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Update the text fields containing personal info in the user's profile.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateInfo(Request $request)
    {
        $plaintext_fields = [
            'first_name' => ['nullable', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:1', 'in:m,f,o'],
            //billing address?
        ];
        $special_fields = [
            'birthday' => ['nullable', 'date_format:Y-m-d', 'before:today'],
        ];
        $validator = Validator::make($request->all(), array_merge($plaintext_fields, $special_fields));
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 400);
        }
        $user = Auth::user();

        // If user is already verified, lock this info in (?)
        if ($user->verified) {
            return response()->json([
                'success' => false,
                'errors' => [ 'Your account info has already been verified.' ],
            ], 403);
        }

        // If any personal info needed for KYC is modified, then the user should be
        // stripped of their "verified" status.
        $modified = false;

        // See if any plaintext fields were updated
        foreach (array_keys($plaintext_fields) as $field) {
            if ($user->{$field} !== $request->{$field}) {
                $user->{$field} = $request->{$field};
                $modified = true;
            }
        }

        // Handle other kinds of fields
        if ($user->birthday === null ||
                $user->birthday->format('Y-m-d') !== $request->birthday) {
            $user->birthday = $request->birthday ? new \DateTime($request->birthday) : null;
            $modified = true;
        }

        if ($modified) {
            $user->verified = false;
            $user->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function updateWallets(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'eth' => 'nullable|array|max:5|distinct',
            'eth.*' => new ValidETHAddress,
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 400);
        }
        $user = Auth::user();
        $eth = [];
        foreach ($request->eth as $addr) {
            $eth[] = \str_start($addr, '0x');
        }
        $eth = \array_unique($eth);
        if ($eth !== $user->eth) {
            $user->eth = $eth;
            foreach ($eth as $addr) {
                $qr = QrCode::format('png')
                    ->size(500)
                    ->merge('/storage/eth.png', 0.3)
                    ->errorCorrection('H')
                    ->generate("ethereum:$addr");
                Storage::disk('public')->put("userdata/eth/{$addr}.png", $qr);
            }
        }
        $user->save();
        return response()->json([
            'success' => true,
            'eth' => $user->eth,
        ]);
    }

    public function destroy(Request $request)
    {
        try {
            Auth::user()->delete();
            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $err) {
            return response()->json([
                'success' => false,
                'errors' => [ $err->getMessage() ]
            ], 500);
        }
    }
}
