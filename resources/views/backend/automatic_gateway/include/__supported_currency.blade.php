@foreach ((array) json_decode((string) $supportedCurrencies) as $currency)
    <option
        value="{{$currency}}"> {{$currency}}
    </option>
@endforeach
