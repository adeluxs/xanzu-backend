<?php

namespace App\Services;

use App\Models\WithdrawAccount;
use App\Models\WithdrawMethod;
use App\Support\JsonData;
use App\Traits\ImageUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WithdrawAccountService
{
    use ImageUpload {
        delete as protected fileDelete;
    }

    public function validate(array $data, ?WithdrawMethod $method, $isEdit = false): void
    {
        $rules = [
            'withdraw_method_id' => 'required|exists:withdraw_methods,id',
            'method_name' => 'required|string|max:255',
            'customFields' => 'required|array',
        ];

        if ($isEdit) {
            unset($rules['withdraw_method_id']);
        }

        if (!$method) {
            $method = WithdrawMethod::find($data['withdraw_method_id']);
        }

        $methodFields = JsonData::decodeArray($method?->fields);
        foreach ($methodFields as $fieldKey => $field) {
            if ($field['type'] == 'file') {
                $rules['customFields.' . $fieldKey] = ($isEdit) ? 'nullable|mimes:jpeg,jpg,png,svg|max:2000' : $field['validation'] . '|mimes:jpeg,jpg,png,svg|max:2000';

                continue;
            }

            $rules['customFields.' . $fieldKey] = $field['validation'];
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator, $validator->errors()->first());
        }
    }

    public function store(array $data, ?WithdrawMethod $method, int $userId, $accountId = null): WithdrawAccount
    {
        $credentials = [];

        if (!$method) {
            $method = WithdrawMethod::find($data['withdraw_method_id']);
        }

        $account = WithdrawAccount::find($accountId);
        $withdrawAccount = $accountId ? $account : new WithdrawAccount;

        foreach (JsonData::decodeArray($method?->fields) as $key => $value) {
            $customFieldValue = Arr::get($data['customFields'], $key);

            if ($customFieldValue instanceof UploadedFile) {
                $credentials[$key] = array_merge($value, [
                    'value' => self::imageUploadTrait($customFieldValue, null, 'withdraw_accounts') ?? 'error',
                ]);

                $oldValue = $accountId ? data_get(json_decode($account->credentials), "$key.value") : null;
                if ($accountId && $oldValue) {
                    $this->fileDelete($oldValue);
                }
            } else {
                $credentials[$key] = array_merge($value, [
                    'value' => $customFieldValue,
                ]);
            }

            if ($accountId && !$credentials[$key]['value']) {
                $credentials[$key]['value'] = data_get(json_decode($account->credentials), "$key.value");
            }
        }


        $withdrawAccount->user_id = $userId;
        $withdrawAccount->withdraw_method_id = $accountId ? $account->withdraw_method_id : $data['withdraw_method_id'];
        $withdrawAccount->method_name = $data['method_name'];
        $withdrawAccount->credentials = json_encode($credentials);
        $withdrawAccount->save();

        return $withdrawAccount;
    }

    public function delete($id, ?int $userId = null)
    {
        $withdrawAccounts = WithdrawAccount::query()
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->find($id);
        if (!$withdrawAccounts) {
            throw new \Exception('Withdraw account not found');
        }

        $oldCredentials = JsonData::decodeArray($withdrawAccounts->credentials);

        foreach ($oldCredentials as $value) {
            if (isset($value['value']) && $value['type'] == 'file') {
                $this->fileDelete($value['value']);
            }
        }

        $withdrawAccounts->delete();

        return true;
    }
}
