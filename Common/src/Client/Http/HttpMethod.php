<?php
    namespace Common\Client\Http;
    
    enum HttpMethod : string {
        case GET = "GET";
        case POST = "POST";
        case PATCH = "PATCH";
        case PUT = "PUT";
        case DELETE = "DELETE";
        case HEAD = "HEAD";
    }
?>