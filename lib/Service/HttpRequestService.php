<?php

/**
 * @copyright Copyright (c) 2024, CIDgravity (https://cidgravity.com)
 *
 * @author Florian RUEN <florian.ruen@cidgravity.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
*/

namespace OCA\CidgravityGateway\Service;

use Exception;
use Psr\Log\LoggerInterface;

class HttpRequestService {

    private $ch;

	public function __construct(private LoggerInterface $logger) {
        $this->ch = curl_init();
    }

    public function post($url, $data, $username = null, $password = null) {
        $jsonData = json_encode($data);

        // set CURL configuration
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

        // build headers
        $headers = ['Content-Type: application/json'];

        // if username and password provided, add headers for basic authentication
        if ($username !== null && $password !== null) {
            array_push($headers, "X-Username: $username");
            array_push($headers, "X-Password: $password");
        }

        // set header in curl configuration
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);

        // execute the request
        $response = curl_exec($this->ch);

        // check for errors
        if ($error = curl_errno($this->ch)) {
            $errorMessage = curl_error($this->ch);
            throw new Exception("cURL Error: $errorMessage");
        }

        return json_decode($response, true);
    }

    public function __destruct() {
        curl_close($this->ch);
    }
}