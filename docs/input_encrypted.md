## Example code for posting encrypted data to the input/post and input/bulk API


1. Start with a request string conforming with the API options above e.g: node=emontx&data={power1:100,power2:200,power3:300}
2. Create an initialization vector.
3. Encrypt using AES-128-CBC.
4. Create a single string starting with the initialization vector followed by the cipher-text result of the AES-128-CBC encryption.
5. Convert to a base64 encoded string.
6. Generate a HMAC_HASH of the data string together, using the emoncms apikey for authorization.
7. Send the encrypted string in the POST body of a request to either input/post or input/bulk with headers properties 'Content-type' and 'Authorization' set as below.
8. Verify the result. The result is a base64 encoded sha256 hash of the json data string.

PHP Example source code:

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
    curl_close($ch);

    // Generate sha256 hash of data string to compare with returned sha256 hash result
    $sha1 = str_replace(array('+','/'),array('-','_'), base64_encode(hash("sha256", $data, true)));

    if ($sha1==$result) print "ok"; else print $result;


