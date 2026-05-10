<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Posts from {{ $username }}</h2>
            <span class="text-gray-600 dark:text-gray-400">{{ count($this->getPosts()) }} posts</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($this->getPosts() as $post)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden hover:shadow-xl transition">
                    @if(isset($post['media_url']))
                        <img 
                            src="{{ $post['media_url'] }}" 
                            alt="Post"
                            class="w-full h-64 object-cover"
                        />
                    @endif
                    
                    <div class="p-4 space-y-2">
                        @if(isset($post['caption']))
                            <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">
                                {{ $post['caption'] }}
                            </p>
                        @endif
                        
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center space-x-3">
                                @if(isset($post['like_count']))
                                    <span>❤️ {{ number_format($post['like_count']) }}</span>
                                @endif
                                @if(isset($post['comments_count']))
                                    <span>💬 {{ number_format($post['comments_count']) }}</span>
                                @endif
                            </div>
                            @if(isset($post['timestamp']))
                                <span>{{ $this->formatTimestamp($post['timestamp']) }}</span>
                            @endif
                        </div>
                        
                        <div class="pt-2">
                            <details class="relative">
                                <summary class="list-none cursor-pointer block w-full text-center bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded transition">
                                    Moderation Actions
                                </summary>
                                <div class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
                                    <a
                                        href="{{ route('filament.admin.resources.accounts.comments', ['record' => $record->id, 'post' => $post['id']]) }}"
                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                                    >
                                        List Comments
                                    </a>
                                    <a
                                        href="{{ route('filament.admin.resources.accounts.commenters', ['record' => $record->id, 'post' => $post['id']]) }}"
                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                                    >
                                        List Commenters
                                    </a>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">No posts found</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
