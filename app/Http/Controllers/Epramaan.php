<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 ** Use following packages for E-parmaan
 **/

use Jose\Component\Encryption\JWEDecrypterFactory;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
//use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSLoader;


class Epramaan extends Controller
{
    use AuthorizesRequests, ValidatesRequests;


    public function base64url_encode($data)
    {
        // encode $data to Base64 string
        $b64 = base64_encode($data);
        // Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”
        $url = strtr($b64, '+/', '-_');
        // Remove padding character from the end of line and return the Base64URL result
        return rtrim($url, '=');
    }

    public function login()
    {
        $request_uri = 'https://epstg.meripehchaan.gov.in';
        $serviceId = '100001323'; //service id shared by epramaan after registration
        $aeskey = 'e0681502-a91b-4868-b8c0-4274b0144e1a';
        //$aeskey = 'e0681502-a91b-4868-b8c0-4274b0144e1a';
        $redirectionURI = 'http://site2.aadrikainfomedia.in/citizen/authResponseConsumer.do'; //sso success Url as given while registration
        $scope = 'openid';
        $response_type = 'code';
        $code_challenge_method = 'S256';

        setcookie("verifier_c", "", time() - 3600, "/");
        setcookie("nonce_c", "", time() - 3600, "/");
        // Nonce Creation
        $nonce = bin2hex(random_bytes(16));
        setcookie("nonce_c", "$nonce", time() + 3600, "/");

        //State creation
        $state = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

        // Code verifier
        $verifier_bytes = random_bytes(64);
        $code_verifier = $this->base64url_encode($verifier_bytes);
        setcookie("verifier_c", "$code_verifier", time() + 3600, "/");

        //code challenge
        $challenge_bytes = hash("sha256", $code_verifier, true);
        $code_challenge = $this->base64url_encode($challenge_bytes);

        $input = $serviceId . $aeskey . $state . $nonce . $redirectionURI . $scope . $code_challenge;

        $apiHmac = hash_hmac('sha256', $input, $aeskey, true);
        //$apiHmac = trim(base64_encode($apiHmac), '/');
        $apiHmac = base64_encode($apiHmac);

        echo "<form method='post' name='redirect' action='https://epstg.meripehchaan.gov.in?
        		&scope=" . $scope . "
        		&response_type=" . $response_type . "
        		&redirect_uri=" . $redirectionURI . "
        		&state=" . $state . "
        		&code_challenge_method=" . $code_challenge_method . "
        		&nonce=" . $nonce . "
        		&client_id=" . $serviceId . "
        		&code_challenge=" . $code_challenge . "
        		&request_uri=" . $request_uri . "
        		&apiHmac=" . $apiHmac . "'>
                    </form>
                    <script language='javascript'>document.redirect.submit();</script>
                ";
        //die(); 



    }

    public function dashboard()
    {
        $code = htmlspecialchars($_GET["code"]);
        $epramaanTokenRequestUrl = 'https://epramaan.meripehchaan.gov.in/openid/jwt/processJwtTokenRequest.do';
        $serviceId = '100001323';
        $grant_type = 'authorization_code';
        $code_verifier = $_COOKIE["verifier_c"];
        $nonce = $_COOKIE["nonce_c"];
        $scope = 'openid';
        $redirectionURI = 'http://site2.aadrikainfomedia.in/citizen/authResponseConsumer.do'; //sso success Url as given while registration

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL            => $epramaanTokenRequestUrl,
                CURLOPT_RETURNTRANSFER => true,
                //CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => '{
					"code"          : ["' . $code . '"],
					"grant_type"    : ["' . $grant_type . '"],
					"scope"         : ["' . $scope . '"],
					"redirect_uri"  : ["' . $redirectionURI . '"],
					"request_uri"   : ["' . $epramaanTokenRequestUrl . '"],
					"code_verifier" : ["' . $code_verifier . '"],
					"client_id"     : ["' . $serviceId . '"]}',
                CURLOPT_HTTPHEADER     => array(
                    'Content-Type: application/json'
                ),
            )
        );

        $response = curl_exec($curl);
        curl_close($curl);
        //print_r($response); exit();

        //---------processing token-decrypt--------------
        // The key encryption algorithm manager with the A256KW algorithm.
        $keyEncryptionAlgorithmManager = new AlgorithmManager([
            new A256KW(),
        ]);
        // The content encryption algorithm manager with the A256CBC-HS256 algorithm.
        $contentEncryptionAlgorithmManager = new AlgorithmManager([
            new A256GCM(),
        ]);
        $compressionMethodManager = new CompressionMethodManager([
            new Deflate(),

        ]);

        // AES key Generation.
        $sha25 = hash('SHA256', $nonce, true);
        $jwk = new JWK([
            'kty' => 'oct',
            'k' => $this->base64url_encode($sha25),
        ]);

        //decryption
        $jweDecrypter = new JWEDecrypter(
            $keyEncryptionAlgorithmManager,
            $contentEncryptionAlgorithmManager,
            $compressionMethodManager
        );
        // The serializer manager(JWE Compact Serialization Mode)
        $serializerManager = new JWESerializerManager([
            new CompactSerializer(),
        ]);
        print_r($response);
        exit();
        // load the token.
        $jwe = $serializerManager->unserialize($response);
        //decrypt the token
        $success = $jweDecrypter->decryptUsingKey($jwe, $jwk, 0);

        if ($success) {
            $jweLoader = new JWELoader($serializerManager, $jweDecrypter, null);
            $jwe = $jweLoader->loadAndDecryptWithKey($response, $jwk, $recipient);
            $decryptedtoken = $jwe->getPayload();
            setcookie("decryptedtoken_c", "$decryptedtoken", time() + 3600, "/");
        } else {
            throw new RuntimeException('Error Decrypting JWE');
        }
        //Verifying token with the certificate shared by epramaan
        // The algorithm manager with the HS256 algorithm.
        /* $algorithmManager = new AlgorithmManager([
			new RS256(),
		]);
		// JWS Verifier.
		$jwsVerifier = new JWSVerifier($algorithmManager);
		$key = JWKFactory::createFromCertificateFile(
			'D:\epramaan.crt', // The path where the certificate has been stored
			[
				'use' => 'sig', // Additional parameters
			]
		); */
        //$serializerManager = new JWSSerializerManager(
    }
}
