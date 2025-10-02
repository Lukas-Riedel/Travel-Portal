package cz.lriedel.agent.model.request;

import org.springframework.lang.Nullable;

public record TokenPrototype(@Nullable String username, @Nullable String password, @Nullable String refreshToken, String scope) {
}