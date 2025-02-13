<?php

namespace api\models;

class Request
{
    private string $authToken;
    private RequestType $requestType;
    private string $requestData;

    public function __construct(string $authToken, RequestType $requestType, string $requestData)
    {
        $this->authToken = $authToken;
        $this->requestType = $requestType;
        $this->requestData = $requestData;
    }

    public function toJson()
    {
        return json_encode([
            'authToken' => $this->authToken,
            'requestType' => $this->requestType->name,
            'requestData' => $this->requestData
        ]);
    }

    public function fromJson()
    {
        $data = json_decode($this->requestData, true);
        return new Request($data['authToken'], RequestType::fromName($data['requestType']), $data['requestData']);
    }
}