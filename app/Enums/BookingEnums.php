<?php


namespace App\Enums;


use ReflectionClass;

class BookingEnums
{

    public const WaitingForApproval = 0;
    public const approved = 1;
    public const canceled = 2;
    public const reschedule = 3;
    public const handyman_assign = 4;
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


