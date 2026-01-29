@php
    // $record is passed from the view
    $answers = $record->answers;
    $hasVideo = !empty($record->explanation_video_url);
@endphp

<div x-data="{ 
    showExplanation: false, 
    activeTab: 'video', 
    selectedAnswer: null, 
    checkAnswer() {
        // Mock checking logic
    }
}" class="w-full font-cairo" dir="rtl">

    <!-- MathJax Configuration -->
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']]
            },
            svg: {
                fontCache: 'global'
            }
        };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

    <!-- Question Card -->
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <!-- Header Actions -->
        <div
            class="flex justify-between items-center mb-6 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800 pb-4">
            <div class="flex gap-4">
                <button type="button"
                    class="flex items-center gap-1 hover:text-primary-600 dark:hover:text-primary-400">
                    <x-heroicon-o-bookmark class="w-4 h-4" />
                    <span>حفظ</span>
                </button>
                <button type="button" disabled
                    class="flex items-center gap-1 hover:text-danger-600 dark:hover:text-danger-400">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                    <span>تبليغ عن خطأ</span>
                </button>
                <button type="button" @click.stop="showExplanation = true"
                    class="flex items-center gap-1 hover:text-info-600 dark:hover:text-info-400 text-info-600 dark:text-info-500 font-bold">
                    <x-heroicon-o-play-circle class="w-4 h-4" />
                    <span>عرض الشرح</span>
                </button>
                <button type="button" class="flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                    <span class="text-lg font-bold">T</span>
                    <span>الحجم</span>
                </button>
            </div>
            <div class="text-info-600 dark:text-info-500 font-bold">
                سؤال {{ $record->id }}
            </div>
        </div>

        <!-- Question Content -->
        <div class="mb-8">
            <div
                class="prose dark:prose-invert max-w-none text-lg text-gray-800 dark:text-gray-100 mb-4 leading-relaxed mathjax-content">
                {!! $record->text !!}
            </div>

            @if($record->image_path)
                <div class="mb-6 flex justify-center">
                    <img src="{{ $record->image_path }}" alt="Question Image"
                        class="max-h-64 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                </div>
            @endif
        </div>

        <!-- Answers Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            @foreach($answers as $index => $answer)
                @php
                    $char = match ($index) { 0 => 'أ', 1 => 'ب', 2 => 'ج', 3 => 'د', default => '-'};
                @endphp
                <div @click="selectedAnswer = {{ $answer->id }}" :class="{ 
                                    'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/20 dark:ring-primary-400': selectedAnswer === {{ $answer->id }},
                                    'hover:bg-gray-50 dark:hover:bg-gray-800': selectedAnswer !== {{ $answer->id }}
                                }"
                    class="relative flex items-center p-4 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-lg cursor-pointer transition-all">

                    <span class="ml-auto flex-grow text-gray-700 dark:text-gray-200 text-lg mathjax-content">
                        {!! $answer->text !!}
                    </span>

                    <div :class="{
                                        'bg-primary-100 text-primary-600 dark:bg-primary-800 dark:text-primary-200': selectedAnswer === {{ $answer->id }},
                                        'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400': selectedAnswer !== {{ $answer->id }}
                                    }"
                        class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full font-bold mr-3 transition-colors">
                        {{ $char }}
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Confirm Button -->
        <div class="flex justify-center">
            <button class="px-8 py-2 bg-gray-200 dark:bg-gray-700 text-white font-bold rounded-lg cursor-not-allowed"
                disabled>
                تأكيد الإجابة
            </button>
        </div>
    </div>


    <!-- Explanation Modal -->
    <div x-show="showExplanation" style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        x-transition.opacity>

        <div
            class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col border border-gray-100 dark:border-gray-800">
            <!-- Modal Header -->
            <div
                class="p-4 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                <h3 class="font-bold text-gray-800 dark:text-gray-100 text-lg">شرح الإجابة</h3>
                <button type="button" @click="showExplanation = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <x-heroicon-o-x-mark class="w-6 h-6" />
                </button>
            </div>

            <!-- Correct Answer Banner -->
            <div
                class="bg-green-50 dark:bg-green-900/20 p-4 border-b border-green-100 dark:border-green-900/30 text-center">
                <span class="text-green-700 dark:text-green-400 font-bold">الإجابة الصحيحة: </span>
                <span class="mathjax-content text-gray-800 dark:text-gray-100">
                    @if($correct = $answers->where('is_correct', true)->first())
                        {!! $correct->text !!}
                    @else
                        غير محدد
                    @endif
                </span>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 dark:border-gray-700">
                <button type="button" @click="activeTab = 'video'"
                    :class="{ 'border-b-2 border-info-500 text-info-600 dark:text-info-400': activeTab === 'video', 'text-gray-500 dark:text-gray-400': activeTab !== 'video' }"
                    class="flex-1 py-3 text-center font-bold transition-colors">
                    فيديو
                </button>
                <button type="button" @click="activeTab = 'text'"
                    :class="{ 'border-b-2 border-info-500 text-info-600 dark:text-info-400': activeTab === 'text', 'text-gray-500 dark:text-gray-400': activeTab !== 'text' }"
                    class="flex-1 py-3 text-center font-bold transition-colors">
                    نص
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 overflow-y-auto bg-gray-50 dark:bg-gray-900 flex-grow relative">

                <!-- Video Tab -->
                <div x-show="activeTab === 'video'" class="space-y-4">
                    @if($hasVideo)
                        <div
                            class="aspect-video bg-black rounded-lg overflow-hidden shadow-lg relative group cursor-pointer flex items-center justify-center border border-gray-800">
                            <!-- Placeholder for video player -->
                            <div class="absolute inset-0 bg-cover bg-center opacity-60"
                                style="background-image: url('{{ $record->explanation_text_image_path ?? '' }}')"></div>
                            <x-heroicon-s-play-circle
                                class="w-16 h-16 text-white opacity-80 group-hover:opacity-100 transition-opacity z-10" />
                        </div>
                        <p class="text-center text-gray-500 dark:text-gray-400 text-sm mt-2">فيديو الشرح التوضيحي</p>
                    @else
                        <div class="flex flex-col items-center justify-center h-48 text-gray-400 dark:text-gray-500">
                            <x-heroicon-o-video-camera-slash class="w-12 h-12 mb-2" />
                            <p>لا يوجد فيديو للشرح لهذا السؤال</p>
                        </div>
                    @endif
                </div>

                <!-- Text Tab -->
                <div x-show="activeTab === 'text'" class="space-y-4">
                    <div
                        class="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-100 mathjax-content leading-relaxed">
                        {!! $record->explanation_text ?? 'لا يوجد شرح نصي لهذا السؤال' !!}
                    </div>

                    @if($record->explanation_text_image_path)
                        <div class="mt-4 flex justify-center">
                            <img src="{{ $record->explanation_text_image_path }}" alt="Explanation Image"
                                class="max-h-64 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

</div>