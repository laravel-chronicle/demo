@php
    $isAdmin = session('demo_persona') === 'admin';
@endphp

<div class="fi-section-content-ctn border-b border-gray-200 bg-amber-50 px-6 py-3 text-sm text-amber-900 dark:border-white/10 dark:bg-amber-500/10 dark:text-amber-200">
    <span class="font-medium">Public demo - all data is fictional and resets hourly.</span>
    @if ($isAdmin)
        <span>The GDPR erase and export actions are unlocked for the Admin Vega persona. Erasing a subject is irreversible.</span>
    @else
        <span>This panel is read-only. Switch to the Admin Vega persona on the main site to unlock the GDPR erase and export actions.</span>
    @endif
</div>
