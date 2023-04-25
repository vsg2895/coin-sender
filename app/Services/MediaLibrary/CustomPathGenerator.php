<?php

namespace App\Services\MediaLibrary;

use App\Models\{Task, Link, Project, CoinType, SocialLink};

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator as BasePathGenerator;

class CustomPathGenerator implements BasePathGenerator
{
    /**
     * Get the path for the given media, relative to the root storage path.
     *
     * @param \Spatie\MediaLibrary\MediaCollections\Models\Media $media
     *
     * @return string
     */
    public function getPath(Media $media): string
    {
        $path = app()->environment() === 'local' ? 'develop' : app()->environment();

        if ($media->model instanceof Task) {
            $path .= '/tasks/'.$media->id.'/';
        }

        if ($media->model_type === 'App\Models\Project') {
            $path .= '/projects/'.$media->id.'/';
        }

        if ($media->model_type === 'App\Models\User') {
            $path .= '/avatars/'.$media->id.'/';
        }

        if ($media->model_type === 'App\Models\Manager') {
            $path .= '/avatars-managers/'.$media->id.'/';
        }

        if ($media->model instanceof CoinType) {
            $path .= '/coin-types/'.$media->id.'/';
        }

        if ($media->model instanceof Link) {
            $path .= '/link-icons/'.$media->id.'/';
        }

        if ($media->model instanceof SocialLink) {
            $path .= '/social-link-icons/'.$media->id.'/';
        }

        return $path;
    }

    /**
     * Get the path for conversions of the given media, relative to the root storage path.
     *
     * @param \Spatie\MediaLibrary\MediaCollections\Models\Media $media
     *
     * @return string
     */
    public function getPathForConversions(Media $media): string
    {
        $path = app()->environment() === 'local' ? 'develop' : app()->environment();

        if ($media->model instanceof Task) {
            $path .= '/tasks/'.$media->id.'/conversions/';
        }

        if ($media->model_type === 'App\Models\Project') {
            $path .= '/projects/'.$media->id.'/conversions/';
        }

        if ($media->model_type === 'App\Models\User') {
            $path .= '/avatars/'.$media->id.'/conversions/';
        }

        if ($media->model_type === 'App\Models\Manager') {
            $path .= '/avatars-managers/'.$media->id.'/conversions/';
        }

        if ($media->model instanceof CoinType) {
            $path .= '/coin-types/'.$media->id.'/conversions/';
        }

        if ($media->model instanceof Link) {
            $path .= '/link-icons/'.$media->id.'/conversions/';
        }

        if ($media->model instanceof SocialLink) {
            $path .= '/social-link-icons/'.$media->id.'/conversions/';
        }

        return $path;
    }

    /**
     * Get the path for responsive images of the given media, relative to the root storage path.
     *
     * @param \Spatie\MediaLibrary\MediaCollections\Models\Media $media
     *
     * @return string
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        $path = app()->environment() === 'local' ? 'develop' : app()->environment();

        if ($media->model instanceof Task) {
            $path .= '/tasks/'.$media->id.'/responsive-images/';
        }

        if ($media->model_type === 'App\Models\Project') {
            $path .= '/projects/'.$media->id.'/responsive-images/';
        }

        if ($media->model_type === 'App\Models\User') {
            $path .= '/avatars/'.$media->id.'/responsive-images/';
        }

        if ($media->model_type === 'App\Models\Manager') {
            $path .= '/avatars-managers/'.$media->id.'/responsive-images/';
        }

        if ($media->model instanceof CoinType) {
            $path .= '/coin-types/'.$media->id.'/responsive-images/';
        }

        if ($media->model instanceof Link) {
            $path .= '/link-icons/'.$media->id.'/responsive-images/';
        }

        if ($media->model instanceof SocialLink) {
            $path .= '/social-link-icons/'.$media->id.'/responsive-images/';
        }

        return $path;
    }
}
