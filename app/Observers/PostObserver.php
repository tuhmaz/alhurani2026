<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\SitemapService;

class PostObserver
{
    protected SitemapService $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->sitemapService->generatePostsSitemap($post->getConnectionName());
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        $this->sitemapService->generatePostsSitemap($post->getConnectionName());
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        $this->sitemapService->generatePostsSitemap($post->getConnectionName());
    }
}
