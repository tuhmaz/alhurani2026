<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\SchoolClassResource as ApiSchoolClassResource;
use App\Http\Resources\Api\SubjectResource as ApiSubjectResource;
use App\Http\Resources\Api\SemesterResource as ApiSemesterResource;
use App\Http\Resources\Api\KeywordResource as ApiKeywordResource;
use App\Http\Resources\Api\FileResource as ApiFileResource;

class ArticleResource extends BaseResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'content'        => $this->content,
            'image'          => $this->image,
            'meta_description' => $this->meta_description,
            'status'         => (bool) $this->status,
            'visit_count'    => $this->visit_count,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,

            // العلاقات
            'class'   => new ApiSchoolClassResource($this->whenLoaded('schoolClass')),
            'subject' => new ApiSubjectResource($this->whenLoaded('subject')),
            'semester'=> new ApiSemesterResource($this->whenLoaded('semester')),
            'keywords'=> ApiKeywordResource::collection($this->whenLoaded('keywords')),
            'files'   => ApiFileResource::collection($this->whenLoaded('files')),
        ];
    }
}
