package cz.lriedel.agent.model.request;

import lombok.Builder;
import org.springframework.lang.Nullable;

@Builder
public record TokenPrototype(@Nullable String username, @Nullable String password, @Nullable String clientId,
                             @Nullable String clientSecret, @Nullable String refreshToken, @Nullable String scope) {
}