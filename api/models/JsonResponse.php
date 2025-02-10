<?php

namespace api\models;

class JsonResponse
{
    private RequestType $requestType;
    private ResponseType $responseType;
    private string $message;

    public function __construct(RequestType $requestType, ResponseType $responseType, string $message)
    {
        $this->requestType = $requestType;
        $this->responseType = $responseType;
        $this->message = $message;
    }

    public function toJson(): string
    {
        return json_encode([
            'requestType' => $this->requestType->name,
            'responseType' => $this->responseType->name,
            'message' => $this->message
        ]);
    }
}