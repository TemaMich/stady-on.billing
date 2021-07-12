<?php

namespace App\Tests;

use App\DataFixtures\UserFixtures;

class UserTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [UserFixtures::class];
    }

    public function testRegister(): void
    {
        $client = AbstractTest::getClient();

        $url = '/api/v1/register';
        $client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],], [], [],
            '{
            "username":"user@intaro.com",
            "password":"hrhwr4326236"
            }'
        );

        $this->assertResponseCode(201);
    }

    public function testRegisterError(): void
    {
        $client = AbstractTest::getClient();

        $url = '/api/v1/register';
        $client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],], [], [],
            '{
            "username":"usererror.com",
            "password":"h326236"
            }'
        );

        $this->assertResponseCode(500);
    }


    public function testCurrentUser(){
        $client = AbstractTest::getClient();

        $url = '/api/v1/register';
        $client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],], [], [],
            '{
            "username":"user@error.com",
            "password":"errorpass"
            }'
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $client->request('GET', '/api/v1/users/current', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '. $response['token']]);

        $this->assertResponseCode(200);
    }

    public function testCurrentUserErrorJwt(){
        $client = AbstractTest::getClient();

        $url = '/api/v1/users/current';
        $notValidToken = "error";
        $client->request('GET', $url, [], [], ['HTTP_AUTHORIZATION' => 'Bearer '. $notValidToken]);

        $this->assertResponseCode(400);
    }
}