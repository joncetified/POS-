function base64UrlToBuffer(value) {
    const base64 = value.replace(/-/g, '+').replace(/_/g, '/').padEnd(Math.ceil(value.length / 4) * 4, '=');
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);

    for (let index = 0; index < binary.length; index += 1) {
        bytes[index] = binary.charCodeAt(index);
    }

    return bytes.buffer;
}

function bufferToBase64Url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';

    bytes.forEach((byte) => {
        binary += String.fromCharCode(byte);
    });

    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
}

function credentialCreationOptions(options) {
    return {
        publicKey: {
            ...options,
            challenge: base64UrlToBuffer(options.challenge),
            user: {
                ...options.user,
                id: base64UrlToBuffer(options.user.id),
            },
        },
    };
}

function credentialRequestOptions(options) {
    return {
        publicKey: {
            ...options,
            challenge: base64UrlToBuffer(options.challenge),
            allowCredentials: options.allowCredentials.map((credential) => ({
                ...credential,
                id: base64UrlToBuffer(credential.id),
            })),
        },
    };
}

function registrationPayload(credential) {
    return {
        credential_id: credential.id,
        raw_id: bufferToBase64Url(credential.rawId),
        client_data_json: bufferToBase64Url(credential.response.clientDataJSON),
        attestation_object: bufferToBase64Url(credential.response.attestationObject),
    };
}

function authenticationPayload(credential) {
    return {
        credential_id: credential.id,
        raw_id: bufferToBase64Url(credential.rawId),
        client_data_json: bufferToBase64Url(credential.response.clientDataJSON),
        authenticator_data: bufferToBase64Url(credential.response.authenticatorData),
        signature: bufferToBase64Url(credential.response.signature),
        user_handle: credential.response.userHandle ? bufferToBase64Url(credential.response.userHandle) : null,
    };
}

async function browserSupportsBiometric() {
    if (!window.PublicKeyCredential) {
        return false;
    }

    if (typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable !== 'function') {
        return true;
    }

    return PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
}

window.CafeFingerprintAuth = {
    authenticationPayload,
    browserSupportsBiometric,
    credentialCreationOptions,
    credentialRequestOptions,
    registrationPayload,
};
