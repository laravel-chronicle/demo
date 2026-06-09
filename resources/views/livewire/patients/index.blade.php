<div>
    <h1 class="text-2xl font-semibold">Patients</h1>
    <p class="mt-1 text-sm text-gray-600">
        Synthetic records. Open one to record an access event and watch its Chronicle audit trail.
    </p>

    <ul class="mt-6 divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 bg-white">
        @forelse ($patients as $patient)
            <li>
                <a href="{{ route('patients.show', $patient) }}"
                   class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                    <span class="font-medium">{{ $patient->name }}</span>
                    <span class="text-sm text-gray-500">{{ $patient->mrn }}</span>
                </a>
            </li>
        @empty
            <li class="px-4 py-6 text-sm text-gray-500">
                No patients seeded yet. Run <code>php artisan migrate:fresh --seed</code>.
            </li>
        @endforelse
    </ul>
</div>
