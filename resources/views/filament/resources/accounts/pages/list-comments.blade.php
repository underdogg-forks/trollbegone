<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">List Comments</h2>
            <div class="flex items-center space-x-4">
                @if(count($selectedComments) > 0)
                    <span class="text-sm font-medium text-primary-600">
                        {{ count($selectedComments) }} selected
                    </span>
                @endif
                <span class="text-gray-600 dark:text-gray-400">{{ count($this->getComments()) }} comments</span>
            </div>
        </div>

        <div class="space-y-3">
            @forelse($this->getComments() as $comment)
                <div
                    wire:click="toggleComment('{{ $comment['id'] }}')"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 cursor-pointer transition {{ in_array($comment['id'], $selectedComments) ? 'ring-2 ring-primary-600 bg-primary-50 dark:bg-primary-900/20' : 'hover:shadow-md' }}"
                >
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <input
                                type="checkbox"
                                @checked(in_array($comment['id'], $selectedComments))
                                class="w-5 h-5 text-primary-600 rounded focus:ring-primary-500"
                            />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <h3 class="font-bold text-gray-900 dark:text-white">
                                    {{ $comment['username'] ?? 'Unknown User' }}
                                </h3>
                                <button
                                    wire:click.stop="deleteAndBlockComment('{{ $comment['id'] }}')"
                                    class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded"
                                >
                                    Delete + Block
                                </button>
                            </div>

                            @if(isset($comment['text']))
                                <p class="mt-2 text-gray-700 dark:text-gray-300">
                                    {{ $comment['text'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">No comments found</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
