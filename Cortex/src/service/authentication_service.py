import time
from typing import Final

import requests
from fastapi import HTTPException, status
from jose import jwk, jwt
from jose.utils import base64url_decode

JWKS_TTL: Final[int] = 86400


class AuthenticationService:
    def __init__(
        self,
        iam_host: str,
        iam_port: int,
        iam_backend_client_id: str,
        iam_backend_client_secret: str,
    ):
        self.iam_base_url = f"http://{iam_host}:{iam_port}"
        self.iam_backend_client_id = iam_backend_client_id
        self.iam_backend_client_secret = iam_backend_client_secret
        self.jwks_endpoint = f"{self.iam_base_url}/certificates/jwks"
        self.token_endpoint = f"{self.iam_base_url}/token"
        self.cached_keys = None
        self.keys_expires_at = 0.0
        self.cached_service_token = None
        self.service_token_expires_at = 0.0

    def get_service_access_token(self) -> str:
        current_time = time.time()
        if self.cached_service_token is not None and current_time < self.service_token_expires_at:
            return self.cached_service_token

        payload = {
            "clientId": self.iam_backend_client_id,
            "clientSecret": self.iam_backend_client_secret,
        }

        try:
            response = requests.post(self.token_endpoint, json=payload, headers={"Content-Type": "application/json", "Request-Origin": "cortex"}, timeout=10)
            if response.status_code != 201:
                raise RuntimeError(f"Could not fetch service token. Reason: {response.text}")

            data = response.json()

            self.cached_service_token = data.get("accessToken")
            self.service_token_expires_at = current_time + (data.get("expiresIn") * 0.8)

            return data.get("accessToken")
        except Exception as exc:
            raise

    def get_jwks_keys(self):
        current_time = time.time()
        if self.cached_keys is None or current_time >= self.keys_expires_at:
            response = requests.get(self.jwks_endpoint)
            if response.status_code != 200:
                raise RuntimeError(f"Could not fetch JWKS. Reason: {response.text}")

            self.cached_keys = response.json()
            self.keys_expires_at = current_time + JWKS_TTL

        return self.cached_keys

    def authenticate(self, token: str):
        try:
            jwks = self.get_jwks_keys()
            unverified_header = jwt.get_unverified_header(token)
            kid = unverified_header.get("kid")

            key_index = -1
            for i, key in enumerate(jwks["keys"]):
                if key["kid"] == kid:
                    key_index = i
                    break

            if key_index == -1:
                raise RuntimeError("Could not find key in JWKS.")

            public_key = jwk.construct(jwks["keys"][key_index])
            message, encoded_sig = token.rsplit(".", 1)
            decoded_sig = base64url_decode(encoded_sig.encode("utf-8"))

            if not public_key.verify(message.encode("utf-8"), decoded_sig):
                raise RuntimeError("The JWT token could not be verified.")

            claims = jwt.get_unverified_claims(token)

            exp = claims.get("exp")
            if exp is None or time.time() > exp:
                raise RuntimeError("The JWT token has expired.")

            return {
                "user_id": claims.get("sub"),
                "client": claims.get("azp"),
            }
        except Exception as e:
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail=f"An error occurred when decoding JWT token. Reason: {str(e)}"
            )
