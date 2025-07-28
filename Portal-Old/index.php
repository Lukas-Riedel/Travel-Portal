<?php
    if (isset($_GET["code"])) {
    
        require_once(dirname(__FILE__) . "/api/php/Provider/DatabaseProvider.php");
        require_once(dirname(__FILE__) . "/api/php/Provider/ConfigurationProvider.php");
        require_once(dirname(__FILE__) . "/api/php/login.php");

        $databaseProvider = new DatabaseProvider(TRUE);
        $configurationProvider = new ConfigurationProvider($databaseProvider);
        $configuration = $configurationProvider->get(TRUE);

        $payload = array(
            "code" => $_GET["code"],
            "client_id" => GOOGLE_API_CLIENT_ID,
            "client_secret" => GOOGLE_API_CLIENT_SECRET,
            "redirect_uri" => "https://" . $_SERVER["HTTP_HOST"],
            "grant_type" => "authorization_code",
            "access_type" => "offline"
        );

        $curl = curl_init("https://oauth2.googleapis.com/token");

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [ 'Content-Type: application/x-www-form-urlencoded' ]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 300);

        $response = json_decode(curl_exec($curl), true);

        $databaseProvider
            ->statementBuilder("UPDATE configuration SET value = ? WHERE type = 'GOOGLE_API_ACCESS_KEY'")
            ->withParameters($response["refresh_token"])
            ->execute();
    }

    header("Location: /trip/");
?>