package cz.lriedel.bridgex;

public record AccessToken(String accessToken, String refreshToken, long validity) {
}
