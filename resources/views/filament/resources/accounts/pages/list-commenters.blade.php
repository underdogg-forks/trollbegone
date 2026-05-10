<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">List Commenters</h2>
            <div class="flex items-center space-x-4">
                @if(count($selectedCommenters) > 0)
                    <span class="text-sm font-medium text-primary-600">
                        {{ count($selectedCommenters) }} selected
                    </span>
                @endif
                <span class="text-gray-600 dark:text-gray-400">{{ count($this->getCommenters()) }} commenters</span>
            </div>
        </div>

        <div class="space-y-3">
            @forelse($this->getCommenters() as $commenter)
                <div
                    wire:click="toggleCommenter('{{ $commenter['username'] }}')"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 cursor-pointer transition {{ in_array($commenter['username'], $selectedCommenters) ? 'ring-2 ring-primary-600 bg-primary-50 dark:bg-primary-900/20' : 'hover:shadow-md' }}"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">
                                {{ $commenter['username'] }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $commenter['comment_count'] }} comment(s)
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Latest
                            </p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $commenter['latest_comment'] }}
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">No commenters found</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
