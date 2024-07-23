<?php

declare(strict_types=1);

namespace App;

class StaticDoctorSlotsSynchronizer extends DoctorSlotsSynchronizer
{
    protected function getDoctors(): string
    {
        /**
         * I don't see a purpose in this class and inheritance,
         * as I removed strong coupling with the doctors json data source,
         * so now DoctorSlotsSynchronizer can be used itself
         * but with injected Request\StaticDoctorsRequest data source,
         * which can be done on config level
         */

        return '';
    }

}
