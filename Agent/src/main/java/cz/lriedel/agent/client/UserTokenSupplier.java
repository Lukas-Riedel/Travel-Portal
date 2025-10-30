package cz.lriedel.agent.client;

import cz.lriedel.agent.model.IamResponse;
import cz.lriedel.agent.persistance.Configuration;
import cz.lriedel.agent.persistance.ConfigurationRepository;
import lombok.Synchronized;
import org.springframework.stereotype.Component;

import java.util.function.Supplier;

import static cz.lriedel.agent.persistance.ConfigurationRepository.REFRESH_TOKEN_CONFIGURATION_KEY;

@Component
public final class UserTokenSupplier implements Supplier<String> {

    private static final double ACCESS_TOKEN_VALIDITY_MULTIPLIER = 0.95;

    private final IamClient iamClient;
    private final ConfigurationRepository configurationRepository;

    private String cachedAccessToken;
    private long cachedAccessTokenExpiration;

    UserTokenSupplier(IamClient iamClient, ConfigurationRepository configurationRepository) {
        this.iamClient = iamClient;
        this.configurationRepository = configurationRepository;
    }

    public void login(String username, String password) {
        extractIamResponse(iamClient.createUserToken(username, password));
    }

    @Override
    public String get() {
        if (cachedAccessToken != null && System.currentTimeMillis() < cachedAccessTokenExpiration) {
            return cachedAccessToken;
        }

        return doGetAccessToken();
    }

    @Synchronized
    private String doGetAccessToken() {
        String refreshToken = configurationRepository.findById(REFRESH_TOKEN_CONFIGURATION_KEY)
                .map(Configuration::getValue)
                .orElseThrow(() -> new IllegalStateException("Refresh token is not set in session."));

        extractIamResponse(iamClient.createToken(refreshToken));

        return cachedAccessToken;
    }

    private void extractIamResponse(IamResponse iamResponse) {
        try {
            cachedAccessToken = iamResponse.accessToken();
            cachedAccessTokenExpiration = System.currentTimeMillis() + (long) (ACCESS_TOKEN_VALIDITY_MULTIPLIER * iamResponse.expiresIn() * 1000);
            configurationRepository.save(new Configuration(REFRESH_TOKEN_CONFIGURATION_KEY, iamResponse.refreshToken()));
        }
        catch (Exception e) {
            configurationRepository.deleteById(REFRESH_TOKEN_CONFIGURATION_KEY);
            throw e;
        }
    }
}