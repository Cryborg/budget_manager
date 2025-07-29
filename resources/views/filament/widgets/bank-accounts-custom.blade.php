<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Aperçu des comptes
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Banque</th>
                        <th scope="col" class="px-6 py-3">Compte</th>
                        <th scope="col" class="px-6 py-3">Type</th>
                        <th scope="col" class="px-6 py-3">Solde</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->getGroupedAccounts() as $bankName => $accounts)
                        @foreach($accounts as $index => $account)
                            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    @if($index === 0)
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $bankName }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $account->name }}
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $typeLabel = match($account->type) {
                                            'current' => 'Courant',
                                            'savings' => 'Épargne', 
                                            'investment' => 'Investissement',
                                            default => $account->type
                                        };
                                        $typeColors = match($account->type) {
                                            'current' => ['bg' => '#3b82f6', 'text' => 'white'],
                                            'savings' => ['bg' => '#10b981', 'text' => 'white'],
                                            'investment' => ['bg' => '#f59e0b', 'text' => 'white'],
                                            default => ['bg' => '#6b7280', 'text' => 'white']
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          style="background-color: {{ $typeColors['bg'] }}; color: {{ $typeColors['text'] }};">
                                        {{ $typeLabel }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($account->current_balance >= 0)
                                        <span class="font-bold text-lg" style="color: #10b981;">
                                            +{{ number_format($account->current_balance, 2, ',', ' ') }} €
                                        </span>
                                    @else
                                        <span class="font-bold text-lg" style="color: #ef4444;">
                                            {{ number_format($account->current_balance, 2, ',', ' ') }} €
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>