<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Lesson Header --}}
        <div class="rounded-lg p-6 shadow-sm" style="background-color: {{ $this->record->color }}20; border-right: 4px solid {{ $this->record->color }}">
            <div class="flex items-center gap-4">
                @if($this->record->logo)
                    <img src="{{ Storage::url($this->record->logo) }}" alt="{{ $this->record->title }}" class="w-16 h-16 rounded-full object-cover">
                @else
                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold" style="background-color: {{ $this->record->color }}">
                        {{ mb_substr($this->record->title, 0, 1) }}
                    </div>
                @endif
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->record->title }}</h2>
                    @if($this->record->description)
                        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $this->record->description }}</p>
                    @endif
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                        <span>⏱️ {{ $this->record->duration_minutes }} دقيقة</span>
                        <span>📄 {{ $this->getTotalPages() }} صفحة</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    الصفحة {{ $currentPage }} من {{ $this->getTotalPages() }}
                </span>
                <span class="text-sm text-gray-500">
                    {{ round(($currentPage / $this->getTotalPages()) * 100) }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="h-2 rounded-full transition-all" 
                     style="width: {{ ($currentPage / $this->getTotalPages()) * 100 }}%; background-color: {{ $this->record->color }}">
                </div>
            </div>
        </div>

        {{-- Page Content --}}
        @php
            $page = $this->getCurrentPageData();
        @endphp

        @if($page)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-8 shadow-sm min-h-[400px]">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ $page->title }}</h3>
                
                @if($page->type === 'text')
                    <div class="prose dark:prose-invert max-w-none">
                        {!! $page->content['body'] ?? '' !!}
                    </div>
                @elseif($page->type === 'image')
                    <div class="space-y-4">
                        @if(isset($page->content['image_url']))
                            <img src="{{ $page->content['image_url'] }}" alt="{{ $page->title }}" class="w-full rounded-lg">
                        @endif
                        @if(isset($page->content['caption']))
                            <p class="text-gray-600 dark:text-gray-400 text-center italic">{{ $page->content['caption'] }}</p>
                        @endif
                    </div>
                @elseif($page->type === 'question')
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="font-bold text-yellow-800 dark:text-yellow-200">سؤال تفاعلي</span>
                        </div>
                        @if(isset($page->content['instructions']))
                            <p class="text-gray-700 dark:text-gray-300 mb-4">{{ $page->content['instructions'] }}</p>
                        @endif
                        <p class="text-sm text-gray-500">رقم السؤال: {{ $page->content['question_id'] ?? 'N/A' }}</p>
                    </div>
                @elseif($page->type === 'mixed')
                    <div class="space-y-6">
                        @foreach($page->content['sections'] ?? [] as $section)
                            @if($section['type'] === 'text')
                                <div class="prose dark:prose-invert max-w-none">
                                    {!! $section['content'] ?? '' !!}
                                </div>
                            @elseif($section['type'] === 'image')
                                <img src="{{ $section['content'] ?? '' }}" alt="صورة" class="w-full rounded-lg">
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg p-8 shadow-sm text-center text-gray-500">
                لا توجد صفحات في هذا الدرس
            </div>
        @endif

        {{-- Navigation --}}
        <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
            <x-filament::button
                wire:click="previousPage"
                :disabled="$currentPage === 1"
                color="gray"
            >
                ← السابق
            </x-filament::button>

            <div class="flex gap-2">
                @foreach($this->record->pages as $p)
                    <button
                        wire:click="goToPage({{ $p->page_number }})"
                        class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium transition-colors
                               {{ $currentPage === $p->page_number 
                                  ? 'text-white' 
                                  : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                        style="{{ $currentPage === $p->page_number ? 'background-color: ' . $this->record->color : '' }}"
                    >
                        {{ $p->page_number }}
                    </button>
                @endforeach
            </div>

            <x-filament::button
                wire:click="nextPage"
                :disabled="$currentPage === $this->getTotalPages()"
                color="gray"
            >
                التالي →
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
