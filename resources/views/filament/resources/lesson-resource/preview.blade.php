<div class="space-y-6">
    {{-- Lesson Header --}}
    <div class="rounded-lg p-6" style="background-color: {{ $lesson->color }}20; border-right: 4px solid {{ $lesson->color }}">
        <div class="flex items-center gap-4">
            @if($lesson->logo)
                <img src="{{ Storage::url($lesson->logo) }}" alt="{{ $lesson->title }}" class="w-16 h-16 rounded-full object-cover">
            @else
                <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold" style="background-color: {{ $lesson->color }}">
                    {{ mb_substr($lesson->title, 0, 1) }}
                </div>
            @endif
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $lesson->title }}</h2>
                @if($lesson->description)
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $lesson->description }}</p>
                @endif
                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                    <span>⏱️ {{ $lesson->duration_minutes }} دقيقة</span>
                    <span>📄 {{ $lesson->pages->count() }} صفحة</span>
                    <span>📊 الترتيب: {{ $lesson->order }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Pages List --}}
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">صفحات الدرس</h3>
        
        @forelse($lesson->pages()->ordered()->get() as $page)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border-r-4" style="border-color: {{ $lesson->color }}">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background-color: {{ $lesson->color }}">
                        {{ $page->page_number }}
                    </div>
                    <div class="flex-1">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $page->title }}</h4>
                        
                        @if($page->type === 'text')
                            <div class="prose dark:prose-invert max-w-none">
                                {!! $page->content['body'] ?? '' !!}
                            </div>
                        @elseif($page->type === 'image')
                            <div class="space-y-3">
                                @if(isset($page->content['image_url']))
                                    <img src="{{ $page->content['image_url'] }}" alt="{{ $page->title }}" class="w-full rounded-lg max-h-96 object-contain">
                                @endif
                                @if(isset($page->content['caption']))
                                    <p class="text-gray-600 dark:text-gray-400 text-center italic">{{ $page->content['caption'] }}</p>
                                @endif
                            </div>
                        @elseif($page->type === 'question')
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="font-bold text-yellow-800 dark:text-yellow-200">سؤال تفاعلي</span>
                                </div>
                                @if(isset($page->content['instructions']))
                                    <p class="text-gray-700 dark:text-gray-300">{{ $page->content['instructions'] }}</p>
                                @endif
                                <p class="text-sm text-gray-500 mt-2">رقم السؤال: {{ $page->content['question_id'] ?? 'N/A' }}</p>
                            </div>
                        @elseif($page->type === 'mixed')
                            <div class="space-y-4">
                                @foreach($page->content['sections'] ?? [] as $section)
                                    @if($section['type'] === 'text')
                                        <div class="prose dark:prose-invert max-w-none">
                                            {!! $section['content'] ?? '' !!}
                                        </div>
                                    @elseif($section['type'] === 'image')
                                        <img src="{{ $section['content'] ?? '' }}" alt="صورة" class="w-full rounded-lg max-h-96 object-contain">
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        
                        <div class="mt-3 flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $page->type === 'text' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $page->type === 'image' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $page->type === 'question' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $page->type === 'mixed' ? 'bg-purple-100 text-purple-800' : '' }}">
                                {{ $page->type === 'text' ? 'نص' : ($page->type === 'image' ? 'صورة' : ($page->type === 'question' ? 'سؤال' : 'مختلط')) }}
                            </span>
                            @if($page->is_required)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    مطلوب
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-8 text-center text-gray-500">
                لا توجد صفحات في هذا الدرس
            </div>
        @endforelse
    </div>
</div>
