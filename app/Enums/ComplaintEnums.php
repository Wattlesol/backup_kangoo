<?php


namespace App\Enums;


use ReflectionClass;

class ComplaintEnums
{

    public const New = 0;
    public const progressing = 1;
    public const Waiting_reply_from_provider = 2;
    public const Waiting_reply_from_admin = 3;
    public const finished = 5;






    public static function all()
    {
        $self = new ReflectionClass(__CLASS__);
        return $self->getConstants();
    }


    public static function GetById($id)
    {
        $self = new ReflectionClass(__CLASS__);
        return array_search($id, $self->getConstants());
    }



}


