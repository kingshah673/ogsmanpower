@php
    $unreadCount = candidateUnreadNotifications();
    $notifications = candidateNotifications();
    $seekerFallbackUrl = route('candidate.allNotification');
@endphp
<li class="cw-notif-item">
    <div class="notification-icon cw-notif-bell" title="{{ __('notifications') }}" role="button" tabindex="0" aria-label="{{ __('notifications') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path
                d="M5.26904 10.5002C5.26657 9.61461 5.43885 8.73727 5.77603 7.91841C6.1132 7.09956 6.60864 6.35528 7.23394 5.72822C7.85925 5.10116 8.60214 4.60365 9.42006 4.26419C10.238 3.92474 11.1148 3.75 12.0004 3.75C12.8859 3.75 13.7628 3.92474 14.5807 4.26419C15.3986 4.60365 16.1415 5.10116 16.7668 5.72822C17.3921 6.35528 17.8876 7.09956 18.2247 7.91841C18.5619 8.73727 18.7342 9.61461 18.7317 10.5002V10.5002C18.7317 13.8579 19.4342 15.8063 20.0529 16.8712C20.1196 16.985 20.1551 17.1144 20.1558 17.2462C20.1565 17.3781 20.1224 17.5078 20.0569 17.6223C19.9915 17.7368 19.8971 17.832 19.7831 17.8984C19.6691 17.9647 19.5397 17.9998 19.4078 18.0002H4.59222C4.46034 17.9998 4.33087 17.9647 4.21689 17.8984C4.1029 17.832 4.00844 17.7368 3.94301 17.6223C3.87759 17.5077 3.84352 17.378 3.84425 17.2461C3.84498 17.1142 3.88048 16.9849 3.94716 16.8711C4.56622 15.8061 5.26904 13.8577 5.26904 10.5002H5.26904Z"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path
                d="M9 18V18.75C9 19.5456 9.31607 20.3087 9.87868 20.8713C10.4413 21.4339 11.2044 21.75 12 21.75C12.7956 21.75 13.5587 21.4339 14.1213 20.8713C14.6839 20.3087 15 19.5456 15 18.75V18"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
        @if ($unreadCount > 0)
            <span id="unNotifications" class="cw-notif-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif

        <div class="notification-bar cw-notif-panel" onclick="event.stopPropagation()">
            <div class="cw-notif-panel-head">
                <strong>{{ __('notifications') }}</strong>
                <a href="#" onclick="event.preventDefault(); ReadNotification();" class="cw-notif-mark">{{ __('mark_all_as_read') }}</a>
            </div>
            <div class="notification-list cw-notif-list">
                @if ($notifications->count() > 0)
                    <ul>
                        @foreach ($notifications as $noti)
                            @php
                                $data = is_array($noti->data) ? $noti->data : [];
                                $title = $data['title2'] ?? $data['title'] ?? $data['subject'] ?? __('notification');
                                $url = $data['url2'] ?? $data['url'] ?? $seekerFallbackUrl;
                            @endphp
                            <li class="{{ $noti->read_at ? '' : 'is-unread' }}">
                                <a onclick="readSingleNotification(@js($url), @js($noti->id))"
                                    href="javascript:void(0)">
                                    <span class="cw-notif-thumb" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                    </span>
                                    <span class="cw-notif-body">
                                        <span class="cw-notif-title">{{ \Illuminate\Support\Str::limit($title, 80) }}</span>
                                        <span class="cw-notif-time">{{ $noti->created_at->diffForHumans() }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="cw-notif-empty">{{ __('no_notification') }}</div>
                @endif
            </div>
            <a href="{{ route('candidate.allNotification') }}" class="cw-notif-footer">{{ __('view_all_notifications') }}</a>
        </div>
    </div>
</li>
