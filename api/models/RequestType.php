<?php

namespace api\models;

enum RequestType: string
{
    case HELLO_WORLD = 'HELLO_WORLD';
    case VOUCHER = 'VOUCHER';
    case OTHER = 'OTHER';
    case LOGIN = 'LOGIN';
    case LOGOUT = 'LOGOUT';
    case GET_PUBLIC_KEY = 'GET_PUBLIC_KEY';
    case GET_PRODUCTS = 'GET_PRODUCTS';
    case GET_PRODUCT_CATEGORIES = "GET_PRODUCT_CATEGORIES";

    public static function fromName(mixed $requestType): RequestType
    {
        return match ($requestType) {
            self::HELLO_WORLD->name => self::HELLO_WORLD,
            self::VOUCHER->name => self::VOUCHER,
            self::LOGIN->name => self::LOGIN,
            self::LOGOUT->name => self::LOGOUT,
            self::GET_PUBLIC_KEY->name => self::GET_PUBLIC_KEY,
            self::GET_PRODUCTS->name => self::GET_PRODUCTS,
            self::GET_PRODUCT_CATEGORIES->name => self::GET_PRODUCT_CATEGORIES,
            default => self::OTHER
        };
    }
}