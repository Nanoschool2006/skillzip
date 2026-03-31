<?php
// …

namespace local_wdmgroupregistration\privacy;

use core_privacy\local\metadata\collection;

class provider implements \core_privacy\local\metadata\null_provider
{
        // \core_privacy\local\request\preference_provider {
    public static function get_reason() : string {
        return 'privacy:metadata';
    }
}
