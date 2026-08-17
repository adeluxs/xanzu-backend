<?php

namespace App\Services;

use App\Enums\KYCStatus;
use App\Models\Kyc;
use App\Models\User;
use App\Models\UserKyc;
use App\Support\JsonData;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KycService
{
    use ImageUpload, NotifyTrait;

    // validate request
    public function verify(Kyc $kyc, array $data, $resubmit = false)
    {
        $validationRules = $this->validationRules($kyc, $resubmit);
        $validation = Validator::make($data, $validationRules['rules'], $validationRules['messages']);
        throw_if($validation->fails(), ValidationException::class, $validation);
    }

    public function validationRules(Kyc $kyc, $resubmit = false)
    {
        $rules = [];
        $messages = [];

        foreach (JsonData::decodeArray($kyc->fields) as $key => $value) {
            $fieldRules = [];

            if (!empty($value['validation']) && $value['validation'] == 'required' && !$resubmit) {
                $fieldRules[] = 'required';
                $messages["$key.required"] = __('The :attribute field is required.', ['attribute' => $value['title'] ?? $value['name'] ?? $key]);
            } else {
                $fieldRules[] = 'nullable';
            }

            if ($value['type'] == 'file') {
                $fieldRules[] = 'file';
                $fieldRules[] = 'mimes:jpg,jpeg,png,pdf';
                $fieldRules[] = 'max:2048';
                $messages["$key.file"] = __('The :attribute must be a file.', ['attribute' => $value['title'] ?? $value['name'] ?? $key]);
                $messages["$key.mimes"] = __('The :attribute must be a file of type: :values.', ['attribute' => $value['title'] ?? $value['name'] ?? $key, 'values' => 'jpg, jpeg, png, pdf']);
                $messages["$key.max"] = __('The :attribute may not be greater than :max kilobytes.', ['attribute' => $value['title'] ?? $value['name'] ?? $key, 'max' => 2048]);
            } elseif (in_array($value['type'], ['text', 'textarea'])) {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:255';
                $messages["$key.string"] = __('The :attribute must be a string.', ['attribute' => $value['title'] ?? $value['name'] ?? $key]);
                $messages["$key.max"] = __('The :attribute may not be greater than :max characters.', ['attribute' => $value['title'] ?? $value['name'] ?? $key, 'max' => 255]);
            }
            $rules[$key] = implode('|', $fieldRules);
        }

        return [
            'rules' => $rules,
            'messages' => $messages,
        ];
    }

    public function submitKyc($data, Kyc $kyc)
    {
        return $this->submitKycForUser($data, $kyc);
    }

    public function submitKycForUser($data, Kyc $kyc, ?User $user = null, $resubmit = false)
    {
        $processedData = [];
        $user = $user ?? auth()->user();


        // check if kyc already submitted and pending or approved
        $existingKyc = UserKyc::where('user_id', $user->id)->where('kyc_id', $kyc->id)->whereIn('status', ['pending', 'approved'])->where('is_valid', true)->first();
        if ($existingKyc && !$resubmit) {
            throw new \Exception(__('You have already submitted this KYC and it is under review or approved.'));
        }

        $lastRejectedKyc = UserKyc::where('user_id', $user->id)->where('kyc_id', $kyc->id)->where('status', 'rejected')->latest()->first();
        foreach (JsonData::decodeArray($kyc->fields) as $key => $value) {
            $kycCredentialValue = data_get($data, $key);
            if (!$kycCredentialValue && $resubmit) {
                $processedData[$value['name']] = data_get($lastRejectedKyc?->data, $value['name']);
            } else {

                if ($kycCredentialValue instanceof UploadedFile) {
                    $processedData[$value['name']] = self::imageUploadTrait(query: $kycCredentialValue, folder: 'kyc');
                } else {
                    $processedData[$value['name']] = $kycCredentialValue;
                }
            }

        }

        // Save to database
        $userKyc = new UserKyc;
        $userKyc->user_id = $user->id;
        $userKyc->kyc_id = $kyc->id;
        $userKyc->data = $processedData;
        $userKyc->is_valid = true;
        $userKyc->status = 'pending';
        $userKyc->save();

        $pendingCount = UserKyc::where('user_id', $user->id)->whereIn('status', ['pending', 'approved'])->where('is_valid', true)->count();
        $isPending = Kyc::where('status', true)->count() == $pendingCount;

        $user->update([
            'kyc' => KYCStatus::Pending,
        ]);

        if ($isPending) {
            $shortcodes = [
                '[[full_name]]' => $user->full_name,
                '[[email]]' => $user->email,
                '[[kyc_type]]' => $kyc->name,
                '[[kyc_review_link]]' => route('admin.kyc.pending'),
                '[[site_title]]' => setting('site_title', 'global'),
            ];

            $this->sendNotify(setting('site_email', 'global'), 'admin_kyc_request', 'Admin', $shortcodes, $user->phone, $user->id, route('admin.kyc.pending'));
        }

        return $userKyc;

    }

    public function submittableKyc(?User $user = null)
    {
        $user = $user ?? auth()->user();

        $userKycIds = UserKyc::whereIn('status', ['pending', 'approved'])->where('user_id', $user->id)->where('is_valid', true)->pluck('kyc_id');

        $kycs = Kyc::where('status', true)->whereNotIn('id', $userKycIds)->get();

        return $kycs;
    }

    public function submittedKyc(?User $user = null)
    {
        $user = $user ?? auth()->user();

        $userKycs = UserKyc::where('user_id', $user->id)->latest()->get();

        return $userKycs;
    }

    public function merchantKyc(?int $kycId = null)
    {
        return Kyc::where('status', true)
            ->whereIn('user_type', ['merchant', 'both'])
            ->when($kycId, function ($query, $kycId) {
                $query->whereKey($kycId);
            })
            ->first();
    }
}
