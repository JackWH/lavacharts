<?php

/*
|--------------------------------------------------------------------------
| Default Lavacharts Configuration
|--------------------------------------------------------------------------
|
| Here is where you can customize some of the default values that lavacharts
| uses when creating charts.
|
*/
return [
    /*
    |--------------------------------------------------------------------------
    | Auto Run
    |--------------------------------------------------------------------------
    |
    | Toggle for whether or not the lava.js module will run on page load. This
    | can be set to false for you to manually call lava.run() when ready.
    |
    */
    'auto_run' => true,


    /*
    |--------------------------------------------------------------------------
    | Locale
    |--------------------------------------------------------------------------
    |
    | When aspects of the chart have writing generated by Google, it will be in
    | this language.
    |
    | See:
    |  https://wikipedia.org/wiki/ISO_639-1
    |
    */
    'locale' => 'en',


    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | When date, time, and datetime columns are used, they will be based upon
    | this timezone.
    |
    | See:
    |  https://wikipedia.org/wiki/List_of_tz_database_time_zones
    |
    */
    'timezone' => 'America/Los_Angeles',


    /*
    |--------------------------------------------------------------------------
    | DateTime Format
    |--------------------------------------------------------------------------
    |
    | This is the format string that Carbon will use to try and parse datetime
    | strings. Only applies to date, time, datetime, and timeofday columns.
    |
    | When left blank, then dates & times will be parsed with Carbon::parse()
    |
    | When a format is given, such as the MySQL datetime format 'Y-m-d H:i:s',
    | then Carbon::createFromFormat() will be used to parse the value.
    |
    | See:
    |  https://secure.php.net/manual/en/function.date.php
    |
    */
    'datetime_format' => '',


    /*
    |--------------------------------------------------------------------------
    | Google Maps API Key
    |--------------------------------------------------------------------------
    |
    | Set your API key here to quiet the warnings that get thrown for using the
    | public API.
    |
    | See:
    |  https://developers.google.com/maps/documentation/javascript/get-api-key
    |
    */
    'maps_api_key' => '',


    /*
    |--------------------------------------------------------------------------
    | Responsive
    |--------------------------------------------------------------------------
    |
    | Toggle for whether or not the lava.js module will attach a handler to the
    | resizing event of the window. If true, then debounce_timeout will be used
    | to prevent firing of too many events.
    |
    */
    'responsive' => true,
    'debounce_timeout' => 250,

];