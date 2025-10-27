<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Instagram Account: {{ $record->username }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Last synced: {{ $record->last_synced_at?->diffForHumans() ?? 'Never' }}
            </p>
        </div>

        @if(empty($stories))
            <div class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                <p class="text-gray-500 dark:text-gray-400">No stories found. Click "Refresh Stories" to load stories.</p>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($stories as $story)
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Story #{{ $story['id'] ?? 'Unknown' }}</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Created: {{ isset($story['timestamp']) ? \Carbon\Carbon::parse($story['timestamp'])->diffForHumans() : 'Unknown' }}
                        </p>
                        <button 
                            wire:click="loadComments('{{ $story['id'] }}')"
                            class="mt-4 w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700"
                        >
                            View Comments
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        @if($selectedStoryId && !empty($comments))
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Comments for Story #{{ $selectedStoryId }}</h3>
                <div class="mt-4 space-y-4">
                    @foreach($comments as $comment)
                        <div class="border-b border-gray-200 pb-4 dark:border-gray-700">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        @{{ $comment['from']['username'] ?? 'Unknown User' }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $comment['text'] ?? 'No text' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ isset($comment['timestamp']) ? \Carbon\Carbon::parse($comment['timestamp'])->diffForHumans() : 'Unknown time' }}
                                    </p>
                                </div>
                                <button 
                                    wire:click="blockUser('{{ $comment['from']['username'] ?? '' }}', '{{ addslashes($comment['text'] ?? '') }}')"
                                    class="ml-4 rounded-lg bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-700"
                                >
                                    Block User
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
