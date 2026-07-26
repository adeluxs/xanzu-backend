<div class="hv-item">
    <div class="hv-item-parent">
        <div class="person">
            @if(null != $levelUser->avatar)
                <img src="{{ $levelUser->avatar_path }}">
            @else
                <div class="f-name-l-name">{{ $levelUser->first_name[0] }}{{ $levelUser->last_name[0] }}</div>
            @endif
            <p class="name">
                @if($me)
                    {{ __("It's Me") }}( {{ $levelUser->full_name }} )
                @else
                    <b>{{ $levelUser->full_name }} <br> @if(setting('deposit_level'))
                            {{ __('Topup') }} {{ $currencySymbol.$levelUser->totalTopup() }}
                        @endif 
                    </b>
                @endif
            </p>
        </div>
    </div>

    @if($depth && $level >= $depth && $levelUser->referrals->count() > 0)
        <div class="hv-item-children">
            @foreach($levelUser->referrals as $levelUser)
                <div class="hv-item-child">
                    <!-- Key component -->
                    @include('backend.include.__referral_tree',['levelUser' => $levelUser,'level' => $level,'depth' => $depth + 1,'me' => false])
                </div>
            @endforeach
        </div>
    @endif
</div>


