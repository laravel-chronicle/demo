@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'MedLedger - Chronicle Demo' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-gray-50 text-gray-900 antialiased">
        {{-- Persistent honesty banner + manual reset --}}
        <div class="flex flex-wrap items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-center text-sm font-medium text-amber-950">
            <span>Public demo - all data is fictional - resets hourly.</span>
            <livewire:reset-demo />
        </div>

        <header class="border-b border-gray-200 bg-white">
            <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
                <div class="flex items-center gap-6">
                    <a href="{{ route('home') }}" class="text-lg font-semibold">MedLedger</a>
                    <div class="hidden items-center gap-4 text-sm text-gray-600 md:flex">
                        <a href="{{ route('patients.index') }}" class="hover:text-gray-900">Patients</a>
                        <a href="{{ route('ledger.index') }}" class="hover:text-gray-900">Ledger</a>
                        <a href="{{ route('lab.index') }}" class="hover:text-gray-900">Integrity Lab</a>
                        <a href="{{ route('auditors.index') }}" class="hover:text-gray-900">For Auditors</a>
                        <a href="{{ route('how.it.works') }}" class="hover:text-gray-900">How it works</a>
                    </div>
                </div>

                {{-- No-auth persona switcher (native <details>, no JS dependency) --}}
                <details class="relative">
                    <summary class="cursor-pointer list-none rounded-md border border-gray-300 px-3 py-1.5 text-sm">
                        Signed in as <span class="font-semibold">{{ $currentPersona['name'] }}</span>
                        <span class="text-gray-500">({{ $currentPersona['role'] }})</span>
                    </summary>
                    <div
                        class="absolute right-0 z-10 mt-2 w-56 rounded-md border border-gray-200 bg-white p-1 shadow-lg">
                        @foreach ($personas as $key => $persona)
                            <form method="POST" action="{{ route('persona.switch') }}">
                                @csrf
                                <input type="hidden" name="persona" value="{{ $key }}">
                                <button type="submit"
                                        class="flex w-full items-center justify-between rounded px-3 py-2 text-left text-sm hover:bg-gray-100 {{ $key === $currentPersonaKey ? 'font-semibold' : '' }}">
                                    <span>{{ $persona['name'] }}</span>
                                    <span class="text-xs text-gray-500">{{ $persona['role'] }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </details>
            </nav>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            {{ $slot }}
        </main>
    </body>
</html>
