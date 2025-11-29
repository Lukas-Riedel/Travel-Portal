<?php
    namespace Core\Common;

    class CommonConstants {
        public const GOOGLE_USER_CONTENT_BASE_URL = "https://lh3.googleusercontent.com";
        public const GOOGLE_USER_CONTENT_PROXY_BASE_URL = "/proxy/googleusercontent";
        public const DELAYED_EVENTS_SORTED_SET_KEY = "Scheduler:DelayedEvents";
        public const FITNESS_RECORD_DURATION_SECONDS = 1800;
        public const ONE_HOUR_SECONDS = 3600;
        public const ONE_DAY_SECONDS = 24 * self::ONE_HOUR_SECONDS;
        public const ONE_WEEK_SECONDS = 7 * self::ONE_DAY_SECONDS;
        public const ONE_MONTH_SECONDS = 30 * self::ONE_DAY_SECONDS;
        public const ONE_YEAR_SECONDS = 365 * self::ONE_DAY_SECONDS;
        public const DMY_DATE_FORMAT = "j.n.Y";   
        public const YMD_DATE_FORMAT = "Y-m-d";     
        public const JPG_FILE_EXTENSION = ".jpg";
    }
?>