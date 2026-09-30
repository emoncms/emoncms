# Encrypted input

Devices that cannot use HTTPS can encrypt the data they post to `input/post` and `input/bulk`. The write API key is the shared key. The API key itself is not sent.

## Request

- Method: `POST` to `input/post` or `input/bulk`.
- Header `Content-Type: aes128cbc`. Use `aes128cbcgz` when the request string is compressed with zlib (PHP `gzcompress`) before encryption. The HMAC and the response hash then cover the compressed data.
- Header `Authorization: USERID:HMAC`, where HMAC is the SHA-256 HMAC of the plain request string, keyed with the hex decoded write API key.
- Body: base64 of the 16 byte IV followed by the AES-128-CBC ciphertext of the plain request string. The key is the write API key, hex decoded to 16 bytes. Use URL safe base64 with no padding.

The plain request string uses the same parameters as an unencrypted request, for example `node=emontx&data=100,200,300`.

## Response

The response is the URL safe base64 SHA-256 hash of the plain request string. Compare it with your own hash to confirm that Emoncms received the data.

## Steps

1. Build the request string, for example `node=emontx&data=100,200,300`.
2. Create a random 16 byte initialisation vector.
3. Encrypt the request string with AES-128-CBC.
4. Join the IV and the ciphertext and encode as base64.
5. Calculate the HMAC of the request string.
6. POST the encoded string with the two headers.
7. Check the returned hash.

## PHP example

    <?php

    $userid = USERID;
    $apikey = "WRITE APIKEY";

    $data = "node=emontx&data=100,200,300";

    // Encrypt data
    $iv = openssl_random_pseudo_bytes(16);
    $encryptedData = $iv.openssl_encrypt($data, 'AES-128-CBC', hex2bin($apikey), OPENSSL_RAW_DATA, $iv);
    $base64EncryptedData = rtrim(strtr(base64_encode($encryptedData), '+/', '-_'), '=');

    // Generate hmac_hash for user authorization
    $hmac = hash_hmac('sha256',$data,hex2bin($apikey));

    // Generate request
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"http://localhost/emoncms/input/post");
    curl_setopt($ch,CURLOPT_POST,1);

    // Set request headers Authorization & Content-Type
    curl_setopt($ch,CURLOPT_HTTPHEADER, array(
      "Authorization: $userid:$hmac",
      "Content-Type: aes128cbc"
    ));

    curl_setopt($ch,CURLOPT_POSTFIELDS,$base64EncryptedData);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

    $result = curl_exec($ch);
    if (PHP_VERSION_ID < 80000) {
        curl_close($ch);
    }

    // Generate sha256 hash of data string to compare with returned sha256 hash result
    $sha1 = str_replace(array('+','/'),array('-','_'), base64_encode(hash("sha256", $data, true)));

    if ($sha1==$result) {
        print "ok";
    } else {
        print $result;
    }


