<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    protected ?string $token;
    protected ?string $message;

    public function __construct($resource, ?string $token = null, ?string $message = null)
    {
        parent::__construct($resource);
        $this->token = $token;
        $this->message = $message;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response = [
            'user' => new UserResource($this->resource),
        ];

        if ($this->token) {
            $response['token'] = $this->token;
            $response['token_type'] = 'Bearer';
        }

        if ($this->message) {
            $response['message'] = $this->message;
        }

        return $response;
    }
}
