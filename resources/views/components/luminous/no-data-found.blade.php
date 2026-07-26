<div class="no-data-found {{ $attributes->get('class', 'mt-3') }}">
    <div class="img-box text-center">
        <img src="{{ $attributes->get('img', themeAsset('images/icon/no-data-img.png')) }}"
            alt="{{ __('No :type Found', ['type' => __($attributes->get('type', 'Data'))]) }}">
    </div>
    <span class="text">{{ __('No :type Found', ['type' => __($attributes->get('type', 'Data'))]) }}!</span>

</div>
