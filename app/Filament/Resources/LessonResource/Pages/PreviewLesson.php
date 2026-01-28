<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Models\Lesson;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class PreviewLesson extends Page
{
    protected static string $resource = \App\Filament\Resources\LessonResource::class;

    protected static string $view = 'filament.resources.lesson-resource.pages.preview-lesson';

    public Lesson $record;

    public int $currentPage = 1;

    public function mount(int|string $record): void
    {
        $this->record = Lesson::with('pages')->findOrFail($record);
        $this->currentPage = 1;
    }

    public function getTitle(): string|Htmlable
    {
        return "معاينة: {$this->record->title}";
    }

    public function nextPage(): void
    {
        $totalPages = $this->record->pages->count();
        if ($this->currentPage < $totalPages) {
            $this->currentPage++;
        }
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function goToPage(int $pageNumber): void
    {
        $totalPages = $this->record->pages->count();
        if ($pageNumber >= 1 && $pageNumber <= $totalPages) {
            $this->currentPage = $pageNumber;
        }
    }

    public function getCurrentPageData()
    {
        return $this->record->pages->where('page_number', $this->currentPage)->first();
    }

    public function getTotalPages(): int
    {
        return $this->record->pages->count();
    }
}
