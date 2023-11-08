<?php

namespace App\Entity\Item\Attribute;

enum ItemKeyword: string
{
    case SEND_ITEM = 'snd';
    case RECEIVE_ITEM = 'rcv';
    case PAY_ITEM = 'pay';
}