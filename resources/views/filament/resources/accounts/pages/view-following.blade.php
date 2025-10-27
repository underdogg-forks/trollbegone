<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Following (@{{ count($this->getFollowing()) }})</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($this->getFollowing() as $user)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 hover:shadow-lg transition">
                    <div class="flex items-center space-x-4">
                        <img 
                            src="{{ $user['profile_picture_url'] ?? 'https://via.placeholder.com/64' }}" 
                            alt="{{ $user['username'] }}"
                            class="w-16 h-16 rounded-full"
                        />
                        <div class="flex-1">
                            <h3 class="font-bold text-lg">{{ $user['username'] }}</h3>
                            @if(isset($user['full_name']))
                                <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $user['full_name'] }}</p>
                            @endif
                            @if(isset($user['followers_count']))
                                <p class="text-gray-500 dark:text-gray-500 text-xs">{{ number_format($user['followers_count']) }} followers</p>
                            @endif
                        </div>
                        <div>
                            <a 
                                href="{{ route('filament.admin.resources.accounts.posts', ['record' => $record->id, 'username' => $user['username']]) }}"
                                class="text-primary-600 hover:text-primary-700 font-medium text-sm"
                            >
                                View Posts →
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">No users found</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
