<?php

namespace api\models;

enum ResponseType: string
{
    case SUCCESS = 'SUCCESS';
    case ERROR = 'ERROR';
}
