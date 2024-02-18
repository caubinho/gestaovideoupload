<?php

namespace App\Models;

use App\Models\Traits\CreatedAt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use GuzzleHttp\Client;
USE GuzzleHttp\Psr7;

class Video extends Model
{
    use HasFactory;
    use CreatedAt;

    const QUEUED = 0;
    const Processing = 1;
    const Encoding = 2;
    const Finished = 3;
    const Resolution = 4;
    const Failed = 5;
    const PresignedUploadStarted = 6;
    const PresignedUploadFinished = 7;
    const PresignedUploadFailed = 8;
    const CaptionsGenerated = 9;
    const TitleOrDescriptionGenerated = 10;

    protected $guarded;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);

    }

    public function scopeSearch($query, $value)
    {
        $query->where('name', 'like', "%{$value}%")->orWhere('file_path', 'like', "%{$value}%");

    }

    public function createVideoInBunny($title){

        $setup = Setting::find('1');

        $client = new Client();

        $response = $client->request('POST', 'https://video.bunnycdn.com/library/'.$setup->streamLibraryId.'/videos', [
            'body' => '{"title":"'.$title.'"}',
            'headers' => [
                'AccessKey' => $setup->streamApiKey,
                'accept' => 'application/json',
                'content-type' => 'application/*+json',
            ],
        ]);

        $resultado = json_decode($response->getBody()->getContents());

        return $resultado;
    }

    public function delVideo($guid)
    {
        $setup = Setting::find('1');

        $client = new Client();

        $response = $client->request('DELETE', 'https://video.bunnycdn.com/library/'.$setup->streamLibraryId.'/videos/'.$guid, [
            'headers' => [
                'AccessKey' => $setup->streamApiKey,
                'accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents());
    }


    public function uploadVideoFromBunny($guid, $url)
    {
        $setup = Setting::find('1');

        $client = new Client();

        try {
            $response = $client->request('PUT', 'https://video.bunnycdn.com/library/'.$setup->streamLibraryId.'/videos/'.$guid, [
                'headers' => [
                    'AccessKey' => $setup->streamApiKey,
                    'accept' => 'application/json',
                ],
                'body' => Psr7\Utils::tryFopen($url, 'r'),

            ]);

            return json_decode($response->getBody()->getContents());
        }catch (\Exception $e) {
            echo 'Erro durante o upload: ' . $e->getMessage();
        }
    }


}
