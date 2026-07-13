@php
    $matomoUrl = config('demo.analytics.matomo_url');
    $matomoSiteId = config('demo.analytics.matomo_site_id');
@endphp

@if (filled($matomoUrl) && filled($matomoSiteId))
    @php
        $base = rtrim($matomoUrl, '/');
    @endphp
    {{-- Cookieless Matomo tracker: no cookies, honours Do Not Track, no consent banner required. --}}
    <script>
        var _paq = window._paq = window._paq || [];
        _paq.push(['disableCookies']);
        _paq.push(['setDoNotTrack', true]);
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function () {
            _paq.push(['setTrackerUrl', @js($base.'/matomo.php')]);
            _paq.push(['setSiteId', @js((string) $matomoSiteId)]);
            var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
            g.async = true;
            g.src = @js($base.'/matomo.js');
            s.parentNode.insertBefore(g, s);
        })();
    </script>
@endif
