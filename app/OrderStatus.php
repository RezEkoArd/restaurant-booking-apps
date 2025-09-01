<?php

namespace App;

enum OrderStatus: string
{
    case Open = 'open';
    case Close = 'close';
}
