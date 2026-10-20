'use strict';

const {
    createCipheriv,
    createDecipheriv,
    decapsulate,
    encapsulate,
    generateKeyPairSync,
    hkdfSync,
    randomBytes,
} = require('node:crypto');
const { performance } = require('node:perf_hooks');

const CONTEXT = 'perpustakaan-anggota:v1';

function elapsed(start) {
    return Number((performance.now() - start).toFixed(3));
}

function keygen() {
    const { publicKey, privateKey } = generateKeyPairSync('ml-kem-768');

    return {
        public_key: publicKey.export({ type: 'spki', format: 'pem' }),
        private_key: privateKey.export({ type: 'pkcs8', format: 'pem' }),
    };
}

function deriveKey(sharedSecret, salt) {
    return Buffer.from(hkdfSync('sha256', sharedSecret, salt, Buffer.from(CONTEXT), 32));
}

function encrypt(plaintext, key, aad) {
    const nonce = randomBytes(12);
    const cipher = createCipheriv('aes-256-gcm', key, nonce, { authTagLength: 16 });
    cipher.setAAD(Buffer.from(aad));
    const ciphertext = Buffer.concat([cipher.update(plaintext), cipher.final()]);

    return {
        nonce: nonce.toString('base64'),
        ciphertext: ciphertext.toString('base64'),
        tag: cipher.getAuthTag().toString('base64'),
    };
}

function decrypt(payload, key, aad) {
    const decipher = createDecipheriv(
        'aes-256-gcm',
        key,
        Buffer.from(payload.nonce, 'base64'),
        { authTagLength: 16 }
    );
    decipher.setAAD(Buffer.from(aad));
    decipher.setAuthTag(Buffer.from(payload.tag, 'base64'));
    return Buffer.concat([
        decipher.update(Buffer.from(payload.ciphertext, 'base64')),
        decipher.final(),
    ]);
}

function encryptHybrid(data, publicKeyPem, aad) {
    const started = performance.now();
    const encapsulated = encapsulate(publicKeyPem);
    const encapsulationMs = elapsed(started);
    const salt = randomBytes(32);
    const hkdfStarted = performance.now();
    const key = deriveKey(encapsulated.sharedKey, salt);
    const hkdfMs = elapsed(hkdfStarted);
    const aesStarted = performance.now();
    const encrypted = encrypt(Buffer.from(JSON.stringify(data)), key, aad);
    const aesEncryptionMs = elapsed(aesStarted);

    return {
        payload: {
            version: 1,
            algorithm: 'ML-KEM-768 + HKDF-SHA-256 + AES-256-GCM',
            kem_ciphertext: encapsulated.ciphertext.toString('base64'),
            salt: salt.toString('base64'),
            ...encrypted,
        },
        metrics: {
            kem_encapsulation_ms: encapsulationMs,
            hkdf_ms: hkdfMs,
            aes_encryption_ms: aesEncryptionMs,
            total_encryption_ms: elapsed(started),
        },
    };
}

function decryptHybrid(payload, privateKeyPem, aad) {
    try {
        const started = performance.now();
        const sharedSecret = decapsulate(
            privateKeyPem,
            Buffer.from(payload.kem_ciphertext, 'base64')
        );
        const kemDecapsulationMs = elapsed(started);
        const hkdfStarted = performance.now();
        const key = deriveKey(sharedSecret, Buffer.from(payload.salt, 'base64'));
        const hkdfMs = elapsed(hkdfStarted);
        const aesStarted = performance.now();
        const plaintext = decrypt(payload, key, aad);

        return {
            authenticated: true,
            plaintext: JSON.parse(plaintext.toString('utf8')),
            metrics: {
                kem_decapsulation_ms: kemDecapsulationMs,
                hkdf_ms: hkdfMs,
                aes_decryption_ms: elapsed(aesStarted),
                total_decryption_ms: elapsed(started),
            },
        };
    } catch (error) {
        if (error.code === 'ERR_OSSL_EVP_BAD_DECRYPT' ||
            error.message.includes('unable to authenticate')) {
            return { authenticated: false };
        }

        throw error;
    }
}

function protect(input) {
    const plaintext = Buffer.from(JSON.stringify(input.data));
    const hybrid = encryptHybrid(input.data, input.public_key, input.aad || CONTEXT);
    const baselineKey = Buffer.from(input.baseline_key, 'base64');
    const baselineStarted = performance.now();
    const baselinePayload = {
        version: 1,
        algorithm: 'AES-256-GCM',
        ...encrypt(plaintext, baselineKey, input.aad || CONTEXT),
    };
    const baselineEncryptionMs = elapsed(baselineStarted);
    const hybridBytes = Buffer.byteLength(JSON.stringify(hybrid.payload));
    const baselineBytes = Buffer.byteLength(JSON.stringify(baselinePayload));

    return {
        hybrid_payload: hybrid.payload,
        baseline_payload: baselinePayload,
        metrics: {
            ...hybrid.metrics,
            baseline_encryption_ms: baselineEncryptionMs,
            hybrid_storage_bytes: hybridBytes,
            baseline_storage_bytes: baselineBytes,
            storage_overhead_bytes: hybridBytes - baselineBytes,
        },
    };
}

function decryptBaseline(payload, baselineKey, aad) {
    try {
        const plaintext = decrypt(payload, baselineKey, aad);
        return {
            authenticated: true,
            plaintext: JSON.parse(plaintext.toString('utf8')),
        };
    } catch (error) {
        if (error.code === 'ERR_OSSL_EVP_BAD_DECRYPT' ||
            error.message.includes('unable to authenticate')) {
            return { authenticated: false };
        }

        throw error;
    }
}

function benchmark(input) {
    const aad = input.aad || CONTEXT;
    const plaintext = Buffer.from(JSON.stringify(input.data));
    const keygenStarted = performance.now();
    const { publicKey, privateKey } = generateKeyPairSync('ml-kem-768');
    const keyGenerationMs = elapsed(keygenStarted);
    const hybrid = encryptHybrid(input.data, publicKey, aad);

    const decapsulationStarted = performance.now();
    const sharedSecret = decapsulate(privateKey, Buffer.from(hybrid.payload.kem_ciphertext, 'base64'));
    const kemDecapsulationMs = elapsed(decapsulationStarted);
    const hkdfStarted = performance.now();
    const hybridKey = deriveKey(sharedSecret, Buffer.from(hybrid.payload.salt, 'base64'));
    const hkdfDecryptionMs = elapsed(hkdfStarted);
    const hybridDecryptionStarted = performance.now();
    const hybridPlaintext = decrypt(hybrid.payload, hybridKey, aad);
    const hybridDecryptionMs = elapsed(hybridDecryptionStarted);

    const baselineKey = Buffer.from(input.baseline_key, 'base64');
    const baselineEncryptionStarted = performance.now();
    const baselinePayload = {
        version: 1,
        algorithm: 'AES-256-GCM',
        ...encrypt(plaintext, baselineKey, aad),
    };
    const baselineEncryptionMs = elapsed(baselineEncryptionStarted);
    const baselineDecryptionStarted = performance.now();
    const baselinePlaintext = decrypt(baselinePayload, baselineKey, aad);
    const baselineDecryptionMs = elapsed(baselineDecryptionStarted);

    const tamperedPayload = { ...hybrid.payload };
    const tamperedCiphertext = Buffer.from(tamperedPayload.ciphertext, 'base64');
    tamperedCiphertext[0] ^= 1;
    tamperedPayload.ciphertext = tamperedCiphertext.toString('base64');
    const tamperResult = decryptHybrid(tamperedPayload, privateKey, aad);

    const hybridStorageBytes = Buffer.byteLength(JSON.stringify(hybrid.payload));
    const baselineStorageBytes = Buffer.byteLength(JSON.stringify(baselinePayload));

    return {
        data_size_bytes: plaintext.length,
        results: {
            key_generation_ms: keyGenerationMs,
            kem_encapsulation_ms: hybrid.metrics.kem_encapsulation_ms,
            kem_decapsulation_ms: kemDecapsulationMs,
            hkdf_encryption_ms: hybrid.metrics.hkdf_ms,
            hkdf_decryption_ms: hkdfDecryptionMs,
            aes_encryption_ms: hybrid.metrics.aes_encryption_ms,
            aes_decryption_ms: hybridDecryptionMs,
            hybrid_total_ms: Number((
                hybrid.metrics.total_encryption_ms +
                kemDecapsulationMs +
                hkdfDecryptionMs +
                hybridDecryptionMs
            ).toFixed(3)),
            baseline_encryption_ms: baselineEncryptionMs,
            baseline_decryption_ms: baselineDecryptionMs,
            baseline_total_ms: Number((baselineEncryptionMs + baselineDecryptionMs).toFixed(3)),
            hybrid_storage_bytes: hybridStorageBytes,
            baseline_storage_bytes: baselineStorageBytes,
            storage_overhead_bytes: hybridStorageBytes - baselineStorageBytes,
            hybrid_roundtrip_matches: hybridPlaintext.equals(plaintext),
            baseline_roundtrip_matches: baselinePlaintext.equals(plaintext),
            tampering_rejected: tamperResult.authenticated === false,
        },
    };
}

async function main() {
    const operation = process.argv[2];
    let input = '';

    for await (const chunk of process.stdin) {
        input += chunk;
    }

    const data = input === '' ? {} : JSON.parse(input);
    let result;

    switch (operation) {
        case 'keygen':
            result = keygen();
            break;
        case 'protect':
            result = protect(data);
            break;
        case 'decrypt-hybrid':
            result = decryptHybrid(data.payload, data.private_key, data.aad || CONTEXT);
            break;
        case 'decrypt-baseline':
            result = decryptBaseline(
                data.payload,
                Buffer.from(data.baseline_key, 'base64'),
                data.aad || CONTEXT
            );
            break;
        case 'benchmark':
            result = benchmark(data);
            break;
        default:
            throw new Error(`Operasi tidak dikenal: ${operation}`);
    }

    process.stdout.write(JSON.stringify(result));
}

main().catch((error) => {
    process.stderr.write(error.stack || String(error));
    process.exitCode = 1;
});
