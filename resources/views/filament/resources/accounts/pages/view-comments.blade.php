<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Comments</h2>
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
                                    @{{ $comment['username'] ?? 'Unknown User' }}
                                </h3>
                                @if(isset($comment['timestamp']))
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($comment['timestamp'])->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                            
                            @if(isset($comment['text']))
                                <p class="mt-2 text-gray-700 dark:text-gray-300">
                                    {{ $comment['text'] }}
                                </p>
                            @endif
                            
                            @if(isset($comment['like_count']))
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    ❤️ {{ number_format($comment['like_count']) }} likes
                                </div>
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

        @if(count($selectedComments) > 0)
            <div class="fixed bottom-6 right-6 bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-4 border-2 border-primary-600">
                <div class="flex items-center space-x-4">
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ count($selectedComments) }} user(s) selected
                    </span>
                    <button 
                        wire:click="blockSelected"
                        class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded transition"
                    >
                        Block Selected Users
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
