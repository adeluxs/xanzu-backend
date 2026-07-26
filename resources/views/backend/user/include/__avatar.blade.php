@if (null != $avatar)
    <img class="avatar avatar-round" src="{{ $avatar_path ?? $avatar }}" alt="" height="40" width="40">
@elseif(isset($first_name) && isset($last_name))
    <span class="avatar-text">{{ $first_name[0] ?? 'X' }}{{ $last_name[0] ?? '' }}</span>
@else
    <span class="avatar-text">X</span>
@endif
