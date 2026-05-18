<x-filament-panels::page>
    {{ $this->table }}

    @php($pending = $this->getPendingInvitations())

    @if ($pending->isNotEmpty())
        <x-filament::section heading="Invitaciones pendientes" class="mt-6">
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($pending as $invitation)
                    <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-gray-950 dark:text-white">{{ $invitation->email }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Rol: {{ $invitation->role->label() }}
                                · Expira: {{ $invitation->expires_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <p class="break-all text-sm text-gray-600 dark:text-gray-300">
                            {{ $invitation->acceptUrl() }}
                        </p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
