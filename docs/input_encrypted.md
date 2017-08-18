## Example code for posting data to the input/encrypted API

    <?php

    $username = "USERNAME";
    $apikey = "WRITE_APIKEY";

    $time = time();

    $data = array();
    $data[] = array($time-30,"emontx",120.3,58.8,408.2);
    $data[] = array($time-20,"emontx",120.3,58.8,408.2);
    $data[] = array($time-00,"emontx",120.3,58.8,408.2);

    $data = json_encode($data);

    // Encrypt data
    $iv = openssl_random_pseudo_bytes(16);
    $encryptedData = $iv.openssl_encrypt($data, 'AES-128-CBC', hex2bin($apikey), OPENSSL_RAW_DATA, $iv);
    $base64EncryptedData = rtrim(strtr(base64_encode($encryptedData), '+/', '-_'), '=');

    // Develop a sha256 hash of the data to check against the reply
    $sha256 = hash("sha256", $data, true);
    $sha256base64 = str_replace(array('+','/'),array('-','_'), base64_encode($sha256));

    // Send request
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"http://emoncms.org/input/encrypted");
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,"username=$username&data=".$base64EncryptedData);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $result = curl_exec($ch);
    curl_close($ch);

    // Check that result matches sha256base64 of request data
    if ($result==$sha256base64) echo "ok\n";


