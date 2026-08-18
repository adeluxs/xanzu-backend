<script src="{{ asset('global/js/jquery.min.js') }}"></script>
<script src="{{ asset('global/js/jquery-migrate.js') }}"></script>
<script src="{{ asset('backend/js/jquery-ui.js') }}"></script>

<script src="{{ asset('backend/js/bootstrap.bundle.min.js') }}"></script>

<script src="{{ asset('backend/js/scrollUp.min.js') }}"></script>
<script src="{{ asset('global/js/waypoints.min.js') }}"></script>
<script src="{{asset('global/js/jquery.counterup.min.js')}}"></script>
{{-- Chart.js v4 uses an ES-module build for dist/chart.js. The dashboard uses the browser-global API, so load the matching UMD build. --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.3.0/chart.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="{{ asset('global/js/lucide.min.js') }}"></script>
<script src="{{ asset('global/js/jquery.nice-select.min.js') }}"></script>
<script src="{{ asset('global/js/moment.min.js') }}"></script>
<script src="{{ asset('global/js/daterangepicker.min.js') }}"></script>

<script src="{{ asset('global/js/simple-notify.min.js') }}"></script>
<script src="{{ asset('backend/js/summernote-lite.min.js') }}"></script>

<script src="{{ asset('global/js/select2.min.js') }}"></script>
<script src="{{ asset('backend/js/main.js?v=1') }}"></script>
<script src="{{ asset('global/js/pusher.min.js') }}"></script>
<script src="{{ asset('global/js/custom.js?v=1.1') }}"></script>
<script src="{{ asset('backend/js/choices.min.js?v=1.1') }}"></script>

@include('global.__notification_script',['for'=>'admin','userId' => ''])
@yield('script')
@stack('single-script')


