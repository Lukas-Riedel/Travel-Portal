<?php
    if (isset($_GET["code"])) {
    
        require_once(dirname(__FILE__) . "/php/provider/DatabaseProvider.php");
        require_once(dirname(__FILE__) . "/php/provider/ConfigurationProvider.php");
        require_once(dirname(__FILE__) . "/php/login.php");

        $databaseProvider = new DatabaseProvider(TRUE);
        $configurationProvider = new ConfigurationProvider($databaseProvider);
        $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);

        $payload = array(
            "code" => $_GET["code"],
            "client_id" => $configuration["googleApiCredentials"]["clientId"],
            "client_secret" => $configuration["googleApiCredentials"]["clientSecret"],
            "redirect_uri" => "https://" . $configuration["hostName"],
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
            ->statementBuilder("UPDATE configuration SET value = ? WHERE type = 'GOOGLE_API_CREDENTIALS' AND `key` = 'accessKey'")
            ->withParameters($response["refresh_token"])
            ->execute();
    }

    header("Location: /trip/");
?>