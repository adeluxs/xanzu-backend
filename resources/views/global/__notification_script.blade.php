<script src="{{ asset('global/js/pusher.min.js') }}"></script>

<script>
    (function($) {
        'use strict';

        const pusherConfig = {
            key: "{{ config('broadcasting.connections.pusher.key') }}",
            cluster: "{{ config('broadcasting.connections.pusher.options.cluster') }}",
            encrypted: true,
        };

        const soundUrl = "{{ route('notification-tune') }}";

        const notification = new Pusher(pusherConfig.key, {
            cluster: pusherConfig.cluster,
            encrypted: true,
        });

        const channelName = '{{ $for }}-notification{{ $userId }}';

        const channel = notification.subscribe(channelName);

        // Listen for our event
        channel.bind('notification-event', (data) => {
            playSound();
            notifyToast(data);
        });

        function notifyToast(data) {
            try {
                @if ($for == 'Admin')
                    new Notify({
                        status: 'info',
                        title: data.data.title,
                        text: data.data.notice,
                        effect: 'slide',
                        speed: 300,
                        customClass: '',
                        customIcon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-megaphone"><path d="m3 11 18-5v12L3 14v-3z"></path><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"></path></svg>',
                        showIcon: true,
                        showCloseButton: true,
                        autoclose: true,
                        autotimeout: 9000,
                        gap: 20,
                        distance: 20,
                        type: 1,
                        position: 'right bottom',
                        customWrapper: '<div><a href="' + data.data.action_url +
                            '" class="learn-more-link">Explore<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-lucide="external-link" class="lucide lucide-external-link"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" x2="21" y1="14" y2="3"></line></svg></a></div>',
                    });
                @else
                    showNotification('success', data.data.notice, data.data.action_url);
                @endif
            } catch (error) {
                console.error('Notify toast error:', error);
            }
        }

        function playSound() {
            $.get(soundUrl, function(data) {
                try {
                    var audio = new Audio(data);
                    audio.play();
                    audio.muted = false;
                } catch (error) {
                    console.error('Audio playback error:', error);
                }
            }).fail(function(error) {
                console.error('Sound URL fetch error:', error);
            });
        }

    })(jQuery);
</script>
