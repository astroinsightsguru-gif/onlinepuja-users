<?php

namespace App\AI\Contracts;

interface VideoProvider
{
    public function slug(): string;

    public function label(): string;

    public function isConfigured(): bool;

    /**
     * Generate a video from a text prompt.
     *
     * @param  array{model?:string,width?:int,height?:int,seconds?:int,image_url?:string,title?:string,subtitle?:string}  $args
     * @return array{ok:bool,binary:string,url:string,mime:string,model:string,duration_ms:int,credit:string,error:string}
     */
    public function create(string $prompt, array $args = []): array;
}
