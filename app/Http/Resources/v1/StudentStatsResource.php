<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'period' => $this->resource['period'],
            'labels' => $this->resource['labels'],
            'datasets' => [
                [
                    'label' => 'New Students',
                    'data' => $this->resource['new_students'],
                ],
                [
                    'label' => 'Active Students',
                    'data' => $this->resource['active_students'],
                ],
            ],
            'summary' => [
                'total_new' => $this->resource['total_new'],
                'total_active' => $this->resource['total_active'],
            ],
        ];
    }
}
