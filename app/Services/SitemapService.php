<?php

namespace App\Services;

use App\Models\Article;
use App\Models\News;
use App\Models\Post;
use Carbon\Carbon;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL as URLFacade;

class SitemapService
{
     private function getFirstImageFromContent($content, $defaultImageUrl)
    {
        preg_match('/<img[^>]+src="([^">]+)"/', $content, $matches);
        return $matches[1] ?? $defaultImageUrl;
    }

    public function generateArticlesSitemap(string $database)
    {
        $sitemap = Sitemap::create();
        $defaultImageUrl = asset('assets/img/front-pages/icons/articles_default_image.webp');

        $articles = Article::on($database)->get();

        foreach ($articles as $article) {
            $url = Url::create(route('frontend.articles.show', ['database' => $database, 'article' => $article->id]))
                ->setLastModificationDate($article->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8);

             $imageUrl = $article->image_url ?? $this->getFirstImageFromContent($article->content, $defaultImageUrl);
            $altText = $article->alt ?? $article->title;

             if ($imageUrl) {
                $url->addImage($imageUrl, $altText);
            }

            $sitemap->add($url);
        }

        $fileName = "sitemaps/sitemap_articles_{$database}.xml";
        Storage::disk('public')->put($fileName, $sitemap->render());

        $this->updateSitemapIndex($database);
    }

    public function generateNewsSitemap(string $database)
    {
        $sitemap = Sitemap::create();
        $defaultImageUrl = asset('assets/img/front-pages/icons/news_default_image.webp');

        // Fetch news items based on the selected database
        $newsItems = News::on($database)->get();

        foreach ($newsItems as $news) {
            // Create the URL for the news item (mapped to posts routes)
            $url = Url::create(route('content.frontend.posts.show', ['database' => $database, 'id' => $news->id]))
                ->setLastModificationDate($news->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.8);

            // Add image to sitemap if available
            $imageUrl = $news->image_url ?? $this->getFirstImageFromContent($news->content, $defaultImageUrl);
            $altText = $news->alt ?? $news->title;

            if ($imageUrl) {
                $url->addImage($imageUrl, $altText);
            }

            $sitemap->add($url);
        }

        // Save the sitemap
        $fileName = "sitemaps/sitemap_news_{$database}.xml";
        Storage::disk('public')->put($fileName, $sitemap->render());

        $this->updateSitemapIndex($database);
    }

    public function generatePostsSitemap(string $database)
    {
        $sitemap = Sitemap::create();
        $defaultImageUrl = asset('assets/img/front-pages/icons/news_default_image.webp');

        // Fetch posts items based on the selected database
        $posts = Post::on($database)->get();

        foreach ($posts as $post) {
            // Create the URL for the post item
            $url = Url::create(route('content.frontend.posts.show', ['database' => $database, 'id' => $post->id]))
                ->setLastModificationDate($post->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.8);

            $imageUrl = null;

            if (! empty($post->image)) {
                $imageUrl = Storage::url($post->image);
            }

            if (! $imageUrl) {
                $imageUrl = $this->getFirstImageFromContent((string) $post->content, $defaultImageUrl);
            }

            $altText = $post->alt ?? $post->title;

            if ($imageUrl) {
                $url->addImage($imageUrl, $altText);
            }

            $sitemap->add($url);
        }

        // Save the sitemap
        // Remove legacy filename if it exists to avoid stale files
        $legacyFile = "sitemaps/sitemap_posts_{$database}.xml";
        if (Storage::disk('public')->exists($legacyFile)) {
            Storage::disk('public')->delete($legacyFile);
        }

        $fileName = "sitemaps/sitemap_post_{$database}.xml";
        Storage::disk('public')->put($fileName, $sitemap->render());

        $this->updateSitemapIndex($database);
    }


    public function generateSitemap()
    {
        Sitemap::create()
            ->add(Url::create('/')->setPriority(1.0)->setChangeFrequency('daily'))
            ->add(Url::create('/about')->setPriority(0.8))
            ->writeToFile(public_path('sitemap.xml'));

        return response()->json(['success' => true, 'message' => 'Sitemap generated successfully']);
    }

    protected function updateSitemapIndex(string $database): void
    {
        $sitemapIndex = SitemapIndex::create();

        $types = ['articles', 'post', 'news', 'static'];

        foreach ($types as $type) {
            $fileName = "sitemaps/sitemap_{$type}_{$database}.xml";

            if (! Storage::disk('public')->exists($fileName)) {
                continue;
            }

            $lastModified = Storage::disk('public')->lastModified($fileName);

            $sitemapIndex->add(
                URLFacade::to(Storage::url($fileName)),
                Carbon::createFromTimestamp($lastModified)
            );
        }

        Storage::disk('public')->put(
            "sitemaps/sitemap_index_{$database}.xml",
            $sitemapIndex->render()
        );
    }
}
