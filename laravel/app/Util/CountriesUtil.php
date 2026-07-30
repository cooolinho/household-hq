<?php

namespace App\Util;

use League\ISO3166\ISO3166;

class CountriesUtil
{
    public static function options()
    {
        $whitelist = config('app.countries.whitelist');

        $options = array_reduce((new ISO3166()->all()), function ($carry, $item) use ($whitelist) {
            if (!in_array($item[ISO3166::KEY_ALPHA2], $whitelist)) {
                return $carry;
            }

            $carry[$item[ISO3166::KEY_ALPHA2]] = $item[ISO3166::KEY_NAME];

            return $carry;
        }, []);

        // sort by name
        asort($options);

        return $options;
    }
}
