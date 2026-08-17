@extends('backend.layouts.app')

@section('title')
    {{ __('Transfer Settings') }}
@endsection

@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-6 col-md-6">
                        <div class="title-content">
                            <h2 class="title">{{ __('Transfer Settings') }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-md-6">
                    <div class="site-card">
                        <div class="site-card-header">
                            <h3 class="title">{{ __('Global Transfer Controls') }}</h3>
                        </div>
                        <div class="site-card-body">
                            <form action="{{ route('admin.settings.update') }}" method="post">
                                @csrf
                                <input type="hidden" name="section" value="transfer">

                                <div class="site-input-groups mb-3">
                                    <label class="box-input-label">{{ __('Global Transfer Status') }}</label>
                                    <div class="switch-field">
                                        <input type="radio" id="transfer_global_status-1" name="transfer_global_status" value="1" @checked(setting('transfer_global_status', 'transfer'))>
                                        <label for="transfer_global_status-1">{{ __('Enabled') }}</label>
                                        <input type="radio" id="transfer_global_status-0" name="transfer_global_status" value="0" @checked(!setting('transfer_global_status', 'transfer'))>
                                        <label for="transfer_global_status-0">{{ __('Disabled') }}</label>
                                    </div>
                                    <small class="text-muted">{{ __('Master switch for all transfers. When disabled, no user can send money regardless of role.') }}</small>
                                </div>

                                <div class="site-input-groups mb-3">
                                    <label class="box-input-label">{{ __('Buyer Transfer Status') }}</label>
                                    <div class="switch-field">
                                        <input type="radio" id="transfer_default_buyer-1" name="transfer_default_buyer" value="1" @checked(setting('transfer_default_buyer', 'transfer'))>
                                        <label for="transfer_default_buyer-1">{{ __('Enabled') }}</label>
                                        <input type="radio" id="transfer_default_buyer-0" name="transfer_default_buyer" value="0" @checked(!setting('transfer_default_buyer', 'transfer'))>
                                        <label for="transfer_default_buyer-0">{{ __('Disabled') }}</label>
                                    </div>
                                    <small class="text-muted">{{ __('Enables or disables transfers for existing and newly registered buyers. Individual accounts can still be controlled from the user profile.') }}</small>
                                </div>

                                <div class="site-input-groups mb-3">
                                    <label class="box-input-label">{{ __('Merchant Transfer Status') }}</label>
                                    <div class="switch-field">
                                        <input type="radio" id="transfer_default_merchant-1" name="transfer_default_merchant" value="1" @checked(setting('transfer_default_merchant', 'transfer'))>
                                        <label for="transfer_default_merchant-1">{{ __('Enabled') }}</label>
                                        <input type="radio" id="transfer_default_merchant-0" name="transfer_default_merchant" value="0" @checked(!setting('transfer_default_merchant', 'transfer'))>
                                        <label for="transfer_default_merchant-0">{{ __('Disabled') }}</label>
                                    </div>
                                    <small class="text-muted">{{ __('Enables or disables transfers for existing and newly registered merchants. Individual accounts can still be controlled from the user profile.') }}</small>
                                </div>

                                <div class="site-input-groups mb-3">
                                    <label class="box-input-label">{{ __('Require KYC for Transfers') }}</label>
                                    <div class="switch-field">
                                        <input type="radio" id="transfer_require_kyc-1" name="transfer_require_kyc" value="1" @checked(setting('transfer_require_kyc', 'transfer'))>
                                        <label for="transfer_require_kyc-1">{{ __('Yes') }}</label>
                                        <input type="radio" id="transfer_require_kyc-0" name="transfer_require_kyc" value="0" @checked(!setting('transfer_require_kyc', 'transfer'))>
                                        <label for="transfer_require_kyc-0">{{ __('No') }}</label>
                                    </div>
                                    <small class="text-muted">{{ __('If enabled, users must complete KYC verification before sending money.') }}</small>
                                </div>

                                <button type="submit" class="site-btn-sm primary-btn">
                                    {{ __('Save Settings') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
