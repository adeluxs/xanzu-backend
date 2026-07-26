 @extends('backend.layouts.app')
 @section('title')
     {{ $title }}
 @endsection
 @section('content')
     <div class="main-content">
         <div class="page-title">
             <div class="container-fluid">
                 <div class="row">
                     <div class="col">
                         <div class="title-content">
                             <h2 class="title">{{ $title }}</h2>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
         <div class="container-fluid">
             <div class="row">
                 <div class="col-xl-12">
                     <div class="site-table table-responsive">
                         <form action="{{ request()->url() }}" method="get">
                             <div class="table-filter">
                                 <div class="filter">
                                     <div class="search">
                                         <input type="text" id="search" name="query" value="{{ request('query') }}"
                                             placeholder="{{ __('Search') }}" />
                                     </div>
                                     <select name="email_status" id="email_status" class="form-select form-select-sm">
                                         <option value="" selected>{{ __('Filter By Email Status') }}</option>
                                         <option value="verified"
                                             {{ request('email_status') == 'verified' ? 'selected' : '' }}>
                                             {{ __('Email Verified') }}</option>
                                         <option value="unverified"
                                             {{ request('email_status') == 'unverified' ? 'selected' : '' }}>
                                             {{ __('Email Unverified') }}</option>
                                     </select>
                                     <select name="kyc_status" id="kyc_status" class="form-select form-select-sm">
                                         <option value="" selected>{{ __('Filter By KYC') }}</option>
                                         <option value="1" {{ request('kyc_status') == '1' ? 'selected' : '' }}>
                                             {{ __('Verified') }}</option>
                                         <option value="0" {{ request('kyc_status') == '0' ? 'selected' : '' }}>
                                             {{ __('Unverified') }}</option>
                                     </select>

                                     <select name="status" id="status" class="form-select form-select-sm">
                                         <option value="" selected>{{ __('Filter By Status') }}</option>
                                         <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>
                                             {{ __('Active') }}</option>
                                         <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>
                                             {{ __('Disabled') }}</option>
                                         <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>
                                             {{ __('Closed') }}</option>
                                     </select>
                                     <button type="submit" class="apply-btn"><i
                                             data-lucide="search"></i>{{ __('Find') }}</button>
                                 </div>
                             </div>
                         </form>
                         <table class="table">
                             <thead>
                                 <tr>
                                     @include('backend.filter.th', [
                                         'label' => 'User',
                                         'field' => 'username',
                                     ])
                                     @include('backend.filter.th', [
                                         'label' => 'Email',
                                         'field' => 'email',
                                     ])
                                     @include('backend.filter.th', [
                                         'label' => 'Balance',
                                         'field' => 'balance',
                                     ])
                                     <th>{{ __('KYC') }}</th>
                                     @include('backend.filter.th', [
                                         'label' => 'Status',
                                         'field' => 'status',
                                     ])
                                     <th>{{ __('Action') }}</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 @forelse($users as $user)
                                     <tr>
                                         <td>
                                             <div class="icon">
                                                 @include('backend.user.include.__avatar', [
                                                     'avatar' => $user->avatar_path,
                                                     'first_name' => $user->first_name,
                                                     'last_name' => $user->last_name,
                                                 ])
                                                 <a href="{{ route('admin.user.edit', $user->id) }}"
                                                     class="link">{{ Str::limit($user->username, 15) }} @if ($user->is_popular)
                                                         <i class="fas fa-bolt text-danger" data-bs-toggle="tooltip"
                                                             data-bs-placement="top" title="{{ __('Popular User') }}"></i>
                                                     @endif
                                                 </a>
                                             </div>
                                         </td>
                                         <td>{{ config('app.demo') ? safe($user->email) : Str::limit($user->email, 20) }}
                                             {!! $user->email_verified_at
                                                 ? '<i data-bs-toggle="tooltip" data-bs-placement="top" title="Email Verified" class="fas fa-check-circle text-success"></i>'
                                                 : '' !!}</td>
                                         <td>{{ $currencySymbol . $user->balance }}</td>
                                         <td>
                                             @include('backend.user.include.__kyc', ['kyc' => $user->kyc])
                                         </td>
                                         <td>
                                             @include('backend.user.include.__status', [
                                                 'status' => $user->status,
                                             ])
                                         </td>
                                         <td>
                                             @include('backend.user.include.__action', ['user' => $user])
                                         </td>
                                     </tr>
                                 @empty
                                     <td colspan="8" class="text-center">{{ __('No Data Found!') }}</td>
                                 @endforelse
                             </tbody>
                             <!-- Modal for Send Mail -->
                             @can('customer-mail-send')
                                 @include('backend.user.include.__mail_send')
                             @endcan
                             <!-- Modal for Send Mail End-->
                         </table>

                         {{ $users->links('backend.include.__pagination') }}
                     </div>
                 </div>
             </div>
         </div>
     </div>
 @endsection

 @section('script')
     <script>
         (function($) {
             "use strict";

             //send mail modal form open
             $('body').on('click', '.send-mail', function() {
                 var id = $(this).data('id');
                 var name = $(this).data('name');
                 $('#name').html(name);
                 $('#userId').val(id);
                 $('#sendEmail').modal('toggle')
             })

             // Delete
             $('body').on('click', '#deleteModal', function() {
                 var id = $(this).data('id');
                 var name = $(this).data('name');

                 $('#data-name').html(name);
                 var url = '{{ route('admin.user.destroy', ':id') }}';
                 url = url.replace(':id', id);
                 $('#deleteForm').attr('action', url);
                 $('#delete').modal('toggle')

             });

         })(jQuery);
     </script>
 @endsection
